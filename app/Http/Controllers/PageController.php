<?php

namespace App\Http\Controllers;

use App\Models\QuoteFavorite;
use App\Models\QuoteLike;
use App\Models\Quote;
use App\Models\User;
use App\Models\UserFollow;
use App\Models\UserNotification;
use App\Models\SpecialProfileMessage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PageController extends Controller
{
    public function about(): View
    {
        return view('pages.about');
    }

    public function muallaqat(): View
    {
        return view('pages.muallaqat', ['muallaqat' => config('muallaqat')]);
    }

    public function muallaqa(string $slug): View
    {
        $muallaqa = collect(config('muallaqat'))->firstWhere('slug', $slug);
        abort_unless($muallaqa, 404);

        $poems = collect(config('muallaqat'));
        $index = $poems->search(fn ($poem) => $poem['slug'] === $slug);

        return view('pages.muallaqa', [
            'muallaqa' => $muallaqa,
            'previous' => $poems->get(($index - 1 + $poems->count()) % $poems->count()),
            'next' => $poems->get(($index + 1) % $poems->count()),
        ]);
    }

    public function poets(): View
    {
        $priority = ['المتنبي', 'أبو الطيب المتنبي', 'أبو العلاء المعري', 'عنترة بن شداد', 'زهير بن أبي سلمى', 'أبو فراس الحمداني'];
        $poets = Quote::authorCounts()
            ->sortBy(function ($poet) use ($priority) {
                foreach ($priority as $index => $name) {
                    if (mb_strpos($poet->author, $name) !== false || mb_strpos($name, $poet->author) !== false) {
                        return $index;
                    }
                }

                return count($priority) + (1000 - $poet->quotes_count);
            })
            ->values();

        return view('pages.poets', ['poets' => $poets]);
    }

    public function favorites(): View
    {
        // Saved quotes, most recently saved first.
        $quotes = Quote::query()
            ->whereHas('favorites', fn ($favorites) => $favorites->where('user_id', Auth::id()))
            ->withCardData()
            ->orderByDesc(QuoteFavorite::query()->select('created_at')
                ->whereColumn('quote_id', 'quotes.id')->where('user_id', Auth::id())->limit(1))
            ->paginate(12);

        return view('pages.favorites', ['quotes' => $quotes]);
    }

    public function notifications(): View
    {
        $notifications = UserNotification::query()
            ->where('user_id', Auth::id())
            ->with(['actor', 'quote'])
            ->latest()
            ->paginate(20);

        return view('pages.notifications', ['notifications' => $notifications]);
    }

    public function markNotificationsRead(): RedirectResponse
    {
        UserNotification::query()
            ->where('user_id', Auth::id())
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return back()->with('status', 'تم تعليم الإشعارات كمقروءة.');
    }

    public function profile(Request $request, User $user): View
    {
        $isOwner = Auth::check() && Auth::user()->is($user);
        // Liked and saved lists are private to their owner.
        $tab = in_array($request->query('tab'), ['liked', 'saved'], true) && $isOwner ? $request->query('tab') : 'quotes';

        $quotes = match ($tab) {
            'liked' => Quote::query()->whereHas('likes', fn ($likes) => $likes->where('user_id', $user->id))->withCardData()
                ->orderByDesc(QuoteLike::query()->select('created_at')->whereColumn('quote_id', 'quotes.id')->where('user_id', $user->id)->limit(1)),
            'saved' => Quote::query()->whereHas('favorites', fn ($favorites) => $favorites->where('user_id', $user->id))->withCardData()
                ->orderByDesc(QuoteFavorite::query()->select('created_at')->whereColumn('quote_id', 'quotes.id')->where('user_id', $user->id)->limit(1)),
            default => $user->quotes()->withCardData()->latest()->latest('id'),
        };
        $quotes = $quotes->paginate(9)->withQueryString();

        $stats = [
            'quotes' => $tab === 'quotes' ? $quotes->total() : $user->quotes()->count(),
            'likes_received' => QuoteLike::query()->whereHas('quote', fn ($quote) => $quote->where('user_id', $user->id))->count(),
            'liked' => $isOwner ? $user->likes()->count() : null,
            'saved' => $isOwner ? $user->favorites()->count() : null,
        ];
        $specialMessage = null;
        $specialEmail = (string) config('ethraa.special_profile_email', '');

        $isSpecialOwner = Auth::check() && Auth::user()->is($user)
            && $specialEmail !== '' && hash_equals(mb_strtolower($specialEmail), mb_strtolower($user->email));

        if ($isSpecialOwner && $user->hasVerifiedEmail()) {
            $specialMessage = SpecialProfileMessage::query()->firstOrCreate(
                ['user_id' => $user->id, 'message_key' => 'jarir-welcome-2026'],
                [
                    'title' => 'إلى زياد إمام، رئيس نادي طويق',
                    'body' => "إِذا قَصَدَ الرِّجالُ حِماكَ يَوْمًا\nتُلاقيهِمْ بِبِشرٍ وَارتِياحِ\nوَتَكفيهِمْ مَؤونَةَ كُلِّ أَمرٍ\nبِرَأيٍ صائِبٍ وَيَدِ السَّماحِ\nفِعالُكَ تَسبِقُ الأَقوالَ دَوماً\nوَتَسعى لِلصَّلاحِ وَلِلنَّجاحِ\nفَدُمتَ لِقَومِكَ الغَيثَ المُرَجّى\nوَنَجمَ هِدايَةٍ نَحوَ الفَلاحِ",
                    'sender_name' => 'عبدالعزيز المطرفي',
                    'sender_role' => 'مدير مِداد',
                ],
            );

            // Stays on the owner's profile on every visit; shown_at records the first time it was seen.
            if (! $specialMessage->shown_at) {
                $specialMessage->forceFill(['shown_at' => now()])->save();
            }
        }

        return view('profile.show', [
            'profile' => $user,
            'quotes' => $quotes,
            'tab' => $tab,
            'isOwner' => $isOwner,
            'stats' => $stats,
            // The special card waits for a verified address; until then the owner sees how to unlock it.
            'specialPending' => $isSpecialOwner && ! $user->hasVerifiedEmail(),
            'mailReady' => config('mail.default') !== 'log',
            'followers' => UserFollow::query()->where('followed_id', $user->id)->count(),
            'following' => UserFollow::query()->where('follower_id', $user->id)->count(),
            'isFollowing' => Auth::check() && UserFollow::query()
                ->where('follower_id', Auth::id())
                ->where('followed_id', $user->id)
                ->exists(),
            'specialMessage' => $specialMessage,
        ]);
    }

    public function editProfile(): View
    {
        return view('profile.edit', ['profile' => Auth::user()]);
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $user = Auth::user();
        $validated = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:50'],
            'bio' => ['nullable', 'string', 'max:600'],
            'location' => ['nullable', 'string', 'max:120'],
            'education' => ['nullable', 'string', 'max:180'],
            'skills' => ['nullable', 'string', 'max:500'],
            'social_links.linkedin' => ['nullable', 'url:http,https', 'max:255'],
            'social_links.github' => ['nullable', 'url:http,https', 'max:255'],
            'social_links.instagram' => ['nullable', 'url:http,https', 'max:255'],
            'social_links.x' => ['nullable', 'url:http,https', 'max:255'],
            'recovery_phone' => ['nullable', 'string', 'max:25'],
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            // A cover may be an animated GIF; it is stored as uploaded, so it keeps moving.
            'cover' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:4096'],
            'remove_avatar' => ['nullable', 'boolean'],
            'remove_cover' => ['nullable', 'boolean'],
        ]);
        $recoveryPhone = User::normalizeRecoveryPhone($validated['recovery_phone'] ?? null);
        if (filled($validated['recovery_phone'] ?? null) && ! $recoveryPhone) {
            throw ValidationException::withMessages(['recovery_phone' => 'اكتب الرقم كاملًا، مثل 05xxxxxxxx أو ‎+9665xxxxxxxx.']);
        }

        $user->fill([
            'name' => $validated['name'],
            'bio' => $validated['bio'] ?? null,
            'location' => $validated['location'] ?? null,
            'education' => $validated['education'] ?? null,
            'skills' => isset($validated['skills'])
                ? array_values(array_filter(array_map('trim', explode(',', $validated['skills']))))
                : [],
            'social_links' => array_filter($validated['social_links'] ?? []),
            'recovery_phone' => $recoveryPhone,
        ]);

        foreach (['avatar' => 'avatar_path', 'cover' => 'cover_path'] as $input => $column) {
            if ($request->boolean('remove_'.$input)) {
                if ($user->{$column}) {
                    Storage::disk('public')->delete($user->{$column});
                }
                $user->{$column} = null;
            }

            if ($request->hasFile($input)) {
                if ($user->{$column}) {
                    Storage::disk('public')->delete($user->{$column});
                }
                $user->{$column} = $request->file($input)->store('profiles/'.$input.'s', 'public');
            }
        }

        $user->save();

        return redirect()->route('profile.show', $user)->with('status', 'تم تحديث ملفك الشخصي.');
    }
}
