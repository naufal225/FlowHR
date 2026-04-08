# Report Export Worker Guide

Report export async uses queue `reports` with database queue driver.

## Start Worker (Dev / PowerShell)

```powershell
php artisan queue:work --queue=reports,default --tries=1 --timeout=1800 --sleep=1
```

Run this in a separate terminal from the web server.

## Process One Job (Debug)

```powershell
php artisan queue:work --queue=reports,default --once -v
```

## Notes

- If panel status stays `queued`, worker is not running or cannot access queue table.
- Exports are generated in background and listed in the floating report export panel.
