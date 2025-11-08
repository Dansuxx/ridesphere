## Ridesphere — Copilot instructions for code edits

Short goal: make safe, focused changes to the Ridesphere PHP/JS monolith (XAMPP/htdocs-based) while preserving current runtime behavior. Use the specific file references below when making edits.

Key files & responsibilities
- `db.php` — single PDO-based DB connector. Return value may be null on failure; callers expect a PDO instance.
- `auth.php` — handles `signup` and `login` (JSON POST API). Passwords are migrated from plain/md5 to bcrypt when applicable.
- `vehicles.php`, `bookings.php`, `messages.php` — JSON POST API endpoints using action-based routing (`action` in request body). Follow existing parameter names (e.g., `owner_id`, `vehicle_id`, `renter_id`).
- `create_tables.php`, `setup_database.php` — schema creator; run these to initialize DB during local development.
- `index.php` + `script.js` — Single-page frontend. `script.js` uses `apiCall(endpoint, data)` and expects API endpoints at the same folder root (e.g., `auth.php`, `vehicles.php`).

Architecture & data flow
- Monolith served from XAMPP `htdocs/ridesphere/`. Frontend is static HTML/CSS/JS in `index.php` and `script.js`. Backend is PHP files that expose JSON APIs via POST bodies containing `action`.
- State passes from browser → `fetch` POST → PHP endpoint → PDO queries → JSON response. Example: `script.js` calls `bookings.php` with `{ action: 'create_booking', vehicle_id, renter_id, ... }`.

Project-specific conventions and gotchas
- Action routing: Each PHP endpoint inspects `$_POST` JSON and switches on `action`. Always preserve `action` names and parameter keys.
- Responses: Endpoints return JSON with `success` boolean and either `message` or payload (`user`, `vehicles`, `bookings`). Keep this contract stable.
- Authentication: There is no session; the frontend stores the user object returned by `auth.php` and passes `currentUser.id` in subsequent calls. Avoid introducing server-side session changes without updating the frontend.
- Password migration: `auth.php` supports legacy plain or MD5 passwords. When verifying a legacy password the code re-hashes to bcrypt and updates the DB — keep this flow when touching auth.
- Database connection: `db.php` returns null on failure. PHP scripts check for this and return a JSON error. Do not change to throw exceptions unless callers are updated.

Developer workflows (local)
- Run XAMPP (Apache + MySQL). Place project at `xampp/htdocs/ridesphere` (already the case).
- Create database and tables: open browser and visit `http://localhost/ridesphere/setup_database.php` (this runs `create_tables.php`).
- Debugging tips: enable XAMPP error display and check `error_log()` output in Apache/PHP logs. `script.js` logs API responses to console.

Patterns to follow when editing
- Keep API surface backwards-compatible: preserve `action` names, expected JSON fields and response shape.
- Use prepared statements (PDO) — existing code uses them widely; follow same style and error handling pattern (try/catch, error_log, return JSON).
- Frontend calls `apiCall(endpoint, data)` which stringifies `data`. Avoid changes that change parameter shapes (e.g., renaming `owner_id` → `ownerId`) unless you update all call sites in `script.js`.

Examples from the codebase
- Booking creation: `bookVehicle()` in `script.js` calls `bookings.php` with `{ action: 'create_booking', vehicle_id, renter_id, start_date, end_date, total_amount }` and expects `{ success: true, booking_id }`.
- Vehicle list: frontend calls `vehicles.php` with `{ action: 'get_vehicles' }` and receives `{ success: true, vehicles: [...] }` used by `renderVehicles()`.

Safe small tasks Copilot can do automatically
- Fix minor UI text, button labels, or add logging inside `script.js`.
- Add input validation checks on the frontend and mirror server-side checks in the relevant PHP endpoint.
- Small database schema tweaks that include migration SQL in `create_tables.php` and are backward compatible (add nullable columns, NOT drop/rename without migration plan).

When to ask the human
- Any change that changes API field names, authentication flow, or adds server-side sessions/CSRF protections.
- Schema migrations that rename or delete columns, or change foreign key behaviors.

Where to look for more context
- Start with `index.php` and `script.js` for user flows. For backend logic, open `auth.php`, `vehicles.php`, `bookings.php`, `messages.php`, and `db.php`.

If something seems missing
- If you need environment credentials, don't attempt to read secrets; ask the developer for the DB credentials and the intended runtime (XAMPP config).

Please review these instructions and tell me if you'd like additional rules (linting, tests, or a different API contract) added.

---

Recent changes (important for Copilot edits)
- Image upload: The frontend now supports image uploads using FormData and calls a multipart endpoint action named `add_vehicle_with_image` in `vehicles.php`. The backend writes uploaded files to the `uploads/` directory and stores `image_path` and `image_filename` in the DB. Some older code paths still support a legacy `image` LONGTEXT (base64) column — see schema and migration notes below.
- Toasts & modal confirms: UI no longer uses blocking browser `alert()`/`confirm()` calls. Instead use the `notify(message, type)` toast helper and `showConfirm(title, message)` modal (Promise-based) defined in `script.js` when adding or modifying UX flows.
- Booking lifecycle: When an owner marks a booking `completed`, the backend now sets the vehicle `status` back to `available`. Frontend refreshes both owner and renter vehicle lists after booking status changes to avoid stale state.
- Role-based UI: The owner/renter toggle is now only added for users with role `owner`. Do not add or rely on global toggles that allow a renter to view owner UI without proper role checks in the frontend (`showDashboard()` and related logic in `script.js`).

Files to check first when making edits
- `script.js` — central SPA logic. Many recent UX changes (image preview, FormData vehicle submission, notify/showConfirm, refreshed load flows) live here. Keep function names and parameter names stable (e.g., `addVehicle`, `loadVehiclesForRenter`, `updateBookingStatus`).
- `vehicles.php` — vehicle API endpoint. Supports both `add_vehicle` (legacy) and `add_vehicle_with_image` (multipart). Returns `image_path` and/or an `image_url` when listing. Preserve `action` names and JSON shapes.
- `bookings.php` — booking API endpoint. `update_booking_status` now toggles vehicle availability when appropriate. Preserve `action` and parameter names.
- `create_tables.php` — DB schema creator. New `image LONGTEXT` column may be present here for fresh installs.
- `migrations/add_image_column.php` — migration that safely adds the `image` LONGTEXT column if missing. Use it when you need the legacy column present.
- `index.php` — Single-page HTML. Note: embedded API handlers were removed to avoid header modification warnings. Keep API logic in the dedicated `*.php` endpoints.

API and data-contract notes (preserve these)
- Action routing: Continue using `action`-based requests via POST. Do not change action names unless you update all call sites in `script.js`.
- Image upload contract: Frontend sends a FormData POST to `/vehicles.php` with `action=add_vehicle_with_image` and a `vehicle_image` file field (plus other form fields like `owner_id`, `name`, `rate`, etc.). The endpoint responds with `{ success: true, vehicle_id, image_path }` on success and includes `image_url` when returning vehicle records.
- Responses: Keep returning `{ success: boolean, message?: string, ...payload }`. Frontend expects `success` and specific payload keys (e.g., `vehicles`, `bookings`, `user`).

DB and migration guidance
- Prefer adding nullable columns with migrations. The repo includes `migrations/add_image_column.php` which checks `INFORMATION_SCHEMA` and runs an `ALTER TABLE` if needed. For backward-compatible changes (add nullable columns), include a migration script in `migrations/` and update `create_tables.php` to match fresh installs.
- Avoid dropping or renaming columns without a migration plan. If a schema change affects API shapes, update `script.js` accordingly and document the change in this file.

UX & testing guidance
- When you replace a blocking `alert()`/`confirm()` call, wire the new flow to `notify()` and `showConfirm()` helpers. Both live in `script.js` and are used pervasively by the UI.
- After edits that affect availability or bookings, exercise the full flow manually locally: owner creates vehicle (with image), renter books, owner confirms/completes — verify vehicle `status` transitions and UI list refreshes. The frontend will call `loadOwnerVehicles()`, `loadVehiclesForRenter()`, and `loadVehicles()` to refresh data.

When to ask the human (unchanged + additions)
- Any change that changes API field names, authentication flow, or adds server-side sessions/CSRF protections.
- Schema migrations that rename or delete columns, or change foreign key behaviors.
- Changes to image storage strategy (move from filesystem `uploads/` to external object storage) — this requires credentials and deployment planning.

Where to look for more context (expanded)
- Start with `index.php` and `script.js` for user flows. For backend logic, open `auth.php`, `vehicles.php`, `bookings.php`, `messages.php`, and `db.php`.
- Check `migrations/` for small migration helpers and `create_tables.php` for the canonical fresh-install schema.

Quick local dev steps (reminder)
- Run XAMPP (Apache + MySQL) and place the project in `xampp/htdocs/ridesphere`.
- If your local DB is missing the legacy `image` column, run:
	- `php migrations/add_image_column.php`
- Use the browser to visit `http://localhost/ridesphere/setup_database.php` to run fresh schema creation when starting from scratch.

If you'd like, I can also add small tests or a quick smoke-test script that exercises: signup/login, owner adds vehicle (with/without image), renter lists & books, owner confirms/completes booking (and vehicle becomes available). Let me know which you'd prefer.
