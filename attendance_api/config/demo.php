<?php

return [
    'portfolio_enabled' => (bool) env('PORTFOLIO_DEMO_ENABLED', false),
    'visual_enabled' => (bool) env('VISUAL_DEMO_ENABLED', false),
    'visual_password' => env('DEMO_SETUP_PASSWORD', env('DEMO_PASSWORD')),
    'enabled' => (bool) env('DEMO_SEED_ENABLED', false),
    'password' => env('DEMO_PASSWORD'),
];
