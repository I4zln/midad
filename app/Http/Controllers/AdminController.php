<?php

namespace App\Http\Controllers;

use App\Http\Requests\QuoteRequest;
use App\Models\Quote;
use App\Models\QuoteLike;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->string('q')->trim()->toString();
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
            'members' => User::query()->withCount('quotes')->latest()->latest('id')->take(6)->get(),
        ]);
    }

    public function destroy(Quote $quote): RedirectResponse
    {
        $quote->delete();

        return back()->with('status', 'حُذف الاقتباس من المكتبة.');
    }
}
