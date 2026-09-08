<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PublicImageController extends Controller
{
    public function __invoke(string $directory, string $filename): StreamedResponse
    {
        abort_unless(in_array($directory, ['company', 'products'], true), 404);

        $path = $directory.'/'.basename($filename);

        abort_unless(Storage::disk('public')->exists($path), 404);

        return Storage::disk('public')->response($path, null, [
            'Cache-Control' => 'public, max-age=31536000, immutable',
        ]);
    }
}
