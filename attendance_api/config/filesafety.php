<?php

return ['scanner' => env('FILE_SCANNER', 'local_validation'), 'clamav_host' => env('CLAMAV_HOST', '127.0.0.1'), 'clamav_port' => (int) env('CLAMAV_PORT', 3310)];
