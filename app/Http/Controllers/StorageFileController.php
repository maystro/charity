<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class StorageFileController extends Controller
{
    /**
     * Serve a public file from storage/app/public without requiring the
     * public/storage symlink.
     *
     * Shared hosts (Hostinger, etc.) disable exec()/symlink() and block
     * direct /storage/ URLs with a 403, so files are served through Laravel
     * using a neutral /media/ prefix that reaches the framework.
     */
    public function __invoke(Request $request, string $path): BinaryFileResponse
    {
        $root = realpath(storage_path('app/public'));
        $file = realpath(storage_path('app/public/'.$path));

        if ($root === false
            || $file === false
            || ! str_starts_with($file, $root.DIRECTORY_SEPARATOR)
            || ! is_file($file)) {
            abort(404);
        }

        return response()->file($file, [
            'Cache-Control' => 'public, max-age=31536000, immutable',
        ]);
    }
}
