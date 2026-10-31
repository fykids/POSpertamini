<?php

// Vercel's deployment filesystem is read-only. Blade needs a writable cache
// path, so keep compiled views in the ephemeral /tmp filesystem.
$compiledViewPath = getenv('VIEW_COMPILED_PATH');

if ($compiledViewPath === false || $compiledViewPath === '') {
    $compiledViewPath = sys_get_temp_dir().DIRECTORY_SEPARATOR.'laravel-views';
    putenv('VIEW_COMPILED_PATH='.$compiledViewPath);
}

if (! is_dir($compiledViewPath)) {
    mkdir($compiledViewPath, 0775, true);
}

require __DIR__ . '/../public/index.php';
