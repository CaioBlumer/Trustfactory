# Simple E-commerce Shopping Cart (Laravel + Vue + Tailwind)

This project is a simple e-commerce shopping cart built on Laravel 12 with the Breeze Vue starter kit (Inertia.js + Vue 3). It demonstrates product browsing, user-scoped cart operations, a checkout flow that creates orders, and background jobs for low-stock alerts and daily sales reporting.

## Requirements coverage (mapping)

### 1) Starter kit + authentication
- **Requirement:** Use Laravel starter kit and built-in auth.
- **Implementation:** Laravel Breeze (Vue/Inertia) scaffolding.
- **Code:** `routes/auth.php`, `resources/js/Pages/Auth/*`, `app/Http/Controllers/Auth/*`

### 2) Products with name, price, stock_quantity
- **Requirement:** Product has name, price, stock quantity.
- **Implementation:** `products` table with `name`, `price`, `stock_quantity`.
- **Code:** `database/migrations/2026_01_04_163147_create_products_table.php`, `app/Models/Product.php`

### 3) User-scoped cart (no session/local storage)
- **Requirement:** Cart belongs to authenticated user, stored in DB.
- **Implementation:** `cart_items` table with `user_id` + `product_id` and a unique constraint; all queries scoped by `auth()->id()` or `$request->user()`.
- **Code:** `database/migrations/2026_01_04_163148_create_cart_items_table.php`, `app/Models/CartItem.php`, `app/Http/Controllers/CartController.php`

### 4) Browse, add, update, remove items
- **Requirement:** User can browse products, add to cart, update quantities, remove items.
- **Implementation:** Products page with add-to-cart form; Cart page with update/remove actions.
- **Code:** `resources/js/Pages/Products/Index.vue`, `resources/js/Pages/Cart/Index.vue`, routes in `routes/web.php`

### 5) Low stock notification (job + queue)
- **Requirement:** When stock is low, trigger a job and email admin.
- **Implementation:** During checkout, stock is decremented inside a DB transaction; if remaining stock is <= threshold, a queued job is dispatched.
- **Code:** `app/Jobs/SendLowStockNotification.php`, `app/Mail/LowStockNotification.php`, `resources/views/emails/low-stock.blade.php`, threshold in `config/shop.php`

### 6) Daily sales report (scheduled job)
- **Requirement:** Scheduled job runs nightly and emails products sold that day.
- **Implementation:** `SendDailySalesReport` job aggregates order items for the current day and emails the summary.
- **Code:** `app/Jobs/SendDailySalesReport.php`, `app/Mail/DailySalesReport.php`, `resources/views/emails/daily-sales-report.blade.php`, scheduled in `routes/console.php`

### 7) Styling: Tailwind CSS
- **Requirement:** Tailwind styling.
- **Implementation:** Breeze + Tailwind; pages use utility classes.
- **Code:** `resources/css/app.css`, `tailwind.config.js`

## Architecture overview (high-level Laravel flow)

1) **Routing -> Controllers**
   - Routes defined in `routes/web.php` map to `ProductController` and `CartController`.
2) **Controllers -> Eloquent**
   - Controllers query Eloquent models (`Product`, `CartItem`, `Order`, `OrderItem`) and return Inertia responses.
3) **Inertia -> Vue Pages**
   - Inertia renders Vue pages in `resources/js/Pages/...`.
4) **Auth Middleware**
   - All shop/cart routes sit behind the `auth` middleware to ensure user-scoped data.
5) **Jobs + Scheduler**
   - Low stock job is queued when stock falls below threshold.
   - Daily sales report is scheduled nightly by the Laravel scheduler.

## Data model

### Tables
- **products**
  - `name`, `price`, `stock_quantity`
- **cart_items**
  - `user_id`, `product_id`, `quantity`
  - Unique constraint on (`user_id`, `product_id`)
- **orders**
  - `user_id`, `total`, `status`, `placed_at`
- **order_items**
  - `order_id`, `product_id`, `quantity`, `unit_price`, `subtotal`

### Relationships
- `User` has many `CartItem` and `Order`
- `Product` has many `CartItem` and `OrderItem`
- `Order` has many `OrderItem`

## Key flows

### Product browsing
- `GET /products` returns `Products/Index.vue` with product listing.
- Users can select quantity and add to cart.

### Cart management
- `POST /cart`: add to cart (merges by user + product).
- `PATCH /cart/{cartItem}`: update quantity.
- `DELETE /cart/{cartItem}`: remove item.
- All actions are scoped by the authenticated user.

### Checkout
- `POST /checkout`
- Process:
  1) Lock cart items and products in a DB transaction.
  2) Validate stock.
  3) Create order + order items.
  4) Decrement product stock.
  5) Dispatch low-stock job for products at/below threshold.
  6) Clear cart items.
- Code: `app/Http/Controllers/CartController.php`

## Jobs and scheduling

### Low stock notification (queue)
- **Trigger:** During checkout if `stock_quantity <= LOW_STOCK_THRESHOLD`.
- **Job:** `SendLowStockNotification`
- **Mail:** `LowStockNotification`
- **Config:** `config/shop.php`, `.env` (`ADMIN_EMAIL`, `LOW_STOCK_THRESHOLD`)

### Daily sales report (scheduler)
- **Trigger:** Nightly at 20:00 server time.
- **Job:** `SendDailySalesReport`
- **Mail:** `DailySalesReport`
- **Schedule:** `routes/console.php`

## Mail setup (Mailpit)

Mail is configured to send via SMTP to Mailpit:
- SMTP: `127.0.0.1:1025`
- UI: `http://localhost:8025`

See `.env` for the current mail settings.

## Frontend stack

- Vue 3 + Inertia.js (Breeze)
- Tailwind CSS
- Vite for dev build

## Running the app

1) Install PHP and Node dependencies
   ```bash
   composer install
   npm install
   ```
2) Set up environment
   - Update `.env` DB credentials
   - Generate APP_KEY
   ```bash
   php artisan key:generate
   ```
3) Run migrations + seed
   ```bash
   php artisan migrate --seed
   ```
4) Start servers
   ```bash
   php artisan serve
   npm run dev
   ```
5) Queue worker (for emails)
   ```bash
   php artisan queue:work
   ```
6) Scheduler (local)
   ```bash
   php artisan schedule:work
   ```

### Default seeded user
- Email: `test@example.com`
- Password: `password`

### Key URLs
- App (Laravel): `http://127.0.0.1:8000`
- Products: `http://127.0.0.1:8000/products`
- Cart: `http://127.0.0.1:8000/cart`
- Mailpit UI: `http://localhost:8025`

## Notes for presentation

- **Best practices:** Eloquent relationships, server-side validation, DB transactions, queues, and scheduler.
- **Security:** All cart operations are protected by auth middleware and scoped to the current user.
- **Extensibility:** Admin email + low-stock threshold are centralized in `config/shop.php` and `.env`.

