<?php

return [
    'labels' => [
        // --- DIRECT POV (Own Family) ---
        'direct' => [
            'self' => 'saya',
            'spouse_M' => 'Suami',
            'spouse_F' => 'Istri',
            'up_1_M' => 'Ayah',
            'up_1_F' => 'Ibu',
            'up_1_M_step' => 'Ayah Tiri',
            'up_1_F_step' => 'Ibu Tiri',
            'up_2_M' => 'Kakek',
            'up_2_F' => 'Nenek',
            'up_3_M' => 'Kakek Buyut',
            'up_3_F' => 'Nenek Buyut',
            'down_1' => 'Anak',
            'down_1_M' => 'Anak Laki-laki',
            'down_1_F' => 'Anak Perempuan',
            'down_1_M_step' => 'Anak Tiri Laki-laki',
            'down_1_F_step' => 'Anak Tiri Perempuan',
            'down_1_step' => 'Anak Tiri',
            'down_2' => 'Cucu',
            'sibling_M_older' => 'Abang',
            'sibling_F_older' => 'Kakak',
            'sibling_M_younger' => 'Adik Laki-laki',
            'sibling_F_younger' => 'Adik Perempuan',
            'sibling_default' => 'Saudara Kandung',
            'up_2_down_1_M' => 'Paman',
            'up_2_down_1_F' => 'Bibi',
            'up_1_down_2' => 'Keponakan',
            'up_1_down_3' => 'Cucu Keponakan',
            'up_1_down_4' => 'Cicit Keponakan',
            'up_2_down_2' => 'Sepupu',
        ],

        // --- VIA SPOUSE POV (In-Laws) ---
        'via_spouse' => [
            'up_1_M' => 'Ayah Mertua',
            'up_1_F' => 'Ibu Mertua',
            'up_2_M' => 'Kakek Mertua',
            'up_2_F' => 'Nenek Mertua',
            'sibling_M' => 'Ipar Laki-laki',
            'sibling_F' => 'Ipar Perempuan',
            'sibling_default' => 'Saudara Ipar',
            'up_2_down_1_M' => 'Paman Mertua',
            'up_2_down_1_F' => 'Bibi Mertua',
            'up_2_down_2' => 'Sepupu Ipar',
            'up_1_down_2' => 'Keponakan Ipar',
            'down_1' => 'Anak', // Descendants are still children
            'down_2' => 'Cucu',
        ],

        // --- OTHER IN-LAWS (e.g. Brother's Wife, Son's Wife) ---
        'in_law' => [
            'sibling_M' => 'Ipar Laki-laki',
            'sibling_F' => 'Ipar Perempuan',
            'down_1' => 'Menantu',
            'down_2' => 'Cucu Menantu',
            'up_1_M' => 'Ayah Mertua',
            'up_1_F' => 'Ibu Mertua',
            'up_1_down_2' => 'Keponakan Ipar',
        ],

        'default' => 'Teman',
    ],
];
