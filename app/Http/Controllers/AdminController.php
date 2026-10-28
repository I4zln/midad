<?php

namespace App\Http\Controllers;

use App\Models\Quote;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AdminController extends Controller
{
    public function index(): View
    {
        return view('admin.index', [
            'quotes' => Quote::query()->with('user')->withCount(['likes', 'comments'])->latest()->paginate(20),
            'leaderboard' => Quote::query()->with('user')->withCount(['likes', 'comments'])
                ->orderByDesc('likes_count')->orderByDesc('comments_count')->latest()->take(10)->get(),
            'quoteCount' => Quote::query()->count(),
            'memberCount' => \App\Models\User::query()->count(),
        ]);
    }

    public function destroy(Quote $quote): RedirectResponse
    {
        $quote->delete();

        return back()->with('status', 'حُذف الاقتباس من المكتبة.');
    }
}
