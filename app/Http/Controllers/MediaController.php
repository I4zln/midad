<?php

namespace App\Http\Controllers;

use App\Models\Media;
use Symfony\Component\HttpFoundation\Response;

class MediaController extends Controller
{
    // Each upload gets a new id, so a picture never changes under its address: browsers keep it for a year.
    public function show(Media $media): Response
    {
        abort_unless(in_array($media->mime, ['image/jpeg', 'image/png', 'image/webp', 'image/gif'], true), 404);

        return response(base64_decode($media->data), 200, [
            'Content-Type' => $media->mime,
            'Content-Length' => (string) $media->size,
            'Cache-Control' => 'public, max-age=31536000, immutable',
            'Content-Disposition' => 'inline',
        ]);
    }
}
