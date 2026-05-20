# v1.2.0
## 05/20/2026

1. [](#new)
    * Per-IP rate limiting on the vote endpoint, configurable via `rate_limit_enabled` and `rate_limit_per_minute` (default: 10/minute)
    * Interval-gated auto-cleanup that sweeps rows with invalid ids from the database, configurable via `auto_cleanup_enabled` and `auto_cleanup_interval_hours` (default: every 24 hours)
1. [](#improved)
    * Validate vote `id` against a route-like allowlist (1-255 chars) to keep scanner payloads out of the database and options store
    * Allowlist `getAll()` `$order` column to prevent a latent ORDER BY injection in CLI usage
    * Drop the user-supplied value from the "invalid vote type" error message

# v1.1.0
## 05/01/2026

1. [](#improved)
    * Added 1.7|2.0 compatibility flags
    * Folded in pending working-tree changes (vendor refresh / minor PHP 8 modernization)

# v1.0.3
## 05/29/2024

1. [](#bugfix)
   * Compatibility fix for SimpleSearch [#1](https://github.com/trilbymedia/grav-plugin-likes-ratings/issues/1)

# v1.0.2
## 05/24/2024

1. [](#improved)
   * Removed unused 'use' statements

# v1.0.1
## 05/20/2024

1. [](#new)
   * Set the plugin icon
   
# v1.0.0
## 05/20/2024

1. [](#new)
    * ChangeLog started...
