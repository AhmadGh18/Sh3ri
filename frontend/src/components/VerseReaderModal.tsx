"use client";

import { useEffect, useRef, useState, useCallback } from "react";
import { createPortal } from "react-dom";
import { API_URL, type PoemDetail } from "@/lib/api";
import FavoriteButton from "./FavoriteButton";
import UpgradeModal from "./UpgradeModal";
import AudioQuotaBadge from "./AudioQuotaBadge";
import { audioUsage } from "@/lib/audioUsage";
import {
  PlayIcon, StopIcon, CopyIcon, LinkIcon, ShareIcon,
  XIcon, ArrowRightIcon, ArrowLeftIcon,
} from "./Icon";

/**
 * Focused reader for a single verse. Streams the MP3 from
 * /api/v1/verses/{uuid}/audio (server-cached via ElevenLabs).
 */
export default function VerseReaderModal({
  poem,
  verseIndex,
  onClose,
  onNavigate,
}: {
  poem: PoemDetail;
  verseIndex: number;
  onClose: () => void;
  onNavigate: (delta: number) => void;
}) {
  const verse = poem.verses[verseIndex];
  const hasPrev = verseIndex > 0;
  const hasNext = verseIndex < poem.verses.length - 1;

  const audioRef = useRef<HTMLAudioElement | null>(null);
  const [playing, setPlaying] = useState(false);
  const [status, setStatus] = useState<{ text: string; tone: "muted" | "loading" | "error" }>({ text: "", tone: "muted" });
  const [toastShow, setToastShow] = useState(false);
  const [mounted, setMounted] = useState(false);
  const [upgradeOpen, setUpgradeOpen] = useState(false);
  const [upgradeReason, setUpgradeReason] = useState<string | undefined>();
  useEffect(() => { setMounted(true); }, []);

  useEffect(() => {
    const prev = document.body.style.overflow;
    document.body.style.overflow = "hidden";
    return () => { document.body.style.overflow = prev; };
  }, []);

  const toggle = useCallback(() => {
    const a = audioRef.current;
    if (!a) return;
    if (a.paused || a.ended) {
      setStatus({ text: "جاري التحضير…", tone: "loading" });
      a.play().catch((e) => console.warn("audio play failed", e));
    } else {
      a.pause();
    }
  }, []);

  // Keyboard: Esc close, Space toggle, ← → navigate.
  useEffect(() => {
    function onKey(e: KeyboardEvent) {
      if (e.key === "Escape") { onClose(); return; }
      if (e.key === " " || e.code === "Space") { e.preventDefault(); toggle(); return; }
      if (e.key === "ArrowRight") { onNavigate(-1); return; }
      if (e.key === "ArrowLeft")  { onNavigate(+1); return; }
    }
    document.addEventListener("keydown", onKey);
    return () => document.removeEventListener("keydown", onKey);
  }, [onClose, onNavigate, toggle]);

  // Load audio source once mounted + on verse change.
  useEffect(() => {
    if (!mounted) return;
    const a = audioRef.current;
    if (!a) return;
    a.pause();
    a.src = `${API_URL}/api/v1/verses/${verse.uuid}/audio`;
    a.load();
    setPlaying(false);
    setStatus({ text: "", tone: "muted" });
  }, [verse.uuid, mounted]);

  async function copyText(t: string) {
    try {
      await navigator.clipboard.writeText(t);
      setToastShow(true);
      setTimeout(() => setToastShow(false), 1200);
    } catch { alert(t); }
  }

  async function fetchErrorMessage(): Promise<string> {
    try {
      const r = await fetch(`${API_URL}/api/v1/verses/${verse.uuid}/audio`, {
        headers: { Accept: "application/json" },
      });
      if (r.status === 402) {
        // Per-user quota exceeded → open the upgrade modal and return a
        // status line pointing at it (so the tiny "error" span isn't the
        // only signal).
        const j = await r.json().catch(() => null);
        setUpgradeReason(j?.error?.message);
        setUpgradeOpen(true);
        audioUsage.reportBlocked();
        return j?.error?.message ?? "استنفدت حصّة الاستماع اليومية.";
      }
      if (r.status === 429) return "تجاوزت حدّ الطلبات، حاول بعد قليل.";
      if (r.status === 503) {
        const j = await r.json().catch(() => null);
        return j?.error?.message ?? "خدمة الصوت غير مهيّأة على الخادم.";
      }
      const j = await r.json().catch(() => null);
      return j?.error?.message ?? "تعذّر توليد الصوت.";
    } catch { return "تعذّر توليد الصوت."; }
  }

  const verseText = verse.hemistich_a + (verse.hemistich_b ? "\n" + verse.hemistich_b : "");
  const attribution = `\n\n— ${poem.poet?.name_ar ?? ""}, «${poem.title_ar}»`;
  const deepLink = typeof window !== "undefined"
    ? `${window.location.origin}/poems/${poem.slug}#verse-${verse.position}`
    : "";

  if (!mounted) return null;

  return createPortal(
    <div
      className="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm overflow-y-auto"
      onClick={onClose}
    >
      <div
        className="w-full max-w-2xl rounded-2xl bg-parchment-elev border border-border-strong shadow-2xl p-8 md:p-10 relative my-auto"
        onClick={(e) => e.stopPropagation()}
      >
        <button
          onClick={onClose}
          className="absolute top-3 start-3 w-8 h-8 rounded-full text-ink-muted hover:text-wine hover:bg-parchment-soft grid place-items-center"
          aria-label="Close"
        ><XIcon size={16} /></button>

        {/* verse number + ornaments */}
        <div
          className="text-center text-[color:var(--gold)] text-xs tracking-[.35em] uppercase mb-4"
          style={{ fontFamily: "var(--font-reem)" }}
        >
          <span className="opacity-50 mx-2">◆</span>
          البيت {verse.position}
          <span className="opacity-50 mx-2">◆</span>
        </div>

        {/* verse */}
        <div
          className={`transition-colors ${playing ? "text-[color:var(--wine)]" : "text-ink"}`}
          style={{ fontFamily: "var(--font-amiri)" }}
        >
          <div className="text-2xl md:text-[28px] leading-loose text-center">{verse.hemistich_a}</div>
          {verse.hemistich_b && (
            <div className="text-2xl md:text-[28px] leading-loose text-center opacity-90">{verse.hemistich_b}</div>
          )}
        </div>

        {/* divider */}
        <div className="flex items-center justify-center gap-3 text-gold text-xs my-6 tracking-widest">
          <span className="flex-1 h-px bg-border-strong max-w-[100px]" />
          ◆ ❦ ◆
          <span className="flex-1 h-px bg-border-strong max-w-[100px]" />
        </div>

        {/* context */}
        <div className="text-center text-sm text-ink-muted">
          من قصيدة{" "}
          <a href={`/poems/${poem.slug}`} className="text-wine border-b border-dotted border-gold">
            «{poem.title_ar}»
          </a>
          {poem.poet && (
            <>
              {" — "}
              <a href={`/poets/${poem.poet.slug}`} className="text-wine border-b border-dotted border-gold">
                {poem.poet.name_ar}
              </a>
            </>
          )}
        </div>

        {/* actions */}
        <div className="mt-6 flex gap-2 justify-center flex-wrap items-center">
          <button
            onClick={toggle}
            className={`rounded-full px-4 py-2 text-sm font-semibold transition inline-flex items-center gap-2
              ${playing
                ? "bg-[color:var(--gold)] text-[color:var(--ink)]"
                : "bg-[color:var(--wine)] text-white hover:brightness-90"}`}
          >
            {playing ? <><StopIcon size={14} /> إيقاف</> : <><PlayIcon size={14} /> استمع</>}
          </button>

          <FavoriteButton
            kind="verse"
            id={verse.uuid}
            title="احفظ البيت"
          />

          <AudioQuotaBadge compact />


          <ActionBtn onClick={() => copyText(verseText + attribution)}>
            <CopyIcon size={14} /> نسخ
          </ActionBtn>
          <ActionBtn onClick={() => copyText(deepLink)}>
            <LinkIcon size={14} /> رابط
          </ActionBtn>
          {typeof navigator !== "undefined" && typeof navigator.share === "function" && (
            <ActionBtn
              onClick={() =>
                navigator.share({ text: verseText + attribution, url: deepLink }).catch(() => {})
              }
            ><ShareIcon size={14} /> مشاركة</ActionBtn>
          )}
          <span
            className={`text-[11px] transition-opacity ${toastShow ? "opacity-100" : "opacity-0"}`}
            style={{ color: "color-mix(in oklab, var(--wine) 60%, green)" }}
          >
            ✓ تم النسخ
          </span>
          {status.text && (
            <span
              className={`text-[11px] inline-flex items-center gap-1 ${status.tone === "error" ? "text-[color:var(--wine)]" : "text-ink-muted"}`}
            >
              {status.tone === "loading" && <span className="inline-block w-3 h-3 rounded-full border-2 border-border border-t-[color:var(--wine)] animate-spin" />}
              {status.text}
            </span>
          )}
        </div>

        {/* prev / next */}
        <div className="flex justify-between mt-6 pt-4 border-t border-border">
          <button
            disabled={!hasPrev}
            onClick={() => onNavigate(-1)}
            className="inline-flex items-center gap-1.5 text-sm text-ink-muted hover:text-wine disabled:text-ink-dim disabled:cursor-not-allowed px-2"
          >
            <ArrowRightIcon size={14} /> السابق
          </button>
          <button
            disabled={!hasNext}
            onClick={() => onNavigate(+1)}
            className="inline-flex items-center gap-1.5 text-sm text-ink-muted hover:text-wine disabled:text-ink-dim disabled:cursor-not-allowed px-2"
          >
            التالي <ArrowLeftIcon size={14} />
          </button>
        </div>

        {/* audio element */}
        <audio
          ref={audioRef}
          preload="auto"
          onCanPlay={() => { setStatus({ text: "", tone: "muted" }); audioUsage.reportPlayed(); }}
          onPlay={() => { setPlaying(true); setStatus({ text: "", tone: "muted" }); }}
          onPause={() => setPlaying(false)}
          onEnded={() => setPlaying(false)}
          onError={async () => {
            setPlaying(false);
            setStatus({ text: await fetchErrorMessage(), tone: "error" });
          }}
        />

        <UpgradeModal
          open={upgradeOpen}
          onClose={() => setUpgradeOpen(false)}
          reason={upgradeReason}
        />
      </div>
    </div>,
    document.body,
  );
}

function ActionBtn({ children, onClick }: { children: React.ReactNode; onClick: () => void }) {
  return (
    <button
      onClick={onClick}
      className="rounded-full bg-parchment-soft border border-border text-ink text-sm px-3.5 py-1.5 hover:border-wine hover:text-wine transition inline-flex items-center gap-1.5"
    >
      {children}
    </button>
  );
}
