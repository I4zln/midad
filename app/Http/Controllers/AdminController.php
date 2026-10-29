<?php

namespace App\Http\Controllers;

use App\Http\Requests\QuoteRequest;
use App\Models\Quote;
use App\Models\QuoteLike;
use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;

class AdminController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->string('q')->trim()->toString();
        $memberSearch = $request->string('member')->trim()->toString();
        $category = in_array($request->query('category'), QuoteRequest::CATEGORIES, true) ? $request->query('category') : '';

        // Every quote, newest first; the search looks in the text, the author and the publisher's name.
        $quotes = Quote::query()->with('user')->withCount(['likes', 'comments'])
            ->when($category !== '', fn ($query) => $query->where('category', $category))
            ->when($search !== '', fn ($query) => $query->where(fn ($inner) => $inner
                ->where('text', 'like', "%{$search}%")
                ->orWhere('author', 'like', "%{$search}%")
                ->orWhereHas('user', fn ($user) => $user->where('name', 'like', "%{$search}%"))))
            ->latest()->latest('id')
            ->paginate(15)->withQueryString();

        return view('admin.index', [
            'quotes' => $quotes,
            'search' => $search,
            'category' => $category,
            'categories' => QuoteRequest::CATEGORY_LABELS,
            'stats' => [
                'quotes' => Quote::query()->count(),
                'members' => User::query()->count(),
                'likes' => QuoteLike::query()->count(),
                'week' => Quote::query()->where('created_at', '>=', now()->subDays(7))->count(),
            ],
            'top' => Quote::query()->withCount('likes')->orderByDesc('likes_count')->latest()->latest('id')->take(5)->get(),
            // Newest members, or the ones matching a name, username or email (to hand someone a reset link).
            'memberSearch' => $memberSearch,
            'members' => User::query()->withCount('quotes')->with('latestQuote')
                ->when($memberSearch !== '', fn ($query) => $query->where(fn ($inner) => $inner
                    ->where('name', 'like', "%{$memberSearch}%")
                    ->orWhere('username', 'like', "%{$memberSearch}%")
                    ->orWhere('email', 'like', '%'.mb_strtolower($memberSearch).'%')))
                ->latest()->latest('id')->take($memberSearch !== '' ? 10 : 6)->get(),
        ]);
    }

    /**
     * A one-time link to choose a new password (valid as long as an emailed one, 60 minutes), shown once to the admin
     * to send to the member, e.g. on WhatsApp. Creating it replaces any earlier link for that member.
     */
    public function resetLink(User $user): RedirectResponse
    {
        $token = Password::broker()->createToken($user);
        // The owner sees this in «الإشعارات»: if someone else asked for the link, they know to tell the admin.
        UserNotification::query()->create([
            'user_id' => $user->id,
            'actor_id' => auth()->id(),
            'type' => 'security',
            'message' => 'أنشأ رابطًا لاستعادة كلمة مرور حسابك. إن لم تطلبه فراسل الإدارة فورًا.',
        ]);

        return back()->with('resetLink', [
            'name' => $user->name,
            'url' => route('password.reset', ['token' => $token, 'email' => $user->email]),
            'minutes' => (int) config('auth.passwords.'.config('auth.defaults.passwords').'.expire', 60),
        ]);
    }

    public function destroy(Quote $quote): RedirectResponse
    {
        $quote->delete();

        return back()->with('status', 'حُذف الاقتباس من المكتبة.');
    }
}
