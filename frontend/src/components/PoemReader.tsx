"use client";

import { useCallback, useEffect, useRef, useState } from "react";
import { API_URL, type PoemDetail } from "@/lib/api";
import VerseReaderModal from "./VerseReaderModal";
import { HeadphonesIcon, StopIcon, PlayIcon } from "./Icon";
import AudioQuotaBadge from "./AudioQuotaBadge";
import UpgradeModal from "./UpgradeModal";
import { audioUsage } from "@/lib/audioUsage";

/**
 * Interactive verse list + "listen to whole poem" toolbar.
 *
 * Audio reliability notes:
 * - We wait for `canplay` before calling `play()`. Calling play() before the
 *   browser has any data resolves in some browsers, rejects in others; the
 *   canplay-then-play pattern is portable.
 * - Every attempt has a hard 15s ceiling. If the server is synthesizing a
 *   cold verse very slowly (or is down), we skip forward instead of hanging.
 * - We fetch HEAD before creating the <audio> element only on the FIRST
 *   verse of a run — if the daily TTS budget is exhausted (503), we show a
 *   toast up front instead of silently failing verse-by-verse.
 * - Handlers are torn down BEFORE pause() so a stray `ended` from src-clear
 *   can't retrigger auto-advance and make Stop feel broken.
 */
export default function PoemReader({ poem }: { poem: PoemDetail }) {
  const [openIdx, setOpenIdx] = useState<number | null>(null);
  const [playingIdx, setPlayingIdx] = useState<number | null>(null);
  const [progress, setProgress] = useState<{ i: number; loading: boolean } | null>(null);
  const [notice, setNotice] = useState<string | null>(null);
  const [upgradeOpen, setUpgradeOpen] = useState(false);
  const [upgradeReason, setUpgradeReason] = useState<string | undefined>();

  const audioRef  = useRef<HTMLAudioElement | null>(null);
  const preloadRef = useRef<HTMLAudioElement | null>(null);
  const timeoutRef = useRef<ReturnType<typeof setTimeout> | null>(null);
  const rowRefs = useRef<(HTMLLIElement | null)[]>([]);
  const sessionRef = useRef(0);

  const audioUrl = useCallback(
    (uuid: string) => `${API_URL}/api/v1/verses/${uuid}/audio`,
    []
  );

  const clearTimer = () => {
    if (timeoutRef.current) { clearTimeout(timeoutRef.current); timeoutRef.current = null; }
  };

  /**
   * Detach handlers BEFORE pause + src-clear. A residual `ended` event
   * (fires when src is reassigned) otherwise triggers auto-advance and Stop
   * appears broken. Bump session so any in-flight promise is ignored.
   */
  const stop = useCallback(() => {
    sessionRef.current++;
    clearTimer();
    const a = audioRef.current;
    if (a) {
      a.onended = null; a.onerror = null; a.oncanplay = null;
      try { a.pause(); } catch {}
      try { a.removeAttribute("src"); a.load(); } catch {}
      audioRef.current = null;
    }
    const p = preloadRef.current;
    if (p) {
      p.onended = null; p.onerror = null; p.oncanplay = null;
      try { p.removeAttribute("src"); p.load(); } catch {}
      preloadRef.current = null;
    }
    setPlayingIdx(null);
    setProgress(null);
  }, []);

  useEffect(() => () => stop(), [stop]);

  // Auto-dismiss the notice after a few seconds.
  useEffect(() => {
    if (!notice) return;
    const t = setTimeout(() => setNotice(null), 4200);
    return () => clearTimeout(t);
  }, [notice]);

  const playAt = useCallback((i: number) => {
    if (i >= poem.verses.length) { stop(); return; }
    const session = ++sessionRef.current;
    const stillActive = () => sessionRef.current === session;

    clearTimer();
    setPlayingIdx(i);
    setProgress({ i, loading: true });
    rowRefs.current[i]?.scrollIntoView({ behavior: "smooth", block: "center" });

    // Reuse the warmed-up next-verse element if it matches this index.
    let a: HTMLAudioElement;
    if (preloadRef.current && preloadRef.current.dataset.idx === String(i)) {
      a = preloadRef.current;
      preloadRef.current = null;
    } else {
      a = new Audio();
      a.src = audioUrl(poem.verses[i].uuid);
    }
    audioRef.current = a;
    a.dataset.idx = String(i);
    a.preload = "auto";

    // Timeout: if canplay never fires in 15s, skip this verse.
    timeoutRef.current = setTimeout(() => {
      if (!stillActive()) return;
      console.warn("poem-player: verse", i, "timeout, skipping");
      setNotice("تجاوز أحد الأبيات لبطء التحميل…");
      playAt(i + 1);
    }, 15000);

    // Kick off playback. Extracted so we can also fire it immediately when
    // the audio element is already ready (preloaded elements have their
    // canplay event before we get here, and attaching oncanplay after the
    // event has already fired misses it — which was the "only verse 1
    // plays" symptom).
    const kickOff = () => {
      if (!stillActive()) return;
      clearTimer();
      setProgress({ i, loading: false });
      audioUsage.reportPlayed();
      a.play().catch((e) => {
        if (!stillActive()) return;
        console.warn("audio.play failed:", e);
        playAt(i + 1);
      });
    };

    // readyState 3 = HAVE_FUTURE_DATA, 4 = HAVE_ENOUGH_DATA. Either is enough.
    if (a.readyState >= 3) {
      kickOff();
    } else {
      a.oncanplay = kickOff;
    }

    a.onended = () => { if (stillActive()) playAt(i + 1); };

    // ALWAYS advance to the next verse on error. The upgrade-modal path is
    // gone in demo mode — every verse gets a fresh attempt regardless of
    // why the previous one failed. Only the timeout above bounds how long
    // we wait per verse.
    a.onerror = () => {
      if (!stillActive()) return;
      clearTimer();
      console.warn("poem-player: verse", i, "failed, skipping to next");
      setNotice("تعذّر تشغيل بيت — تخطّي…");
      playAt(i + 1);
    };

    // Warm up the next verse in the background — same audio element pattern
    // so `oncanplay` fills the media cache without playing.
    if (i + 1 < poem.verses.length) {
      const next = new Audio();
      next.dataset.idx = String(i + 1);
      next.preload = "auto";
      next.src = audioUrl(poem.verses[i + 1].uuid);
      preloadRef.current = next;
    }
  }, [poem.verses, stop, audioUrl]);

  const isPlayingPoem = playingIdx !== null;

  return (
    <>
      {/* Toolbar */}
      <div className="flex flex-wrap justify-center items-center gap-3 bg-parchment-soft border border-border rounded-full px-4 py-2 mx-auto my-5 w-fit">
        <button
          onClick={() => (isPlayingPoem ? stop() : playAt(0))}
          className={`rounded-full px-4 py-1.5 text-sm font-semibold transition inline-flex items-center gap-2
            ${isPlayingPoem
              ? "bg-[color:var(--gold)] text-[color:var(--ink)]"
              : "bg-[color:var(--wine)] text-white hover:brightness-90"}`}
        >
          {isPlayingPoem
            ? <><StopIcon size={14} /> إيقاف</>
            : <><HeadphonesIcon size={16} /> استمع للقصيدة</>}
        </button>

        <AudioQuotaBadge compact />


        {progress && (
          <span className="text-xs text-ink-muted inline-flex items-center gap-1.5 min-w-[70px]">
            {progress.loading && (
              <span className="inline-block w-3 h-3 rounded-full border-2 border-border border-t-[color:var(--wine)] animate-spin" />
            )}
            {progress.i + 1} / {poem.verses.length}
          </span>
        )}

        {isPlayingPoem && progress && !progress.loading && (
          <button
            onClick={() => playAt((playingIdx ?? 0) + 1)}
            className="text-xs text-ink-muted hover:text-ink border border-border rounded-full px-2 py-0.5"
            title="تخطي هذا البيت"
          >تخطّي ⤴</button>
        )}
      </div>

      {/* Toast-ish notice */}
      {notice && (
        <div className="mx-auto my-3 max-w-md text-center text-[12px] rounded-lg bg-wine-soft border border-[color:var(--wine)]/30 text-[color:var(--wine)] px-3 py-1.5 animate-[fade-in_.2s_ease]">
          {notice}
        </div>
      )}

      {/* Divider */}
      <div className="flex items-center justify-center gap-3 text-gold text-xs my-5 tracking-widest">
        <span className="flex-1 h-px bg-border-strong max-w-24" />
        ◆ ❦ ◆
        <span className="flex-1 h-px bg-border-strong max-w-24" />
      </div>

      {/* Verses */}
      <ol className="space-y-0">
        {poem.verses.map((v, i) => {
          const hasB = !!v.hemistich_b;
          const isPlaying = playingIdx === i;
          return (
            <li
              key={v.uuid}
              ref={(node) => { rowRefs.current[i] = node; }}
              id={`verse-${v.position}`}
              onClick={() => { stop(); setOpenIdx(i); }}
              className={`group cursor-pointer relative grid ${hasB ? "grid-cols-[1fr_auto_1fr]" : "grid-cols-1 text-center"} items-baseline gap-4 py-2.5 border-b border-border/60 last:border-0 transition
                ${isPlaying
                  ? "bg-gradient-to-l from-gold-soft to-transparent shadow-[inset_4px_0_0_var(--gold)]"
                  : "hover:bg-parchment-soft/40"}`}
              style={{ fontFamily: "var(--font-amiri)", fontSize: "1.15rem", lineHeight: 1.9 }}
              title="اضغط لقراءة البيت"
            >
              <span className="absolute -start-4 top-3 text-[10px] text-ink-dim">{v.position}</span>
              <span className={hasB ? `text-end pe-2 ${isPlaying ? "text-[color:var(--wine)]" : "text-ink"}` : (isPlaying ? "text-[color:var(--wine)]" : "text-ink")}>
                {v.hemistich_a}
              </span>
              {hasB && <span className="text-gold text-[10px] justify-self-center opacity-60">◆</span>}
              {hasB && (
                <span className={`text-start ps-2 ${isPlaying ? "text-[color:var(--wine)]" : "text-ink/90"}`}>
                  {v.hemistich_b}
                </span>
              )}
              <span className="absolute top-1/2 -translate-y-1/2 end-2 text-[color:var(--gold)] opacity-0 group-hover:opacity-100 transition">
                <PlayIcon size={14} />
              </span>
            </li>
          );
        })}
      </ol>

      {openIdx !== null && (
        <VerseReaderModal
          poem={poem}
          verseIndex={openIdx}
          onClose={() => setOpenIdx(null)}
          onNavigate={(delta) => {
            const next = openIdx + delta;
            if (next >= 0 && next < poem.verses.length) setOpenIdx(next);
          }}
        />
      )}

      <UpgradeModal
        open={upgradeOpen}
        onClose={() => setUpgradeOpen(false)}
        reason={upgradeReason}
      />

      <style>{`@keyframes fade-in { from { opacity: 0 } to { opacity: 1 } }`}</style>
    </>
  );
}
