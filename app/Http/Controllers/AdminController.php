<?php

namespace App\Http\Controllers;

use App\Http\Requests\QuoteRequest;
use App\Models\PasswordHelpRequest;
use App\Models\Quote;
use App\Models\QuoteLike;
use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
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
            // Members who asked on «نسيت كلمة المرور» while email cannot be sent, newest first.
            'helpRequests' => PasswordHelpRequest::query()->open()->with(['user' => fn ($user) => $user->withCount('quotes')->with('latestQuote')])->latest('updated_at')->get(),
            'members' => User::query()->withCount('quotes')
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

        PasswordHelpRequest::query()->open()->where('user_id', $user->id)->update(['handled_at' => now()]);

        $url = route('password.reset', ['token' => $token, 'email' => $user->email]);
        $minutes = (int) config('auth.passwords.'.config('auth.defaults.passwords').'.expire', 60);
        $user->loadCount('quotes')->load('latestQuote');

        return back()->with('resetLink', [
            'name' => $user->name,
            'url' => $url,
            'minutes' => $minutes,
            // What to check before sending, and a WhatsApp message to the number on the account (the safest way to send).
            'phone' => $user->recovery_phone,
            'whatsapp' => $user->recovery_phone ? 'https://wa.me/'.ltrim($user->recovery_phone, '+').'?text='.rawurlencode(
                "مرحبًا {$user->name}، هذا رابط استعادة كلمة مرورك في مِداد. يعمل مرة واحدة خلال {$minutes} دقيقة:\n{$url}"
            ) : null,
            'username' => $user->username,
            'joined' => $user->created_at?->format('Y/m/d'),
            'quotes' => $user->quotes_count,
            'latestQuote' => $user->latestQuote ? Str::limit($user->latestQuote->text, 60) : null,
        ]);
    }

    /** «تمّ»: the admin dealt with the request another way (or it was not genuine). */
    public function closeHelpRequest(PasswordHelpRequest $helpRequest): RedirectResponse
    {
        $helpRequest->update(['handled_at' => now()]);

        return back()->with('status', 'أُغلق الطلب.');
    }

    public function destroy(Quote $quote): RedirectResponse
    {
        $quote->delete();

        return back()->with('status', 'حُذف الاقتباس من المكتبة.');
    }
}
