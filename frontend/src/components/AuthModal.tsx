"use client";

import { useEffect, useRef, useState } from "react";
import { createPortal } from "react-dom";
import { session, type SessionUser } from "@/lib/session";
import { apiClient, ApiError } from "@/lib/apiClient";

type Tab = "login" | "register";

/**
 * Translate common English validation messages from Laravel into Arabic.
 * The backend responds in English by default; rather than change the whole
 * lang layer, we handle the handful of messages users actually see here.
 */
function arabizeError(msg: string): string {
  const map: Record<string, string> = {
    "The provided credentials are incorrect.": "بيانات الدخول غير صحيحة. تأكّد من البريد وكلمة المرور.",
    "The email field is required.":            "البريد الإلكتروني مطلوب.",
    "The password field is required.":         "كلمة المرور مطلوبة.",
    "The email has already been taken.":       "هذا البريد الإلكتروني مسجّل مسبقًا — جرّب تسجيل الدخول.",
    "The password field confirmation does not match.": "كلمتا المرور غير متطابقتين.",
  };
  if (map[msg]) return map[msg];
  if (/at least 9 characters/i.test(msg))     return "كلمة المرور قصيرة — تحتاج ٩ أحرف على الأقل.";
  if (/one letter/i.test(msg) || /one number/i.test(msg) || /one symbol/i.test(msg))
    return "كلمة المرور ضعيفة — يجب أن تحتوي على حرف ورقم ورمز.";
  if (/must be a valid email/i.test(msg))     return "صيغة البريد الإلكتروني غير صحيحة.";
  if (/found in a data leak/i.test(msg))      return "كلمة المرور معروفة في تسريبات سابقة — اختر واحدة أخرى.";
  return msg;
}

interface Props { initialTab?: Tab; onClose: () => void }

interface AuthResponse {
  data: { user: SessionUser; token: string; expires_at: string };
}

interface AppConfig {
  data: {
    google_client_id: string;
    features: { google_signin: boolean };
  };
}

// Load Google Identity Services on-demand exactly once.
let gsiReady: Promise<void> | null = null;
function ensureGsi(): Promise<void> {
  if (gsiReady) return gsiReady;
  gsiReady = new Promise((resolve, reject) => {
    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    if ((window as any).google?.accounts?.id) return resolve();
    const s = document.createElement("script");
    s.src = "https://accounts.google.com/gsi/client";
    s.async = true; s.defer = true;
    s.onload = () => resolve();
    s.onerror = () => reject(new Error("Failed to load Google Identity Services"));
    document.head.appendChild(s);
  });
  return gsiReady;
}

const deviceName = () =>
  `next-${(typeof navigator !== "undefined" ? navigator.userAgent.split(" ").pop() : "web")}`;

export default function AuthModal({ initialTab = "login", onClose }: Props) {
  const [tab, setTab] = useState<Tab>(initialTab);
  const [err, setErr] = useState<string | null>(null);
  const [busy, setBusy] = useState<null | "login" | "register" | "google">(null);
  const [cfg, setCfg] = useState<AppConfig["data"] | null>(null);
  const [mounted, setMounted] = useState(false);
  const dialogRef = useRef<HTMLDivElement>(null);

  // createPortal requires document, which only exists after mount.
  useEffect(() => { setMounted(true); }, []);

  useEffect(() => {
    apiClient<AppConfig>("/config").then(c => setCfg(c.data)).catch(() => setCfg(null));
  }, []);

  // Prevent the page underneath from scrolling while the modal is open.
  useEffect(() => {
    const prev = document.body.style.overflow;
    document.body.style.overflow = "hidden";
    return () => { document.body.style.overflow = prev; };
  }, []);

  // Esc closes.
  useEffect(() => {
    const h = (e: KeyboardEvent) => { if (e.key === "Escape") onClose(); };
    document.addEventListener("keydown", h);
    return () => document.removeEventListener("keydown", h);
  }, [onClose]);

  async function submitLogin(e: React.FormEvent<HTMLFormElement>) {
    e.preventDefault();
    const fd = new FormData(e.currentTarget);
    setErr(null); setBusy("login");
    try {
      const r = await apiClient<AuthResponse>("/auth/login", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          email: fd.get("email"),
          password: fd.get("password"),
          device_name: deviceName(),
        }),
      });
      session.set(r.data.token, r.data.user);
      onClose();
    } catch (e) {
      console.error("login failed:", e);
      const raw = e instanceof ApiError ? e.message : (e instanceof Error ? e.message : String(e));
      setErr(arabizeError(raw));
    } finally { setBusy(null); }
  }

  async function submitRegister(e: React.FormEvent<HTMLFormElement>) {
    e.preventDefault();
    const fd = new FormData(e.currentTarget);
    const pw = fd.get("password");
    if (pw !== fd.get("password_confirmation")) {
      setErr("كلمتا المرور غير متطابقتين."); return;
    }
    setErr(null); setBusy("register");
    try {
      const r = await apiClient<AuthResponse>("/auth/register", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          name: fd.get("name"),
          email: fd.get("email"),
          password: pw,
          password_confirmation: fd.get("password_confirmation"),
          device_name: deviceName(),
        }),
      });
      session.set(r.data.token, r.data.user);
      onClose();
    } catch (e) {
      console.error("register failed:", e);
      const raw = e instanceof ApiError ? e.message : (e instanceof Error ? e.message : String(e));
      setErr(arabizeError(raw));
    } finally { setBusy(null); }
  }

  async function signInWithGoogle() {
    if (!cfg?.features.google_signin) return;
    setErr(null); setBusy("google");
    try {
      await ensureGsi();
      // eslint-disable-next-line @typescript-eslint/no-explicit-any
      const g = (window as any).google.accounts.id;
      g.initialize({
        client_id: cfg.google_client_id,
        // eslint-disable-next-line @typescript-eslint/no-explicit-any
        callback: async (resp: any) => {
          if (!resp?.credential) { setErr("لم يُرجع جوجل رمز الاعتماد."); setBusy(null); return; }
          try {
            const r = await apiClient<AuthResponse>("/auth/google", {
              method: "POST",
              headers: { "Content-Type": "application/json" },
              body: JSON.stringify({ id_token: resp.credential, device_name: deviceName() }),
            });
            session.set(r.data.token, r.data.user);
            onClose();
          } catch (e) {
            setErr(e instanceof ApiError ? e.message : "فشل الدخول بجوجل.");
          } finally { setBusy(null); }
        },
        ux_mode: "popup",
      });
      // eslint-disable-next-line @typescript-eslint/no-explicit-any
      g.prompt((n: any) => {
        if (n.isNotDisplayed?.() || n.isSkippedMoment?.()) {
          // Fallback: render a real GIS button and click it programmatically.
          const host = document.createElement("div");
          host.style.cssText = "position:fixed;bottom:24px;left:50%;transform:translateX(-50%);z-index:200";
          document.body.appendChild(host);
          g.renderButton(host, { theme: "outline", size: "large", shape: "pill", text: "signin_with", locale: "ar" });
          setBusy(null);
        }
      });
    } catch (e) {
      setErr(e instanceof Error ? e.message : "تعذّر تحميل جوجل.");
      setBusy(null);
    }
  }

  if (!mounted) return null;

  // Portal to <body> — escapes any containing-block trap from ancestors that
  // use transform / filter / backdrop-filter (e.g. the sticky blurred header).
  return createPortal(
    <div
      className="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm overflow-y-auto"
      onClick={onClose}
    >
      <div
        ref={dialogRef}
        className="w-full max-w-md rounded-2xl bg-parchment-elev border border-border-strong shadow-2xl p-6 relative my-auto"
        onClick={(e) => e.stopPropagation()}
      >
        <button
          onClick={onClose}
          className="absolute top-3 start-3 w-8 h-8 rounded-full text-ink-muted hover:text-wine hover:bg-parchment-soft grid place-items-center"
          aria-label="Close"
        >×</button>

        <h2 className="text-xl font-bold text-ink text-center mb-1" style={{ fontFamily: "var(--font-reem)" }}>
          {tab === "register" ? "مرحبًا بك في شِعْري" : "أهلًا بعودتك"}
        </h2>
        <p className="text-xs text-ink-muted text-center mb-4">
          ادخل لحفظ المفضلة، إضافة قصائدك، والمشاركة في التحسين.
        </p>

        {/* Google button + hint */}
        <button
          onClick={signInWithGoogle}
          disabled={!cfg?.features.google_signin || busy !== null}
          className="w-full flex items-center justify-center gap-2 bg-parchment-elev border border-border-strong hover:border-gold hover:bg-parchment-soft transition rounded-lg py-2.5 text-sm font-medium text-ink disabled:opacity-50 disabled:cursor-not-allowed"
          title={cfg?.features.google_signin ? "الدخول بحساب جوجل" : "غير مفعّل على الخادم"}
        >
          <GoogleG /> المتابعة باستخدام Google
        </button>
        {cfg && !cfg.features.google_signin && (
          <p className="text-[10px] text-ink-dim text-center mt-1">مطلوب GOOGLE_CLIENT_ID على الخادم.</p>
        )}

        <div className="flex items-center gap-2 my-4 text-[11px] text-ink-dim">
          <span className="flex-1 h-px bg-border" /> أو <span className="flex-1 h-px bg-border" />
        </div>

        {/* Tab switch */}
        <div className="flex gap-1 bg-parchment-soft p-1 rounded-full mb-4">
          {(["login", "register"] as Tab[]).map(t => (
            <button
              key={t}
              type="button"
              onClick={() => { setTab(t); setErr(null); }}
              className={`flex-1 rounded-full px-3 py-1.5 text-xs font-medium transition ${
                tab === t ? "bg-parchment-elev text-ink shadow-sm" : "text-ink-muted"
              }`}
            >
              {t === "login" ? "تسجيل الدخول" : "إنشاء حساب"}
            </button>
          ))}
        </div>

        {err && (
          <div className="mb-3 rounded-lg border border-[color:var(--wine)] bg-wine-soft text-[color:var(--wine)] px-4 py-3 text-sm leading-relaxed font-medium">
            <div className="flex items-start gap-2">
              <span className="text-lg leading-none">⚠</span>
              <div className="flex-1">
                <div>{err}</div>
                {tab === "login" && /بيانات الدخول/.test(err) && (
                  <button
                    type="button"
                    onClick={() => { setTab("register"); setErr(null); }}
                    className="mt-1 text-[11px] text-[color:var(--wine)]/85 underline hover:no-underline"
                  >
                    لا تملك حسابًا؟ سجّل حسابًا جديدًا
                  </button>
                )}
              </div>
            </div>
          </div>
        )}

        {tab === "login" ? (
          <form onSubmit={submitLogin} className="flex flex-col gap-3" autoComplete="on">
            <Field label="البريد الإلكتروني" name="email" type="email" autoComplete="email" required />
            <Field label="كلمة المرور"     name="password" type="password" autoComplete="current-password" required />
            <SubmitBtn busy={busy === "login"}>دخول</SubmitBtn>
          </form>
        ) : (
          <form onSubmit={submitRegister} className="flex flex-col gap-3" autoComplete="on">
            <Field label="الاسم"                 name="name"                  type="text"     autoComplete="name" required />
            <Field label="البريد الإلكتروني"     name="email"                 type="email"    autoComplete="email" required />
            <Field label="كلمة المرور"           name="password"              type="password" autoComplete="new-password" required minLength={9}
              hint="٩ أحرف على الأقل، مع حرف ورقم ورمز." />
            <Field label="تأكيد كلمة المرور"    name="password_confirmation" type="password" autoComplete="new-password" required minLength={9} />
            <SubmitBtn busy={busy === "register"}>إنشاء الحساب</SubmitBtn>
          </form>
        )}
      </div>
    </div>,
    document.body,
  );
}

function Field(props: {
  label: string; name: string; type: string; autoComplete?: string;
  required?: boolean; minLength?: number; hint?: string;
}) {
  const { label, hint, ...rest } = props;
  return (
    <label className="block">
      <span className="text-[11px] text-ink-muted mb-1 block">{label}</span>
      <input
        {...rest}
        className="w-full rounded-md border border-border bg-parchment text-ink px-3 py-2 text-sm focus:outline-none focus:border-gold focus:ring-2 focus:ring-gold-soft"
      />
      {hint && <span className="text-[10px] text-ink-dim mt-1 block">{hint}</span>}
    </label>
  );
}

function SubmitBtn({ busy, children }: { busy: boolean; children: React.ReactNode }) {
  return (
    <button
      type="submit"
      disabled={busy}
      className="mt-1 bg-[color:var(--wine)] hover:brightness-90 text-white font-semibold py-2.5 rounded-lg text-sm disabled:opacity-60 disabled:cursor-wait"
    >
      {busy ? "…جاري المعالجة" : children}
    </button>
  );
}

function GoogleG() {
  return (
    <svg width="18" height="18" viewBox="0 0 48 48" aria-hidden="true">
      <path fill="#FFC107" d="M43.6 20.5H42V20.4H24v7.2h11.1c-1.5 4-5.3 7-9.1 7-6.1 0-11-4.9-11-11s4.9-11 11-11c2.8 0 5.4 1.1 7.4 2.8l5.2-5.2C34.8 6.6 29.7 5 24 5 13.5 5 5 13.5 5 24s8.5 19 19 19c11 0 18.4-8 18.4-19 0-1.2-.1-2.4-.4-3.5z"/>
      <path fill="#FF3D00" d="M6.3 14.7l6 4.4C13.9 15.3 18.6 12.5 24 12.5c2.8 0 5.4 1.1 7.4 2.8l5.2-5.2C34.8 6.6 29.7 5 24 5 16.3 5 9.7 9.4 6.3 14.7z"/>
      <path fill="#4CAF50" d="M24 43c5.6 0 10.6-2.1 14.4-5.6l-6.7-5.7c-2 1.5-4.6 2.5-7.7 2.5-3.8 0-7.6-3-9.1-7l-6 4.6C7.9 38.4 15.4 43 24 43z"/>
      <path fill="#1976D2" d="M43.6 20.5H42V20.4H24v7.2h11.1c-.7 2-2 3.7-3.7 4.9l6.7 5.7C42.4 34.6 44 29.5 44 24c0-1.2-.1-2.4-.4-3.5z"/>
    </svg>
  );
}
