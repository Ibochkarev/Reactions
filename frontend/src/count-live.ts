import { renderCountText } from './count-format';

const UPDATED_EVENT = 'reactions:updated';

interface CountUpdatedDetail {
  classKey: string;
  objectId: number;
  context: string;
  counts: Record<string, number>;
}

let bound = false;

function parseCountElement(el: HTMLElement): CountUpdatedDetail | null {
  const classKey = el.dataset.classKey?.trim();
  const objectId = Number(el.dataset.objectId);
  const context = el.dataset.context?.trim() ?? 'web';

  if (!classKey || !Number.isFinite(objectId) || objectId <= 0) {
    return null;
  }

  return { classKey, objectId, context, counts: {} };
}

function updateCountElement(el: HTMLElement, counts: Record<string, number>): void {
  const format = el.dataset.format ?? '{TOTAL}';
  const typeFilter = el.dataset.type?.trim() || undefined;
  el.textContent = renderCountText(format, counts, typeFilter);
}

export function syncCountsForObject(
  classKey: string,
  objectId: number,
  context: string,
  counts: Record<string, number>,
  root: ParentNode = document,
): void {
  const nodes = root.querySelectorAll<HTMLElement>('.reactions-count');
  for (const el of nodes) {
    const meta = parseCountElement(el);
    if (!meta) {
      continue;
    }
    if (
      meta.classKey !== classKey
      || meta.objectId !== objectId
      || meta.context !== context
    ) {
      continue;
    }

    updateCountElement(el, counts);
  }
}

export function initCountLive(_root: ParentNode = document): void {
  if (!bound) {
    window.addEventListener(UPDATED_EVENT, (event: Event) => {
      const detail = (event as CustomEvent<CountUpdatedDetail>).detail;
      if (!detail?.classKey || !detail.objectId) {
        return;
      }

      syncCountsForObject(
        detail.classKey,
        detail.objectId,
        detail.context,
        detail.counts,
      );
    });
    bound = true;
  }
}
