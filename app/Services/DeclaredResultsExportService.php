<?php

namespace App\Services;

use App\Models\IntramuralEdition;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use RuntimeException;
use ZipArchive;

class DeclaredResultsExportService
{
    public function __construct(private readonly StandingsService $standings) {}

    /**
     * Build a read-only snapshot of the data already saved for the active
     * edition. This method deliberately does not recalculate or persist data.
     *
     * @return array{edition: IntramuralEdition|null, generated_at: string, standings: Collection, results: Collection, result_groups: Collection, declared_count: int}
     */
    public function data(?IntramuralEdition $edition): array
    {
        $events = $this->standings->scoringEventsFor($edition);
        $declared = $this->standings->declaredResultsFor($edition);
        $eventsById = $events->keyBy('id');

        $results = $declared->map(function (array $result) use ($eventsById): array {
            $event = $eventsById->get($result['id']);
            $result['declared_by'] = $event?->sportResult?->submitter?->name ?? 'Unknown';

            return $result;
        })->values();
        $resultGroups = collect([
            'Sports Results' => $results->filter(fn (array $result): bool => in_array($result['category'], ['sports', 'track-field'], true))->values(),
            'Cultural Results' => $results->where('category', 'cultural')->values(),
        ]);

        return [
            'edition' => $edition,
            'generated_at' => now('Asia/Manila')->format('M j, Y g:i:s A').' PHT (UTC+8)',
            'standings' => $this->standings->standingsFor($edition),
            'results' => $results,
            'result_groups' => $resultGroups,
            'declared_count' => $results->count(),
        ];
    }

    public function filename(?IntramuralEdition $edition, string $extension): string
    {
        $editionName = $edition?->name ?: 'intramurals';

        return Str::slug($editionName).'-declared-results.'.strtolower($extension);
    }

    public function pdf(?IntramuralEdition $edition): string
    {
        return Pdf::loadView('tabulator.declared-results-pdf', $this->data($edition))
            ->setPaper('a4', 'landscape')
            ->output();
    }

    public function xlsx(?IntramuralEdition $edition): string
    {
        $data = $this->data($edition);
        $edition = $data['edition'];
        $title = $edition?->name ?: 'Intramurals';
        $schoolYear = $edition?->school_year ?: '-';

        $summaryRows = [
            [$title, null, null, null, null, null],
            ['School year', $schoolYear, null, null, null, null],
            ['Generated', $data['generated_at'], null, null, null, null],
            ['Declared events', $data['declared_count'], null, null, null, null],
            ['Sports events declared', $data['result_groups']['Sports Results']->count(), null, null, null, null],
            ['Cultural events declared', $data['result_groups']['Cultural Results']->count(), null, null, null, null],
            [null, null, null, null, null, null],
            ['Rank', 'Faction', 'Points', 'Gold', 'Silver', 'Bronze'],
        ];
        foreach ($data['standings'] as $row) {
            $summaryRows[] = [
                $row['rank'],
                $row['team']->name,
                (float) $row['tally']->points,
                (int) $row['tally']->gold_count,
                (int) $row['tally']->silver_count,
                (int) $row['tally']->bronze_count,
            ];
        }

        $sheets = ['Summary' => $summaryRows];
        foreach ($data['result_groups'] as $groupName => $groupResults) {
            $sheets[$groupName] = $this->resultRows($title.' - '.$groupName, $schoolYear, $data['generated_at'], $groupResults);
        }

        return $this->zipWorkbook($sheets);
    }

    /** @return array<int, array<int, mixed>> */
    private function resultRows(string $title, string $schoolYear, string $generatedAt, Collection $results): array
    {
        $rows = [
            [$title, null, null, null, null, null, null, null, null],
            ['School year', $schoolYear, null, null, null, null, null, null, null],
            ['Generated', $generatedAt, null, null, null, null, null, null, null],
            [null, null, null, null, null, null, null, null, null],
            ['Category', 'Score group', 'Event', 'Declared at (PHT)', 'Declared by', 'Placement', 'Placement label', 'Faction', 'Points'],
        ];
        foreach ($results as $result) {
            foreach ($result['placements'] as $placement) {
                $rows[] = [
                    $result['category_label'],
                    ucfirst($result['score_group'] ?? 'student'),
                    $result['name'],
                    $this->formatDate($result['declared_at']),
                    $result['declared_by'],
                    (int) $placement['placement'],
                    $placement['label'],
                    $placement['team_name'],
                    (float) $placement['points'],
                ];
            }
        }
        if (count($rows) === 5) {
            $rows[] = ['No declared results in this category', null, null, null, null, null, null, null, null];
        }

        return $rows;
    }

    private function formatDate(mixed $value): string
    {
        if (! $value instanceof \DateTimeInterface) {
            return 'Time unavailable';
        }

        return \Carbon\Carbon::parse($value)->setTimezone('Asia/Manila')->format('M j, Y g:i:s A').' PHT (UTC+8)';
    }

    /** @param array<string, array<int, array<int, mixed>>> $sheets */
    private function zipWorkbook(array $sheets): string
    {
        $rels = 'http://schemas.openxmlformats.org/package/2006/relationships';
        $office = 'http://schemas.openxmlformats.org/officeDocument/2006/relationships';
        $files = [
            '[Content_Types].xml' => $this->contentTypes(count($sheets)),
            '_rels/.rels' => '<Relationships xmlns="'.$rels.'"><Relationship Id="rId1" Type="'.$office.'/officeDocument" Target="xl/workbook.xml"/></Relationships>',
            'xl/workbook.xml' => $this->workbookXml(array_keys($sheets), $office),
            'xl/_rels/workbook.xml.rels' => $this->workbookRels(count($sheets), $rels, $office),
            'xl/styles.xml' => $this->stylesXml(),
        ];
        foreach (array_values($sheets) as $index => $rows) {
            $files['xl/worksheets/sheet'.($index + 1).'.xml'] = $this->sheetXml($rows);
        }

        $path = tempnam(sys_get_temp_dir(), 'declared-results-');
        if ($path === false) {
            throw new RuntimeException('Cannot create Excel export.');
        }

        try {
            $zip = new ZipArchive;
            if ($zip->open($path, ZipArchive::OVERWRITE) !== true) {
                throw new RuntimeException('Cannot open Excel export.');
            }
            foreach ($files as $name => $contents) {
                $zip->addFromString($name, $contents);
            }
            $zip->close();

            return (string) file_get_contents($path);
        } finally {
            @unlink($path);
        }
    }

    private function contentTypes(int $sheetCount): string
    {
        $overrides = '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>';
        for ($i = 1; $i <= $sheetCount; $i++) {
            $overrides .= '<Override PartName="/xl/worksheets/sheet'.$i.'.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>';
        }

        return '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/>'.$overrides.'</Types>';
    }

    /** @param array<int, string> $names */
    private function workbookXml(array $names, string $office): string
    {
        $sheets = '';
        foreach ($names as $index => $name) {
            $sheets .= '<sheet name="'.$this->xml($name).'" sheetId="'.($index + 1).'" r:id="rId'.($index + 1).'"/>';
        }

        return '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="'.$office.'"><sheets>'.$sheets.'</sheets></workbook>';
    }

    private function workbookRels(int $sheetCount, string $rels, string $office): string
    {
        $relationships = '';
        for ($i = 1; $i <= $sheetCount; $i++) {
            $relationships .= '<Relationship Id="rId'.$i.'" Type="'.$office.'/worksheet" Target="worksheets/sheet'.$i.'.xml"/>';
        }

        return '<Relationships xmlns="'.$rels.'">'.$relationships.'<Relationship Id="rId'.($sheetCount + 1).'" Type="'.$office.'/styles" Target="styles.xml"/></Relationships>';
    }

    private function stylesXml(): string
    {
        return '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><numFmts count="0"/><fonts count="2"><font><sz val="11"/><name val="Arial"/></font><font><b/><sz val="11"/><name val="Arial"/></font></fonts><fills count="2"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="solid"><fgColor rgb="D9EAF7"/><bgColor indexed="64"/></patternFill></fill></fills><borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders><cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs><cellXfs count="2"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/><xf numFmtId="0" fontId="1" fillId="1" borderId="0"/></cellXfs></styleSheet>';
    }

    /** @param array<int, array<int, mixed>> $rows */
    private function sheetXml(array $rows): string
    {
        $xml = '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetViews><sheetView showGridLines="0" workbookViewId="0"/></sheetViews><sheetFormatPr defaultRowHeight="18"/><sheetData>';
        foreach ($rows as $rowIndex => $row) {
            $number = $rowIndex + 1;
            $xml .= '<row r="'.$number.'">';
            foreach (array_values($row) as $columnIndex => $value) {
                if ($value === null) {
                    continue;
                }
                $reference = $this->columnName($columnIndex + 1).$number;
                $style = ($this->isHeaderRow($rows, $rowIndex) || $rowIndex === 0) ? ' s="1"' : '';
                if (is_int($value) || is_float($value)) {
                    $xml .= '<c r="'.$reference.'"'.$style.'><v>'.(is_finite((float) $value) ? $value : 0).'</v></c>';
                } else {
                    $xml .= '<c r="'.$reference.'" t="inlineStr"'.$style.'><is><t>'.$this->xml((string) $value).'</t></is></c>';
                }
            }
            $xml .= '</row>';
        }
        $lastColumn = $this->columnName(max(1, collect($rows)->map(fn (array $row): int => count($row))->max() ?? 1));
        $xml .= '</sheetData><autoFilter ref="A1:'.$lastColumn.max(1, count($rows)).'"/><pageMargins left="0.25" right="0.25" top="0.5" bottom="0.5" header="0.2" footer="0.2"/><pageSetup orientation="landscape" fitToWidth="1" fitToHeight="0"/></worksheet>';

        return $xml;
    }

    /** @param array<int, array<int, mixed>> $rows */
    private function isHeaderRow(array $rows, int $rowIndex): bool
    {
        $first = $rows[$rowIndex][0] ?? null;

        return $first === 'Rank' || $first === 'Category';
    }

    private function columnName(int $number): string
    {
        $name = '';
        while ($number > 0) {
            $remainder = ($number - 1) % 26;
            $name = chr(65 + $remainder).$name;
            $number = intdiv($number - 1, 26);
        }

        return $name;
    }

    private function xml(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
