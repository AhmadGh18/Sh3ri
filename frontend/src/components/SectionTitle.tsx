/**
 * Section heading with an ornamental gradient line that fills the remaining
 * width. Keeps the mild Reem Kufi weight for Arabic serif-sans contrast.
 */
export default function SectionTitle({ glyph = "❦", children, hint }: {
  glyph?: string;
  children: React.ReactNode;
  hint?: string | null;
}) {
  return (
    <div className="flex items-center gap-3 mb-4">
      <h2
        className="text-lg md:text-xl font-bold text-ink flex items-center gap-2.5 shrink-0"
        style={{ fontFamily: "var(--font-reem)" }}
      >
        <span className="text-[color:var(--gold)] text-xl md:text-2xl leading-none">{glyph}</span>
        <span>{children}</span>
      </h2>
      {/* Gradient filler line */}
      <span
        aria-hidden
        className="flex-1 h-px bg-gradient-to-l from-[color:var(--gold)]/40 via-border to-transparent"
      />
      {hint && (
        <span className="text-xs text-ink-muted font-normal shrink-0 rounded-full bg-parchment-soft border border-border px-2 py-0.5">
          {hint}
        </span>
      )}
    </div>
  );
}
