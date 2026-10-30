<?php

namespace App\Http\Controllers;

use App\Models\Quote;
use App\Models\QuoteComment;
use App\Models\QuoteFavorite;
use App\Models\QuoteLike;
use App\Models\SpecialProfileMessage;
use App\Models\User;
use App\Models\UserFollow;
use App\Models\UserNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

// A member deletes their own account and everything tied to it. Asking for the current password
// means a session left open on a shared device cannot be used to wipe the account.
class AccountController extends Controller
{
    public function destroy(Request $request): RedirectResponse
    {
        $request->validate(['password' => ['required', 'current_password']], [
            'password.required' => 'أدخل كلمة المرور لتأكيد حذف الحساب.',
            'password.current_password' => 'كلمة المرور غير صحيحة.',
        ]);

        /** @var User $user */
        $user = $request->user();
        $files = array_filter([$user->avatar_path, $user->cover_path]);

        // Sign out first: logout() refreshes the remember-me token and saves the user, which after a delete would re-create the account.
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        DB::transaction(function () use ($user) {
            // Their quotes go too (with the likes, saves and comments on them); deleting each model keeps caches in step.
            Quote::query()->where('user_id', $user->id)->get()->each->delete();
            QuoteLike::query()->where('user_id', $user->id)->delete();
            QuoteFavorite::query()->where('user_id', $user->id)->delete();
            QuoteComment::query()->where('user_id', $user->id)->delete();
            UserFollow::query()->where('follower_id', $user->id)->orWhere('followed_id', $user->id)->delete();
            UserNotification::query()->where('user_id', $user->id)->delete();
            UserNotification::query()->where('actor_id', $user->id)->update(['actor_id' => null]);
            SpecialProfileMessage::query()->where('user_id', $user->id)->delete();
            DB::table('password_reset_tokens')->where('email', $user->email)->delete();
            DB::table('sessions')->where('user_id', $user->id)->delete();
            $user->delete();
        });
        // Their likes and comments went in bulk (no model events), so rank «الأكثر تفاعلًا» again.
        \Illuminate\Support\Facades\Cache::forget(Quote::LEADERBOARD_CACHE);

        foreach ($files as $path) {
            Storage::disk('public')->delete($path);
        }

        return redirect()->route('home')->with('status', 'حُذف حسابك وكل بياناتك من مِداد.');
    }
}
