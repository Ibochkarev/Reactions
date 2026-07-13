import { beforeEach, describe, expect, it, vi } from 'vitest';
import * as api from './api';
import { ReactionsWidget, getReactionTypes, parseConfig } from './widget';

vi.mock('./api', () => ({
  fetchCounts: vi.fn(),
  getCsrf: vi.fn(),
  react: vi.fn(),
  unreact: vi.fn(),
  createNonce: vi.fn(() => 'test-nonce'),
}));

const mockedApi = vi.mocked(api);

function createHost(attrs: Record<string, string> = {}): HTMLElement {
  const defaults: Record<string, string> = {
    'data-api': '/assets/components/reactions/api.php',
    'data-class-key': 'modResource',
    'data-object-id': '42',
    'data-set': 'updown',
    'data-context': 'web',
    'data-csrf': 'csrf-token',
  };

  const el = document.createElement('div');
  el.className = 'reactions-widget';

  for (const [key, value] of Object.entries({ ...defaults, ...attrs })) {
    el.setAttribute(key, value);
  }

  document.body.appendChild(el);
  return el;
}

describe('parseConfig', () => {
  it('reads data attributes from host element', () => {
    const el = createHost();
    const config = parseConfig(el);

    expect(config).toEqual({
      api: '/assets/components/reactions/api.php',
      classKey: 'modResource',
      objectId: 42,
      set: 'updown',
      context: 'web',
      csrf: 'csrf-token',
    });
  });

  it('returns null when required attributes are missing', () => {
    const el = document.createElement('div');
    expect(parseConfig(el)).toBeNull();
  });
});

describe('ReactionsWidget', () => {
  beforeEach(() => {
    document.body.innerHTML = '';
    vi.clearAllMocks();

    mockedApi.fetchCounts.mockResolvedValue({
      class_key: 'modResource',
      object_id: 42,
      context: 'web',
      counts: { like: 3, dislike: 1 },
      total: 4,
      user_reaction: ['like'],
    });
  });

  it('renders reaction buttons with counts and aria attributes', async () => {
    const el = createHost();
    const config = parseConfig(el)!;
    const widget = new ReactionsWidget(el, config);

    await widget.init();

    expect(el.getAttribute('role')).toBe('group');
    expect(el.getAttribute('aria-label')).toBe('Reactions');

    const buttons = el.querySelectorAll<HTMLButtonElement>('.reactions-widget__button');
    expect(buttons).toHaveLength(2);

    const like = buttons[0];
    expect(like.dataset.type).toBe('like');
    expect(like.getAttribute('aria-pressed')).toBe('true');
    expect(like.querySelector('.reactions-widget__count')?.textContent).toBe('3');

    const dislike = buttons[1];
    expect(dislike.getAttribute('aria-pressed')).toBe('false');
    expect(dislike.querySelector('.reactions-widget__count')?.textContent).toBe('1');
  });

  it('applies optimistic update and syncs after react', async () => {
    mockedApi.react.mockResolvedValue({
      action: 'changed',
      counts: { like: 2, dislike: 2 },
      total: 4,
      user_reaction: ['dislike'],
      type: 'dislike',
    });

    const el = createHost();
    const config = parseConfig(el)!;
    const widget = new ReactionsWidget(el, config);
    await widget.init();

    await widget.handleClick('dislike');

    expect(mockedApi.react).toHaveBeenCalledWith(
      config.api,
      expect.objectContaining({
        type: 'dislike',
        class_key: 'modResource',
        object_id: 42,
        csrf: 'csrf-token',
        nonce: 'test-nonce',
      }),
    );

    const dislike = el.querySelector<HTMLButtonElement>('[data-type="dislike"]');
    expect(dislike?.getAttribute('aria-pressed')).toBe('true');
    expect(dislike?.querySelector('.reactions-widget__count')?.textContent).toBe('2');
  });

  it('reverts optimistic update on API error', async () => {
    mockedApi.unreact.mockRejectedValue(new Error('Rate limit exceeded'));

    const el = createHost();
    const config = parseConfig(el)!;
    const widget = new ReactionsWidget(el, config);
    await widget.init();

    await widget.handleClick('like');

    expect(mockedApi.unreact).toHaveBeenCalled();
    expect(el.querySelector('[data-type="like"]')?.getAttribute('aria-pressed')).toBe('true');
    expect(el.querySelector('[data-type="like"] .reactions-widget__count')?.textContent).toBe('3');
  });

  it('uses github set types when configured', () => {
    expect(getReactionTypes('github')).toHaveLength(8);
    expect(getReactionTypes('unknown')).toEqual(getReactionTypes('updown'));
  });
});
