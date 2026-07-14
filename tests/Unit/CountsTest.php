<?php

use Reactions\Support\Counts;

it('normalizes json strings and casts numeric values', function () {
    expect(Counts::normalize('{"like":"3","dislike":1}'))
        ->toBe(['like' => 3, 'dislike' => 1]);
});

it('drops non-numeric junk including nested json strings', function () {
    expect(Counts::normalize([
        'like' => 2,
        'bad' => '[]',
        'nested' => '{"x":1}',
        0 => 'like',
        '' => 5,
    ]))->toBe(['like' => 2]);
});

it('totals only numeric values without warnings', function () {
    expect(Counts::total([
        'like' => 2,
        'bad' => '[]',
        'dislike' => '1',
    ]))->toBe(3);
});

it('returns empty for invalid payloads', function () {
    expect(Counts::normalize(null))->toBe([]);
    expect(Counts::normalize('not-json'))->toBe([]);
    expect(Counts::total(['x' => '[]']))->toBe(0);
});
