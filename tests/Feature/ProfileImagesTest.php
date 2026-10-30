<?php

use App\Models\Media;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

// Profile pictures and covers live in the database so a host that wipes its disk on deploy keeps them;
// animated GIFs stay byte for byte, old pictures are replaced, and a missing older file shows the initial.

function animatedGif(): string
{
    return base64_decode('R0lGODlhAgACAPAAAAAAAP///yH/C05FVFNDQVBFMi4wAwEAAAAh+QQACgAAACwAAAAAAgACAAACAoRRACH5BAAKAAAALAAAAAACAAIAAAIChFEAOw==');
}

it('saves an animated GIF as both the picture and the cover, and serves them unchanged', function () {
    Storage::fake('public');
    $user = User::factory()->create();

    $this->actingAs($user)->patch(route('profile.update'), [
        'name' => $user->name,
        'avatar' => UploadedFile::fake()->createWithContent('me.gif', animatedGif()),
        'cover' => UploadedFile::fake()->createWithContent('cover.gif', animatedGif()),
    ])->assertSessionHasNoErrors();

    $user->refresh();
    expect($user->avatar_path)->toStartWith('media:')->and($user->cover_path)->toStartWith('media:')
        ->and(Storage::disk('public')->allFiles())->toBe([]); // nothing on the disk that a deploy would wipe

    $response = $this->get($user->imageUrl('avatar_path'))->assertOk()
        ->assertHeader('Content-Type', 'image/gif')
        ->assertHeader('Cache-Control', 'immutable, max-age=31536000, public');
    expect($response->getContent())->toBe(animatedGif())
        ->and($response->headers->getCookies())->toBe([]);

    $this->get(route('profile.show', $user))->assertSee($user->imageUrl('cover_path'))->assertSee($user->imageUrl('avatar_path'));
});

it('replaces the old picture instead of keeping both, and removing deletes it', function () {
    $user = User::factory()->create();
    $upload = fn () => UploadedFile::fake()->createWithContent('me.gif', animatedGif());

    $this->actingAs($user)->patch(route('profile.update'), ['name' => $user->name, 'avatar' => $upload()]);
    $this->actingAs($user)->patch(route('profile.update'), ['name' => $user->name, 'avatar' => $upload()]);
    expect(Media::query()->where('user_id', $user->id)->count())->toBe(1);

    $this->actingAs($user)->patch(route('profile.update'), ['name' => $user->name, 'remove_avatar' => '1']);
    expect(Media::query()->count())->toBe(0)->and($user->fresh()->avatar_path)->toBeNull();
});

it('stops new uploads once the pictures reach the storage limit, without breaking the profile form', function () {
    $user = User::factory()->create();
    Media::query()->create(['user_id' => $user->id, 'kind' => 'cover', 'mime' => 'image/gif', 'size' => Media::TOTAL_LIMIT_BYTES, 'data' => '']);

    $this->actingAs($user)->patch(route('profile.update'), ['name' => $user->name, 'avatar' => UploadedFile::fake()->createWithContent('me.gif', animatedGif())])
        ->assertSessionHasErrors('cover');
    $this->actingAs($user)->patch(route('profile.update'), ['name' => 'اسم جديد'])->assertSessionHasNoErrors();
    expect($user->fresh()->name)->toBe('اسم جديد');
});

it('shows the initial instead of a broken image when an older picture file has gone', function () {
    Storage::fake('public');
    $user = User::factory()->create(['name' => 'سارة', 'avatar_path' => 'profiles/avatars/gone.jpg', 'cover_path' => 'profiles/covers/gone.jpg']);

    expect($user->imageUrl('avatar_path'))->toBeNull()->and($user->imageUrl('cover_path'))->toBeNull();
    $this->get(route('profile.show', $user))->assertOk()->assertDontSee('gone.jpg')->assertSee('member-avatar-fallback', false);
});

it('deletes the pictures with the account', function () {
    $user = User::factory()->create(['password' => 'Secret-pass-2026!']);
    $this->actingAs($user)->patch(route('profile.update'), ['name' => $user->name, 'avatar' => UploadedFile::fake()->createWithContent('me.gif', animatedGif())]);

    $this->actingAs($user)->delete(route('account.destroy'), ['password' => 'Secret-pass-2026!']);
    expect(Media::query()->count())->toBe(0);
});
