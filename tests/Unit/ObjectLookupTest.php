<?php

use Reactions\Support\ObjectLookup;

it('expands short MiniShop aliases before the short class name', function () {
    $candidates = ObjectLookup::classCandidates('msProduct');

    expect($candidates[0])->toBe('MiniShop3\\Model\\msProduct');
    expect($candidates)->not->toContain('msProduct');
});

it('keeps FQCN as the only candidate', function () {
    expect(ObjectLookup::classCandidates('MiniShop3\\Model\\msProduct'))
        ->toBe(['MiniShop3\\Model\\msProduct']);
});

it('allows native mod* short names', function () {
    expect(ObjectLookup::classCandidates('modResource'))
        ->toContain('modResource');
});
