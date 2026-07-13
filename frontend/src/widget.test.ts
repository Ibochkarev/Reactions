import { beforeEach, describe, expect, it, vi } from 'vitest';
import * as api from './api';
import {
  ReactionsWidget,
  getReactionTypes,
  parseConfig,
  resolveApiUrl,
  resolveApiUrlFromScript,
  resolveReactionTypes,
} from './widget';

vi.mock('./api', () => ({
  fetchCounts: vi.fn(),
  getCsrf: vi.fn(),
  react: vi.fn(),
  unreact: vi.fn(),
  createNonce: vi.fn(() => 'test-nonce'),
}));

const mockedApi = vi.mocked(api);

function appendReactionsScript(src = '/assets/components/reactions/js/web/reactions.js'): HTMLScriptElement {
  const script = document.createElement('script');
  script.src = src;
  document.head.appendChild(script);
  return script;
}

function createHost(attrs: Record<string, string | null> = {}): HTMLElement {
  const defaults: Record<string, string> = {
    'data-class-key': 'modResource',
    'data-object-id': '42',
    'data-set': 'updown',
    'data-context': 'web',
    'data-csrf': 'csrf-token',
  };

  const merged: Record<string, string | null> = { ...defaults, ...attrs };
  const el = document.createElement('div');
  el.className = 'reactions-widget';

  for (const [key, value] of Object.entries(merged)) {
    if (value === null) {
      continue;
    }
    el.setAttribute(key, value);
  }

  document.body.appendChild(el);
  return el;
}

describe('resolveApiUrl', () => {
  beforeEach(() => {
    document.head.innerHTML = '';
    document.body.innerHTML = '';
    if (window.Reactions) {
      delete window.Reactions.config;
    }
  });

  it('resolves api.php relative to reactions.js script src', () => {
    appendReactionsScript('/assets/components/reactions/js/web/reactions.js');
    const resolved = resolveApiUrlFromScript();
    expect(resolved).toBe(new URL('/assets/components/reactions/api.php', window.location.href).href);
  });

  it('prefers data-api over script and global config', () => {
    appendReactionsScript();
    window.Reactions = { init: () => [], config: { api: '/from-global' } };
    const el = createHost({ 'data-api': '/from-attr' });
    expect(resolveApiUrl(el)).toBe('/from-attr');
  });

  it('uses window.Reactions.config.api when data-api is absent', () => {
    window.Reactions = { init: () => [], config: { api: '/from-global' } };
    const el = createHost();
    expect(resolveApiUrl(el)).toBe('/from-global');
  });
});

describe('parseConfig', () => {
  beforeEach(() => {
    document.head.innerHTML = '';
    document.body.innerHTML = '';
    if (window.Reactions) {
      delete window.Reactions.config;
    }
  });

  it('reads data attributes and resolves api from script', () => {
    appendReactionsScript();
    const el = createHost();
    const config = parseConfig(el);

    expect(config).toEqual({
      api: new URL('/assets/components/reactions/api.php', window.location.href).href,
      classKey: 'modResource',
      objectId: 42,
      set: 'updown',
      context: 'web',
      csrf: 'csrf-token',
      types: undefined,
    });
  });

  it('parses data-types into config', () => {
    appendReactionsScript();
    const el = createHost({ 'data-types': 'like, fire, star' });
    const config = parseConfig(el);

    expect(config?.types).toEqual(['like', 'fire', 'star']);
  });

  it('keeps empty data-types as empty list', () => {
    appendReactionsScript();
    const el = createHost({ 'data-types': '' });
    const config = parseConfig(el);

    expect(config?.types).toEqual([]);
  });

  it('uses data-api when provided', () => {
    const el = createHost({ 'data-api': '/assets/components/reactions/api.php' });
    const config = parseConfig(el);

    expect(config?.api).toBe('/assets/components/reactions/api.php');
  });

  it('returns null when required attributes are missing', () => {
    const el = document.createElement('div');
    expect(parseConfig(el)).toBeNull();
  });

  it('returns null when api cannot be resolved', () => {
    const el = createHost();
    expect(parseConfig(el)).toBeNull();
  });
});

describe('ReactionsWidget', () => {
  beforeEach(() => {
    document.head.innerHTML = '';
    document.body.innerHTML = '';
    if (window.Reactions) {
      delete window.Reactions.config;
    }
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
    appendReactionsScript();
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

    appendReactionsScript();
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

    appendReactionsScript();
    const el = createHost();
    const config = parseConfig(el)!;
    const widget = new ReactionsWidget(el, config);
    await widget.init();

    await widget.handleClick('like');

    expect(mockedApi.unreact).toHaveBeenCalled();
    expect(el.querySelector('[data-type="like"]')?.getAttribute('aria-pressed')).toBe('true');
    expect(el.querySelector('[data-type="like"] .reactions-widget__count')?.textContent).toBe('3');
  });

  it('uses github and full set types when configured', () => {
    expect(getReactionTypes('github')).toHaveLength(8);
    expect(getReactionTypes('full')).toHaveLength(24);
    expect(getReactionTypes('unknown')).toEqual(getReactionTypes('updown'));
  });

  it('resolves subset from data-types names intersected with set', () => {
    expect(resolveReactionTypes('full', ['like', 'fire', 'star'])).toEqual([
      { name: 'like', label: '👍' },
      { name: 'fire', label: '🔥' },
      { name: 'star', label: '⭐' },
    ]);
  });

  it('drops names outside the set catalog', () => {
    expect(resolveReactionTypes('updown', ['like', 'fire', 'beer'])).toEqual([
      { name: 'like', label: '👍' },
    ]);
  });

  it('keeps empty filtered list empty', () => {
    expect(resolveReactionTypes('full', [])).toEqual([]);
  });

  it('falls back to set catalog when names omitted', () => {
    expect(resolveReactionTypes('updown')).toEqual(getReactionTypes('updown'));
  });

  it('skips cross-origin script for api resolve', () => {
    appendReactionsScript('https://cdn.example.com/components/reactions/js/web/reactions.js');
    expect(resolveApiUrlFromScript()).toBeNull();
  });
});
