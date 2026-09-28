<?php

namespace App\Services;

use Barryvdh\DomPDF\Facade\Pdf;

class ScoreSheetPdfService
{
    public function create(string $png, int $width, int $height, string $orientation = 'portrait'): string
    {
        $source = imagecreatefromstring($png);
        $opaque = imagecreatetruecolor($width, $height);
        imagefill($opaque, 0, 0, imagecolorallocate($opaque, 255, 255, 255));
        imagecopy($opaque, $source, 0, 0, 0, 0, $width, $height);
        ob_start();
        imagepng($opaque);
        $png = ob_get_clean();
        imagedestroy($source);
        imagedestroy($opaque);

        return Pdf::loadView('admin.sports.basketball-score-sheet-pdf', [
            'previewDataUri' => 'data:image/png;base64,'.base64_encode($png),
            'orientation' => $orientation,
        ])->setPaper('a4', $orientation)->output();
    }
}
