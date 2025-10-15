# Taobao Order Management Commands

Bu sənəd Taobao order idarəetmə komandlarının istifadəsini izah edir.

## 🚀 Mövcud Komandlar

### 1. Order Yaratma (Create Order)
Lokal orderləri Taobao-ya göndərir və `purchase_id` alır.

```bash
# Bütün pending orderləri göndər
php artisan sync:orders-to-taobao

# Spesifik order göndər
php artisan sync:orders-to-taobao --order_id=59
```

**Nəticə:**
- Order Taobao-da yaradılır
- `purchase_id` alınır və database-də saxlanılır
- Status `pending` → `created` dəyişir

---

### 2. Order Ödənişi (Pay Order)
Yaradılmış orderləri ödəyir (Taobao hesabından).

```bash
# Bütün "created" statuslu orderləri ödə
php artisan sync:pay
```

**Nəticə:**
- Uğurlu ödənişlər: Status `created` → `paid`
- Uğursuz ödənişlər: Log edilir, status dəyişmir
- Ümumi səbəblər:
  - `INSUFFICIENT_BALANCE_FOR_PAYMENT` - Balans çatmır

---

### 3. Order Status Sync (Eksperimental)
**⚠️ QEYD:** Taobao API-də order status query endpoint-i public deyil və ya fərqli autentifikasiya tələb edir.

```bash
# Bütün aktiv orderləri yoxla
php artisan sync:order-status

# Spesifik purchase_id yoxla
php artisan sync:order-status --purchase_id=200081134310
```

**Status:** Hal-hazırda işləmir - API endpoint tapılmadı.

**Alternativlər:**
1. Payment response-undakı məlumatları istifadə edin
2. Taobao dashboard-dan manual yoxlayın: https://distributor.taobao.global/apps/order/list
3. Webhook sistemi quraşdırın

---

## 📊 Order Status Flow

```
pending → created → paid → shipped → completed
                     ↓
                 cancelled
```

### Status İzahı:
- **pending**: Lokal yaradılıb, Taobao-ya göndərilməyib
- **created**: Taobao-da yaradılıb, ödəniş gözləyir
- **paid**: Ödənilib, göndəriş gözləyir
- **shipped**: Göndərilib
- **completed**: Tamamlanıb
- **cancelled**: Ləğv edilib

---

## 🔑 Token İdarəetməsi

### Valid Token Yoxlama
Token avtomatik yoxlanılır və yenilənir. Əgər hər iki token (access və refresh) vaxtı bitibsə:

1. Taobao Seller Center-ə daxil olun
2. Yeni authorization code alın
3. Database-ə əlavə edin:

```sql
INSERT INTO taobao_tokens (...) VALUES (...);
```

---

## 🐛 Debugging

### Logları yoxlama
```bash
tail -f storage/logs/laravel.log | grep -i taobao
```

### Ümumi Problemlər

**1. "IllegalAccessToken"**
- Səbəb: Token vaxtı bitib
- Həll: Yeni token əlavə edin

**2. "INSUFFICIENT_BALANCE_FOR_PAYMENT"**
- Səbəb: Taobao hesabda balans yoxdur
- Həll: Hesaba balans əlavə edin

**3. "Product missing SKU"**
- Səbəb: Məhsulun `product_stocks` cədvəlində SKU yoxdur
- Həll: SKU əlavə edin

---

## 📝 Test Nümunəsi

```bash
# 1. Test order yarat
php artisan sync:orders-to-taobao --order_id=59

# Nəticə:
# ✓ Order 59 processed successfully
# Purchase ID: 200081134310

# 2. Ödəməyi cəhd et
php artisan sync:pay

# Nəticə (əgər balans varsa):
# ✓ Payment successful
# Status: paid

# Nəticə (əgər balans yoxdursa):
# ⚠️ Payment failed: INSUFFICIENT_BALANCE_FOR_PAYMENT
```

---

## 🔗 Faydalı Linklər

- Taobao Seller Center: https://distributor.taobao.global/
- Order List: https://distributor.taobao.global/apps/order/list
- API Documentation: https://open.taobao.global/doc/api.htm

---

## 💡 Tövsiyələr

1. **Cron Job qurun** - Avtomatik status sync üçün
2. **Monitoring əlavə edin** - Failed payment-ları izləyin
3. **Log retention** - Logları 30 gün saxlayın
4. **Backup** - Database-i gündəlik backup edin

---

Əlavə suallar üçün: development team ilə əlaqə saxlayın.
