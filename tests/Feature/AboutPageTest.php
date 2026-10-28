<?php

use App\Models\User;

test('the old about-me address redirects permanently to the maker section of «من نحن»', function () {
    $this->get('/about-me')->assertStatus(301)->assertRedirect('/about#maker');
    expect(route('creator'))->toEndWith('/about-me');
});

test('navigation and footer link to «من نحن» and no longer show «عنّي»', function () {
    $html = $this->get(route('quotes.index'))->assertOk()->getContent();

    expect(substr_count($html, 'href="'.route('about').'"'))->toBeGreaterThanOrEqual(2)
        ->and($html)->not->toContain('>عنّي<')
        ->and($html)->not->toContain(route('creator'));
});

test('the about page lists the sections in order with only the provided facts', function () {
    $response = $this->get(route('about'))->assertOk();

    $response->assertSeeInOrder([
        'مِداد', 'المِداد هو الحبر الذي تُكتب به الكلمات', 'لأن للكلمات أثرًا يبقى',
        'ما هي مِداد؟',
        'ماذا يمكنك أن تفعل هنا؟', 'تصفّح وابحث', 'انشر اقتباسك', 'عدّل واحذف ما نشرت', 'أعجب واحفظ',
        'صانع مِداد', 'عبدالعزيز تركي المطرفي', 'طالب هندسة برمجيات في جامعة أم القرى',
        'React.js', 'Laravel', 'PHP', 'SQL',
        'دبلوم علوم الحاسب', 'تدريب في وزارة التعليم بمكة', 'تدريب تعاوني في أمانة العاصمة المقدسة', 'بكالوريوس هندسة البرمجيات',
        'PC Builder',
        'LinkedIn', 'GitHub', 'Instagram', 'X',
        'أضف اقتباسًا',
    ], false);

    // Social links open safely in a new tab and say so to screen readers.
    expect(substr_count($response->getContent(), 'target="_blank" rel="noopener noreferrer"'))->toBe(4)
        ->and(substr_count($response->getContent(), '(يفتح في نافذة جديدة)'))->toBe(4);
});

test('the closing button adapts to the visitor', function () {
    $this->get(route('about'))->assertSee(e(route('login', ['reason' => 'add', 'next' => '/quotes/create'])), false);

    $this->actingAs(User::factory()->create())->get(route('about'))->assertSee('href="'.route('quotes.create').'"', false);
});
