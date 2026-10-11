import type { NextConfig } from "next";

const nextConfig: NextConfig = {
  // The global poems list was removed (too slow) — poems are browsed per poet.
  // Keep old links/bookmarks working. Only the exact /poems path; /poems/[slug] is untouched.
  async redirects() {
    return [{ source: "/poems", destination: "/poets", permanent: false }];
  },
};

export default nextConfig;
