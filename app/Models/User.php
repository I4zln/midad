<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
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
        'recovery_phone',
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
        'recovery_phone',
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

    public function latestQuote(): HasOne
    {
        return $this->hasOne(Quote::class)->latestOfMany();
    }

    /**
     * A recovery number as +<country><number>, or null when it cannot be read. Saudi numbers may be typed locally
     * (05xxxxxxxx or 5xxxxxxxx); spaces, dashes and brackets are ignored, and a leading 00 counts as +.
     */
    public static function normalizeRecoveryPhone(?string $phone): ?string
    {
        $digits = preg_replace('/[\s\-().]/u', '', (string) $phone);
        $digits = preg_replace('/^00/', '+', $digits);
        if (preg_match('/^05\d{8}$/', $digits)) {
            $digits = '+966'.substr($digits, 1);
        } elseif (preg_match('/^5\d{8}$/', $digits)) {
            $digits = '+966'.$digits;
        } elseif (preg_match('/^966\d{9}$/', $digits)) {
            $digits = '+'.$digits;
        }

        return preg_match('/^\+[1-9]\d{7,14}$/', $digits) ? $digits : null;
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
