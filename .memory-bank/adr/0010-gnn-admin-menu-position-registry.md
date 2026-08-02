# ADR 0010: GNN Product Family Admin Menu Position Registry

- **Status:** Accepted
- **Confidence:** Verified
- **Date:** 2026-08-02

## Context
GNN is a growing family of separately-developed WordPress themes and plugins (this plugin, plus sibling products such as GNN Tema and GNN Shortner, with more planned). Each product registers its own `wp-admin` menu independently. Left to arbitrary `add_menu_page()` position choices, GNN products end up scattered across the sidebar instead of forming a recognizable, predictable group.

Two additional constraints shaped the design:
1. Users conceptually look for **theme** settings near Appearance (Görünüm) and **plugin** settings near Settings (Ayarlar) — one shared position band for both types would fight that expectation.
2. `add_menu_page()` keys WordPress's internal `$menu` array off `"$position"` (string interpolation). A bare PHP float literal with a trailing zero, e.g. `58.010`, is parsed as `58.01` before it ever reaches that interpolation — so two developers who each believe they picked a distinct slot can silently collide.

## Decision
- Two position bands, chosen by product type, each parked next to the core menu item users already associate with that product type:
  - **Themes** → `'58.xyz'`–`'59.xyz'` (next to Appearance/Görünüm, since `59` sits in WordPress core's own reserved blank separator slot right before Appearance at `60`).
  - **Plugins** → `'78.xyz'`–`'79.xyz'` (next to Settings/Ayarlar; Tools sits at `75` and Settings at `80`, leaving this range free of any core item).
- Every position is written as a **quoted string literal** in source (`'79.101'`), never a bare number, specifically to sidestep float-truncation collisions.
- Every slot — including the first/anchor product in a band — uses the same 3-digit-suffix format (`'79.101'`), so the registry table stays visually uniform as more products are added.
- A product with more than one admin screen registers exactly ONE top-level menu and nests every other screen under it via `add_submenu_page()`.
- GNN SMTPMail is assigned position slot `'79.101'`.

### Slot registry

| Position | Product | Type | Repo |
|---|---|---|---|
| `'59.100'` | GNN Tema (Theme panel + Slider submenu) | Theme | `gnn-wptheme` |
| `'58.101'` | *(next theme product — unassigned)* | Theme | — |
| `'79.101'` | GNN SMTPMail | Plugin | this repo (`gnn-smtpmail`) |
| `'79.102'` | GNN Shortner | Plugin | `gnn-shortner` |

## Consequences
- Every GNN product visually clusters next to the core menu section its users would already look under.
- Zero coordination code is required between products — each product stays a fully independent codebase.
- This table must be kept up to date by hand as new GNN products are built.
