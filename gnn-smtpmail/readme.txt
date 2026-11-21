=== GNN SMTPMail ===
Contributors: BigDesigner
Requires at least: 6.2
Tested up to: 7.1.5
Requires PHP: 7.4
Stable tag: 1.6.2
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Custom SMTP veya Brevo API ile WordPress e-postalarını güvenle gönderin. Test mail ve ayrıntılı loglama içerir. Kaldırınca tüm veriler temizlenir.

== Özellikler ==
* Custom SMTP (sunucu/port/ssl-tls/auth) ve Brevo API v3 gönderim kanalları.
* Test mail gönderme ekranı.
* Başarılı/başarısız gönderimler için log tablosu (DB) ve otomatik günlük temizlik.
* Güvenlik: nonce, yetki kontrolü, sanitize/escape, prepared statements, wp-config.php sabit desteği.
* Uninstall sırasında tüm ayar ve tablo silinir.

== Kurulum ==
1. `gnn-smtpmail` klasörünü `wp-content/plugins/` içine yükleyin.
2. Eklentiyi etkinleştirin.
3. Yönetim panelinde **GNN SMTPMail → Ayarlar** bölümünden ayarlarınızı yapın.

== Değişiklikler ==
= 1.6.2 =
* GitHub güncelleme modülü (`inc/updater.php`) birleşik GNN Updater standardına geçirildi.

= 1.6.1 =
* Güvenlik, Updater, WCAG & WP 7.1.5 uyumluluk iyileştirmeleri:
  * SMTP şifresi ve Brevo API anahtarının HTML DOM `value` niteliğine düz metin yazılması kaldırıldı; boş bırakıldığında mevcut gizli değer korunur, isteğe bağlı temizleme kutusu eklendi.
  * Brevo ek dosya (attachment) doğrulamasından `ABSPATH` kökü çıkarılarak `WP_CONTENT_DIR` / `uploads` ile sınırlandırıldı ve `.php` / `wp-config.php` dosyaları engellendi.
  * Updater `zipball_url` fallback ve GitHub CDN desteği için `api.github.com` ve `release-assets.githubusercontent.com` host izin listesine eklendi; `$args->slug` ve `$wp_filesystem` kontrolleri güçlendirildi.
  * `prune_old_logs()` saat dilimi uyuşmazlığı `wp_date()` ile düzeltildi; cron kontrolü yalnızca admin bağlamına alındı.
  * `admin_hardening_headers()` içinde `Referrer-Policy` çekirdek ile uyumlu `strict-origin-when-cross-origin` olarak güncellendi.
  * `smtp_secure = none` durumunda `SMTPAutoTLS` devre dışı bırakıldı ve port aralığı `1-65535` ile sınırlandırıldı.
  * `paginate_links()` `wp_kses_post()` ile sarıldı, eksik text domain'ler ve tanı mesajı çevirileri tamamlandı, `languages/gnn-smtpmail.pot` dolduruldu.
  * Geçersiz HTML iç içe `<p>` yapısı, başlık hiyerarşisi (`h2`), satır içi stiller ve `release.yml` CRLF sürüm ayrıştırması düzeltildi.
  * `Tested up to` değeri WordPress `7.1.5` olarak güncellendi.

= 1.6.0 =
* Kurşun geçirmezlik & WP 7.1.4 uyumluluk güncellemesi:
  * Her sayfa isteğinde çalışan `SHOW TABLES` sorgusu ve global `upgrade.php` yüklemesi kaldırıldı (lazy DB kontrolü).
  * Brevo API kanalında `Cc:` ve `Bcc:` alıcı ayrıştırma desteği eklendi.
  * Ek dosya yol doğrulaması `ABSPATH`, `WP_CONTENT_DIR` ve `uploads` köklerini destekleyecek şekilde güncellendi.
  * 30 günden eski logları temizleyen günlük WP-Cron eklendi.
  * `wp-config.php` üzerinden `GNN_SMTPMAIL_SMTP_PASS` ve `GNN_SMTPMAIL_BREVO_KEY` sabit desteği eklendi.
  * `load_plugin_textdomain()` `init` (priority 1) kancasına eklendi ve `languages/gnn-smtpmail.pot` oluşturuldu.
  * Updater hata önbelleklemesi (`'error'` sentinel), HTTPS/CDN host doğrulaması, `$overwrite = true` ve `requires`/`tested` meta verileri düzeltildi.
  * jQuery bağımlılığı kaldırılarak Vanilla JS'e geçildi, WCAG erişilebilirlik ve mobil tablo uyumluluğu eklendi.
  * Multisite ve tam uninstall temizliği eklendi.

= 1.5.4 =
* Depo yapısı düzenlendi: eklenti dosyaları repo kökünden `gnn-smtpmail/` alt klasörüne taşındı ve release iş akışı bu yapıya göre sadeleştirildi.

= 1.4.0 =
* Brevo Senders API entegrasyonu tamamlandı. Manuel gönderici adı/domain girişleri kaldırılarak, Brevo hesabınızda doğrulanmış kayıtlı göndericilerin seçilebildiği açılır liste (dropdown) eklendi.

= 1.3.4 =
* Loglar sayfasında veri tabanı ve log tablosu durumunu canlı denetleyen tanılayıcı (debug) uyarı kutusu eklendi.

= 1.3.3 =
* Geliştirici bilgileri ve eklenti bağlantıları güncellendi.

= 1.3.2 =
* Veri tabanı otomatik kontrol sistemi güçlendirildi. dbDelta SQL yazım standartları (küçük harfli alan tipleri) düzeltildi ve tablo yoksa otomatik iyileştirme eklendi.

= 1.3.1 =
* Veri tabanı log tablosunun otomatik kurulumu ve dbDelta bağımlılıkları düzeltildi.

= 1.3.0 =
* Brevo API entegrasyonu geri getirildi. Kullanıcılar artık Custom SMTP veya Brevo API arasında seçim yapabilirler.

= 1.2.1 =
* Güvenlik güncellemesi yapıldı. Geliştirici ortamı yapılandırmaları ve geçici dosyalar dışlandı.

= 1.2.0 =
* GitHub deposundaki görsel varlıklar, belgeler ve iş akışı dosyaları ile yereldeki sade ve güvenli Custom SMTP yapısı harmanlandı.

= 1.1.0 =
* Brevo entegrasyonu kaldırıldı; custom-only.

= 1.0.0 =
* İlk sürüm (Brevo + Custom).
