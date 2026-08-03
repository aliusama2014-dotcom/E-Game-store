# YOWA Gaming Store

A modern, secure, and dynamic e-commerce platform for digital games built with **PHP, React, MySQL, and Tailwind CSS** — no build step required, production-ready.

![License](https://img.shields.io/badge/license-MIT-blue.svg)
![PHP](https://img.shields.io/badge/PHP-8.3+-purple.svg)
![React](https://img.shields.io/badge/React-18-61dafb.svg)
![MySQL](https://img.shields.io/badge/MySQL-8.0+-blue.svg)

---

## 🎮 Features

### Storefront
- **Dynamic product browsing** — live search and category filtering without page reloads
- **Interactive shopping cart** — instant add-to-cart, quantity adjustments, and item removal
- **Wishlist / Favorites** — session-based, with heart-toggle on every product
- **Product ratings & reviews** — purchase-gated, only verified buyers can review
- **4-game hero carousel** — auto-rotating showcase with dot navigation
- **"Popular Right Now" rail** — ranked by actual review ratings

### Checkout & Payments
- **Secure checkout flow** — stock locking (database-level FOR UPDATE locks) prevents overselling
- **Mock Luhn-validated payment gateway** — test cards: `4242 4242 4242 4242` (approve), any card ending `0000` (decline)
- **Instant digital key delivery** — purchased games and keys available immediately on order confirmation
- **Order tracking** — customers can review order history with delivered keys

### Admin Dashboard
- **Product management** — full CRUD with optional discount pricing (compare-at-price)
- **Order management** — view all orders, update status
- **Real-time analytics** — user count, product count, order count, revenue totals
- **Sales dashboard** — latest orders at a glance

### Security
- **SQL injection prevention** — PDO prepared statements throughout, no string interpolation
- **Password security** — bcrypt hashing (PHP `password_hash()`), constant-time verification
- **CSRF protection** — token-based on every form, validated server-side
- **Session hardening** — `session_regenerate_id()` on login, secure cookie flags
- **Role-based access control** — customer vs. admin separation
- **PCI compliance-friendly** — card data never persisted (only last 4 digits + brand stored)
- **Error logging** — PHP errors logged to `storage/logs/php-error.log`, not displayed to users

### Performance
- **Zero build step** — no npm, webpack, or vite required; React loads via CDN
- **Progressive enhancement** — all pages work with JavaScript disabled
- **Lazy JSON API** — cart/product updates via fetch, full pages for no-JS fallback
- **Optimized images** — original generated placeholder art for new titles, centered with aspect-ratio containers

---

## 🏗️ Architecture

### Tech Stack
- **Backend:** PHP 8.3+, PDO (MySQL driver)
- **Database:** MySQL 8.0+ (or MariaDB 10.5+)
- **Frontend:** React 18 (UMD via CDN), Tailwind CSS 3 (via CDN)
- **Storage:** Session-based cart and wishlist (no localStorage)
- **Icons:** Inline SVG (no icon font dependencies)

### Project Structure
```
website/
├── config/
│   ├── config.php          # App constants, session config
│   └── database.php        # PDO connection singleton
├── includes/
│   ├── auth.php            # Login/logout, role checks
│   ├── cart.php            # Session-based shopping cart
│   ├── wishlist.php        # Session-based favorites
│   ├── functions.php       # Helpers, icons, CSRF, flash messages
│   ├── header.php          # HTML head + sidebar + topbar
│   └── footer.php          # HTML footer, React/JS scripts
├── api/
│   ├── products.php        # GET /api/products.php?q=&category=
│   ├── cart.php            # GET /api/cart.php
│   └── wishlist.php        # GET /api/wishlist.php
├── admin/
│   ├── index.php           # Dashboard
│   ├── products.php        # Product CRUD
│   ├── orders.php          # Order management
│   └── _layout_top.php / _layout_bottom.php
├── assets/
│   ├── css/
│   │   └── custom.css      # Tailwind supplements (buttons, toasts, wishlist)
│   ├── js/
│   │   ├── api.js          # Fetch wrapper around PHP endpoints
│   │   ├── toast.js        # Toast notification system
│   │   ├── product-grid.js # React component: live search/filter/cart
│   │   ├── cart-app.js     # React component: cart page interactions
│   │   └── main.js         # Non-React utilities (carousel, card formatting)
│   └── images/
│       └── generated/      # Original placeholder cover art (SVG)
├── database/
│   └── schema.sql          # MySQL schema, seed data (18 products, 9 categories)
├── storage/
│   └── logs/
│       └── php-error.log   # PHP error logging
├── [page].php              # Public pages (index, shop, product, cart, etc.)
├── [handler].php           # Form handlers (cart_add, cart_update, checkout, etc.)
└── README.md
```

### Database Schema
- **users** — account info, bcrypt password hash, role (customer/admin)
- **categories** — game genres (Action, RPG, Racing, Horror, etc.)
- **products** — game listings with optional discount pricing
- **product_keys** — pool of digital keys claimed on purchase
- **orders** — order headers (user, status, timestamps)
- **order_items** — line items per order
- **payments** — transaction records (last 4 digits, brand, reference only)
- **reviews** — 1–5 star ratings + comments (purchase-gated, unique per user per product)
- **contact_messages** — form submissions from the contact page
- **password_resets** — temporary hashed tokens for forgot-password flow

### API Endpoints

#### Read (GET)
- `GET /api/products.php?q=witcher&category=rpg&limit=24` — product listing with optional search/filter
- `GET /api/cart.php` — current session cart (lines + subtotal)
- `GET /api/wishlist.php` — favorited product IDs

#### Write (POST, dual-mode: form-post + fetch)
- `POST /cart_add.php` — add product, qty to cart
- `POST /cart_update.php` — change line-item quantity
- `POST /cart_remove.php` — remove product from cart
- `POST /wishlist_toggle.php` — add/remove from favorites

**Dual-mode:** Every POST endpoint checks for `X-Requested-With: fetch` header. If present, it returns JSON; otherwise, it does a server-side redirect + flash message (graceful no-JS fallback).

---

## 🚀 Getting Started

### Prerequisites
- PHP 8.3+
- MySQL 8.0+ or MariaDB 10.5+
- A web server (Apache, Nginx) or `php -S 127.0.0.1:8000` for local testing

### Installation

1. **Clone the repository**
   ```bash
   git clone https://github.com/yourusername/yowa-gaming-store.git
   cd yowa-gaming-store/website
   ```

2. **Set up the database**
   ```bash
   mysql -u root -p < database/schema.sql
   ```
   This creates the `website` database and seeds it with:
   - Admin account: `admin@yowa.test` / `Admin#12345`
   - 18 games across 9 categories
   - Digital key pool for testing

3. **Configure environment** (if needed)
   Edit `config/config.php`:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'website');
   define('DB_USER', 'root');
   define('DB_PASS', '');
   define('BASE_URL', 'http://localhost:8000/');
   define('APP_ENV', 'development'); // Set to 'production' to hide debug info
   ```

4. **Start a local server** (for testing)
   ```bash
   cd website
   php -S 127.0.0.1:8000
   ```
   Open `http://127.0.0.1:8000` in your browser.

5. **Deploy to production**
   - Copy the `website/` folder to your web server's document root
   - Set file permissions: `web/storage/logs/` should be writable by the PHP process
   - Update `BASE_URL` and `APP_ENV` in `config/config.php`
   - Use a real payment gateway instead of the mock Luhn validator
   - Set up a real email service for password reset links (currently shown on-screen in dev mode)

---

## 📦 Default Test Data

### Admin Account
- **Email:** `admin@yowa.test`
- **Password:** `Admin#12345`
- **Access:** `/admin/index.php` (dashboard, products, orders)

### Test Payment Card (Mock Gateway)
- **Approve:** `4242 4242 4242 4242` + any future expiry + any CVV
- **Decline:** Any card ending in `0000`

### Sample Games
- **Action:** The Last of Us, God of War, Grand Theft Auto V
- **RPG:** The Witcher 3, Crimson Vale Chronicles
- **Tactical:** Rainbow Six Siege, Ironclad Vanguard
- **Racing:** Velocity Circuit, Skyline Rivals
- **Horror:** Hollow Requiem, Nightfall Manor
- **Adventure:** Skyward Expanse, Emberfall Saga
- **Survival:** Driftline: Outlast, Wasteland Embers
- **Sports:** EA Sports FC 25
- **Battle Royale:** Fortnite, Call of Duty: Warzone

---

## 🎨 Design & UX

### Color Palette
- **Background:** `#210d18` (dark maroon)
- **Surface:** `#2a1220` (slightly lighter maroon)
- **Primary Accent:** `#ff5d82` (hot pink) → `#7b5cfa` (purple) gradient
- **Muted Text:** `#a58a9a` (taupe)

### Responsive Design
- **Mobile-first:** 2-column product grid, bottom navigation
- **Tablet:** 3-column grid, sidebar emerges
- **Desktop:** 4-column grid, fixed left sidebar, search in topbar

### Icons
All navigation and UI icons are **inline SVG** (no icon font), with live rendering:
- Home, Shop, Orders, Favorites, Admin Dashboard, Support
- Search, Cart, Notifications, Star ratings, Hearts, Plus/Minus

### Progressive Enhancement
Pages render fully with PHP alone (classic form submission, server-side redirect). React (loaded from CDN) then:
1. Detects the root elements (`#product-grid-root`, `#cart-root`, `#toast-root`)
2. Takes over interactivity without destroying the DOM
3. Sends `X-Requested-With: fetch` headers to tell the backend to return JSON
4. Updates the UI in place (no reload)

If JavaScript is disabled, everything still works via traditional form posts.

---

## 🔐 Security

### Built-in Protections
- **PDO Prepared Statements** — prevents SQL injection
- **Password Hashing** — bcrypt with PHP `password_hash()` / `password_verify()`
- **CSRF Tokens** — generated per session, validated on every POST
- **Session Security** — regenerated on login, secure cookie flags set
- **Role-Based Access Control** — `/admin/` pages check `is_admin()`
- **Input Validation** — sanitized via `htmlspecialchars()` (context-appropriate)
- **Error Logging** — sent to `storage/logs/`, not displayed to users in production

### Production Checklist
- [ ] Set `APP_ENV = 'production'` in `config/config.php`
- [ ] Replace mock payment gateway with real provider (Stripe, PayPal, etc.)
- [ ] Set up real email for password resets (not console output)
- [ ] Use HTTPS everywhere (redirect HTTP → HTTPS)
- [ ] Set `session.secure` and `session.httponly` flags in `php.ini`
- [ ] Restrict `/admin/` via web server auth or firewall rules
- [ ] Back up the database regularly
- [ ] Monitor `storage/logs/php-error.log` for issues

---

## 🛠️ Development

### Adding a New Product
1. Admin login at `/admin/index.php`
2. Navigate to **Products**
3. Fill in:
   - Name, Platform, Description
   - Price, Optional "Was" Price (for discount badge)
   - Stock quantity, Image path
   - Digital product checkbox (enables key delivery)
4. Click "Create Product"
5. If it's a paid game, add digital keys in the `product_keys` table:
   ```sql
   INSERT INTO product_keys (product_id, key_code) VALUES
   (19, 'YOUR-KEY-HERE-XXXX');
   ```

### Customizing Cover Art
New products with placeholder generated covers are in `assets/images/generated/*.svg`. To swap in real art:
1. Add your image to `assets/images/`
2. Update the product's `image_path` in the admin panel to point to it
3. The card component automatically centers it (no cropping)

### Adding a New Category
1. Database:
   ```sql
   INSERT INTO categories (name, slug) VALUES ('Strategy', 'strategy');
   ```
2. It immediately appears in filter pills on home, shop, and admin pages

### Extending the Payment Gateway
Replace the mock Luhn logic in `process_payment.php`:
```php
// Mock: just check card format
// Real: call Stripe API, handle webhooks, PCI compliance
$approved = luhn_validate($cardNum);
```

### Modifying the React Components
All React is in `assets/js/`:
- **product-grid.js** — search, filter, add-to-cart, wishlist
- **cart-app.js** — cart quantity/remove with live subtotal
- **toast.js** — global notification system
- **api.js** — fetch wrapper (auto-sets `X-Requested-With` header)

To port from `createElement` to JSX, add a build step (Vite, Webpack):
```bash
npm install react react-dom
npm run dev  # or build
```

---

## 📊 Performance

### Page Load
- Static assets cached by browser (CSS, JS, images)
- React/ReactDOM loaded from CDN (HTTP/2 push recommended)
- Initial PHP render includes product data in `data-config` JSON (no waterfall)

### Database
- Indexes on `products.slug`, `users.email`, `orders.user_id` for fast lookups
- Prepared statements prevent re-parsing
- `FOR UPDATE` locks on product stock during checkout (prevents race conditions)

### API
- Read endpoints return only necessary fields (not entire product rows)
- Pagination via `LIMIT` (default 24, configurable)
- No N+1 queries; reviews/ratings joined in one pass

---

## 🤝 Contributing

We welcome contributions! Please:
1. Fork the repo
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

Please ensure:
- PHP files pass `php -l` syntax check
- No hardcoded database credentials
- Security best practices (prepared statements, validation)
- Tests pass for any added features

---

## 📝 License

This project is licensed under the **MIT License** — see the [LICENSE](LICENSE) file for details.

---

## 📧 Support & Contact

For issues, questions, or suggestions:
- **Email:** support@yowa.test (via contact form on the site)
- **GitHub Issues:** [Create an issue](https://github.com/yourusername/yowa-gaming-store/issues)

---

## 🙏 Acknowledgments

- **React** for the UMD build that makes zero-build-step development possible
- **Tailwind CSS** for the utility-first design system
- **PHP PDO** for safe database access
- Inspired by modern gaming marketplaces (Steam, Epic Games Store)

---

## 📚 Additional Resources

- [PHP Security Guide](https://www.php.net/manual/en/security.php)
- [OWASP Top 10](https://owasp.org/www-project-top-ten/)
- [React Documentation](https://react.dev)
- [Tailwind CSS Docs](https://tailwindcss.com/docs)
- [MySQL Security](https://dev.mysql.com/doc/refman/8.0/en/security.html)

---

## 🎯 Roadmap

- [ ] Real payment gateway integration (Stripe/PayPal)
- [ ] Email notifications (order confirmation, password reset)
- [ ] User profile page (edit name, change password)
- [ ] Advanced admin analytics (revenue trends, top products)
- [ ] Wishlist sharing (public lists)
- [ ] Game bundles (buy multiple games at a discount)
- [ ] Coupon codes / promotional campaigns
- [ ] Two-factor authentication (2FA)
- [ ] Mobile app (React Native)

---

**Happy gaming! 🎮**

Made with ❤️ by the YOWA Team
