<?php

use Illuminate\Support\Facades\Route;

// Define the base path for controllers
$basePath = base_path('app/Http/Controllers');

// Middleware and prefix configuration.
//
// The named 'api' group (bootstrap/app.php) already is
// [ThrottleRequests::60,1, EnsureFrontendRequestsAreStateful, CompressResponse].
// Routing previously applied 'throttle:60,1' directly here instead of the named
// group, so CompressResponse/EnsureFrontendRequestsAreStateful were defined but
// never actually attached to any route (docs/ARCHITECTURE.md) — fixed by using
// the group itself, which also removes the redundant duplicate throttle
// registration this used to carry.
Route::middleware(['api'])
    ->prefix('v1')
    ->group(function () use ($basePath) {
        // Recursively scan the Controllers directory for route files
        $directories = new RecursiveDirectoryIterator($basePath, FilesystemIterator::SKIP_DOTS);
        $iterator = new RecursiveIteratorIterator($directories);

        foreach ($iterator as $file) {
            // Check if the file is a PHP file and located in a "Router" subdirectory
            if ($file->isFile() && $file->getExtension() === 'php' && strpos($file->getPath(), 'Router') !== false) {
                require $file->getRealPath();
            }
        }
    });
