import { describe, expect, it } from 'vitest';
import { applyCountFormat, metricsFromCounts, renderCountText } from './count-format';
import { syncCountsForObject } from './count-live';

describe('metricsFromCounts', () => {
  it('sums like/up and dislike/down', () => {
    expect(metricsFromCounts({ like: 2, up: 1, dislike: 1, love: 3 })).toEqual({
      likes: 3,
      dislikes: 1,
      rating: 2,
      total: 7,
    });
  });
});

describe('applyCountFormat', () => {
  it('replaces built-ins and named types', () => {
    expect(
      applyCountFormat('👍 {LIKES} · Σ {TOTAL} · {love}', {
        '{TOTAL}': '4',
        '{LIKES}': '1',
        '{DISLIKES}': '0',
        '{RATING}': '1',
        '{PCT_UP}': '25',
        '{PCT_DOWN}': '0',
      }, { love: 2 }),
    ).toBe('👍 1 · Σ 4 · 2');
  });

  it('fills missing named placeholders with 0', () => {
    expect(applyCountFormat('{love} {fire}', { '{TOTAL}': '0' }, {})).toBe('0 0');
  });
});

describe('renderCountText', () => {
  it('renders catalog format', () => {
    expect(renderCountText('👍 {LIKES} · Σ {TOTAL}', { like: 1, dislike: 1, love: 1 }))
      .toBe('👍 1 · Σ 3');
  });

  it('applies type filter', () => {
    expect(renderCountText('{TOTAL}', { like: 2, love: 5 }, 'love')).toBe('5');
  });
});

describe('syncCountsForObject', () => {
  it('updates matching .reactions-count nodes', () => {
    document.body.innerHTML = `
      <span class="reactions-count"
        data-class-key="msProduct"
        data-object-id="22"
        data-context="web"
        data-format="👍 {LIKES} · Σ {TOTAL}">👍 0 · Σ 0</span>
      <span class="reactions-count"
        data-class-key="msProduct"
        data-object-id="23"
        data-context="web"
        data-format="{TOTAL}">0</span>
    `;

    syncCountsForObject('msProduct', 22, 'web', { like: 1, dislike: 0 });

    const nodes = document.querySelectorAll('.reactions-count');
    expect(nodes[0].textContent).toBe('👍 1 · Σ 1');
    expect(nodes[1].textContent).toBe('0');
  });
});
