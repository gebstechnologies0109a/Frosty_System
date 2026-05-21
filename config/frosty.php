<?php

return [
    'qualification_points' => (int) env('FROSTY_QUALIFICATION_POINTS', 20),
    'peso_per_point' => (float) env('FROSTY_PESO_PER_POINT', 1),
    'override_percentages' => [
        1 => (float) env('FROSTY_OVERRIDE_L1', 10),
        2 => (float) env('FROSTY_OVERRIDE_L2', 5),
        3 => (float) env('FROSTY_OVERRIDE_L3', 3),
        4 => (float) env('FROSTY_OVERRIDE_L4', 2),
    ],
    'max_genealogy_depth' => 4,
    'main_distributor_id' => 1,
    'require_payment_proof' => (bool) env('FROSTY_REQUIRE_PAYMENT_PROOF', true),
];
