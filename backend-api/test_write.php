<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$service = app(\App\Services\GoogleSheetSyncService::class);

$row = $service->findProcurementRow(1, '1');
echo "Row for no=1: " . ($row ?? 'NULL') . "\n";

$current = \App\Models\Procurement::where('master_project_id', 1)->where('no', '1')->value('priority');
echo "DB priority for no=1: " . ($current ?? 'NULL') . "\n";

// Safe test: write back the SAME value (no-op, tidak mengubah data)
$result = $service->writeBackToSheet('6. Procurement', "G{$row}", (string) $current);
echo "Write-back result: " . ($result ? 'SUCCESS' : 'FAILED') . "\n";
