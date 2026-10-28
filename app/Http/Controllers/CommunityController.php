<?php

namespace App\Http\Controllers;

use App\Models\Quote;
use App\Models\QuoteComment;
use App\Models\QuoteFavorite;
use App\Models\QuoteLike;
use App\Models\User;
use App\Models\UserFollow;
use App\Models\UserNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class CommunityController extends Controller
{
    public function like(Request $request, Quote $quote): RedirectResponse|JsonResponse
    {
        $user = Auth::user();
        $liked = DB::transaction(function () use ($quote, $user): bool {
            Quote::query()->whereKey($quote->id)->lockForUpdate()->first();
            $like = QuoteLike::query()->where('quote_id', $quote->id)->where('user_id', $user->id)->first();

            if ($like) {
                $like->delete();

                return false;
            }

            QuoteLike::query()->create(['quote_id' => $quote->id, 'user_id' => $user->id]);

            return true;
        });

        if ($liked) {
            $this->notifyQuoteOwner($quote, $user, 'like', 'أُعجب باقتباسك.');
        }

        $likesCount = QuoteLike::query()->where('quote_id', $quote->id)->count();

        if ($request->expectsJson()) {
            return response()->json([
                'liked' => $liked,
                'likes_count' => $likesCount,
                'message' => $liked ? 'أُعجبك الاقتباس.' : 'أُزيل إعجابك.',
            ]);
        }

        return back()->with('status', $liked ? 'أضفت إعجابك إلى الاقتباس.' : 'أُزيل إعجابك.');
    }

    public function favorite(Quote $quote): RedirectResponse
    {
        // Same locked toggle as likes, so a double submit cannot hit the unique (quote_id, user_id) index.
        $saved = DB::transaction(function () use ($quote): bool {
            Quote::query()->whereKey($quote->id)->lockForUpdate()->first();
            $favorite = QuoteFavorite::query()->where('quote_id', $quote->id)->where('user_id', Auth::id())->first();

            if ($favorite) {
                $favorite->delete();

                return false;
            }

            QuoteFavorite::query()->create(['quote_id' => $quote->id, 'user_id' => Auth::id()]);

            return true;
        });

        return back()->with('status', $saved ? 'حُفظ الاقتباس في مفضلتك.' : 'أُزيل الاقتباس من مفضلتك.');
    }

    public function comment(Request $request, Quote $quote): RedirectResponse
    {
        $validated = $request->validate([
            'body' => ['required', 'string', 'max:1000'],
        ]);
        $comment = QuoteComment::query()->create([
            'quote_id' => $quote->id,
            'user_id' => Auth::id(),
            'body' => $validated['body'],
        ]);

        $this->notifyQuoteOwner($quote, Auth::user(), 'comment', 'علّق على اقتباسك.');

        return redirect()->route('quotes.show', $quote)->with('status', 'تم نشر تعليقك.');
    }

    public function follow(User $user): RedirectResponse
    {
        $follower = Auth::user();
        if ($follower->is($user)) {
            throw ValidationException::withMessages(['follow' => 'لا يمكنك متابعة حسابك الشخصي.']);
        }

        $follow = UserFollow::query()->where('follower_id', $follower->id)->where('followed_id', $user->id)->first();
        if ($follow) {
            $follow->delete();

            return back()->with('status', 'ألغيت متابعة هذا الحساب.');
        }

        UserFollow::query()->create(['follower_id' => $follower->id, 'followed_id' => $user->id]);
        UserNotification::query()->create([
            'user_id' => $user->id,
            'actor_id' => $follower->id,
            'type' => 'follow',
            'message' => 'بدأ بمتابعتك.',
        ]);

        return back()->with('status', 'أصبحت تتابع هذا الحساب.');
    }

    private function notifyQuoteOwner(Quote $quote, User $actor, string $type, string $message): void
    {
        if (! $quote->user_id || $quote->user_id === $actor->id) {
            return;
        }

        UserNotification::query()->create([
            'user_id' => $quote->user_id,
            'actor_id' => $actor->id,
            'quote_id' => $quote->id,
            'type' => $type,
            'message' => $message,
        ]);
    }
}
