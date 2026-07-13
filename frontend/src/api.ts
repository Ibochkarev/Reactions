import type { ApiResponse, CountsData, ReactionData } from './types';

const fetchOptions: RequestInit = {
  credentials: 'include',
  headers: {
    Accept: 'application/json',
  },
};

function buildUrl(api: string, action: string, params?: Record<string, string | number>): string {
  const url = new URL(api, window.location.origin);
  url.searchParams.set('action', action);

  if (params) {
    for (const [key, value] of Object.entries(params)) {
      url.searchParams.set(key, String(value));
    }
  }

  return url.toString();
}

async function parseJson<T>(response: Response): Promise<ApiResponse<T>> {
  const body = (await response.json()) as ApiResponse<T>;
  return body;
}

export async function getCsrf(api: string): Promise<string> {
  const response = await fetch(buildUrl(api, 'csrf'), fetchOptions);
  const body = await parseJson<{ csrf: string }>(response);

  if (!body.success || !('csrf' in body) || !body.csrf) {
    throw new Error('Failed to fetch CSRF token');
  }

  return body.csrf;
}

export async function fetchCounts(
  api: string,
  classKey: string,
  objectId: number,
  context: string,
): Promise<CountsData> {
  const response = await fetch(
    buildUrl(api, 'counts', {
      class_key: classKey,
      object_id: objectId,
      context,
    }),
    fetchOptions,
  );

  const body = await parseJson<CountsData>(response);

  if (!body.success || !body.data) {
    const message = 'error' in body ? body.error : 'Failed to fetch counts';
    throw new Error(message);
  }

  return body.data;
}

export interface ReactPayload {
  csrf: string;
  nonce: string;
  class_key: string;
  object_id: number;
  type: string;
  context: string;
  set: string;
}

export async function react(api: string, payload: ReactPayload): Promise<ReactionData> {
  const response = await fetch(buildUrl(api, 'react'), {
    ...fetchOptions,
    method: 'POST',
    headers: {
      ...fetchOptions.headers,
      'Content-Type': 'application/json',
    },
    body: JSON.stringify(payload),
  });

  const body = await parseJson<ReactionData>(response);

  if (!body.success || !body.data) {
    const message = 'error' in body ? body.error : 'Failed to react';
    throw new Error(message);
  }

  return body.data;
}

export async function unreact(api: string, payload: ReactPayload): Promise<ReactionData> {
  const response = await fetch(buildUrl(api, 'react'), {
    ...fetchOptions,
    method: 'DELETE',
    headers: {
      ...fetchOptions.headers,
      'Content-Type': 'application/json',
    },
    body: JSON.stringify(payload),
  });

  const body = await parseJson<ReactionData>(response);

  if (!body.success || !body.data) {
    const message = 'error' in body ? body.error : 'Failed to unreact';
    throw new Error(message);
  }

  return body.data;
}

export function createNonce(): string {
  const bytes = new Uint8Array(16);
  crypto.getRandomValues(bytes);
  return Array.from(bytes, (byte) => byte.toString(16).padStart(2, '0')).join('');
}
