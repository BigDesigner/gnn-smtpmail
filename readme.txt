=== GNN SMTPMail ===
Contributors: BigDesigner
Requires at least: 5.2
Tested up to: 6.6
Stable tag: 1.4.4
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
