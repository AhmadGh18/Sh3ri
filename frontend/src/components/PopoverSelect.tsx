"use client";

import { useEffect, useLayoutEffect, useRef, useState } from "react";
import { createPortal } from "react-dom";

export interface PopoverOption { value: string; label: string }

interface Props {
  value: string;
  onChange: (value: string) => void;
  options: PopoverOption[];
  anyLabel: string;
  /** Optional icon glyph rendered inside the trigger. */
  icon?: string;
  /** Optional label shown before the value inside the trigger. */
  label?: string;
  /** True = trigger looks pill-shaped-active (wine border + soft glow). */
  active?: boolean;
}

/**
 * A properly-designed dropdown that replaces `<select>`. Renders the popover
 * via createPortal so it escapes any containing-block trap (backdrop-filter
 * in the sticky header etc.). Supports arrow-key navigation, Enter/Space to
 * select, Esc to close, click-outside to dismiss, and type-to-jump for lists
 * longer than 8 items.
 */
export default function PopoverSelect({
  value, onChange, options, anyLabel, icon, label, active,
}: Props) {
  const [open, setOpen] = useState(false);
  const [pos, setPos]   = useState<{ top: number; left: number; width: number } | null>(null);
  const [focusIdx, setFocusIdx] = useState(0);
  const [typed, setTyped] = useState("");
  const typedResetRef = useRef<ReturnType<typeof setTimeout> | null>(null);
  const triggerRef = useRef<HTMLButtonElement>(null);
  const popRef     = useRef<HTMLDivElement>(null);
  const listRef    = useRef<HTMLUListElement>(null);

  // Options with the "any" pseudo-option at the top.
  const all: PopoverOption[] = [{ value: "", label: anyLabel }, ...options];
  const selected = all.find(o => o.value === value) ?? all[0];

  // Anchor the popover to the trigger.
  useLayoutEffect(() => {
    if (!open || !triggerRef.current) return;
    const rect = triggerRef.current.getBoundingClientRect();
    // Place under the trigger, edge-aligned to its end (RTL: end = left in
    // rtl OR right depending on writing mode — use the trigger's own left).
    setPos({
      top: rect.bottom + 6 + window.scrollY,
      left: rect.left + window.scrollX,
      width: Math.max(rect.width, 200),
    });
  }, [open]);

  // Reset focus/typed when opened.
  useEffect(() => {
    if (!open) return;
    const initial = Math.max(0, all.findIndex(o => o.value === value));
    setFocusIdx(initial);
    setTyped("");
  }, [open, value, all]);

  // Close on outside click / Esc / scroll.
  useEffect(() => {
    if (!open) return;
    function onDoc(e: MouseEvent) {
      const t = e.target as Node;
      if (popRef.current?.contains(t) || triggerRef.current?.contains(t)) return;
      setOpen(false);
    }
    function onKey(e: KeyboardEvent) {
      if (e.key === "Escape") { setOpen(false); triggerRef.current?.focus(); return; }
      if (e.key === "ArrowDown") { e.preventDefault(); setFocusIdx(i => Math.min(all.length - 1, i + 1)); return; }
      if (e.key === "ArrowUp")   { e.preventDefault(); setFocusIdx(i => Math.max(0, i - 1)); return; }
      if (e.key === "Enter" || e.key === " ") {
        e.preventDefault();
        const opt = all[focusIdx];
        if (opt) { onChange(opt.value); setOpen(false); triggerRef.current?.focus(); }
        return;
      }
      // Type-to-jump: append character, jump to first match starting with typed prefix.
      if (e.key.length === 1) {
        const next = (typed + e.key).slice(-16);
        setTyped(next);
        if (typedResetRef.current) clearTimeout(typedResetRef.current);
        typedResetRef.current = setTimeout(() => setTyped(""), 700);
        const idx = all.findIndex(o => o.label.startsWith(next));
        if (idx >= 0) setFocusIdx(idx);
      }
    }
    // Close on scroll *outside* the popover only. Scrolling the list itself
    // (`overflow-y-auto`) must NOT close the popover — otherwise the user
    // can't reach options below the fold. The capture-phase listener runs
    // for every scroll event on the page; we bail when the target is the
    // popover or any of its descendants.
    function onScroll(e: Event) {
      const t = e.target as Node | null;
      if (t && popRef.current?.contains(t)) return;
      setOpen(false);
    }
    document.addEventListener("mousedown", onDoc);
    document.addEventListener("keydown", onKey);
    window.addEventListener("scroll", onScroll, true);
    return () => {
      document.removeEventListener("mousedown", onDoc);
      document.removeEventListener("keydown", onKey);
      window.removeEventListener("scroll", onScroll, true);
    };
  }, [open, all, focusIdx, onChange, typed]);

  // Scroll the focused option into view.
  useEffect(() => {
    if (!open || !listRef.current) return;
    const li = listRef.current.querySelector<HTMLLIElement>(`li[data-idx="${focusIdx}"]`);
    li?.scrollIntoView({ block: "nearest" });
  }, [focusIdx, open]);

  const isActive = active || !!value;

  return (
    <>
      <button
        ref={triggerRef}
        type="button"
        onClick={() => setOpen(v => !v)}
        aria-haspopup="listbox"
        aria-expanded={open}
        className={`
          group inline-flex items-center gap-2 rounded-full pe-1 ps-3 py-1 min-w-[180px]
          bg-[color:var(--parchment-elev)] border transition text-start
          ${isActive
            ? "border-[color:var(--wine)] shadow-[0_0_0_3px_var(--wine-soft)]"
            : "border-[color:var(--border)] hover:border-[color:var(--border-strong)]"}
        `}
      >
        {icon && <span className="text-[color:var(--gold)] text-sm leading-none">{icon}</span>}
        {label && (
          <span className="text-[11px] text-[color:var(--ink-muted)] font-medium leading-none">
            {label}
          </span>
        )}
        <span className="w-px self-stretch bg-[color:var(--border)]" />
        <span className="flex-1 text-[13px] text-[color:var(--ink)] font-medium truncate">
          {selected.label}
        </span>
        <span className="text-[10px] text-[color:var(--ink-dim)] px-1">▾</span>
      </button>

      {open && pos && typeof document !== "undefined" && createPortal(
        <div
          ref={popRef}
          role="listbox"
          className="fixed z-[110] rounded-xl border border-[color:var(--border-strong)] bg-[color:var(--parchment-elev)] shadow-[0_10px_30px_-8px_rgba(0,0,0,.35)] overflow-hidden animate-[popover-in_.12s_ease]"
          style={{ top: pos.top, left: pos.left, width: pos.width }}
          dir="rtl"
        >
          <ul
            ref={listRef}
            className="max-h-72 overflow-y-auto py-1"
          >
            {all.map((o, i) => {
              const isSel = o.value === value;
              const isFoc = i === focusIdx;
              return (
                <li
                  key={o.value || "__any"}
                  data-idx={i}
                  onMouseEnter={() => setFocusIdx(i)}
                  onClick={() => { onChange(o.value); setOpen(false); triggerRef.current?.focus(); }}
                  className={`
                    px-3 py-1.5 text-[13px] cursor-pointer flex items-center gap-2 rounded mx-1 my-0.5
                    ${isSel
                      ? "bg-gold-soft text-[color:var(--ink)] font-semibold"
                      : isFoc
                        ? "bg-parchment-soft text-[color:var(--ink)]"
                        : "text-[color:var(--ink-muted)]"}
                  `}
                >
                  <span className={`text-xs ${isSel ? "text-[color:var(--gold)]" : "text-transparent"}`}>✓</span>
                  <span className="flex-1 truncate">{o.label}</span>
                </li>
              );
            })}
          </ul>
        </div>,
        document.body,
      )}

      <style>{`
        @keyframes popover-in {
          from { opacity: 0; transform: translateY(-4px); }
          to   { opacity: 1; transform: translateY(0); }
        }
      `}</style>
    </>
  );
}
