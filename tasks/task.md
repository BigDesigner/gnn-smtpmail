# Task List: GNN SMTPMail

This document tracks the evolution of the GNN SMTPMail WordPress plugin.

## ✅ Completed Milestones

### MB — Foundation & Infrastructure
- [x] **MB-001:** Initialize project structure as a WordPress Plugin.
- [x] **MB-002:** Set up GitHub Actions for automated `.zip` releases.
- [x] **MB-003:** Create specialized Memory Bank for AI context persistence.
- [x] **MB-004:** Implement GitHub Updater for automatic core updates.

### SEC — Security & Hardening
- [x] **SEC-001:** Implement Nonce verification for form actions (settings saving, test sending, clearing logs).
- [x] **SEC-002:** Implement `defined('ABSPATH') || exit;` guard in all PHP files.
- [x] **SEC-003:** Apply comprehensive SQL preparation using `$wpdb->prepare()`.
- [x] **SEC-004:** Add administrative hardening security headers (nosniff, sameorigin).

### UI — Premium Design System
- [x] **UI-001:** Refine admin pages with unified grid layout and badges.
- [x] **UI-002:** Add custom plugin action links (Donate, Settings, Check Updates) to the plugins table.

---

## 🚀 Release History

### v1.2.0 — Harmanlama & GitHub Güncelleyici
- [x] GitHub Updater entegrasyonu tamamlandı.
- [x] Eklenti eylemleri bağlantılarına "Check Updates", "Donate" ve "Settings" eklendi.
- [x] GitHub'daki eksik varlıklar (görseller, dokümantasyon, `.gitignore`) geri getirildi.
- [x] Memory Bank, Agents, Docs ve Tasks klasörleri oluşturuldu.

### v1.1.0 — Custom-Only SMTP
- [x] Brevo API entegrasyonu kaldırılarak sadece Özel SMTP yapısına odaklanıldı.
- [x] Ayarlar ve veri tabanı yapılandırmaları sadeleştirildi.

### v1.0.0 — Initial Release
- [x] Brevo API + Custom SMTP gönderim desteği.
- [x] Gönderim kayıtlarının veri tabanında loglanması ve yönetim arayüzü.

## 📂 Backlog
- [ ] Log çıktılarının CSV veya JSON olarak dışa aktarılması (export).
- [ ] Gelişmiş e-posta gönderim istatistikleri ve grafikler.
