<?php

namespace App\Http\Controllers;

use App\Http\Requests\QuoteRequest;
use App\Models\Quote;
use App\Services\DiscoverFeed;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use InvalidArgumentException;

// «اكتشف»: the first batch is rendered with the page for a fast first paint; the rest arrive from feed() as the reader scrolls.
class DiscoverController extends Controller
{
    public const MODES = ['focus', 'timeline'];

    public function __construct(private DiscoverFeed $feed) {}

    public function index(Request $request): View
    {
        [$tab, $category] = $this->filters($request);
        $page = $this->feed->page($request->user(), $tab, $category, null, $request->integer('quote') ?: null);

        return view('discover.index', [
            'quotes' => $page['quotes'],
            'next' => $page['next'],
            'tab' => $tab,
            'category' => $category,
            'categories' => array_intersect_key(QuoteRequest::CATEGORY_LABELS, array_flip($this->feed->categories())),
            // «تأمّل» shows where the reader is: "3 من 31".
            'total' => Quote::query()->when($category !== '', fn ($query) => $query->where('category', $category))->count(),
            'mode' => in_array($request->user()?->discover_mode, self::MODES, true) ? $request->user()->discover_mode : null,
        ]);
    }

    public function feed(Request $request): JsonResponse
    {
        [$tab, $category] = $this->filters($request);
        $cursor = $request->query('cursor');

        try {
            $page = $this->feed->page($request->user(), $tab, $category, is_string($cursor) && $cursor !== '' ? $cursor : null);
        } catch (InvalidArgumentException) {
            return response()->json(['message' => 'تعذّر متابعة هذه القائمة. حدّث الصفحة لتبدأ من جديد.'], 422);
        }

        return response()->json([
            'html' => view('discover._items', ['quotes' => $page['quotes'], 'tab' => $tab, 'category' => $category])->render(),
            'ids' => $page['quotes']->modelKeys(),
            'next' => $page['next'],
        ]);
    }

    public function mode(Request $request): Response
    {
        $validated = $request->validate(['mode' => ['required', 'in:'.implode(',', self::MODES)]]);
        $request->user()->forceFill(['discover_mode' => $validated['mode']])->save();

        return response()->noContent();
    }

    /** Unknown tabs fall back to «مختارة لك» and unknown categories to «الكل». */
    private function filters(Request $request): array
    {
        $tab = $request->string('tab')->toString();
        $category = $request->string('category')->toString();

        return [
            in_array($tab, DiscoverFeed::TABS, true) ? $tab : 'for-you',
            in_array($category, QuoteRequest::CATEGORIES, true) ? $category : '',
        ];
    }
}
