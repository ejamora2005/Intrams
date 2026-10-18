<?php

$fiba = ['label' => 'FIBA basketball scoresheet', 'url' => 'https://assets.fiba.basketball/image/upload/documents-corporate-fiba-official-rules-2024-v10a.pdf'];
$fivb = ['label' => 'FIVB refereeing forms', 'url' => 'https://www.fivb.com/inside-fivb/refereeing/reports-documents/'];
$bwf = ['label' => 'BWF umpire manual - scoresheet section', 'url' => 'https://www.badminton.org.au/wp-content/uploads/2026/01/bwf_umpires_manual_level_1.pdf'];
$ittf = ['label' => 'ITTF match officials and match records', 'url' => 'https://www.ittf.com/committees/umpires-referees/documents/'];
$wa = ['label' => 'World Athletics technical rules and recording guidance', 'url' => 'https://worldathletics.org/about-iaaf/documents/technical-information'];
$wbsc = ['label' => 'WBSC official scorer requirements', 'url' => 'https://static.wbsc.org/uploads/federations/279/documents/4bd5da5e-f5a9-9aa5-6be9-5789589528f6.pdf'];
$profile = fn ($title, $template, $orientation, $source = null, $extras = []) => ['title' => $title, 'template' => $template, 'orientation' => $orientation, 'source' => $source, ...$extras];

return ['profiles' => [
    'basketball-5x5' => $profile('Basketball scoresheet', 'legacy-basketball', 'portrait', $fiba),
    'basketball-3x3' => $profile('3x3 basketball scoresheet', 'basketball-3x3', 'portrait', ['label' => 'FIBA 3x3 competition scoring', 'url' => 'https://help.fiba3x3.com/en/support/solutions/articles/35000012976-where-can-i-print-the-play-by-play-report-or-the-digital-scoresheet-']),
    'volleyball' => $profile('Volleyball scoresheet', 'legacy-volleyball', 'landscape', $fivb),
    'school-volleyball' => $profile('Volleyball match record', 'indoor-volleyball', 'landscape', $fivb),
    'beach-volleyball' => $profile('Beach volleyball scoresheet', 'beach-volleyball', 'landscape', ['label' => 'FIVB beach volleyball scoring forms', 'url' => 'https://www.fivb.com/beach-volleyball/tools-and-forms/']),
    'badminton' => $profile('Badminton umpire scoresheet', 'badminton', 'landscape', $bwf),
    'badminton-team' => $profile('Badminton team tie sheet', 'team-tie', 'landscape', $bwf, ['games' => 3]),
    'table-tennis' => $profile('Table tennis match record', 'table-tennis', 'portrait', $ittf),
    'table-tennis-team' => $profile('Table tennis team match record', 'team-tie', 'landscape', $ittf, ['games' => 5]),
    'chess' => $profile('Chess scoresheet', 'chess', 'portrait', ['label' => 'FIDE paper scoresheet requirements', 'url' => 'https://arbiters.fide.com/wp-content/uploads/Publications/Manual/Arbiters_Manual_2025.pdf']),
    'baseball' => $profile('Baseball scorebook', 'scorebook', 'landscape', $wbsc, ['innings' => 9]),
    'softball' => $profile('Softball scorebook', 'scorebook', 'landscape', $wbsc, ['innings' => 7]),
    'russian-softball' => $profile('Russian softball scorebook', 'scorebook', 'landscape', null, ['innings' => 7]),
    'mobile-legends' => $profile('Mobile Legends match record', 'mobile-legends', 'landscape', null),
    'track' => $profile('Track event result sheet', 'track', 'landscape', $wa),
    'relay' => $profile('Relay declaration and result sheet', 'relay', 'landscape', $wa),
    'throws' => $profile('Throwing event trial record', 'field-series', 'landscape', $wa),
    'horizontal-jump' => $profile('Horizontal jump trial record', 'field-series', 'landscape', $wa),
    'high-jump' => $profile('High jump trial record', 'high-jump', 'landscape', $wa),
    'custom' => $profile('Competition score record', 'custom', 'landscape'),
]];
