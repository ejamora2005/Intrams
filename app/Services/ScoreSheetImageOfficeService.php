<?php

namespace App\Services;

use RuntimeException;
use ZipArchive;

class ScoreSheetImageOfficeService
{
    /** The PNG already contains the complete A4 paper, including its margins. */
    public function create(string $format, string $png, string $orientation = 'portrait', string $sheetName = 'Basketball score sheet'): string
    {
        $rels = 'http://schemas.openxmlformats.org/package/2006/relationships';
        $office = 'http://schemas.openxmlformats.org/officeDocument/2006/relationships';
        $landscape = $orientation === 'landscape';
        $cx = $landscape ? 10692000 : 7560000;
        $cy = $landscape ? 7560000 : 10692000;
        $pageWidth = $landscape ? 16838 : 11906;
        $pageHeight = $landscape ? 11906 : 16838;
        $sheetName = htmlspecialchars($sheetName, ENT_XML1 | ENT_QUOTES, 'UTF-8');
        if ($format === 'docx') {
            $main = 'word/document.xml';
            $files = [
                $main => '<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main" xmlns:r="'.$office.'" xmlns:wp="http://schemas.openxmlformats.org/drawingml/2006/wordprocessingDrawing" xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main" xmlns:pic="http://schemas.openxmlformats.org/drawingml/2006/picture"><w:body><w:p><w:pPr><w:spacing w:before="0" w:after="0" w:line="20" w:lineRule="exact"/></w:pPr><w:r><w:rPr><w:sz w:val="2"/></w:rPr><w:drawing><wp:anchor distT="0" distB="0" distL="0" distR="0" simplePos="0" relativeHeight="0" behindDoc="1" locked="1" layoutInCell="0" allowOverlap="1"><wp:simplePos x="0" y="0"/><wp:positionH relativeFrom="page"><wp:posOffset>0</wp:posOffset></wp:positionH><wp:positionV relativeFrom="page"><wp:posOffset>0</wp:posOffset></wp:positionV><wp:extent cx="'.$cx.'" cy="'.$cy.'"/><wp:wrapNone/><wp:docPr id="1" name="A4 score sheet"/><wp:cNvGraphicFramePr><a:graphicFrameLocks noChangeAspect="1"/></wp:cNvGraphicFramePr><a:graphic><a:graphicData uri="http://schemas.openxmlformats.org/drawingml/2006/picture"><pic:pic><pic:nvPicPr><pic:cNvPr id="1" name="A4 score sheet"/><pic:cNvPicPr/></pic:nvPicPr><pic:blipFill><a:blip r:embed="rId1"/><a:stretch><a:fillRect/></a:stretch></pic:blipFill><pic:spPr><a:xfrm><a:off x="0" y="0"/><a:ext cx="'.$cx.'" cy="'.$cy.'"/></a:xfrm><a:prstGeom prst="rect"><a:avLst/></a:prstGeom></pic:spPr></pic:pic></a:graphicData></a:graphic></wp:anchor></w:drawing></w:r></w:p><w:sectPr><w:pgSz w:w="'.$pageWidth.'" w:h="'.$pageHeight.'"/><w:pgMar w:top="0" w:right="0" w:bottom="0" w:left="0" w:header="0" w:footer="0" w:gutter="0"/></w:sectPr></w:body></w:document>',
                'word/_rels/document.xml.rels' => '<Relationships xmlns="'.$rels.'"><Relationship Id="rId1" Type="'.$office.'/image" Target="media/score-sheet.png"/></Relationships>',
                'word/media/score-sheet.png' => $png,
            ];
            $overrides = '<Override PartName="/'.$main.'" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/>';
        } else {
            $main = 'xl/workbook.xml';
            $files = [
                $main => '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="'.$office.'"><sheets><sheet name="'.$sheetName.'" sheetId="1" r:id="rId1"/></sheets><definedNames><definedName name="_xlnm.Print_Area" localSheetId="0">&apos;'.$sheetName.'&apos;!$A$1:$A$3</definedName></definedNames></workbook>',
                'xl/_rels/workbook.xml.rels' => '<Relationships xmlns="'.$rels.'"><Relationship Id="rId1" Type="'.$office.'/worksheet" Target="worksheets/sheet1.xml"/></Relationships>',
                'xl/worksheets/sheet1.xml' => '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="'.$office.'"><sheetPr><pageSetUpPr fitToPage="1"/></sheetPr><sheetViews><sheetView showGridLines="0" workbookViewId="0" view="pageLayout"/></sheetViews><cols><col min="1" max="1" width="112.715" customWidth="1"/></cols><sheetData><row r="1" ht="280.63" customHeight="1"/><row r="2" ht="280.63" customHeight="1"/><row r="3" ht="280.63" customHeight="1"/></sheetData><pageMargins left="0" right="0" top="0" bottom="0" header="0" footer="0"/><pageSetup paperSize="9" orientation="'.$orientation.'" fitToWidth="1" fitToHeight="1"/><drawing r:id="rId1"/></worksheet>',
                'xl/worksheets/_rels/sheet1.xml.rels' => '<Relationships xmlns="'.$rels.'"><Relationship Id="rId1" Type="'.$office.'/drawing" Target="../drawings/drawing1.xml"/></Relationships>',
                'xl/drawings/drawing1.xml' => '<xdr:wsDr xmlns:xdr="http://schemas.openxmlformats.org/drawingml/2006/spreadsheetDrawing" xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main"><xdr:oneCellAnchor><xdr:from><xdr:col>0</xdr:col><xdr:colOff>0</xdr:colOff><xdr:row>0</xdr:row><xdr:rowOff>0</xdr:rowOff></xdr:from><xdr:ext cx="'.$cx.'" cy="'.$cy.'"/><xdr:pic><xdr:nvPicPr><xdr:cNvPr id="1" name="A4 score sheet"/><xdr:cNvPicPr><a:picLocks noChangeAspect="1"/></xdr:cNvPicPr></xdr:nvPicPr><xdr:blipFill><a:blip xmlns:r="'.$office.'" r:embed="rId1"/><a:stretch><a:fillRect/></a:stretch></xdr:blipFill><xdr:spPr><a:prstGeom prst="rect"><a:avLst/></a:prstGeom></xdr:spPr></xdr:pic><xdr:clientData/></xdr:oneCellAnchor></xdr:wsDr>',
                'xl/drawings/_rels/drawing1.xml.rels' => '<Relationships xmlns="'.$rels.'"><Relationship Id="rId1" Type="'.$office.'/image" Target="../media/score-sheet.png"/></Relationships>',
                'xl/media/score-sheet.png' => $png,
            ];
            $overrides = '<Override PartName="/'.$main.'" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/><Override PartName="/xl/drawings/drawing1.xml" ContentType="application/vnd.openxmlformats-officedocument.drawing+xml"/>';
        }
        $files['[Content_Types].xml'] = '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Default Extension="png" ContentType="image/png"/>'.$overrides.'</Types>';
        $files['_rels/.rels'] = '<Relationships xmlns="'.$rels.'"><Relationship Id="rId1" Type="'.$office.'/officeDocument" Target="'.$main.'"/></Relationships>';
        $path = tempnam(sys_get_temp_dir(), 'a4-sheet-');
        if ($path === false) throw new RuntimeException('Cannot create export.');
        try {
            $zip = new ZipArchive();
            if ($zip->open($path, ZipArchive::OVERWRITE) !== true) throw new RuntimeException('Cannot open export.');
            foreach ($files as $name => $content) $zip->addFromString($name, $content);
            $zip->close();
            return file_get_contents($path);
        } finally {
            unlink($path);
        }
    }
}
