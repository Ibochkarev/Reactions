import { createNonce, fetchCounts, getCsrf, react, unreact } from './api';
import { REACTION_SETS, type CountsData, type ReactionTypeDef, type WidgetConfig } from './types';

export interface WidgetState {
  counts: Record<string, number>;
  userReactions: string[];
  csrf: string;
  loading: boolean;
  pending: boolean;
  error: string | null;
}

const REACTIONS_SCRIPT_RE = /(?:^|\/)components\/reactions\/js\/web\/reactions\.js(?:\?|$)/i;

/**
 * Resolve API URL: data-api → window.Reactions.config.api → path relative to reactions.js.
 */
export function resolveApiUrlFromScript(doc: Document = document): string | null {
  const scripts = doc.querySelectorAll('script[src]');

  for (const script of scripts) {
    const src = script.getAttribute('src');
    if (!src || !REACTIONS_SCRIPT_RE.test(src)) {
      continue;
    }

    try {
      const apiUrl = new URL('../../api.php', new URL(src, doc.baseURI || window.location.href));
      if (apiUrl.origin !== window.location.origin) {
        continue;
      }

      return apiUrl.href;
    } catch {
      continue;
    }
  }

  return null;
}

export function resolveApiUrl(el: HTMLElement, doc: Document = document): string | null {
  const fromAttr = el.dataset.api?.trim();
  if (fromAttr) {
    return fromAttr;
  }

  const fromGlobal = window.Reactions?.config?.api?.trim();
  if (fromGlobal) {
    return fromGlobal;
  }

  return resolveApiUrlFromScript(doc);
}

export function parseConfig(el: HTMLElement): WidgetConfig | null {
  const api = resolveApiUrl(el);
  const classKey = el.dataset.classKey;
  const objectId = Number(el.dataset.objectId);
  const set = el.dataset.set ?? 'updown';
  const context = el.dataset.context ?? 'web';
  const csrf = el.dataset.csrf ?? '';
  const types = parseTypeNamesFromElement(el);

  if (!api || !classKey || !Number.isFinite(objectId) || objectId <= 0) {
    return null;
  }

  return { api, classKey, objectId, set, context, csrf, types };
}

/**
 * Absent attribute → undefined (use set catalog).
 * Present (even empty) → list of names (may be []).
 */
export function parseTypeNamesFromElement(el: HTMLElement): string[] | undefined {
  if (!el.hasAttribute('data-types')) {
    return undefined;
  }

  const raw = el.getAttribute('data-types') ?? '';
  const names: string[] = [];
  for (const part of raw.split(',')) {
    const name = part.trim().toLowerCase();
    if (name === '' || names.includes(name)) {
      continue;
    }
    names.push(name);
  }

  return names;
}

export function getReactionTypes(set: string): ReactionTypeDef[] {
  return REACTION_SETS[set] ?? REACTION_SETS.updown;
}

/**
 * undefined names → catalog for set.
 * array (including []) → intersection with set catalog, preserving names order.
 */
export function resolveReactionTypes(set: string, names?: string[]): ReactionTypeDef[] {
  if (names === undefined) {
    return getReactionTypes(set);
  }

  const byName = new Map(getReactionTypes(set).map((def) => [def.name, def]));
  const resolved: ReactionTypeDef[] = [];
  for (const name of names) {
    const def = byName.get(name);
    if (def) {
      resolved.push(def);
    }
  }

  return resolved;
}

export class ReactionsWidget {
  readonly el: HTMLElement;
  readonly config: WidgetConfig;
  readonly types: ReactionTypeDef[];
  state: WidgetState;
  private groupEl: HTMLElement | null = null;

  constructor(el: HTMLElement, config: WidgetConfig) {
    this.el = el;
    this.config = config;
    this.types = resolveReactionTypes(config.set, config.types);
    this.state = {
      counts: {},
      userReactions: [],
      csrf: config.csrf,
      loading: true,
      pending: false,
      error: null,
    };

    this.el.classList.add('reactions-widget');
    this.el.setAttribute('role', 'group');
    this.el.setAttribute('aria-label', 'Reactions');
  }

  async init(): Promise<void> {
    try {
      if (!this.state.csrf) {
        this.state.csrf = await getCsrf(this.config.api);
      }

      const data = await fetchCounts(
        this.config.api,
        this.config.classKey,
        this.config.objectId,
        this.config.context,
      );

      this.applyCounts(data);
      this.state.loading = false;
      this.render();
    } catch (err) {
      this.state.loading = false;
      this.state.error = err instanceof Error ? err.message : 'Failed to load reactions';
      this.render();
    }
  }

  applyCounts(data: CountsData): void {
    this.state.counts = { ...data.counts };
    this.state.userReactions = [...data.user_reaction];
  }

  render(): void {
    this.el.innerHTML = '';

    if (this.state.error && this.state.loading === false && Object.keys(this.state.counts).length === 0) {
      const errorEl = document.createElement('p');
      errorEl.className = 'reactions-widget__error';
      errorEl.setAttribute('role', 'alert');
      errorEl.textContent = this.state.error;
      this.el.appendChild(errorEl);
      return;
    }

    this.groupEl = document.createElement('div');
    this.groupEl.className = 'reactions-widget__buttons';

    for (const type of this.types) {
      const button = this.createButton(type);
      this.groupEl.appendChild(button);
    }

    this.el.appendChild(this.groupEl);
  }

  private createButton(type: ReactionTypeDef): HTMLButtonElement {
    const count = this.state.counts[type.name] ?? 0;
    const pressed = this.state.userReactions.includes(type.name);
    const disabled = this.state.loading || this.state.pending;

    const button = document.createElement('button');
    button.type = 'button';
    button.className = 'reactions-widget__button';
    button.dataset.type = type.name;
    button.setAttribute('aria-pressed', pressed ? 'true' : 'false');
    button.setAttribute('aria-label', `${type.name}${count > 0 ? `, ${count}` : ''}`);
    button.disabled = disabled;

    const emoji = document.createElement('span');
    emoji.className = 'reactions-widget__emoji';
    emoji.setAttribute('aria-hidden', 'true');
    emoji.textContent = type.label;

    const countEl = document.createElement('span');
    countEl.className = 'reactions-widget__count';
    countEl.textContent = String(count);

    button.append(emoji, countEl);
    button.addEventListener('click', () => void this.handleClick(type.name));
    button.addEventListener('keydown', (event) => this.handleKeydown(event, type.name));

    return button;
  }

  private handleKeydown(event: KeyboardEvent, typeName: string): void {
    if (event.key === 'Enter' || event.key === ' ') {
      event.preventDefault();
      void this.handleClick(typeName);
    }
  }

  async handleClick(typeName: string): Promise<void> {
    if (this.state.pending || this.state.loading) {
      return;
    }

    const isActive = this.state.userReactions.includes(typeName);
    const snapshot = {
      counts: { ...this.state.counts },
      userReactions: [...this.state.userReactions],
    };

    this.applyOptimistic(typeName, isActive);
    this.state.pending = true;
    this.state.error = null;
    this.render();

    try {
      const payload = {
        csrf: this.state.csrf,
        nonce: createNonce(),
        class_key: this.config.classKey,
        object_id: this.config.objectId,
        type: typeName,
        context: this.config.context,
        set: this.config.set,
      };

      const result = isActive
        ? await unreact(this.config.api, payload)
        : await react(this.config.api, payload);

      this.state.counts = { ...result.counts };
      this.state.userReactions = [...result.user_reaction];
    } catch (err) {
      this.state.counts = snapshot.counts;
      this.state.userReactions = snapshot.userReactions;
      this.state.error = err instanceof Error ? err.message : 'Reaction failed';

      try {
        this.state.csrf = await getCsrf(this.config.api);
      } catch {
        // Keep existing token if refresh fails.
      }
    } finally {
      this.state.pending = false;
      this.render();
    }
  }

  applyOptimistic(typeName: string, isActive: boolean): void {
    const exclusive = this.config.set === 'updown';

    if (isActive) {
      this.state.userReactions = this.state.userReactions.filter((name) => name !== typeName);
      this.state.counts[typeName] = Math.max(0, (this.state.counts[typeName] ?? 0) - 1);
      return;
    }

    if (exclusive) {
      for (const active of this.state.userReactions) {
        this.state.counts[active] = Math.max(0, (this.state.counts[active] ?? 0) - 1);
      }
      this.state.userReactions = [typeName];
    } else {
      this.state.userReactions = [...this.state.userReactions, typeName];
    }

    this.state.counts[typeName] = (this.state.counts[typeName] ?? 0) + 1;
  }
}

export function mountWidget(el: HTMLElement): ReactionsWidget | null {
  const config = parseConfig(el);
  if (!config) {
    return null;
  }

  const widget = new ReactionsWidget(el, config);
  void widget.init();
  return widget;
}

export function initWidgets(root: ParentNode = document): ReactionsWidget[] {
  const elements = root.querySelectorAll<HTMLElement>('.reactions-widget');
  const widgets: ReactionsWidget[] = [];

  for (const el of elements) {
    if (el.dataset.mounted === 'true') {
      continue;
    }

    const widget = mountWidget(el);
    if (widget) {
      el.dataset.mounted = 'true';
      widgets.push(widget);
    }
  }

  return widgets;
}
