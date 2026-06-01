# v1.2.1
## 06/01/2026

1. [](#bugfix)
    * Fixed a duplicated label when a custom template wraps the widget, by replacing only the rating element on each vote [#5](https://github.com/trilbymedia/grav-plugin-likes-ratings/issues/5)
    * Stopped vote clicks from being counted more than once after the first vote
    * Removed a broken error-message assignment that never displayed anything

# v1.2.0
## 05/20/2026

1. [](#new)
    * Per-IP rate limiting on the vote endpoint, configurable via `rate_limit_enabled` and `rate_limit_per_minute` (default: 10/minute) [#4](https://github.com/trilbymedia/grav-plugin-likes-ratings/issues/4)
    * Interval-gated auto-cleanup that sweeps rows with invalid ids from the database, configurable via `auto_cleanup_enabled` and `auto_cleanup_interval_hours` (default: every 24 hours)
1. [](#improved)
    * Validate vote `id` against a route-like allowlist (1-255 chars) to keep scanner payloads out of the database and options store [#4](https://github.com/trilbymedia/grav-plugin-likes-ratings/issues/4)
    * Allowlist `getAll()` `$order` column to prevent a latent ORDER BY injection in CLI usage [#4](https://github.com/trilbymedia/grav-plugin-likes-ratings/issues/4)
    * Drop the user-supplied value from the "invalid vote type" error message [#4](https://github.com/trilbymedia/grav-plugin-likes-ratings/issues/4)

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
