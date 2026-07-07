# Project Bootstrap & Specifications

This document outlines setup steps, environment configurations, and active integrations.

## 1. Prerequisites
- **WordPress Environment:** Target compatibility WordPress 5.2+ (tested up to 6.6).
- **PHP version:** PHP 7.2 or higher.
- **MySQL version:** 5.6 or higher.

## 2. Setup & Installation
- Clone the repository into `wp-content/plugins/gnn-smtpmail/`.
- Activate the plugin from the WordPress Admin "Plugins" page.
- Activation automatically triggers the database logging table setup (`wp_gnn_smtpmail_logs`).

## 3. Database Schema Specification
- **Table Name:** `{wp_prefix}gnn_smtpmail_logs`
- **Columns:**
  - `id` (bigint, primary key, auto increment)
  - `logged_at` (datetime)
  - `channel` (varchar, custom/brevo)
  - `recipient` (text)
  - `subject` (text)
  - `status` (varchar, success/error)
  - `message` (text)

---

## 4. CI/CD Integration
- **GitHub Workflow:** `.github/workflows/release.yml`
- **Purpose:** Automatically package clean releases (zipping plugin, excluding IDE configs and local memory docs) on a manual `workflow_dispatch` trigger.
