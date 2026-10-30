<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;

// A member's profile picture or cover, kept in the database (see the create_media_table migration).
class Media extends Model
{
    // Stops new uploads before the free database fills up; the site keeps working either way.
    public const TOTAL_LIMIT_BYTES = 800 * 1024 * 1024;

    protected $fillable = ['user_id', 'kind', 'mime', 'size', 'data'];

    protected $hidden = ['data'];

    public static function store(User $user, string $kind, UploadedFile $file): self
    {
        return static::query()->create([
            'user_id' => $user->id,
            'kind' => $kind,
            'mime' => $file->getMimeType() ?: 'application/octet-stream',
            'size' => $file->getSize(),
            'data' => base64_encode($file->getContent()),
        ]);
    }

    /** "media:12" (what avatar_path / cover_path hold) → 12, or null for an older path on disk. */
    public static function idFromPath(?string $path): ?int
    {
        return $path !== null && str_starts_with($path, 'media:') ? (int) substr($path, 6) : null;
    }

    public static function hasRoomFor(int $bytes): bool
    {
        return (int) static::query()->sum('size') + $bytes <= self::TOTAL_LIMIT_BYTES;
    }
}
