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
            $filtered = $this->applyLibraryFilters($query, $category, $search);
            $quotes = $query->paginate(12)->withQueryString();

            return view('quotes.index', [
                'quotes' => $quotes,
                'activeCategory' => $category,
                'search' => $search,
                // Unfiltered, the paginator already counted the whole library.
                'totalQuotes' => $filtered ? Quote::query()->count() : $quotes->total(),
                'categories' => ['تأمل' => 'تأمّل', 'حياة' => 'حياة', 'حب' => 'حب', 'أمل' => 'أمل'],
            ]);
        }

        $latestQuotes = (clone $query)->take(5)->get();

        $poets = Quote::authorCounts()->take(12);

        return view('welcome', [
            'quotes' => $latestQuotes,
            'leaderboard' => Quote::leaderboard(),
            'featuredQuote' => $latestQuotes->first(),
            'poets' => $poets,
            'activeCategory' => '',
            'search' => '',
        ]);
    }

    /** Applies the category and search filters; returns whether any filter narrowed the list. */
    private function applyLibraryFilters($query, string $category, string $search): bool
    {
        $filtered = false;
        if (in_array($category, QuoteRequest::CATEGORIES, true)) {
            $query->where('category', $category);
            $filtered = true;
        }

        if ($search !== '') {
            $filtered = true;
            $query->where(function ($builder) use ($search) {
                $builder->where('text', 'like', "%{$search}%")
                    ->orWhere('author', 'like', "%{$search}%")
                    ->orWhere('source', 'like', "%{$search}%");
            });
        }

        return $filtered;
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
        $previous = url()->previous();
        $quote->delete();

        // The quote's own pages no longer exist; send the owner to their quotes. Otherwise stay where they were.
        $wasOnQuotePage = in_array(strtok($previous, '?#'), [route('quotes.show', $quote), route('quotes.edit', $quote)], true);
        $sameSite = str_starts_with($previous, url('/'));

        return ($wasOnQuotePage || ! $sameSite ? redirect()->route('profile.show', Auth::user()) : redirect()->to($previous))
            ->with('status', 'حُذف اقتباسك.');
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
