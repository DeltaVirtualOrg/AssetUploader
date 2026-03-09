# DVAssetUploader (phpVMS v7)

Admin-only module for **phpVMS v7** that lets staff upload files into **pre-approved (whitelisted) folders** on your server (ex: SPTheme banners, awards, tours, events, downloads). Each destination (“target”) can enforce its own file types, max size, and helper rules.

This build is based on the **DVAssetUploader** module skeleton (phpVMS-style providers) so **phpVMS Updater** can discover and run module migrations.

---

## Features

- Upload to **approved folders** (“targets”) you define
- Per-target rules:
  - allowed file extensions
  - max file size
  - naming mode: original or unique
  - optional overwrite (if enabled)
  - optional UI hint (ex: required image dimensions)
- Logs uploads to DB table `asset_uploads` (shows “Recent Uploads”)
- Adds an **Admin → Addons** link
- Safe destination handling (prevents saving outside allowed roots)

---

## Requirements

- phpVMS v7
- PHP 8.1+
- Writable target folders (permissions)
- Recommended: access to phpVMS **Updater** (`/update`) for migrations

---

## Installation (recommended: no SSH)

> **Important:** avoid nested folders. After install, you must have:
> `modules/DVAssetUploader/module.json` (not `modules/DVAssetUploader/DVAssetUploader/module.json`)

1. Upload/unzip the module to:
   ```
   /modules/DVAssetUploader
   ```

2. Enable the module:
   - Admin → **Addons → Modules** → Enable **DVAssetUploader**

3. Run migrations via phpVMS Updater:
   - Visit: `https://your-site.com/update`

4. Clear cache (recommended):
   - Admin → Maintenance → **Clear Application Cache**
   - Or (CLI): `php artisan optimize:clear`

5. Open the uploader:
   - `https://your-site.com/admin/asset-uploader`

---

## Migrations (how phpVMS runs them)

This module registers migrations the phpVMS way:

- `Providers/AppServiceProvider.php` calls:
  ```php
  $this->loadMigrationsFrom(__DIR__ . '/../Database/migrations');
  ```

When you visit **`/update`**, phpVMS runs pending migrations (including module migrations) and will create:

- `asset_uploads` table

### If /update does not run the migration

Some hosts block updater access or caching can interfere. Try:

1) Clear bootstrap caches:
- delete `bootstrap/cache/*.php` (or run `php artisan optimize:clear`)

2) Revisit `/update`

3) If you have CLI access, you can run ONLY this module’s migration:
```bash
php artisan migrate --path=modules/DVAssetUploader/Database/migrations --force
```

---

## Configuration

Edit:
```
/modules/DVAssetUploader/Config/config.php
```

### Middleware (important)
Default:
```php
'middleware' => ['web', 'auth', 'role:admin'],
```

✅ `role:admin` is recommended for phpVMS admin addon routes.

> Laratrust `ability` middleware requires roles + permissions parameters. Avoid `ability:admin` (it will throw “Too few arguments…”).

---

## Upload Targets

Targets are defined in the config under `targets`.

Example:

```php
'targets' => [

  'sptheme_banners' => [
    'label' => 'SPTheme - Banner Images',
    'base'  => 'public',
    'path'  => 'SPTheme/images/banner',
    'allowed_extensions' => ['jpg','jpeg','png','webp','gif'],
    'max_size_kb' => 8192,
    'naming' => 'unique',
    'overwrite' => false,
  ],

  'sptheme_awards' => [
    'label' => 'SPTheme - Award Images',
    'hint'  => 'Awards MUST be 250px × 250px.',
    'base'  => 'public',
    'path'  => 'SPTheme/images/awards',
    'allowed_extensions' => ['png','jpg','jpeg','webp'],
    'max_size_kb' => 8192,
    'naming' => 'unique',
    'overwrite' => false,
  ],

  'sptheme_tours' => [
    'label' => 'SPTheme - Tour Graphics',
    'hint'  => 'Tour images MUST be 1024px × 1024px.',
    'base'  => 'public',
    'path'  => 'SPTheme/images/tours',
    'allowed_extensions' => ['png','jpg','jpeg','webp'],
    'max_size_kb' => 12288,
    'naming' => 'unique',
    'overwrite' => false,
  ],

];
```

### `base` values
- `public` → uploads under `public_path()` (recommended for theme assets)
- `storage` → uploads under `storage_path()` (non-public files)

---

## Troubleshooting

### “Class … AppServiceProvider not found”
This usually means the module is installed in a nested folder. Verify:
- `modules/AssetUploader/module.json` exists
- `modules/AssetUploader/Providers/AppServiceProvider.php` exists

If you see `modules/AssetUploader/AssetUploader/...`, move contents up one level.

Clear caches:
- delete `bootstrap/cache/*.php`, then reload.

### “Too few arguments to Laratrust\Middleware\Ability::handle()”
Your middleware is set to `ability:admin`. Change it to:
```php
'middleware' => ['web','auth','role:admin']
```

### “Table asset_uploads doesn't exist”
Run `/update` after enabling the module. If needed (CLI):
```bash
php artisan migrate --path=modules/DVAssetUploader/Database/migrations --force
```

---

## Development Notes

- Routes: `modules/DVAssetUploader/Http/Routes/admin.php`
- Controller: `modules/DVAssetUploader/Http/Controllers/Admin/AssetUploaderController.php`
- View: `modules/DVAssetUploader/Resources/views/admin/index.blade.php`
- Config: `modules/DVAssetUploader/Config/config.php`
- Migration: `modules/DVAssetUploader/Database/migrations/*create_asset_uploads_table.php`

---

## License

MIT
