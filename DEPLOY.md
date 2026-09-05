# Deploy Sh3ri for testing (100% free)

Estimated time: **20 minutes**. No credit card required anywhere.

## What you get

- **Frontend**: `https://sh3ri.vercel.app` — instant, always fast (Vercel edge CDN)
- **Backend**: `https://sh3ri-backend.onrender.com` — sleeps when idle; wakes on first request in ~30 s
- **Postgres**: on Neon, 0.5 GB free forever
- **Redis**: skipped — Laravel uses file cache/session in production (fine for testing)

---

## 1. Create the free accounts (5 min)

| Service | Where | Sign in with |
|---|---|---|
| Neon (Postgres) | https://neon.tech | GitHub |
| Render (backend) | https://render.com | GitHub |
| Vercel (frontend) | https://vercel.com | GitHub |

No credit card needed on any of them.

---

## 2. Create the database on Neon (2 min)

1. Neon dashboard → **New Project**
   - Name: `sh3ri`
   - Region: **Frankfurt** (closest free region to MENA)
   - Postgres version: **16**
2. On the project page, click **Connection Details** → copy the **connection string** — it looks like:
   ```
   postgresql://user:pass@ep-xxxx.eu-central-1.aws.neon.tech/neondb?sslmode=require
   ```
   Save it somewhere; you'll paste it into Render.

---

## 3. Deploy the backend on Render (5 min)

1. Render dashboard → **New +** → **Blueprint**
2. Connect the `Sh3ri` GitHub repo → Render reads `render.yaml` and shows one service (`sh3ri-backend`) to create → click **Apply**.
3. On the service's **Environment** tab, fill in every variable marked *sync: false*:
   - `APP_KEY` → open `backend/.env` locally, copy the whole `base64:…` value.
   - `APP_URL` → leave blank for now; you'll set it after step 4.
   - `DB_URL` → paste Neon's connection string from step 2.
   - `SH3RI_CORS_ALLOWED_ORIGINS` → leave blank for now.
   - `ELEVENLABS_API_KEY` → copy from `backend/.env`.
4. Click **Manual Deploy** → **Deploy latest commit**. First build takes ~3 min.
5. When it goes **Live**, copy your service URL from the top of the dashboard — something like `https://sh3ri-backend.onrender.com`.
6. **Go back to Environment** → set:
   - `APP_URL` = `https://sh3ri-backend.onrender.com` (your URL).
7. Open **Shell** (top-right of your service page in Render) and run **once**:
   ```
   php artisan db:seed --force
   ```
   This creates the eras, categories, poets taxonomies, roles, and the four plans.

Verify it worked:
- Visit `https://sh3ri-backend.onrender.com/api/v1/plans` in a browser.
- You should see JSON with three plans (`free`, `starter`, `pro`).

---

## 4. Deploy the frontend on Vercel (3 min)

1. Vercel dashboard → **Add New** → **Project** → import the `Sh3ri` repo.
2. **Root Directory** → click **Edit** → select `frontend`.
3. **Framework Preset** should auto-detect **Next.js**. Don't change it.
4. **Environment Variables** → add:
   - `NEXT_PUBLIC_API_URL` = `https://sh3ri-backend.onrender.com` (your Render URL from step 3.5)
5. Click **Deploy**. Build takes ~90 s.
6. Copy your Vercel URL — something like `https://sh3ri.vercel.app`.

---

## 5. Point CORS at the frontend (1 min)

Back in Render → your backend service → **Environment**:

- `SH3RI_CORS_ALLOWED_ORIGINS` = `https://sh3ri.vercel.app` (or whatever your Vercel URL is)

Save → Render auto-restarts the service (~30 s).

---

## 6. Try it

Open `https://sh3ri.vercel.app`. You should see the homepage. Click a poem. First click on **استمع للقصيدة** will fail if your ElevenLabs credits are still exhausted — that's the same issue as locally, unrelated to hosting.

The **`/plans`** page will show the three tiers. The audio quota badge appears next to the play button.

---

## Cold-start reality check

- Render sleeps the backend after 15 min of inactivity.
- Next visitor after sleep waits ~30 s while the container wakes up.
- For your own testing, keep a tab open on `/api/v1/plans` (or hit it with a quick request) to warm it before demoing.

To eliminate cold-starts, upgrade the backend to Render's `starter` plan ($7/mo). Everything else stays free.

---

## Common issues

| Symptom | Fix |
|---|---|
| Vercel build fails with `NEXT_PUBLIC_API_URL is undefined` | You didn't set the env var in step 4.4. Add it and redeploy. |
| Frontend shows blank page + CORS error in browser console | `SH3RI_CORS_ALLOWED_ORIGINS` doesn't match your Vercel URL exactly. Include the scheme (`https://`) and no trailing slash. |
| `/plans` shows "لا خطط متاحة" | You skipped step 3.7 (`php artisan db:seed --force`). Run it. |
| Backend logs show `SQLSTATE[08006] SSL required` | Your Neon `DB_URL` is missing `?sslmode=require`. Copy the full URL from Neon's dashboard, don't trim it. |
| Every page returns 500 for 30 s then works | Cold start. Normal on free tier. |
