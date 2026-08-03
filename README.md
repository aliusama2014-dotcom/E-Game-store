# YOWA Gaming Store — Rebuilt

A secure, modern rebuild of the original YOWA e-games store. Same dark/cyan gaming
aesthetic, entirely new foundation underneath.

This has been tested end-to-end against a real MySQL/MariaDB instance (schema load,
signup → login → cart → checkout → mock payment → digital key delivery → reviews,
plus admin login/product/order management) — every flow below is confirmed working,
not just written. You should still re-test on your own XAMPP setup before going live,
since environments differ.

---

## 1. Setup on XAMPP

1. Copy this whole `website/` folder into your XAMPP `htdocs/` directory.
2. Start **Apache** and **MySQL** from the XAMPP control panel.
3. Open phpMyAdmin (or the `mysql` CLI) and import `database/schema.sql`. This creates
   the `website` database, all tables, and seed data (sample games, categories, a demo
   admin account, and a few digital keys ready to be claimed).
4. If your MySQL root user has a password (XAMPP's default is usually blank), set it as
   an environment variable, or just edit `config/config.php` directly:
   ```php
   define('DB_USER', getenv('DB_USER') ?: 'root');
   define('DB_PASS', getenv('DB_PASS') ?: 'your_password_here');
   ```
5. Visit `http://localhost/website/index.php`.

**Default admin login:** `admin@yowa.test` / `Admin#12345` — change this password
immediately after your first login in a real deployment (Account → the reset-password
flow works even while logged in, since it doesn't require the old password).

**Test payment card** (mock gateway, no real charge ever occurs):
- `4242 4242 4242 4242`, any future expiry, any CVV → **approved**
- Any card number ending in `0000` → **declined** (for testing the failure path)

---

## 2. What changed, and why

### Database (Phase 1)
The original schema was lost, so it's rebuilt from scratch in `database/schema.sql`:
`users`, `categories`, `products`, `product_keys`, `orders`, `order_items`, `payments`,
`reviews`, `password_resets`, `contact_messages` — with proper foreign keys, an InnoDB
engine, and utf8mb4 throughout.

### Security (Phase 2) — the important part
The legacy code had several serious issues that are fixed here, not patched over:

| Legacy issue | Fix |
|---|---|
| `mysqli->query()` with raw string interpolation everywhere (`sign_in.php`, `sign_up.php`, `changepass.php`, `payment.php`, `paymentcon.php`) — classic SQL injection | Every query now goes through **PDO prepared statements** (`PDO::ATTR_EMULATE_PREPARES => false`), via `config/database.php` |
| Passwords stored and compared as **plaintext** | `password_hash()` / `password_verify()` (bcrypt) |
| `changepass.php` let anyone update *any* row where `old_password` matched a plaintext string — no session check at all | Replaced with a proper token-based forgot-password flow (`changepass.php` → `reset_password.php`), tokens are hashed, single-use, and expire in 30 minutes |
| **Full credit card numbers stored in the database** (`payment.php` → `payment` table), then read back for a second "confirmation" (`paymentcon.php`) — a serious PCI/security problem | Replaced entirely with a mock gateway (`process_payment.php`) that validates card format (Luhn check) in memory only and **never stores the PAN or CVV** — only a masked last-4 and a fake transaction reference |
| No session hardening, no CSRF protection anywhere | httponly session cookies, `session_regenerate_id()` on login, CSRF tokens on every form (`includes/functions.php`) |
| No role/access control — nothing stopped any signed-in user from doing admin actions | `role` column (`customer`/`admin`) + `require_admin()` guarding the whole `/admin` area |
| No error logging — warnings/notices just vanished or leaked to the page | Centralized logging to `storage/logs/php-error.log`; `display_errors` is env-gated (`config/config.php`) |
| `contact_us.html` had a form with **no backend at all** | `contact_us.php` now validates and stores submissions in `contact_messages` |

### Cart & checkout (Phase 2)
Cart lives in the session (`{product_id: qty}`), but is **never trusted directly** —
`cart_resolve()` always re-reads current price/stock from the database before showing
totals. Checkout re-verifies stock with `SELECT ... FOR UPDATE` inside a transaction
before an order is ever written, so two people can't both buy the last unit.

### Frontend (Phase 3)
- Tailwind CSS (via CDN) with a custom theme matching the original palette
  (`bg`, `surface`, `accent` cyan, `accent2` blue — see `includes/header.php`).
- `includes/header.php` / `includes/navbar` (built into header) / `includes/footer.php`
  are now shared partials — no more the same nav bar copy-pasted into 8 files.
- Responsive product grid, search + category filter on `shop.php`, game detail pages
  with reviews on `product.php`.

### New features (Phase 4)
- **Digital key delivery** — `product_keys` holds a pool of keys per product; on
  approved payment, keys are claimed (or freshly minted if the pool runs short) and
  shown on the order confirmation / profile pages.
- **Reviews & ratings** — gated to users who actually have a *paid* order containing
  that product; one review per user per product (DB-level unique constraint).
- **Mock payment gateway** — see security table above.

---

## 3. Directory structure

```
website/
├── config/           app config + PDO connection
├── includes/          shared header/footer, auth, cart, helper functions
├── admin/              admin dashboard, product CRUD, order management
├── assets/             images, css, js
├── database/            schema.sql (run this to set up MySQL)
├── storage/logs/         php-error.log gets written here
├── index.php, shop.php, product.php, cart.php, checkout.php,
│   payment.php, process_payment.php, order_confirmation.php,
│   sign_in.php, sign_up.php, logout.php, changepass.php,
│   reset_password.php, profile.php, contact_us.php, add_review.php
```

Files intentionally **removed** from the legacy set: `paymentcon.php` / `paymentcon.html`
(the insecure "look up stored card by matching fields" confirmation step) and
`testing.php` (a leftover connection-test page) — both are superseded by the new
checkout → payment → confirmation flow.

---

## 5. Visual redesign (v2)

The storefront was restyled to match a reference design: a dark maroon/plum theme
with a pink-to-purple gradient accent, a fixed icon sidebar (Home / Shop / My Orders /
Favorites, plus Support pinned to the bottom), a topbar with search + notifications +
cart + profile, a hero carousel highlighting real discounted titles, and a "Popular
Right Now" rail driven by actual review ratings.

Two small, honest additions were made to support it rather than faking the visuals:

- **`compare_at_price` column** on `products` — powers the "was / now" discount
  pricing and Save-X% badges. It's optional; leave it blank in the admin product form
  for a regular (non-discounted) listing.
- **Wishlist / Favorites** — session-based, same lightweight pattern as the cart. The
  heart icon on every product card and the sidebar's Favorites tab both use it.

**If you already imported the original `schema.sql`,** re-import it (it's `DROP
TABLE IF EXISTS` throughout, so this resets product/order data), or run:
```sql
ALTER TABLE products ADD COLUMN compare_at_price DECIMAL(10,2) NULL AFTER price;
```

The notification bell badge shows the signed-in user's own pending (unpaid) orders —
a real signal, not a decorative counter.

---

## 6. Making it dynamic (v3)

Three things changed here: the admin dashboard became reachable again, images stopped
getting cropped, the catalog grew, and — the big one — the storefront is now
interactive instead of doing a full page reload for every click.

**Admin dashboard fix.** The dashboard page itself was never deleted — the redesign
in v2 just didn't add a link to it anywhere. Admins now see a Shield icon in the
sidebar (desktop) and bottom nav (mobile), visible only when `is_admin()`.

**Images.** Every thumbnail (cards, product page, cart, order confirmation, Popular
Right Now) switched from cropped `object-cover` to a centered, padded `object-contain`
treatment against a soft backdrop, so any image — landscape, portrait, whatever —
sits fully visible instead of getting cut off.

**Catalog.** Added 10 new titles across 4 new categories (Racing, Horror, Adventure,
Survival — matching the reference's category list) plus renamed Shooter→Tactical.
Since real box art is copyrighted and none was provided for these, cover art for the
new titles is **original, generated placeholder art** (`assets/images/generated/`) —
gradient + typography, not photography. Swap in real art any time by updating a
product's `image_path` in the admin panel.

**Dynamic layer — React, no build step.** The site now has a real JSON API
(`api/products.php`, `api/cart.php`, `api/wishlist.php`) and every mutating endpoint
(`cart_add.php`, `cart_update.php`, `cart_remove.php`, `wishlist_toggle.php`) is
dual-mode: a classic form POST still works exactly as before (redirect + flash
message, zero JS required), but a `fetch()` call with an `X-Requested-With: fetch`
header gets JSON back instead. React (loaded from a CDN, no npm/webpack/vite needed)
takes over three pieces of UI on top of that:

- **Product grid** (home, shop, favorites) — typing in search or clicking a category
  pill re-fetches and re-renders the grid in place instead of reloading the page;
  add-to-cart and the wishlist heart update instantly with a toast, no navigation.
- **Cart page** — quantity +/− and remove happen in place with a live subtotal.
- **Toast notifications** — replace the old full-page flash banner for these actions.

This is deliberately built with plain `React.createElement` calls (see
`assets/js/*.js`) instead of JSX, specifically so there's **no build step** — just
`<script>` tags, matching how Tailwind is already loaded via CDN elsewhere in this
project. The trade-off: the code is more verbose than JSX would be, and you're
loading React's production UMD bundle (~140KB) rather than a tree-shaken bundle. If
you later add a real Node build pipeline (Vite, etc.), this is straightforward to
port to JSX and you can drop the UMD CDN tags.

**Progressive enhancement, not a replacement.** Every one of these pages still fully
works with JavaScript disabled — the PHP renders real `<form>`s and `<a>` links first;
React, if it loads, replaces that markup with an equivalent interactive version. This
also means search engines and no-JS browsers see complete, working pages.

---

## 7. Known limitations / next steps

- The mock payment gateway is for demo purposes — swap `process_payment.php`'s logic
  for a real provider's SDK (Stripe, PayPal, etc.) before ever taking real payments.
- Emails (password reset, order receipts) aren't actually sent — no mail server is
  configured. In development, the reset link is shown directly on-screen instead of
  emailed; wire up PHPMailer/SMTP for production.
- Login throttling is a simple per-session counter, not IP-based — fine for a course
  project, not sufficient hardening for a public production site.
- Tailwind is loaded from the CDN for simplicity; for production, install the Tailwind
  CLI and build a purged stylesheet instead.
