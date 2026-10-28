# Deploying مِداد | MIDAD

Steps marked **[YOU]** need your accounts, passwords or payment details — do them yourself; never paste secrets into chat, commits or files other than the host's environment settings.

---

## 0. What is already prepared in the code

| Item | Where |
|---|---|
| HTTPS-only (301 from http), https links | `app/Http/Middleware/SecureResponses.php`, `FORCE_HTTPS`, `AppServiceProvider` |
| Security headers: HSTS, X-Frame-Options, X-Content-Type-Options, Referrer-Policy, Permissions-Policy | `SecureResponses` (HSTS only over https in production) |
| Secure session cookie (secure, httpOnly, SameSite=Lax, encrypted) | `SESSION_SECURE_COOKIE`, `SESSION_ENCRYPT` in `.env.production.example` |
| CSRF on every form | Laravel `web` middleware (POST without token → 419) |
| Rate limits | login 10/min, register 5/min, forgot/reset password 5/min, add quote 10/min (`routes/web.php`) |
| Works behind a proxy / Cloudflare | `trustProxies('*')` in `bootstrap/app.php` |
| Every variable you must set | `.env.production.example` |
| Secrets never committed | `.gitignore`: `.env`, `.env.production`, `.env.backup-*`, `*.sqlite`, `storage/logs/*` |

---

## Option A (recommended): Laravel Cloud — permanent URL, automatic SSL

Laravel Cloud is the official host from the Laravel team. It builds from GitHub, runs migrations on deploy, gives a free `*.laravel.cloud` HTTPS address, and issues SSL automatically for your own domain. Check its current plans on the site before choosing.

### A1. Put the code on GitHub **[YOU]**
1. Create a **private** repository on github.com (e.g. `midad`). Sign-in happens on GitHub's site.
2. In the project folder:
   ```sh
   git remote add origin https://github.com/<your-user>/midad.git
   git push -u origin main
   ```
   `.env`, the SQLite database and logs are ignored and will **not** be uploaded.

### A2. Create the app **[YOU]**
1. Sign up at **cloud.laravel.com** (billing details, if your plan needs them, are entered there by you).
2. *New application* → connect GitHub → pick the `midad` repository and the `main` branch.
3. Add a **database** (MySQL or PostgreSQL) to the environment. Cloud fills the `DB_*` variables for you; if not, copy them from the database panel.

### A3. Build & deploy commands
Set these in the environment's deployment settings:

- **Build commands**
  ```sh
  composer install --no-dev --optimize-autoloader
  npm ci
  npm run build
  ```
- **Deploy commands** (run on each release)
  ```sh
  php artisan migrate --force
  php artisan optimize
  ```

### A4. Environment variables **[YOU]**
Open `.env.production.example` and copy every variable into the environment's *Variables* screen. You must fill:

| Variable | Value |
|---|---|
| `APP_KEY` | run `php artisan key:generate --show` (locally or in the host's console) and paste the `base64:...` value. Use a **new** key, not your local one. |
| `APP_URL` | your final address, e.g. `https://midad.example.com` (or the `*.laravel.cloud` address first) |
| `DB_*` | from the database panel (if not filled automatically) |
| `MAIL_USERNAME`, `MAIL_PASSWORD` | Gmail address + Google App Password (myaccount.google.com/apppasswords, needs 2-Step Verification) |

Keep `APP_ENV=production`, `APP_DEBUG=false`, `FORCE_HTTPS=true`, `SESSION_SECURE_COOKIE=true`.

### A5. Deploy
Press **Deploy**. When it finishes, open the `https://…laravel.cloud` address. Then:
```sh
# from any terminal — expect 200 and the security headers
curl -I https://<your-address>/up
curl -I http://<your-address>/        # expect 301 → https
```
Test in a browser: register, add a quote, like/save, *نسيت كلمة المرور؟* (the reset email should arrive).

### A6. Your own domain, automatic SSL **[YOU]**
1. Buy a domain at any registrar (Namecheap, Cloudflare Registrar, GoDaddy…) — payment on their site.
2. In Laravel Cloud → *Domains* → add `midad.example.com` (and `www` if you like). It shows the DNS record(s) to create.
3. At your registrar's DNS panel, create exactly those records (usually a `CNAME` for `www`/subdomain, or the records Cloud shows for the root domain).
4. Wait for verification (minutes, sometimes up to a few hours). SSL is issued automatically.
5. Update `APP_URL` to `https://midad.example.com` and redeploy.

---

## Option B: Cloudflare Tunnel with your own domain (free, runs from this PC)

Gives a permanent `https://` address on your domain, but the site is only online while this computer is on and running the app. Fine for a demo; Option A is better for real users.

### B1. Requirements **[YOU]**
- A free Cloudflare account and a domain whose DNS is managed by Cloudflare (add the domain in the Cloudflare dashboard and change the nameservers at your registrar as instructed).
- `cloudflared` (already on this PC at `%LOCALAPPDATA%\Temp\cloudflared.exe`; for a permanent install use `winget install --id Cloudflare.cloudflared`).

### B2. Create the tunnel
```powershell
cloudflared tunnel login                      # [YOU] opens a browser; pick your domain
cloudflared tunnel create midad               # prints a tunnel ID and writes a credentials JSON in %USERPROFILE%\.cloudflared
cloudflared tunnel route dns midad midad.example.com
```
Create `%USERPROFILE%\.cloudflared\config.yml`:
```yaml
tunnel: midad
credentials-file: C:\Users\<you>\.cloudflared\<TUNNEL-ID>.json
ingress:
  - hostname: midad.example.com
    service: http://127.0.0.1:8127
  - service: http_status:404
```

### B3. Run the app in production mode, then the tunnel
In the project folder, with `.env` set as in `.env.production.example` (keep your SQLite database or point `DB_*` to MySQL), `FORCE_HTTPS=true`, `SESSION_SECURE_COOKIE=true`, `APP_URL=https://midad.example.com`:
```powershell
php artisan migrate --force
php artisan optimize
php artisan serve --host=127.0.0.1 --port=8127
# second terminal
cloudflared tunnel run midad
```
To keep it running after reboots: `cloudflared service install` (as Administrator), and run the PHP server with a scheduled task or a proper web server (Apache in XAMPP).

> Before running `php artisan test` on this machine again, run `php artisan optimize:clear`, otherwise the tests would read the cached production config.

### Quick, temporary link (no account needed)
```powershell
cloudflared tunnel --url http://127.0.0.1:8127
```
Prints a random `https://….trycloudflare.com` address that works while the command runs. It changes every time.

---

## Running on a classic Linux server / VPS instead

```sh
git clone https://github.com/<your-user>/midad.git /var/www/midad && cd /var/www/midad
cp .env.production.example .env        # then fill the [YOU] values
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan key:generate
php artisan migrate --force
php artisan optimize

# permissions: web server owns the writable folders, code is read-only
sudo chown -R www-data:www-data storage bootstrap/cache
sudo find storage bootstrap/cache -type d -exec chmod 775 {} \;
sudo find storage bootstrap/cache -type f -exec chmod 664 {} \;
sudo chmod 640 .env && sudo chown www-data:www-data .env
```
Point the web server's document root to `public/` (never the project root). Free SSL: `sudo certbot --nginx -d midad.example.com` (Let's Encrypt, auto-renews).

---

## Your existing content

The local database (`database/database.sqlite`: current users, quotes, likes) is **not** uploaded by git. A new host starts empty after `php artisan migrate --force`. If you want to keep the current content, decide before launch:
- **Start fresh** (simplest; accounts are re-created by users), or
- **Move the data**: ask for an export script, or, on a host with a persistent disk, keep `DB_CONNECTION=sqlite` and upload the file securely.

## After every deploy

```sh
php artisan migrate --force
php artisan optimize        # caches config, routes, events, views
```
If something looks stale: `php artisan optimize:clear` then `php artisan optimize`.
