<?php

use Reactions\Support\TypeFilter;

it('parses comma-separated type lists', function () {
    expect(TypeFilter::parseTypeList(' like, Love ,fire,like '))
        ->toBe(['like', 'love', 'fire']);
});

it('returns empty list for blank input', function () {
    expect(TypeFilter::parseTypeList(''))->toBe([]);
    expect(TypeFilter::parseTypeList('  , , '))->toBe([]);
});

it('prefers snippet types over full_types setting', function () {
    expect(TypeFilter::resolveAllowList('full', 'like,star', 'like,fire'))
        ->toBe(['like', 'star']);
});

it('uses full_types setting for full set when snippet types empty', function () {
    expect(TypeFilter::resolveAllowList('full', '', 'like,fire,star'))
        ->toBe(['like', 'fire', 'star']);
});

it('does not apply full_types setting to other sets', function () {
    expect(TypeFilter::resolveAllowList('github', '', 'like,fire'))->toBeNull();
});

it('returns null when full set has empty setting', function () {
    expect(TypeFilter::resolveAllowList('full', '', ''))->toBeNull();
});

it('treats empty allow list as show nothing', function () {
    expect(TypeFilter::filterTypes([], []))->toBe([]);
});

it('treats null allow list as no filter for empty input', function () {
    expect(TypeFilter::filterTypes([], null))->toBe([]);
});
