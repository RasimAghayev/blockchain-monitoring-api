<?php

use Illuminate\Support\Facades\Route;

// Define the base path for controllers
$basePath = base_path('App/Http/Controllers');

// Middleware and prefix configuration
Route::middleware(['throttle:60,1'])
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