<?php

use Reactions\Support\CountFormat;

it('replaces built-ins and named type counters', function () {
    $out = CountFormat::apply(
        'T={TOTAL} L={like} ★={star}',
        ['{TOTAL}' => '3'],
        ['like' => 2, 'star' => 1],
    );

    expect($out)->toBe('T=3 L=2 ★=1');
});

it('fills missing type placeholders with zero', function () {
    $out = CountFormat::apply(
        '❤️ {love} · 🔥 {fire} · 👍 {like}',
        ['{TOTAL}' => '2'],
        ['like' => 2],
    );

    expect($out)->toBe('❤️ 0 · 🔥 0 · 👍 2');
});

it('does not invent uppercase built-in placeholders', function () {
    $out = CountFormat::apply('{TOTAL} {FOO}', ['{TOTAL}' => '1'], []);

    expect($out)->toBe('1 {FOO}');
});
