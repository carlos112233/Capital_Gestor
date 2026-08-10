<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = App\Models\User::where('email', 'ander.234.cm@gmail.com')->first();
if (!$user) $user = App\Models\User::first();
Auth::login($user);

try {
    $controller = new App\Http\Controllers\DashboardController();
    $result = $controller->indexAdmin();
    // Render the view to catch Blade errors
    $html = $result->render();
    echo "Success! Rendered " . strlen($html) . " bytes.";
} catch (\Exception $e) {
    echo "Exception: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
} catch (\Throwable $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}
