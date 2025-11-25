# Repository Guidelines

## Project Structure & Module Organization
- Root contains the Electron shell (`main.js`, `preload.js`, `package.json`) that boots the kiosk UI through `npm start`.  
- `kiosk/ui/` hosts static HTML, CSS, JS, and assets for order, admin, config, and call screens; `kiosk/ui/config.json` stores the active API host/port read by Electron.  
- `kiosk/api/` provides PHP endpoints (e.g., `menu.php`, `save_order.php`, `get-orders.php`) plus SQL scripts and logs; `kiosk/api/index.php` routes `/api/*` requests when running `php -S`.  
- `kiosk/mysql/` holds the MySQL data directory for the bundled Docker service; keep it untouched unless maintaining databases.

## Build, Test, and Development Commands
- `npm install` (root): installs the Electron app dependencies.  
- `npm start`: launches the kiosk shell in development mode.  
- `php -S 0.0.0.0:8000 -t kiosk kiosk/api/index.php`: serves the PHP API and static UI for browser testing.  
- `composer install` (inside `kiosk/` when PHP libs are added): pulls future backend packages.  
- `npx electron-packager .` (optional): produces distributable binaries defined by the `build` stanza.

## Coding Style & Naming Conventions
- JavaScript/HTML: prefer 2-space indentation, ES modules or modern DOM APIs, and descriptive file names (`order.js`, `inventory.js`).  
- PHP: use 4-space indentation, `snake_case` database columns matching the schema in `kiosk/api/create_tables.sql---預刪`, and guard APIs with CORS headers similar to existing files.  
- Configuration files (`config.json`, `.env` when introduced) must not include secrets in commits; use sample files when documenting credentials.

## Testing Guidelines
- No automated harness yet; validate flows manually:  
  1. Start the PHP server, then the Electron shell.  
  2. Place an order via `order.html`, confirm DB inserts in `orders` and `order_items`.  
  3. Verify admin screens load via the same API host.  
- When adding tests, mirror page names (e.g., `order.test.js`) and aim for coverage around cart operations, API integrations, and DB migrations.

## Commit & Pull Request Guidelines
- Recent history favors short, descriptive messages (often prefixed with dates like `1116...`). Keep subjects under 72 chars and note the scope (`menu`, `reports`, etc.).  
- Each PR should link issues or roadmap items, describe affected modules, list manual-test steps (commands above), and attach screenshots for UI updates.  
- Include DB migration notes when schema changes occur, and mention config updates so installers know when to refresh `kiosk/ui/config.json`.

## Security & Configuration Tips
- Treat `kiosk/ui/config.json` as deployment-specific; avoid hardcoding production hosts in JS files.  
- Sanitize all API inputs (follow patterns in `menu.php`/`save_order.php`) and log errors to `kiosk/api/logs/` for traceability without exposing sensitive data to clients.
