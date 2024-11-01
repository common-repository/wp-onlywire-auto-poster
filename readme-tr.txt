/*  
	=== SÜRÜM NOTLARI ===
	
	2011-17-08 - v3.0.9 - Bazý görsel iyileþtirmeler.
	
	2010-05-08 - v3.0.8 - lib.php içinde bildirilmiþ get_file() fonksiyonu WP'in çalýþma akýþý içindeki
						  diðer ayný isimdeki bir fonksiyon ile çakýþtý. fonksiyonun adý deðiþtirilerek
						  sorun çözüldü.
						  (Thanks to therapistcalifornia)
						  
	2010-05-08 - v3.0.7 - short_open_tag ayarýnýn kapalý olduðu PHP konfigürasyonunda ortaya çýkan	
						  kýsa php bildirim tag'inin (<?) kullanýlmasýndan ötürü doðan hata giderildi.
	
	2009-04-07 - v3.0.6 - PHP short_open_tag ayarýnýn pasif olduðu sunucularda kýsa php açma etiketi
						  kullanýmýndan doðan hata düzeltildi.
						  (Thanks to Jesse)
	
	2009-04-07 - v3.0.6 - Bazý görsel iyileþtirmeler yapýldý. Güvenlik amacýyla sunucuda çalýþtýrýlmasýna
						  izin verilmeyen fonksiyonlar için alternatif kontroller eklendi.
						  Paypal baðýþ düðmesi eklendi. Desteðinizi bekliyorum...
						  (Thanks to Srikanth C J)
	
	2009-03-22 - v3.0.5 - Önceki güncellemede oluþan kritik bir kodlama hatasý düzeltildi
						  (Thanks to Selim Yagiz from http://www.diziozeti.net/)	
						
	2009-09-10 - v3.0.4 - Önceki güncellemede oluþan kritik bir kodlama hatasý düzeltildi
						  (Thanks to brakco@vizyonkolik)
	
	2009-09-10 - v3.0.3 - Önceki güncellemede oluþan kritik bir kodlama hatasý düzeltildi
						  (Thanks to brakco@vizyonkolik)						
						
	2009-09-10 - v3.0.2 - OW'a gönderilen permalink'in kodlanmamasýndan kaynaklanan otorizasyon hatasý giderildi.
						  (Thanks to playboi.de)
	
	2009-08-19 - v3.0.1 - Geliþtiriciyi destekle düðmesinin döndürdüðü hata düzeltildi.						
	
	2009-07-29 - v3.0b  - OnlyWire'in döndürdüðü sonuçlar yakalandý ve yönetim panelinde listelendi.
						- Ayný post_id li yazýlarýn tekrardan gönderilmesi engellendi.
	
    2009-07-23 - v3.0a	- OnlyWire'in API'sine gore tags ve comment alanlarý da gönderiliyor.
						- Comment alanýna yazar adý, tarih, özet ve yazýnýn kategorileri de dahil edildi.
						- onlywire_ id ile option tablosunun þiþirilmesi harici bir tablo ile engellendi.
						- OnlyWire'a gönderilenler tabloya kaydedildi.					
	
	v2.0 	- Base author: lionstarr, http:www.lionstarr.de
	
*/