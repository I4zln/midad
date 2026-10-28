<?php

test('the theme is resolved before first paint and defaults to the system setting', function () {
    $html = $this->get(route('home'))->assertOk()->getContent();

    // The inline script runs before the stylesheet so the right palette paints first.
    expect(strpos($html, "matchMedia('(prefers-color-scheme: dark)')"))->toBeLessThan(strpos($html, '/build/assets/'))
        ->and($html)->toContain("if (c !== 'light' && c !== 'dark') c = 'system';");
});

test('the navbar offers light, dark and system themes with Arabic labels', function () {
    $this->get(route('quotes.index'))->assertOk()
        ->assertSee('id="theme-toggle"', false)
        ->assertSee('المظهر: <span data-theme-current>حسب النظام</span>', false)
        ->assertSeeInOrder(['data-theme-option="light"', 'فاتح', 'data-theme-option="dark"', 'داكن', 'data-theme-option="system" aria-pressed="true"', 'حسب النظام'], false);
});

test('components use theme tokens instead of hard-coded colours', function () {
    $css = file_get_contents(resource_path('css/app.css'));
    // Strip the two token blocks; nothing else may carry a literal colour (mask gradients use #000 for alpha only).
    $rest = preg_replace("/^(?::root|:root\\[data-theme='dark'\\]) \\{.*?^\\}/ms", '', str_replace("\r\n", "\n", $css));
    preg_match_all('/#[0-9a-fA-F]{3,8}\b|rgba?\(/', $rest, $matches);

    expect(array_values(array_diff($matches[0], ['#000'])))->toBe([]);
});
