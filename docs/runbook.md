# Operativ runbook

Bu sənəd istismar və problemlərin həlli üçündür. İlk qurulum repo kökündəki
[README](../README.md)-dədir.

---

## Yerləşdirmə addımları

1. **Konfiqurasiya.** `var/.env` faylını yaradın: `cp .env.example var/.env`.
   Faylda heç bir real dəyər olmamalıdır; `APP_KEY` və `DB_PASSWORD` dəyərləri
   mühitə görə doldurulur.
2. **Dependency-lər.** `docker compose run --rm composer install`
3. **İmajlar.** `docker compose build` — bütün bazalar digest ilə pinned-dir və
   yalnız lokal build-lər istifadə olunur.
4. **Stek.** `docker compose up -d`
5. **Migration-lar.** `docker compose run --rm artisan migrate --force`
6. **Storage link.** `docker compose run --rm artisan storage:link`
7. **Yoxlama.** `docker compose ps` — bütün servisler `healthy` olmalıdır;
   `curl http://localhost/up` və `curl http://localhost/api/v1/check/health`
   200 qaytarmalıdır.

`var/` həm `.gitignore`, həm `.dockerignore` ilə bağlıdır. Yeni deployment-da
`var/` yaradılmasa da, Docker bind mount qovluqlarını özü yaradır; yegan
zorurlu fayl `var/.env`-dir.

---

## Əməliyyat işçisi

`FetchTransactions` işi bir ünvanın son əməliyyatlarını xarici API-dən çəkib
`transactions` cədvəlinə yazır. Eyni hash ikinci dəfə gələndə sətir yenilənir,
yeni sətir yarandıqda isə Telegram kanalına bildiriş göndərilir.

**İşi başlatmaq:**

```shell
docker compose run --rm artisan transactions:fetch 0x9f2c1b7c9d4e5f60718293a4b5c6d7e8f9012345
```

**Növbə rejimi:** `QUEUE_CONNECTION=redis` olduqda iş Horizon tərəfindən
işlənir (`php artisan horizon`), `sync` olduqda isə komanda birbaşa icra edir.

**Davamlı monitorinq üçün:** `routes/console.php` faylına `Schedule::job(new
FetchTransactions($address))` sətri əlavə edin və `php artisan schedule:work`
işlədin.

**Dayanma şərtləri (heç nə yazılmadan çıxılır):**

| Log mesajı | Səbəb |
|---|---|
| `Transaction fetch skipped: no blockchain endpoint is configured.` | `API_BLOCKCHAIN_URL` boşdur |
| `Transaction fetch for <address> returned status <kód>.` | Xarici API xəta qaytarıb |

---

## Problemlərin həlli

| Simptom | Səbəb | Həll |
|---|---|---|
| `relation "cache" does not exist` | Migration-lar icra edilməyib | `docker compose run --rm artisan migrate --force` |
| `Unable to detect application namespace` | Tətbiq mənbəyi `app/` deyil, `App/`-dir | Mənbə qovluğu `app/` olmalıdır (`composer.json` PSR-4: `"App\\": "app/"`) |
| `Target [App\Http\Controllers\Transactions\Services\TransactionServiceInterface] is not instantiable` | Container binding-i yoxdur | `AppServiceProvider::register()` içində binding mövcuddur |
| `Class "App\Http\Controllers\Transactions\Enums\TransactionTypes" not found` | Autoload map-i köhnədir | `docker compose run --rm composer dump-autoload` |
| OpenAPI sənədi boşdur | `l5-swagger` annotasiyaları `base_path('app')` altında axtarır | Mənbə qovluğu `app/` olmalıdır; sonra `artisan l5-swagger:generate --all` |
| Boş və ya köhnə `api-docs.json` | Sənəd yenilənməyib | `docker compose run --rm artisan l5-swagger:generate --all` |
| Bildiriş göndərilmir | `TELEGRAM_BOT_TOKEN` / `TELEGRAM_CHAT_ID` təyin edilməyib | `var/.env` faylını doldurun |
| Fayl yazma xətası, `www-data` icazə yoxdur | Konteyner `root` ilə işləyib | `docker compose exec -u root php chown -R www-data:www-data /var/www/html/be` |
| `permission denied` — `var/postgres` | Postgres konteyneri ilk dəfə bu qovluğu yaradıb | `docker compose down` və `var/postgres` qovluğunun sahibini düzəldin |

---

## Yoxlama

| Yoxlama | Əmr | Gözlənilən |
|---|---|---|
| Servislər | `docker compose ps` | Hər biri `healthy` |
| Tətbiq | `curl http://localhost/up` | 200 |
| DB | `curl http://localhost/api/v1/check/db` | 200, `status` mesajı |
| API | `curl http://localhost/api/v1/tokens` | 200 və `result.data` |
| Əməliyyatlar | `curl http://localhost/api/v1/transactions` | 200 və `result.data` |
| Testlər | `docker compose --profile test run --rm test` | Hamısı keçir |
| Lint | `docker compose --profile test run --rm lint` | `PASS` |
| Asılılıqlar | `docker compose --profile test run --rm composer audit --locked` | Advisory sayı 0 |

---

## Yedəkləmə

Veritabanı `var/postgres` qovluğunda saxlanılır. Yedək bu qovluğun kopyasıdır:

```shell
docker compose exec pgsql pg_dump -U pgsql invoicedb > var/backup-invoicedb.sql
```

`var/` git-ignored olduğu üçün yedəklər repoya düşmür — saxlanma yeri ayrıca
təyin edilməlidir.
