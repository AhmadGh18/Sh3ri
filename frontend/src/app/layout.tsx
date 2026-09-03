import type { Metadata } from "next";
import { Amiri, Cairo, Reem_Kufi } from "next/font/google";
import Header from "@/components/Header";
import "./globals.css";

const amiri = Amiri({
  variable: "--font-amiri",
  weight: ["400", "700"],
  subsets: ["arabic", "latin"],
  display: "swap",
});
const cairo = Cairo({
  variable: "--font-cairo",
  weight: ["400", "500", "600", "700"],
  subsets: ["arabic", "latin"],
  display: "swap",
});
const reemKufi = Reem_Kufi({
  variable: "--font-reem",
  weight: ["500", "700"],
  subsets: ["arabic", "latin"],
  display: "swap",
});

export const metadata: Metadata = {
  title: "شِعْري — Sh3ri",
  description: "منصّة الشعر العربي — تصفّح آلاف القصائد، استمع بصوت، احفظ المفضّلة.",
  // Uses public/logo.png for the browser tab. NB: the detailed illumination
  // will look muddy at 16-32 px — swap in a simplified glyph later.
  icons: {
    icon: [
      { url: "/logo.png", sizes: "any", type: "image/png" },
    ],
    apple: "/logo.png",
  },
  openGraph: {
    title: "شِعْري",
    description: "منصّة الشعر العربي — تصفّح آلاف القصائد، استمع بصوت، احفظ المفضّلة.",
    images: [{ url: "/logo.png", width: 1024, height: 1024, alt: "شِعْري" }],
    locale: "ar",
    type: "website",
  },
  twitter: {
    card: "summary",
    title: "شِعْري",
    description: "منصّة الشعر العربي — تصفّح آلاف القصائد، استمع بصوت، احفظ المفضّلة.",
    images: ["/logo.png"],
  },
};

export default function RootLayout({ children }: LayoutProps<"/">) {
  return (
    <html
      lang="ar"
      dir="rtl"
      className={`${amiri.variable} ${cairo.variable} ${reemKufi.variable} h-full antialiased`}
    >
      <body className="min-h-full flex flex-col bg-parchment text-ink">
        <Header />
        <div className="flex-1">{children}</div>
      </body>
    </html>
  );
}
