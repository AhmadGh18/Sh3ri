/**
 * Inline SVG icon set — no CDN, no runtime cost. All icons are 24×24 viewBox
 * with `currentColor` strokes so they inherit text color and Tailwind's
 * `text-{color}` utilities work directly.
 *
 * Style is Tabler/Lucide-ish: 1.75px stroke, rounded caps/joins, no fills.
 */

type IconProps = { size?: number; className?: string; strokeWidth?: number };

function base(props: IconProps, children: React.ReactNode) {
  const { size = 18, className, strokeWidth = 1.75 } = props;
  return (
    <svg
      xmlns="http://www.w3.org/2000/svg"
      width={size} height={size}
      viewBox="0 0 24 24"
      fill="none"
      stroke="currentColor"
      strokeWidth={strokeWidth}
      strokeLinecap="round"
      strokeLinejoin="round"
      className={className}
      aria-hidden="true"
    >
      {children}
    </svg>
  );
}

export const PlayIcon      = (p: IconProps = {}) => base(p, <polygon points="6 4 20 12 6 20 6 4" fill="currentColor" stroke="none" />);
export const StopIcon      = (p: IconProps = {}) => base(p, <rect x="6" y="6" width="12" height="12" rx="1.5" fill="currentColor" stroke="none" />);
export const PauseIcon     = (p: IconProps = {}) => base(p, <><rect x="6" y="5" width="4" height="14" rx="1" fill="currentColor" stroke="none" /><rect x="14" y="5" width="4" height="14" rx="1" fill="currentColor" stroke="none" /></>);
export const HeadphonesIcon = (p: IconProps = {}) => base(p, <><path d="M4 15v-3a8 8 0 0 1 16 0v3" /><path d="M18 19a2 2 0 0 0 2-2v-2a2 2 0 0 0-2-2h-1v6h1z" /><path d="M6 19a2 2 0 0 1-2-2v-2a2 2 0 0 1 2-2h1v6H6z" /></>);
export const CopyIcon      = (p: IconProps = {}) => base(p, <><rect x="9" y="9" width="11" height="11" rx="2" /><path d="M5 15V6a2 2 0 0 1 2-2h9" /></>);
export const LinkIcon      = (p: IconProps = {}) => base(p, <><path d="M10 14a4 4 0 0 0 5.66 0l3.34-3.34a4 4 0 0 0-5.66-5.66L11.5 6.83" /><path d="M14 10a4 4 0 0 0-5.66 0L5 13.34a4 4 0 0 0 5.66 5.66l1.84-1.83" /></>);
export const ShareIcon     = (p: IconProps = {}) => base(p, <><circle cx="18" cy="5" r="3" /><circle cx="6" cy="12" r="3" /><circle cx="18" cy="19" r="3" /><path d="M8.6 13.5l6.8 4M15.4 6.5l-6.8 4" /></>);
export const HeartIcon     = (p: IconProps & { filled?: boolean } = {}) => {
  const { filled, ...rest } = p;
  return base(rest, <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z" fill={filled ? "currentColor" : "none"} />);
};
export const XIcon         = (p: IconProps = {}) => base(p, <><path d="M18 6L6 18" /><path d="M6 6l12 12" /></>);
export const CheckIcon     = (p: IconProps = {}) => base(p, <polyline points="20 6 9 17 4 12" />);
export const ArrowRightIcon = (p: IconProps = {}) => base(p, <><line x1="5" y1="12" x2="19" y2="12" /><polyline points="12 5 19 12 12 19" /></>);
export const ArrowLeftIcon  = (p: IconProps = {}) => base(p, <><line x1="19" y1="12" x2="5" y2="12" /><polyline points="12 19 5 12 12 5" /></>);
