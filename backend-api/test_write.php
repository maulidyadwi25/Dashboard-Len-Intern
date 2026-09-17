<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$service = app(\App\Services\GoogleSheetSyncService::class);

// Test write to sheet: write "High" to column G (priority) row 7
$result = $service->writeBackToSheet('6. Procurement', 'G7', 'High');
echo "Write result: " . ($result ? 'SUCCESS' : 'FAILED') . "\n";
