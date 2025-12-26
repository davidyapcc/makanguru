<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | MakanGuru Locations Configuration
    |--------------------------------------------------------------------------
    |
    | This configuration file contains all available locations across Klang Valley
    | and surrounding areas. This data is shared across:
    | - CLI Scraper Command
    | - Web Scraper Interface
    | - Database Seeder
    | - Any other components that need location data
    |
    | Coordinates are in decimal degrees (WGS84 format)
    |
    */

    'coordinates' => [
        // Central Kuala Lumpur (9 areas)
        'Kuala Lumpur' => ['lat' => 3.1390, 'lng' => 101.6869],
        'KLCC' => ['lat' => 3.1578, 'lng' => 101.7123],
        'Bangsar' => ['lat' => 3.1305, 'lng' => 101.6711],
        'Bukit Bintang' => ['lat' => 3.1478, 'lng' => 101.7123],
        'Cheras' => ['lat' => 3.1157, 'lng' => 101.7366],
        'Sentul' => ['lat' => 3.1871, 'lng' => 101.6864],
        'Kepong' => ['lat' => 3.2196, 'lng' => 101.6386],
        'Setapak' => ['lat' => 3.2099, 'lng' => 101.7181],
        'Wangsa Maju' => ['lat' => 3.2024, 'lng' => 101.7317],

        // Petaling District (9 areas)
        'Petaling Jaya' => ['lat' => 3.1073, 'lng' => 101.6067],
        'Damansara' => ['lat' => 3.1478, 'lng' => 101.6158],
        'Subang Jaya' => ['lat' => 3.0433, 'lng' => 101.5875],
        'Sunway' => ['lat' => 3.0667, 'lng' => 101.6006],
        'Puchong' => ['lat' => 3.0333, 'lng' => 101.6167],
        'Seri Kembangan' => ['lat' => 2.9959, 'lng' => 101.7178],
        'Kota Damansara' => ['lat' => 3.1581, 'lng' => 101.5858],
        'Bandar Utama' => ['lat' => 3.1483, 'lng' => 101.5978],
        'Sri Petaling' => ['lat' => 3.0833, 'lng' => 101.6833],

        // Shah Alam & Klang (4 areas)
        'Shah Alam' => ['lat' => 3.0733, 'lng' => 101.5185],
        'Klang' => ['lat' => 3.0333, 'lng' => 101.4500],
        'Bandar Bukit Tinggi' => ['lat' => 3.0522, 'lng' => 101.5328],
        'Setia Alam' => ['lat' => 3.1167, 'lng' => 101.4667],

        // Ampang & Selayang (3 areas)
        'Ampang' => ['lat' => 3.1500, 'lng' => 101.7667],
        'Selayang' => ['lat' => 3.2667, 'lng' => 101.6500],
        'Batu Caves' => ['lat' => 3.2372, 'lng' => 101.6840],

        // Kajang & Semenyih (3 areas)
        'Kajang' => ['lat' => 2.9925, 'lng' => 101.7904],
        'Bangi' => ['lat' => 2.9264, 'lng' => 101.7740],
        'Semenyih' => ['lat' => 2.9456, 'lng' => 101.8528],

        // Cyberjaya & Putrajaya (2 areas)
        'Cyberjaya' => ['lat' => 2.9222, 'lng' => 101.6556],
        'Putrajaya' => ['lat' => 2.9264, 'lng' => 101.6964],

        // Gombak & Rawang (2 areas)
        'Rawang' => ['lat' => 3.3217, 'lng' => 101.5767],
        'Gombak' => ['lat' => 3.2667, 'lng' => 101.7167],

        // Popular Neighborhoods (10 areas)
        'Mont Kiara' => ['lat' => 3.1683, 'lng' => 101.6517],
        'Hartamas' => ['lat' => 3.1697, 'lng' => 101.6481],
        'Desa Park City' => ['lat' => 3.1900, 'lng' => 101.6300],
        'Taman Tun Dr Ismail' => ['lat' => 3.1361, 'lng' => 101.6258], // TTDI
        'Sri Hartamas' => ['lat' => 3.1656, 'lng' => 101.6497],
        'Publika' => ['lat' => 3.1706, 'lng' => 101.6503],
        'Mid Valley' => ['lat' => 3.1181, 'lng' => 101.6775],
        'The Gardens' => ['lat' => 3.1181, 'lng' => 101.6764],
        'Pavilion' => ['lat' => 3.1497, 'lng' => 101.7139],
        'Suria KLCC' => ['lat' => 3.1569, 'lng' => 101.7119],

        // USJ & Ara Damansara (2 areas)
        'USJ' => ['lat' => 3.0411, 'lng' => 101.5811],
        'Ara Damansara' => ['lat' => 3.1228, 'lng' => 101.5783],

        // Old Klang Road (1 area)
        'Old Klang Road' => ['lat' => 3.0833, 'lng' => 101.6667],

        // Cheras Areas (2 areas)
        'Cheras Leisure Mall' => ['lat' => 3.1181, 'lng' => 101.7231],
        'Taman Connaught' => ['lat' => 3.0956, 'lng' => 101.7528],

        // Seremban (1 area - just outside Klang Valley but popular)
        'Seremban' => ['lat' => 2.7258, 'lng' => 101.9424],
    ],

    /*
    |--------------------------------------------------------------------------
    | Seeder Locations
    |--------------------------------------------------------------------------
    |
    | Subset of locations used for database seeding.
    | These represent high-quality, diverse areas for initial data population.
    |
    */

    'seeder' => [
        // Central KL - High density areas
        ['name' => 'Bangsar', 'radius' => 2000, 'limit' => 10],
        ['name' => 'KLCC', 'radius' => 2000, 'limit' => 10],
        ['name' => 'Bukit Bintang', 'radius' => 1500, 'limit' => 10],
        ['name' => 'Mont Kiara', 'radius' => 2000, 'limit' => 10],

        // Petaling District
        ['name' => 'Petaling Jaya', 'radius' => 3000, 'limit' => 10],
        ['name' => 'Damansara', 'radius' => 2000, 'limit' => 10],
        ['name' => 'Subang Jaya', 'radius' => 2000, 'limit' => 10],
        ['name' => 'Sunway', 'radius' => 2000, 'limit' => 10],
        ['name' => 'Puchong', 'radius' => 2500, 'limit' => 10],

        // Popular Neighborhoods
        ['name' => 'Mid Valley', 'radius' => 1500, 'limit' => 10],
        ['name' => 'Publika', 'radius' => 1500, 'limit' => 10],

        // Other key areas
        ['name' => 'Shah Alam', 'radius' => 3000, 'limit' => 10],
        ['name' => 'Cheras', 'radius' => 2500, 'limit' => 10],
        ['name' => 'Ampang', 'radius' => 2500, 'limit' => 10],
        ['name' => 'Kajang', 'radius' => 2500, 'limit' => 10],
    ],

    /*
    |--------------------------------------------------------------------------
    | Region Groupings
    |--------------------------------------------------------------------------
    |
    | Locations grouped by region for easier filtering and display
    |
    */

    'regions' => [
        'Central Kuala Lumpur' => [
            'Kuala Lumpur', 'KLCC', 'Bangsar', 'Bukit Bintang',
            'Cheras', 'Sentul', 'Kepong', 'Setapak', 'Wangsa Maju',
        ],
        'Petaling District' => [
            'Petaling Jaya', 'Damansara', 'Subang Jaya', 'Sunway',
            'Puchong', 'Seri Kembangan', 'Kota Damansara', 'Bandar Utama', 'Sri Petaling',
        ],
        'Shah Alam & Klang' => [
            'Shah Alam', 'Klang', 'Bandar Bukit Tinggi', 'Setia Alam',
        ],
        'Ampang & Selayang' => [
            'Ampang', 'Selayang', 'Batu Caves',
        ],
        'Kajang & South' => [
            'Kajang', 'Bangi', 'Semenyih',
        ],
        'Cyberjaya & Putrajaya' => [
            'Cyberjaya', 'Putrajaya',
        ],
        'Gombak & Rawang' => [
            'Rawang', 'Gombak',
        ],
        'Popular Neighborhoods' => [
            'Mont Kiara', 'Hartamas', 'Desa Park City', 'Taman Tun Dr Ismail',
            'Sri Hartamas', 'Publika', 'Mid Valley', 'The Gardens', 'Pavilion', 'Suria KLCC',
        ],
        'Other Areas' => [
            'USJ', 'Ara Damansara', 'Old Klang Road',
            'Cheras Leisure Mall', 'Taman Connaught', 'Seremban',
        ],
    ],
];
