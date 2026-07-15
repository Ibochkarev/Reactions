import { createNonce, fetchCounts, getCsrf, react, unreact } from './api';
import { syncCountsForObject } from './count-live';
import {
  REACTION_SETS,
  type CountsData,
  type ReactionTypeDef,
  type WidgetConfig,
  type WidgetLayout,
} from './types';

export interface WidgetState {
  counts: Record<string, number>;
  userReactions: string[];
  csrf: string;
  loading: boolean;
  pending: boolean;
  error: string | null;
}

const REACTIONS_SCRIPT_RE = /(?:^|\/)components\/reactions\/js\/web\/reactions\.js(?:\?|$)/i;
const UPDATED_EVENT = 'reactions:updated';
const PICKER_THRESHOLD = 3;

interface WidgetUpdatedDetail {
  classKey: string;
  objectId: number;
  context: string;
  counts: Record<string, number>;
  userReactions: string[];
  source: ReactionsWidget;
}

function parseDataFlag(value: string | undefined, fallback: boolean): boolean {
  if (value === undefined || value === '') {
    return fallback;
  }

  return value === '1' || value.toLowerCase() === 'true' || value === 'yes';
}

function parseLayout(value: string | undefined): WidgetLayout {
  const raw = (value ?? 'auto').toLowerCase();
  if (raw === 'picker' || raw === 'bar' || raw === 'auto') {
    return raw;
  }

  return 'auto';
}

/** Mirrors ReactionService::isSingleReactionMode */
export function isExclusiveMode(config: Pick<WidgetConfig, 'exclusive' | 'allowMultiple'>): boolean {
  return config.exclusive || !config.allowMultiple;
}

export function resolveLayout(
  layout: WidgetLayout,
  typeCount: number,
  threshold: number = PICKER_THRESHOLD,
): 'bar' | 'picker' {
  if (layout === 'picker') {
    return 'picker';
  }
  if (layout === 'bar') {
    return 'bar';
  }

  return typeCount > threshold ? 'picker' : 'bar';
}

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
  const exclusive = parseDataFlag(el.dataset.exclusive, set === 'updown');
  const allowMultiple = parseDataFlag(el.dataset.allowMultiple, false);
  const layout = parseLayout(el.dataset.layout);

  if (!api || !classKey || !Number.isFinite(objectId) || objectId <= 0) {
    return null;
  }

  return { api, classKey, objectId, set, context, csrf, types, exclusive, allowMultiple, layout };
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
  readonly mode: 'bar' | 'picker';
  state: WidgetState;
  private popoverOpen = false;
  private documentBound: (() => void) | null = null;
  private readonly onExternalUpdate = (event: Event): void => {
    const detail = (event as CustomEvent<WidgetUpdatedDetail>).detail;
    if (!detail || detail.source === this || this.state.pending) {
      return;
    }
    if (
      detail.classKey !== this.config.classKey
      || detail.objectId !== this.config.objectId
      || detail.context !== this.config.context
    ) {
      return;
    }

    this.state.counts = { ...detail.counts };
    this.state.userReactions = [...detail.userReactions];
    this.state.error = null;
    this.render();
  };

  constructor(el: HTMLElement, config: WidgetConfig) {
    this.el = el;
    this.config = config;
    this.types = resolveReactionTypes(config.set, config.types);
    this.mode = resolveLayout(config.layout, this.types.length);
    this.state = {
      counts: {},
      userReactions: [],
      csrf: config.csrf,
      loading: true,
      pending: false,
      error: null,
    };

    this.el.classList.add('reactions-widget');
    this.el.dataset.layout = this.mode;
    this.el.setAttribute('role', 'group');
    this.el.setAttribute('aria-label', 'Reactions');
    window.addEventListener(UPDATED_EVENT, this.onExternalUpdate);
  }

  destroy(): void {
    this.unbindDocument();
    window.removeEventListener(UPDATED_EVENT, this.onExternalUpdate);
  }

  async init(): Promise<void> {
    try {
      // Always sync with the live session — SSR/cache tokens go stale.
      this.state.csrf = await getCsrf(this.config.api);

      const data = await fetchCounts(
        this.config.api,
        this.config.classKey,
        this.config.objectId,
        this.config.context,
      );

      this.applyCounts(data);
      this.state.loading = false;
      this.render();
      this.syncAdjacentCounts();
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

  private syncAdjacentCounts(): void {
    syncCountsForObject(
      this.config.classKey,
      this.config.objectId,
      this.config.context,
      this.state.counts,
    );
  }

  private broadcastUpdate(): void {
    window.dispatchEvent(
      new CustomEvent<WidgetUpdatedDetail>(UPDATED_EVENT, {
        detail: {
          classKey: this.config.classKey,
          objectId: this.config.objectId,
          context: this.config.context,
          counts: { ...this.state.counts },
          userReactions: [...this.state.userReactions],
          source: this,
        },
      }),
    );
  }

  private unbindDocument(): void {
    if (this.documentBound) {
      this.documentBound();
      this.documentBound = null;
    }
  }

  private setPopoverOpen(open: boolean): void {
    this.popoverOpen = open;
    if (!open) {
      this.unbindDocument();
    }
  }

  render(): void {
    this.unbindDocument();
    this.el.innerHTML = '';
    const busy = this.state.loading || this.state.pending;
    this.el.dataset.loading = busy ? 'true' : 'false';
    this.el.dataset.layout = this.mode;
    this.el.setAttribute('aria-busy', busy ? 'true' : 'false');

    if (this.state.error && this.state.loading === false && Object.keys(this.state.counts).length === 0) {
      const errorEl = document.createElement('p');
      errorEl.className = 'reactions-widget__error';
      errorEl.setAttribute('role', 'alert');
      errorEl.textContent = this.state.error;
      this.el.appendChild(errorEl);
      return;
    }

    if (this.mode === 'picker') {
      this.renderPicker();
    } else {
      this.renderBar(this.types);
    }

    if (this.state.error) {
      const errorEl = document.createElement('p');
      errorEl.className = 'reactions-widget__error';
      errorEl.setAttribute('role', 'alert');
      errorEl.textContent = this.state.error;
      this.el.appendChild(errorEl);
    }
  }

  private renderBar(types: ReactionTypeDef[]): void {
    const group = document.createElement('div');
    group.className = 'reactions-widget__buttons';
    for (const type of types) {
      group.appendChild(this.createButton(type));
    }
    this.el.appendChild(group);
  }

  private summaryTypes(): ReactionTypeDef[] {
    return this.types.filter((type) => {
      const count = this.state.counts[type.name] ?? 0;
      return count > 0 || this.state.userReactions.includes(type.name);
    });
  }

  private renderPicker(): void {
    const shell = document.createElement('div');
    shell.className = 'reactions-widget__shell';

    const summary = document.createElement('div');
    summary.className = 'reactions-widget__summary';
    summary.setAttribute('role', 'group');
    summary.setAttribute('aria-label', 'Current reactions');

    for (const type of this.summaryTypes()) {
      summary.appendChild(this.createButton(type));
    }

    const trigger = document.createElement('button');
    trigger.type = 'button';
    trigger.className = 'reactions-widget__trigger' + (this.popoverOpen ? ' is-open' : '');
    trigger.setAttribute('aria-haspopup', 'dialog');
    trigger.setAttribute('aria-expanded', this.popoverOpen ? 'true' : 'false');
    trigger.setAttribute('aria-label', 'Add reaction');
    trigger.disabled = this.state.loading || this.state.pending;

    const triggerIcon = document.createElement('span');
    triggerIcon.className = 'reactions-widget__trigger-icon';
    triggerIcon.setAttribute('aria-hidden', 'true');
    triggerIcon.textContent = this.popoverOpen ? '×' : '+';
    trigger.appendChild(triggerIcon);

    trigger.addEventListener('click', (event) => {
      event.stopPropagation();
      this.setPopoverOpen(!this.popoverOpen);
      this.render();
      if (this.popoverOpen) {
        this.bindPopoverDismiss();
        const first = this.el.querySelector<HTMLButtonElement>('.reactions-widget__picker-button');
        first?.focus();
      } else {
        trigger.focus();
      }
    });

    shell.append(summary, trigger);

    if (this.popoverOpen) {
      shell.appendChild(this.createPopover());
    }

    this.el.appendChild(shell);
  }

  private createPopover(): HTMLElement {
    const popover = document.createElement('div');
    popover.className = 'reactions-widget__popover';
    popover.setAttribute('role', 'dialog');
    popover.setAttribute('aria-label', 'Choose a reaction');

    const grid = document.createElement('div');
    grid.className = 'reactions-widget__picker';
    grid.setAttribute('role', 'listbox');

    for (const type of this.types) {
      grid.appendChild(this.createPickerButton(type));
    }

    popover.appendChild(grid);
    return popover;
  }

  private bindPopoverDismiss(): void {
    this.unbindDocument();

    const onPointer = (event: Event): void => {
      const target = event.target;
      if (!(target instanceof Node) || this.el.contains(target)) {
        return;
      }
      this.setPopoverOpen(false);
      this.render();
    };

    const onKey = (event: KeyboardEvent): void => {
      if (event.key === 'Escape') {
        event.preventDefault();
        this.setPopoverOpen(false);
        this.render();
        this.el.querySelector<HTMLButtonElement>('.reactions-widget__trigger')?.focus();
      }
    };

    // Next tick so the opening click does not close immediately.
    window.setTimeout(() => {
      document.addEventListener('pointerdown', onPointer, true);
      document.addEventListener('keydown', onKey, true);
    }, 0);

    this.documentBound = () => {
      document.removeEventListener('pointerdown', onPointer, true);
      document.removeEventListener('keydown', onKey, true);
    };
  }

  private createPickerButton(type: ReactionTypeDef): HTMLButtonElement {
    const count = this.state.counts[type.name] ?? 0;
    const pressed = this.state.userReactions.includes(type.name);
    const disabled = this.state.loading || this.state.pending;

    const button = document.createElement('button');
    button.type = 'button';
    button.className = 'reactions-widget__picker-button' + (pressed ? ' is-active' : '');
    button.dataset.type = type.name;
    button.setAttribute('role', 'option');
    button.setAttribute('aria-selected', pressed ? 'true' : 'false');
    button.setAttribute('aria-label', `${type.name}${count > 0 ? `, ${count}` : ''}`);
    button.title = type.name;
    button.disabled = disabled;

    const emoji = document.createElement('span');
    emoji.className = 'reactions-widget__emoji';
    emoji.setAttribute('aria-hidden', 'true');
    emoji.textContent = type.label;
    button.appendChild(emoji);

    button.addEventListener('click', (event) => {
      event.stopPropagation();
      this.setPopoverOpen(false);
      void this.handleClick(type.name);
    });

    return button;
  }

  private createButton(type: ReactionTypeDef): HTMLButtonElement {
    const count = this.state.counts[type.name] ?? 0;
    const pressed = this.state.userReactions.includes(type.name);
    const disabled = this.state.loading || this.state.pending;

    const button = document.createElement('button');
    button.type = 'button';
    button.className = 'reactions-widget__button' + (pressed ? ' is-active' : '');
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
    countEl.hidden = count === 0;

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
    this.syncAdjacentCounts();
    this.state.pending = true;
    this.state.error = null;
    this.render();

    let synced = false;
    try {
      const result = await this.sendReaction(typeName, isActive);
      this.state.counts = { ...result.counts };
      this.state.userReactions = [...result.user_reaction];
      synced = true;
    } catch (err) {
      const refreshed = await this.refreshCsrfAndRetry(typeName, isActive);
      if (refreshed) {
        this.state.counts = { ...refreshed.counts };
        this.state.userReactions = [...refreshed.user_reaction];
        synced = true;
      } else {
        this.state.counts = snapshot.counts;
        this.state.userReactions = snapshot.userReactions;
        this.state.error = err instanceof Error ? err.message : 'Reaction failed';
        this.syncAdjacentCounts();
      }
    } finally {
      this.state.pending = false;
      this.render();
      if (synced) {
        this.syncAdjacentCounts();
        this.broadcastUpdate();
      }
    }
  }

  private async sendReaction(typeName: string, isActive: boolean) {
    const payload = {
      csrf: this.state.csrf,
      nonce: createNonce(),
      class_key: this.config.classKey,
      object_id: this.config.objectId,
      type: typeName,
      context: this.config.context,
      set: this.config.set,
    };

    return isActive
      ? unreact(this.config.api, payload)
      : react(this.config.api, payload);
  }

  private async refreshCsrfAndRetry(typeName: string, isActive: boolean) {
    try {
      this.state.csrf = await getCsrf(this.config.api);
      return await this.sendReaction(typeName, isActive);
    } catch {
      return null;
    }
  }

  applyOptimistic(typeName: string, isActive: boolean): void {
    const exclusive = isExclusiveMode(this.config);

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
