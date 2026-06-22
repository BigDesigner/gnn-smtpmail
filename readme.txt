=== GNN SMTPMail ===
Contributors: GNN
Requires at least: 5.2
Tested up to: 6.6
Stable tag: 1.2.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

YALNIZCA Custom SMTP ile WordPress e-postalarını güvenle gönderin. Test mail ve ayrıntılı loglama içerir. Kaldırınca tüm veriler temizlenir.

== Özellikler ==
* Custom SMTP (sunucu/port/ssl-tls/auth).
* Test mail gönderme ekranı.
* Başarılı/başarısız gönderimler için log tablosu (DB).
* Güvenlik: nonce, yetki kontrolü, sanitize/escape, prepared statements.
* Uninstall sırasında tüm ayar ve tablo silinir.
* 1.1.0 sürümünde Brevo tamamen kaldırıldı.

== Kurulum ==
1. `gnn-smtpmail` klasörünü `wp-content/plugins/` içine yükleyin.
2. Eklentiyi etkinleştirin.
3. Yönetim panelinde **GNN SMTPMail → Custom SMTP** bölümünden ayarlarınızı yapın.

== Değişiklikler ==
= 1.2.0 =
* GitHub deposundaki görsel varlıklar, belgeler ve iş akışı dosyaları ile yereldeki sade ve güvenli Custom SMTP yapısı harmanlandı.

= 1.1.0 =
* Brevo entegrasyonu kaldırıldı; custom-only.

= 1.0.0 =
* İlk sürüm (Brevo + Custom).
