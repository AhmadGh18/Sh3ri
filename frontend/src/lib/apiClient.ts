"use client";

/**
 * Browser-side API wrapper. Attaches the Sanctum Bearer token when signed in,
 * unpacks JSON error envelopes for friendlier messages, and clears the
 * session on 401 (expired/revoked token) so the UI reflects reality.
 *
 * Deliberately separate from lib/api.ts — that one is used by server
 * components (Next cache, no auth). This one is used by client components
 * for POST/PATCH/DELETE and any endpoint that varies per user.
 */

import { session } from "./session";

const API_BASE =
  process.env.NEXT_PUBLIC_API_URL ?? "http://127.0.0.1:8000";

export class ApiError extends Error {
  constructor(public status: number, message: string, public errors?: Record<string, string[]>) {
    super(message);
  }
}

export async function apiClient<T = unknown>(
  path: string,
  init: RequestInit = {},
): Promise<T> {
  const headers: Record<string, string> = {
    Accept: "application/json",
    ...(init.headers as Record<string, string> | undefined),
  };
  const tok = session.get().token;
  if (tok) headers.Authorization = `Bearer ${tok}`;

  const res = await fetch(`${API_BASE}/api/v1${path}`, { ...init, headers });

  if (res.status === 401 && session.isSignedIn()) {
    // Expired / revoked — clear so the header switches back to "sign in".
    session.clear();
  }

  if (res.status === 204) return undefined as T;
  const isJson = (res.headers.get("Content-Type") ?? "").includes("json");
  const body = isJson ? await res.json() : null;

  if (!res.ok) {
    // `body` came from `res.json()` typed as any; narrow the validation-error
    // map so TS lets us pluck the first message out of it.
    const errorsMap = body?.error?.errors as Record<string, string[]> | undefined;
    const firstFieldErrors = errorsMap ? Object.values(errorsMap)[0] : undefined;
    const message =
      body?.error?.message ??
      firstFieldErrors?.[0] ??
      `HTTP ${res.status}`;
    throw new ApiError(res.status, String(message), errorsMap);
  }
  return body as T;
}
