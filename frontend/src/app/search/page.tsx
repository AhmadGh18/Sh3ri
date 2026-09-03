import { Suspense } from "react";
import SearchClient from "./SearchClient";

export const dynamic = "force-dynamic";

export default function SearchPage() {
  return (
    <Suspense fallback={<div className="max-w-4xl mx-auto px-4 py-10 text-center text-ink-dim">تحميل…</div>}>
      <SearchClient />
    </Suspense>
  );
}
