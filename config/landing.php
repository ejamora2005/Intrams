<?php

return [
    'background' => 'images/landing_bg.png',
    'appLogo' => 'images/logo/main_logo.png',
    // Set a separate logo path here, or use the crest in the supplied background.
    'universityLogo' => null,
    // Static starting values until official scoring is connected.
    'preview' => true,
    'updatedAt' => null,
    'teamLogos' => [
        'mighty-sea-dragons' => 'images/logo/sea_dragons.png',
        'sea-dragons' => 'images/logo/sea_dragons.png',
        'terraquatic-eagles' => 'images/logo/terraquatic_eagles.png',
        'trojan-warriors' => 'images/logo/trojan_warriors.png',
    ],
    // Set image to a public PNG, WebP, SVG, etc. Transparent logos are never cropped.
    'teams' => [
        [
            'name' => 'Mighty Sea Dragons',
            'department' => 'Marine Biology',
            'image' => 'images/logo/sea_dragons.png',
            // Replace these transparent files with real object cutouts; see their README.
            'objects' => [
                ['image' => 'images/landing-objects/marine-biology/microscope.png', 'placement' => 'upper-left'],
                ['image' => 'images/landing-objects/marine-biology/coral.png', 'placement' => 'right'],
                ['image' => 'images/landing-objects/marine-biology/diving-goggles.png', 'placement' => 'lower-left'],
            ],
            'primaryColor' => '#123e91',
            'secondaryColor' => '#a9e3ff',
            'score' => 0,
            'rank' => null,
            'theme' => 'ocean',
        ],
        [
            'name' => 'Terraquatic Eagles',
            'department' => 'Fisheries & Agriculture',
            'image' => 'images/logo/terraquatic_eagles.png',
            'objects' => [
                ['image' => 'images/landing-objects/fisheries-agriculture/rice-stalks.png', 'placement' => 'upper-left'],
                ['image' => 'images/landing-objects/fisheries-agriculture/fish.png', 'placement' => 'right'],
                ['image' => 'images/landing-objects/fisheries-agriculture/tractor.png', 'placement' => 'lower-left'],
            ],
            'primaryColor' => '#14503c',
            'secondaryColor' => '#c6e7a0',
            'score' => 0,
            'rank' => null,
            'theme' => 'nature',
        ],
        [
            'name' => 'Trojan Warriors',
            'department' => 'Information Technology',
            'image' => 'images/logo/trojan_warriors.png',
            'objects' => [
                ['image' => 'images/landing-objects/information-technology/motherboard.png', 'placement' => 'upper-left'],
                ['image' => 'images/landing-objects/information-technology/mouse.png', 'placement' => 'right'],
                ['image' => 'images/landing-objects/information-technology/keyboard.png', 'placement' => 'lower-left'],
            ],
            'primaryColor' => '#ad481e',
            'secondaryColor' => '#ffdda0',
            'score' => 0,
            'rank' => null,
            'theme' => 'technology',
        ],
    ],
];
