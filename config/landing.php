<?php

return [
    'background' => 'images/landing_bg.png',
    // Set a separate logo path here, or use the crest in the supplied background.
    'universityLogo' => null,
    // Static starting values until official scoring is connected.
    'preview' => true,
    'updatedAt' => null,
    // Set image to a public PNG, WebP, SVG, etc. Transparent logos are never cropped.
    'teams' => [
        [
            'name' => 'Mighty Sea Dragons',
            'department' => 'Marine Biology',
            'image' => null,
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
            'image' => null,
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
            'image' => null,
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
