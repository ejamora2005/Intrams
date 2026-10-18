<?php

namespace App\Http\Controllers;

use App\Models\EditionSport;
use App\Models\IntramuralEdition;
use App\Models\Sport;
use App\Services\AuditService;
use App\Services\ScoreSheetImageOfficeService;
use App\Services\ScoreSheetPdfService;
use App\Services\SportScoreSheetService;
use App\Services\StandingsService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class SportScoreSheetController extends Controller
{
    public function __construct(private readonly SportScoreSheetService $sheets) {}

    public function show(Request $request, Sport $sport): View
    {
        $context = $this->context($request, $sport);
        $participants = $this->sheets->participants($context['editionSport']);
        $context['teams'] = $this->sheets->teams($participants);
        $context['participantCount'] = $participants->count();
        $context['pageCount'] = max(1, (int) ceil($participants->count() / 16));
        $context['sheetPage'] = min($context['pageCount'], max(1, $request->integer('sheet_page', 1)));
        $context['participants'] = $participants->slice(($context['sheetPage'] - 1) * 16, 16)->values();
        $context['allParticipants'] = $participants;
        $context['schedules'] = $context['editionSport']->schedules()->where('is_activity', false)->orderBy('starts_at')->get();
        $context['sheetSchedule'] = $request->filled('schedule_id') ? $context['schedules']->firstWhere('id', $request->integer('schedule_id')) : null;
        abort_if($request->filled('schedule_id') && $context['sheetSchedule'] === null, 404);
        $view = match ($context['profile']['template']) {
            'legacy-basketball' => 'admin.sports.basketball-score-sheet',
            'legacy-volleyball' => 'admin.sports.volleyball-score-sheet',
            default => 'sports.score-sheet',
        };

        return view($view, $context);
    }

    public function download(Request $request, Sport $sport): Response
    {
        $request->validate([
            'edition_id' => ['required', 'integer', 'exists:intramural_editions,id'],
            'format' => ['required', 'in:docx,xlsx,pdf'],
            'preview_image' => ['required', 'file', 'image', 'mimetypes:image/png', 'max:8192', 'dimensions:max_width=4096,max_height=4096'],
        ]);
        $context = $this->context($request, $sport);
        $orientation = $context['profile']['orientation'];
        $preview = $request->file('preview_image');
        [$width, $height] = getimagesize($preview->getRealPath());
        $ratio = $orientation === 'landscape' ? 297 / 210 : 210 / 297;
        if (abs($width / $height - $ratio) > .005 || $width * $height > 8000000) {
            throw ValidationException::withMessages(['preview_image' => 'Capture the full A4 '.$orientation.' sheet. Refresh the preview and try again.']);
        }
        $format = $request->input('format');
        $png = file_get_contents($preview->getRealPath());
        $content = $format === 'pdf'
            ? app(ScoreSheetPdfService::class)->create($png, $width, $height, $orientation)
            : app(ScoreSheetImageOfficeService::class)->create($format, $png, $orientation, $context['profile']['title']);
        app(AuditService::class)->record('sport_score_sheet.downloaded', $sport, null, ['edition_id' => $context['edition']->id, 'format' => $format, 'sheet_format' => $context['profile']['key']]);

        return response($content, 200, [
            'Content-Type' => match ($format) {
                'pdf' => 'application/pdf', 'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                default => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            },
            'Content-Disposition' => 'attachment; filename="'.Str::slug($sport->name).'-score-sheet-'.$context['edition']->id.'.'.$format.'"',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    private function context(Request $request, Sport $sport): array
    {
        $request->validate(['edition_id' => ['nullable', 'integer'], 'schedule_id' => ['nullable', 'integer'], 'sheet_page' => ['nullable', 'integer', 'min:1']]);
        $edition = $request->filled('edition_id') ? IntramuralEdition::findOrFail($request->integer('edition_id')) : app(StandingsService::class)->activeEdition();
        abort_unless($edition !== null, 404);
        $tabulator = $request->user()->role === 'tabulator';
        abort_if($tabulator && $edition->status !== 'active', 404);
        $editionSport = EditionSport::where('edition_id', $edition->id)->where('sport_id', $sport->id)->firstOrFail();
        $profile = $this->sheets->profile($sport);
        $prefix = $tabulator ? 'tabulator' : 'admin';

        return compact('sport', 'edition', 'editionSport', 'profile') + [
            'scoreSheetLayout' => $tabulator ? 'layouts.coordinator' : 'layouts.admin',
            'workspaceLabel' => 'Tabulator workspace', 'homeRoute' => 'tabulator.dashboard',
            'scoreSheetBackUrl' => $tabulator ? route('tabulator.dashboard').'#declare-sports' : route('admin.sports.index', ['edition_id' => $edition->id]),
            'scoreSheetShowRoute' => $prefix.'.sports.score-sheet',
            'scoreSheetDownloadRoute' => $prefix.'.sports.score-sheet.download',
        ];
    }
}
