# ADR 0013: Unified GNN GitHub Updater Standard

- **Status**: Accepted
- **Confidence**: Verified
- **Date**: 2026-11-21
- **Category**: Maintainability & Security
- **Supersedes**: [ADR 0007](0007-plugin-updates.md)
- **Superseded By**: None

## Context
The previous `GNN_SMTPMail_Updater` implementation in `gnn-smtpmail/inc/updater.php` had several gaps compared to the Unified GNN GitHub Updater standard:
1. No HTML/HEAD fallback when `api.github.com` is rate-limited or unreachable.
2. Failed JSON/version/URL validation paths returned `false` without setting the 300-second negative cache transient, and `get_local_version()` did not prefer `GNN_SMTPMAIL_VERSION`.
3. Asset matching used `strpos($asset->name, '.zip') !== false` instead of a strict `.zip` suffix check, semver regex did not support pre-release suffixes, and `*.githubusercontent.com` subdomains were not covered in `sanitize_github_url()`.
4. `check_for_update()` did not populate `$transient->no_update` or unset stale `response`/`no_update` entries.
5. `after_install()` checked `is_plugin_active()` post-install instead of capturing activation state in `upgrader_pre_install`, did not normalize paths with `wp_normalize_path()`, did not guard `is_wp_error($response)`/`is_wp_error($result)`, and did not call `wp_clean_plugins_cache(true)`.
6. `handle_manual_check()` lacked `403` response codes on `wp_die()` and did not `wp_die()` on insufficient `update_plugins` capability.

## Decision
Standardize `GNN_SMTPMail_Updater` (`gnn-smtpmail/inc/updater.php`) on the Unified GNN GitHub Updater architecture while preserving existing identifiers (`gnn_smtpmail_github_update_check`, `gnn_smtpmail_check_update`, `gnn_smtpmail_manual_update`):
1. Use `GNN_SMTPMAIL_VERSION` in `get_local_version()` with `get_plugin_data()` fallback.
2. Add `upgrader_pre_install` (`before_install`) to record `$this->was_active` before files are replaced, and restore activation + `wp_clean_plugins_cache(true)` in `after_install()`.
3. Add `wp_remote_head()` (`redirection => 0`) + `/releases/expanded_assets/{tag}` HTML scraping fallback when the GitHub REST API fails or is rate-limited.
4. Enforce semver tag validation, `.zip` suffix preference, SSRF HTTPS host allowlist (`sanitize_github_url()`), and 300s negative cache sentinel `(object)['failed' => true]`.
5. Populate `response` and `no_update` cleanly in `check_for_update()`, normalize filesystem paths in `after_install()`, and keep `clear_cache()` scoped strictly to `$this->transient_key`.

## Verification
- `php -l gnn-smtpmail/inc/updater.php` passed with zero syntax errors.


## Amendment (2026-11-25): Disk-First Local Version Detection
- **Root Cause:** Reading in-memory version constants (`GNN_*_VERSION` / `$this->version`) first inside `get_local_version()` returned the pre-upgrade version when WordPress fired `pre_set_site_transient_update_plugins` within the same PHP request right after overwriting plugin files, causing the update banner to persist until a second update run.
- **Resolution:** `get_local_version()` now reads the live `Version` header directly from `WP_PLUGIN_DIR . '/' . $this->plugin_basename` via `clearstatcache( true, $main_file )` and `get_file_data()` first, falling back to in-memory constants only if the file is unavailable.
