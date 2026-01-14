### 📦 API Dökümantasyonu
```
Bu klasör, Forum California API sisteminin kullanıcı kayıt işlemlerini yöneten endpoint’lerini içerir.  
Her çağrı, sistemde yeni üyeler oluşturur ve JSON formatında geri döner.  
Bu API, otomasyon sistemleri, Telegram botları ve kayıt simülasyonları için tasarlanmıştır 🌹
```
```
🔗 Endpoint: /get/reg.php?action=bulk&count=5`
```
Amaç
Belirtilen sayıda 
kullanıcıyı 
otomatik olarak 
oluşturur.

🧪 Örnek Çağrı
````
GET https://viosrio.serv00.net/get/reg.php?action=bulk&count=5
````

📥 Parametreler

| Parametre | Açıklama                     | Zorunlu | Örnek Değer |
|-----------|------------------------------|---------|-------------|
| action  | İşlem tipi (bulk)          | ✅      | bulk      |
| count   | Kaç kullanıcı oluşturulacak  | ✅      | 5         |

> ⚠️ localhost gibi yerleri kendi sunucu bilgilerinize göre düzenleyiniz.

---

📤 Yanıt Formatı (JSON)

`json
{
  "users": [
    {
      "username": "KaraKurt16X",
      "email": "araturk162006@yandex.com",
      "password": "^Juu8z1HBh",
      "login_ready": true,
      "user_group": {
        "name": "Aktif Üye",
        "badge": "🔥"
      }
    },
    ...
  ]
}
`

🔑 Dönüş Alanları

| Alan         | Açıklama                         |
|--------------|----------------------------------|
| username   | Oluşturulan kullanıcı adı        |
| email      | E-posta adresi                   |
| password   | Şifre                            |
| login_ready| Girişe hazır mı (true/false)   |
| user_group | Üye tipi ve rozet bilgisi        |

---

🧠 Kullanım Senaryoları

- Telegram botuna otomatik hesap gönderimi  
- Yerel log.txt dosyasına kayıt  
- Forum simülasyonları ve test ortamları  
- API showcase ve demo sunumları  

---
