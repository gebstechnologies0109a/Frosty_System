<?php

/**
 * Frosty Mobile UI Design System — theme tokens for operator experience.
 */
$fontFamily = env('FROSTY_UI_FONT', 'Poppins');

return [
    'font_family' => $fontFamily,
    'font_url' => match (strtolower($fontFamily)) {
        'inter' => 'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap',
        default => 'https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap',
    },

    'colors' => [
        'primary' => '#007bff',
        'primary_dark' => '#0056b3',
        'primary_light' => '#4da3ff',
        'secondary' => '#6c757d',
        'success' => '#198754',
        'warning' => '#ffc107',
        'danger' => '#dc3545',
        'info' => '#0dcaf0',
        'accent' => '#00c2ff',
        'surface' => '#f4f7fb',
        'surface_dark' => '#1a1d21',
        'card_light' => '#ffffff',
        'card_dark' => '#212529',
        'text' => '#1b1b18',
        'text_muted' => '#6c757d',
    ],

    'chart' => [
        'palette' => ['#007bff', '#198754', '#ffc107', '#6610f2', '#0dcaf0', '#fd7e14'],
        'grid' => 'rgba(128, 128, 128, 0.15)',
        'text' => '#6c757d',
    ],

    'layout' => [
        'bottom_nav_height' => '4.5rem',
        'topbar_height' => '4.25rem',
        'fab_size' => '3.5rem',
        'radius_sm' => '0.5rem',
        'radius_md' => '0.75rem',
        'radius_lg' => '1rem',
        'radius_xl' => '1.25rem',
    ],

    'pull_to_refresh_routes' => [
        'operator.dashboard',
        'operator.analytics',
    ],
];
