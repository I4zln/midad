<?php

namespace App\Http\Controllers;

use App\Http\Requests\QuoteRequest;
use App\Models\Quote;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class QuoteController extends Controller
{
    public function index(Request $request): View
    {
        $category = $request->string('category')->toString();
        $search = $request->string('q')->trim()->toString();
        $query = Quote::query()->withCardData()->latest('created_at')->latest('id');

        if ($request->routeIs('quotes.index')) {
            $this->applyLibraryFilters($query, $category, $search);

            return view('quotes.index', [
                'quotes' => $query->paginate(12)->withQueryString(),
                'activeCategory' => $category,
                'search' => $search,
                'totalQuotes' => Quote::query()->count(),
                'categories' => ['تأمل' => 'تأمّل', 'حياة' => 'حياة', 'حب' => 'حب', 'أمل' => 'أمل'],
            ]);
        }

        $latestQuotes = (clone $query)->take(5)->get();

        $leaderboardQuery = Quote::query()->with('user')->withCount(['likes', 'comments']);

        $poets = Quote::query()->selectRaw('author, COUNT(*) as quotes_count')
            ->whereNotNull('author')->where('author', '!=', '')
            ->groupBy('author')->orderByDesc('quotes_count')->limit(12)->get();

        return view('welcome', [
            'quotes' => $latestQuotes,
            'leaderboard' => $leaderboardQuery->orderByDesc('likes_count')->orderByDesc('comments_count')->latest()->take(5)->get(),
            'featuredQuote' => $latestQuotes->first(),
            'poets' => $poets,
            'activeCategory' => '',
            'search' => '',
        ]);
    }

    private function applyLibraryFilters($query, string $category, string $search): void
    {
        if (in_array($category, QuoteRequest::CATEGORIES, true)) {
            $query->where('category', $category);
        }

        if ($search !== '') {
            $query->where(function ($builder) use ($search) {
                $builder->where('text', 'like', "%{$search}%")
                    ->orWhere('author', 'like', "%{$search}%")
                    ->orWhere('source', 'like', "%{$search}%");
            });
        }
    }

    public function create(): View
    {
        return view('quotes.create');
    }

    public function edit(Quote $quote): View
    {
        return view('quotes.edit', ['quote' => $quote]);
    }

    public function store(QuoteRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $validated['user_id'] = Auth::id();
        Quote::query()->create($validated);

        return redirect()->route('home')->with('status', 'تمت إضافة اقتباسك إلى المكتبة، وسيظهر في مقدمة الصفحة.');
    }

    public function update(QuoteRequest $request, Quote $quote): RedirectResponse
    {
        $quote->update($request->validated());

        return redirect()->route('quotes.show', $quote)->with('status', 'تم تحديث اقتباسك.');
    }

    public function destroy(Quote $quote): RedirectResponse
    {
        $quote->delete();

        return redirect()->route('quotes.index')->with('status', 'تم حذف اقتباسك.');
    }

    public function show(Quote $quote): View
    {
        $quote->load(['user', 'comments.user'])->loadCount(['likes', 'comments', 'favorites']);
        if (Auth::check()) {
            $quote->loadExists(['likes as is_liked' => fn ($likes) => $likes->where('user_id', Auth::id())]);
            $quote->loadExists(['favorites as is_favorited' => fn ($favorites) => $favorites->where('user_id', Auth::id())]);
        }

        return view('quotes.show', ['quote' => $quote]);
    }

    public function author(string $author): View
    {
        return view('authors.show', [
            'author' => $author,
            'quotes' => Quote::query()->where('author', $author)->withCardData()->latest()->latest('id')->paginate(12),
        ]);
    }
}
