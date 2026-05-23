<?php

return [
    'components' => [
        'metric_card' => [
            'label' => 'MetricCard',
            'defaults' => [
                'title' => 'Total Users',
                'value' => '0',
                'subtext' => 'Registered members',
                'icon' => 'users',
                'status' => 'default',
            ],
        ],
        'chart_block' => [
            'label' => 'ChartBlock',
            'defaults' => [
                'chart_type' => 'line',
                'title' => 'Chart',
                'labels' => 'Mon,Tue,Wed,Thu,Fri',
                'dataset_source' => 'daily_purchases',
            ],
        ],
        'system_health_panel' => [
            'label' => 'SystemHealthPanel',
            'defaults' => [
                'checks' => [
                    'api' => true,
                    'db' => true,
                    'queue' => true,
                    'cron' => true,
                    'ssl' => true,
                ],
            ],
        ],
        'activity_feed' => [
            'label' => 'ActivityFeed',
            'defaults' => [
                'source' => 'transactions',
                'limit' => 5,
            ],
        ],
        'section' => [
            'label' => 'Section',
            'defaults' => [
                'title' => 'Section title',
                'body' => 'Section content goes here.',
            ],
        ],
        'text_block' => [
            'label' => 'TextBlock',
            'defaults' => [
                'heading' => 'Heading',
                'body' => 'Body text',
            ],
        ],
        'hero_block' => [
            'label' => 'HeroBlock',
            'defaults' => [
                'heading' => 'Welcome',
                'subheading' => 'Subtitle text',
                'button_label' => 'Get started',
                'button_link' => '#',
            ],
        ],
        'divider' => [
            'label' => 'Divider',
            'defaults' => ['style' => 'solid'],
        ],
        'spacer' => [
            'label' => 'Spacer',
            'defaults' => ['height' => 24],
        ],
    ],

    'templates' => [
        'admin_dashboard' => [
            'label' => 'Admin Dashboard',
            'blocks' => [
                ['type' => 'hero_block', 'props' => ['heading' => 'Admin Dashboard', 'subheading' => 'Overview of system activity', 'button_label' => '', 'button_link' => '#']],
                ['type' => 'metric_card', 'props' => ['title' => 'Operators', 'value' => '0', 'subtext' => 'Active operators', 'icon' => 'users', 'status' => 'default']],
                ['type' => 'metric_card', 'props' => ['title' => 'Orders today', 'value' => '0', 'subtext' => 'Supply orders', 'icon' => 'cart', 'status' => 'success']],
                ['type' => 'metric_card', 'props' => ['title' => 'Revenue', 'value' => '₱0', 'subtext' => 'This month', 'icon' => 'chart', 'status' => 'default']],
                ['type' => 'chart_block', 'props' => ['chart_type' => 'line', 'title' => 'Daily purchases', 'labels' => 'Mon,Tue,Wed,Thu,Fri,Sat,Sun', 'dataset_source' => 'daily_purchases']],
                ['type' => 'chart_block', 'props' => ['chart_type' => 'bar', 'title' => 'Monthly purchases', 'labels' => 'Jan,Feb,Mar,Apr,May,Jun', 'dataset_source' => 'monthly_purchases']],
                ['type' => 'system_health_panel', 'props' => ['checks' => ['api' => true, 'db' => true, 'queue' => true, 'cron' => true, 'ssl' => true]]],
                ['type' => 'activity_feed', 'props' => ['source' => 'transactions', 'limit' => 8]],
            ],
        ],
        'public_landing' => [
            'label' => 'Public Landing Page',
            'blocks' => [
                ['type' => 'hero_block', 'props' => ['heading' => 'Frosty Rewards', 'subheading' => 'Grow your business with our network', 'button_label' => 'Join now', 'button_link' => '/login']],
                ['type' => 'section', 'props' => ['title' => 'Features', 'body' => 'POS, inventory, rebates, and genealogy in one platform.']],
                ['type' => 'text_block', 'props' => ['heading' => 'Built for operators', 'body' => 'Mobile-first tools for daily store operations.']],
                ['type' => 'chart_block', 'props' => ['chart_type' => 'line', 'title' => 'Engagement', 'labels' => 'W1,W2,W3,W4', 'dataset_source' => 'daily_purchases']],
                ['type' => 'text_block', 'props' => ['heading' => 'Get started', 'body' => 'Contact your distributor to open an account.']],
            ],
        ],
        'rewards_page' => [
            'label' => 'Rewards Page',
            'blocks' => [
                ['type' => 'hero_block', 'props' => ['heading' => 'Rewards program', 'subheading' => 'Earn points on every purchase', 'button_label' => 'View benefits', 'button_link' => '#benefits']],
                ['type' => 'section', 'props' => ['title' => 'Benefits', 'body' => 'Rebates, overrides, and wallet withdrawals.']],
                ['type' => 'text_block', 'props' => ['heading' => 'How it works', 'body' => 'Qualify monthly and earn on your network volume.']],
                ['type' => 'chart_block', 'props' => ['chart_type' => 'bar', 'title' => 'Redemption stats', 'labels' => 'Jan,Feb,Mar', 'dataset_source' => 'monthly_purchases']],
                ['type' => 'activity_feed', 'props' => ['source' => 'transactions', 'limit' => 10]],
            ],
        ],
    ],
];
