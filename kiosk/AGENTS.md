# Repository Guidelines

## Project Structure & Module Organization
- `api/` hosts PHP endpoints (e.g., `menu.php`, `save_order.php`) and shared utilities such as `db.php` plus the request router in `index.php`.
- `ui/` contains static client assets: HTML entry points (`order.html`, `admin.html`), `js/` modules, `css/` stylesheets, and images within `img/` and `img/products`.
- `data/` and `logs/` hold generated exports and server logs; `mysql/` persists MySQL state when composing; `create_tables.sql` seeds the schema for fresh environments.

## Build, Test, and Development Commands
- `php -S 0.0.0.0:8000 -t . api/index.php` serves the app locally from this directory, routing `/api/**` to PHP endpoints.
- `docker compose up --build` starts the PHP + MySQL stack defined in `docker-compose.yml`; the web server exposes http://localhost:8080 and the database on 3306.
- `php api/test_db.php` verifies database connectivity and credentials before running end-to-end flows.

## Coding Style & Naming Conventions
- Follow PSR-12 style for PHP: 4-space indentation, snake_case variables, early returns on error, and shared helpers kept in `api/` for reuse.
- JavaScript in `ui/js/` favors module-like files with `const`/`let`, arrow functions, and camelCase identifiers; keep DOM IDs and CSS classes kebab-case to match existing templates.
- Store configuration JSON (e.g., `ui/config.json`, `api/system-setting.json`) with lowercase keys and document any additions in file headers.

## Testing Guidelines
- Manual regression is expected: exercise ordering via `ui/order.html`, kitchen updates from `ui/call.html`, and sales reporting at `ui/sales-report.html` after each change set.
- Use `curl http://localhost:8000/api/menu.php` or browser devtools to confirm API responses stay backward compatible; log unexpected responses to `api/logs/`.
- Add lightweight PHP sanity scripts alongside `api/test_db.php` when introducing new services, and remove them before merging if they are one-off diagnostics.

## Commit & Pull Request Guidelines
- Reuse the existing pattern `v<major>,<minor>: short imperative summary` (see `git log --oneline`) and keep commits scoped to one feature or fix.
- Each PR should describe the user-facing impact, list affected endpoints or pages, attach before/after screenshots for UI updates, and link any tracking ticket.
- Include verification notes (commands run, sample order ID) so reviewers can replay your testing steps quickly.

## Configuration & Security Notes
- Store secrets using environment variables loaded by Docker; never commit `.env` files or real credentials under `api/` or `ui/`.
- Regenerate seeded data by re-running `create_tables.sql` inside the MySQL container: `docker exec -i pos-mvp-db mysql -uroot -proot pos_db < create_tables.sql`.
