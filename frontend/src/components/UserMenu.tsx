"use client";

import { useEffect, useRef, useState } from "react";
import Link from "next/link";
import { useRouter } from "next/navigation";
import { session, useSession } from "@/lib/session";
import { apiClient } from "@/lib/apiClient";
import AuthModal from "./AuthModal";

export default function UserMenu() {
  const { token, user } = useSession();
  const [showAuth, setShowAuth] = useState(false);
  const [open, setOpen] = useState(false);
  const menuRef = useRef<HTMLDivElement>(null);
  const router = useRouter();

  // Close dropdown on outside click.
  useEffect(() => {
    function onClick(e: MouseEvent) {
      if (menuRef.current && !menuRef.current.contains(e.target as Node)) setOpen(false);
    }
    if (open) document.addEventListener("mousedown", onClick);
    return () => document.removeEventListener("mousedown", onClick);
  }, [open]);

  async function signOut() {
    setOpen(false);
    try { await apiClient("/auth/logout", { method: "POST" }); } catch { /* revoke server-side is best-effort */ }
    session.clear();
    router.push("/");
    router.refresh();
  }

  if (!token || !user) {
    return (
      <>
        <button
          onClick={() => setShowAuth(true)}
          className="rounded-full bg-[color:var(--wine)] hover:brightness-90 text-white px-4 py-1.5 text-[13px] font-medium"
        >
          دخول
        </button>
        {showAuth && <AuthModal onClose={() => setShowAuth(false)} />}
      </>
    );
  }

  const initials = user.name?.trim().split(/\s+/).slice(0, 2).map(p => p[0] ?? "").join("") || "؟";

  return (
    <div className="relative" ref={menuRef}>
      <button
        onClick={() => setOpen(v => !v)}
        className="w-9 h-9 rounded-full bg-gold-soft text-[color:var(--gold)] font-bold text-sm border border-border hover:border-wine grid place-items-center"
        title={user.name}
        style={{ fontFamily: "var(--font-reem)" }}
      >
        {user.avatar_url
          // eslint-disable-next-line @next/next/no-img-element
          ? <img src={user.avatar_url} alt="" className="w-full h-full rounded-full object-cover" />
          : initials}
      </button>
      {open && (
        <div className="absolute end-0 top-full mt-2 min-w-[220px] rounded-lg border border-border-strong bg-parchment-elev shadow-lg p-1 z-30">
          <div className="px-3 py-2 border-b border-border mb-1">
            <div className="font-semibold text-sm text-ink">{user.name}</div>
            <div className="text-[11px] text-ink-muted break-all">{user.email}</div>
          </div>
          <MenuLink href="/me/favorites" onClick={() => setOpen(false)}>❤ المفضلة</MenuLink>
          <MenuLink href="/me/user-poems" onClick={() => setOpen(false)}>✎ قصائدي</MenuLink>
          <MenuLink href="/me/submissions" onClick={() => setOpen(false)}>📝 مقترحاتي</MenuLink>
          <div className="border-t border-border my-1" />
          <button
            onClick={signOut}
            className="w-full text-start px-3 py-2 rounded text-sm text-[color:var(--wine)] hover:bg-parchment-soft"
          >↩ تسجيل الخروج</button>
        </div>
      )}
    </div>
  );
}

function MenuLink({ href, children, onClick }: { href: string; children: React.ReactNode; onClick: () => void }) {
  return (
    <Link href={href} onClick={onClick} className="block px-3 py-2 rounded text-sm text-ink hover:bg-parchment-soft hover:text-wine no-underline">
      {children}
    </Link>
  );
}
