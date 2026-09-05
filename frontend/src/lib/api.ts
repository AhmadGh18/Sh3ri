/**
 * Tiny wrapper around the Laravel API. Base URL comes from env so the same
 * bundle can point at dev / staging / prod without changes.
 */
const API_BASE =
  process.env.NEXT_PUBLIC_API_URL ?? "http://127.0.0.1:8000";

export const API_URL = API_BASE;

export type Cursor = string | null;

export interface Taxonomy {
  slug: string;
  name_ar: string;
  name_en?: string | null;
}

export interface Era extends Taxonomy {
  start_year: number | null;
  end_year: number | null;
}

export interface Country extends Taxonomy {
  iso_code: string | null;
}

export interface Poet {
  uuid: string;
  slug: string;
  name_ar: string;
  name_en?: string | null;
  bio_ar?: string | null;
  era?: Taxonomy | null;
  country?: Country | null;
  poem_count?: number;
}

export interface Verse {
  uuid: string;
  position: number;
  hemistich_a: string;
  hemistich_b: string | null;
}

export interface Poem {
  uuid: string;
  slug: string;
  title_ar: string;
  verse_count: number;
  poet?: Poet | null;
  era?: Taxonomy | null;
  category?: Taxonomy | null;
  meter?: Taxonomy | null;
}

export type PoemDetail = Poem & { verses: Verse[] };

export interface Paginated<T> {
  data: T[];
  meta: { per_page: number; next_cursor: Cursor; prev_cursor: Cursor };
}

export interface SearchResult {
  data: {
    query: string;
    poems: Poem[];
    poets: Poet[];
    verses: (Verse & { poem?: Poem | null })[];
  };
  meta: { counts: { poems: number; poets: number; verses: number } };
}

interface FetchOpts extends RequestInit { revalidate?: number }

async function apiFetch<T>(path: string, init?: FetchOpts): Promise<T> {
  const { revalidate = 60, ...rest } = init ?? {};
  const res = await fetch(`${API_BASE}/api/v1${path}`, {
    ...rest,
    headers: { Accept: "application/json", ...(rest.headers ?? {}) },
    next: { revalidate },
  });
  if (!res.ok) throw new Error(`API ${path} → HTTP ${res.status}`);
  return res.json() as Promise<T>;
}

/**
 * Build a `?filter[k]=v&...&sort=x&cursor=y` query string, skipping empties.
 * Loose signature (`Record<string, unknown>`) so callers can pass a nested
 * `filter` object without fighting TS's index-signature rules — we stringify
 * defensively inside.
 */
type QsParams = { filter?: Record<string, string | undefined> } & Record<string, unknown>;

export function qs(params: QsParams): string {
  const sp = new URLSearchParams();
  for (const [k, v] of Object.entries(params)) {
    if (k === "filter") continue;
    if (v === undefined || v === null || v === "") continue;
    sp.set(k, String(v));
  }
  if (params.filter) {
    for (const [k, v] of Object.entries(params.filter)) {
      if (v) sp.set(`filter[${k}]`, v);
    }
  }
  const s = sp.toString();
  return s ? `?${s}` : "";
}

export interface CommunityPoem {
  uuid: string;
  title_ar: string;
  raw_text: string;
  visibility: "public";
  status: "published";
  author?: { uuid: string; name: string };
  category?: Taxonomy | null;
  upvote_count?: number;
  comment_count?: number;
  upvoted_by_me?: boolean;
  published_at: string | null;
  created_at: string;
}

export interface Plan {
  code: string;
  name_ar: string;
  name_en: string;
  tagline_ar?: string | null;
  price_cents: number;
  currency: string;
  daily_audio_plays: number | null;   // null = unlimited
  allow_download: boolean;
  is_free: boolean;
  is_unlimited: boolean;
}

export interface AudioUsage {
  plan: {
    code: string;
    name_ar: string;
    name_en: string;
    is_unlimited: boolean;
    daily_limit: number | null;
  };
  used_today: number;
  remaining: number | null;   // null = unlimited
}

export interface CommunityComment {
  uuid: string;
  body: string;
  author?: { uuid: string; name: string };
  created_at: string;
  can_delete?: boolean;
}

export const api = {
  listPoems: (opts: { per_page?: number; cursor?: string; filter?: { era?: string; category?: string; meter?: string; poet_slug?: string } } = {}) =>
    apiFetch<Paginated<Poem>>(`/poems${qs({ per_page: opts.per_page ?? 24, cursor: opts.cursor, filter: opts.filter })}`),

  getPoem: (slug: string) =>
    apiFetch<{ data: PoemDetail }>(`/poems/${encodeURIComponent(slug)}`),

  listPoets: (opts: { per_page?: number; cursor?: string; filter?: { era?: string; country?: string } } = {}) =>
    apiFetch<Paginated<Poet>>(`/poets${qs({ per_page: opts.per_page ?? 30, cursor: opts.cursor, filter: opts.filter })}`),

  getPoet: (slug: string) =>
    apiFetch<{ data: Poet }>(`/poets/${encodeURIComponent(slug)}`),

  getPoetPoems: (slug: string, opts: { per_page?: number } = {}) =>
    apiFetch<Paginated<Poem>>(`/poets/${encodeURIComponent(slug)}/poems${qs({ per_page: opts.per_page ?? 50 })}`),

  listEras: () => apiFetch<{ data: Era[] }>(`/eras`, { revalidate: 3600 }),
  listCategories: () => apiFetch<{ data: Taxonomy[] }>(`/categories`, { revalidate: 3600 }),
  listCountries: () => apiFetch<{ data: Country[] }>(`/countries`, { revalidate: 3600 }),
  listMeters: () => apiFetch<{ data: Taxonomy[] }>(`/meters`, { revalidate: 3600 }),

  search: (q: string, type: "all" | "poem" | "poet" | "verse" = "all") =>
    apiFetch<SearchResult>(`/search${qs({ q, type })}`, { revalidate: 30 }),

  listCommunityPoems: (opts: {
    per_page?: number;
    cursor?: string;
    sort?: "new" | "top";
    filter?: { category?: string };
  } = {}) =>
    apiFetch<Paginated<CommunityPoem>>(
      `/community/user-poems${qs({
        per_page: opts.per_page ?? 24,
        cursor: opts.cursor,
        sort: opts.sort,
        filter: opts.filter,
      })}`,
      { revalidate: 15 },
    ),

  getCommunityPoem: (uuid: string) =>
    apiFetch<{ data: CommunityPoem }>(
      `/community/user-poems/${encodeURIComponent(uuid)}`,
      { revalidate: 15 },
    ),

  listComments: (uuid: string) =>
    apiFetch<Paginated<CommunityComment>>(
      `/community/user-poems/${encodeURIComponent(uuid)}/comments`,
      { revalidate: 10 },
    ),

  listPlans: () => apiFetch<{ data: Plan[] }>(`/plans`, { revalidate: 300 }),
};
