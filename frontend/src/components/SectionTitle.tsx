export default function SectionTitle({ glyph = "❦", children, hint }: {
  glyph?: string;
  children: React.ReactNode;
  hint?: string | null;
}) {
  return (
    <div className="flex items-center gap-3 mb-3">
      <h2 className="text-lg font-bold text-ink flex items-center gap-2" style={{ fontFamily: "var(--font-reem)" }}>
        <span className="text-gold">{glyph}</span>
        <span>{children}</span>
      </h2>
      {hint && <span className="ms-auto text-xs text-ink-muted font-normal">{hint}</span>}
    </div>
  );
}
