<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Notifications\EthraaResetPasswordNotification;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
        'bio',
        'location',
        'education',
        'avatar_path',
        'cover_path',
        'skills',
        'social_links',
        // is_admin is deliberately not mass-assignable; grant it with forceFill from the console only.
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'email',
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'skills' => 'array',
            'social_links' => 'array',
            'is_admin' => 'boolean',
        ];
    }

    public function quotes(): HasMany
    {
        return $this->hasMany(Quote::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(QuoteComment::class);
    }

    public function likes(): HasMany
    {
        return $this->hasMany(QuoteLike::class);
    }

    public function favorites(): HasMany
    {
        return $this->hasMany(QuoteFavorite::class);
    }

    /** Rules for a new password (sign-up and reset): at least 8 characters, with letters and numbers. */
    public static function newPasswordRules(): array
    {
        return ['required', 'string', \Illuminate\Validation\Rules\Password::min(8)->letters()->numbers()];
    }

    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new EthraaResetPasswordNotification($token));
    }

    public function sendEmailVerificationNotification(): void
    {
        $this->notify(new \App\Notifications\MidadVerifyEmail);
    }
}
