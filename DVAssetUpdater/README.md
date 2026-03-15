# DVAssetUpdater (phpVMS v7)

## Changelog

### 2026-03-15
- Fixed upload filename handling so files no longer receive unwanted timestamp/random suffixes unless a real filename conflict exists
- Updated `AssetUploaderController.php` to preserve original/custom filenames more reliably
- Improved collision handling for both unique and non-unique naming modes
- Cleaned up leftover legacy `AssetUploader` references and standardized naming under `DVAssetUpdater`
- Reworked the admin upload page with a cleaner, more modern, and more readable interface
- Fixed blank target metadata fields by replacing the old script approach with a more reliable inline JSON-driven implementation
- Improved recent uploads display with better file details and easier scanning
- Increased readability across the admin page with larger text, stronger contrast, and better spacing

Admin-only module that uploads files into *whitelisted* destinations (targets), like:
- `/public/SPTheme/images/banner`
- `/public/SPTheme/images/awards`
- `/public/downloads`

## Install
1. Upload / unzip to: `modules/DVAssetUpdater`
2. (Optional) Publish config:
   - `php artisan vendor:publish --tag=dvassetupdater-config`
3. Run migrations:
   - `Visit /update (preferred) or run:

php artisan migrate --path=modules/DVAssetUpdater/Database/migrations --force`
4. Visit:
   - `/admin/asset-uploader`

## Configure destinations
Edit:
- `modules/DVAssetUpdater/Config/config.php`

Add/modify targets under `targets`.

## Admin middleware
By default the routes use:
- `['web','auth','ability:admin']`

If your install uses a different admin middleware, change:
- `dvassetupdater.middleware` in the config.

## Notes
- Uploads are validated by extension and size per target.
- Destination paths are constrained to stay under the chosen root (`public_path()` by default).
- Successful uploads are logged in `asset_uploads`.

## Dimension reminders (UI)
You can add a `hint` to any target in the config to display rules like required dimensions (e.g., awards 250×250, tours 1024×1024).

## Controller base class note
phpVMS installs commonly use `App\\Contracts\\Controller` as the base controller for modules. If you see `Class "App\\Http\\Controllers\\Controller" not found`, update your module controllers to `use App\\Contracts\\Controller;`.

## Laratrust middleware note
If you see `Too few arguments to function Laratrust\Middleware\Ability::handle()` it means your route middleware is using `ability:admin` (invalid by itself). Use `role:admin` (recommended) or provide full ability params (roles, permissions, validate_all).
