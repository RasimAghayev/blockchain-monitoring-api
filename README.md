# Blockchain Monitoring API

Token və əməliyyat monitorinqi API-si. Token-un ümumi emissiyasını və holder
balanslarını saxlayır, əməliyyatları isə xarici blockchain API-sindən çəkilir.

| | |
|---|---|
| **Stack** | PHP 8.4 · Laravel 12 · PostgreSQL 16 · Redis · Horizon 5 · Sanctum 4 · JWT-auth 2 · l5-swagger 8.6 |
| **Giriş nöqtəsi** | nginx → php-fpm → Laravel |
| **İcra mühiti** | Yalnız Docker. Host toolchain lazım deyil |
| **API bazası** | `http://localhost/api/v1` |

---

## Qurulma

**1. `.env` yaradın.** Repo `.env` faylını saxlamır (`.gitignore`-da).

```shell
cp .env.example .env
```

**2. Dependency-ləri qurun.** `composer` servisi `www-data` ilə işləyir — bu, `php`
servisi ilə eyni uid-dir və bind mount-da kök sahibli faylların yaranmasının qarşısını alır.

```shell
docker compose run --rm composer install
```

**3. Steki qaldırın.** `nginx`, `php` və `pgsql` `service_healthy` şərti ilə
bir-birinin ardınca başlayır.

```shell
docker compose up -d --build
```

**4. Migration-ları icra edin.** Bu addım mütləqdir: cədvəllər yaradılmadan
`/v1/tokens` `relation "cache" does not exist` qismən 500 qaytarır.

```shell
docker compose run --rm artisan migrate --force
docker compose run --rm artisan storage:link
```

**5. Yoxlayın.** Dörd servis də `healthy` göstərməlidir.

```shell
docker compose ps
curl http://localhost/up
curl http://localhost/api/v1/check/health
```

---

## Servislər

| Servis | İmaj | Port | Qeyd |
|---|---|---|---|
| `nginx` | `nginx.dockerfile` | 80, 443 | Giriş nöqtəsi. Healthcheck `/up` |
| `php` | `php.dockerfile` | 9000 (daxili) | php-fpm, `www-data` |
| `artisan` | `php.dockerfile` | — | Tək çalışan CLI konteyneri |
| `composer` | `composer.dockerfile` | — | CLI konteyneri, `www-data` |
| `pgsql` | `postgres.dockerfile` | 6432 | PostgreSQL 16 + `pg_cron`, `postgis`, `timescaledb` |
| `redis` | `redis.dockerfile` | 8002 | Lokal build — registry image işlədilmir |
| `test` | `test.dockerfile` | — | `profiles: ["test"]` |
| `lint` | `test.dockerfile` | — | `profiles: ["test"]` |

Bütün bazalar digest ilə pinned-dir və bütün servislərdə CPU, yaddaş və PID
limiti var.

---

## Endpoint-lər

Bütün endpoint-lər `/api/v1` prefiksi altında və `throttle:60,1` ilə mühafizə olunur.
Cavablar `ApiBaseResponse` formatındadır:

```json
{ "timestamp": "...", "path": "...", "method": "...", "error": null, "result": {} }
```

### Tokens

| Metod | Yol | Qaydası |
|---|---|---|
| GET | `/tokens` | Siyahı. `tokenSymbol[eq]=MTK`, `per_page=15` |
| POST | `/tokens` | Yarat. `tokenAddress`, `tokenName`, `tokenSymbol`, `tokenTotalSupply` |
| GET | `/tokens/{id}` | Bir token |
| PATCH/PUT | `/tokens/{id}` | Yenilə |
| DELETE | `/tokens/{id}` | Sil (204) |
| GET | `/tokens/{address}/info` | Xarici API-dən token məlumatı, 10 dəqiqəlik keş |
| GET | `/tokens/{address}/top-holders` | Top holder-lər, 10 dəqiqəlik keş |

`/tokens` və `/transactions` uçun süzəni sintaksisi: `tokenSymbol[lk]`, `transactionAmount[gte]`
və s. Operator siyahısı hər feature-in `Filters/` klassında.

### Transactions

| Metod | Yol | Qaydası |
|---|---|---|
| GET | `/transactions` | Siyahı. `transactionHash[lk]`, `transactionAmount[gte]` |
| POST | `/transactions` | Yarat — **işləmir**, bax "Məlum çatışmazlıqlar" |
| GET | `/transactions/{id}` | Bir əməliyyat — **işləmir** |
| PATCH/PUT | `/transactions/{id}` | Yenilə — **işləmir** |
| DELETE | `/transactions/{id}` | Sil — **işləmir** |

### Yoxlama

| Metod | Yol | Qaydası |
|---|---|---|
| GET | `/check/db` | DB əlçatanlığı |
| GET | `/check/health` | Tətbiq sağlamlığı |
| GET | `/check/static` | Statik fayllar |
| GET | `/check/ip` | Sorğunun IP-si |
| GET | `/check/clear_cache` | Keşi təmizlə |

Laravel-ın öz sağlamlıq marşrutu `/up`-dir (`bootstrap/app.php`).

---

## Test və lint

İkisi də Docker-da işləyir. Host-da PHP, Composer və ya Node quraşdırmağa ehtiyac yoxdur.

```shell
docker compose --profile test run --rm test    # phpunit
docker compose --profile test run --rm lint    # pint --test
```

Test mühiti SQLite `:memory:` və `phpunit.xml`-dəki `APP_KEY` istifadə edir.
`phpunit.xml` bu mühitin **yeganə mənbəyidir** — `test` və `lint` servisində
`env_file` yoxdur, çünki konteyner mühiti `$_SERVER`-ə düşür və Dotenv
`$_SERVER`-i `$_ENV`-dən əvvəl oxuyur.

Testləri yazmadan dependency major keçidi etməyin: hər keçiddən sonra
`docker compose --profile test run --rm test` işləməlidir.

---

## Gündəlik əmrlər

```shell
docker compose run --rm artisan optimize:clear
docker compose run --rm artisan migrate:fresh --seed
docker compose run --rm artisan db:seed --class=UserSeeder
docker compose run --rm artisan jwt:secret
docker compose run --rm artisan l5-swagger:generate --all
docker compose down                            # steki dayandır
docker compose down -v                         # həmçinin volume-ları sil
```

---

## Məlum çatışmazlıqlar

Bunlar qəsdə gizlədilmir; hesabat `reports/blockchain-monitoring-api.md` faylındadır.

| # | Problem | Vəziyyət |
|---|---|---|
| 1 | Transactions feature-i işləmir: `Enums\TransactionTypes` yoxdur, tələb qatı `name`/`type` validasiya edir (cədvəl isə `hash`/`from_address`/`to_address`/`amount`/`gas_fee` saxlayır), `FetchTransactions::handle()` boşdur, `storeTokenTransactions()` mövcud olmayan `$this->address` və `timestamp` sütununu istifadə edir | 🔴 bloklayıcı, dizayn qərarı lazımdır |
| 2 | `php artisan route:list` `Unable to detect application namespace` xətası verir — tətbiq qovluğu `App/` (böyük hərf) | 🔴 `app/`-ə normallaşdırma T15-də nəzərdə tutulub |
| 3 | `node.dockerfile` və nginx-dən əvvəlki `src/fe` kopyası orphan-dir; repoda frontend yoxdur | ℹ️ nginx faylı düzəldildi, `node.dockerfile` istifadə olunmur |
| 4 | `bootstrap/app.php` `api` middleware qrupunu **iki dəfə** təyin edir — ikinci təyinat birincisini əvəzləyir və `throttle:api` itir | ⚠️ düzəliş tələb edir |
| 5 | `ErrorInternalServerErrorResponse` `config('App.debug')` oxuyur (səhv açar) — `APP_DEBUG=true` olsa da mesaj heç vaxt cavaba düşmür | ⚠️ düzəliş tələb edir |
| 6 | `TokenRepository::delete()` `findOrFail()` nəticəsi `string` ola bilər və then `.delete()` çağırılır | ⚠️ indi controller qarşısını alır, repository isə qalır |
| 7 | `Cache` və `Session` `file` sürücüsündədir; Redis yalnız queue/cache üçün konteyner kimi qalır | ℹ️ qərar tələb edir |
| 8 | Mount-lar `./src/be`, `./db/postgres`, `./db/redis` yollarındadır, `var/` altında deyil | ⚠️ R92 ilə uyğunsuzluq |

---

## Repo strukturu

```
blockchain-monitoring-api/
├── docker-compose.yml
├── nginx.dockerfile  php.dockerfile  composer.dockerfile
├── test.dockerfile   postgres.dockerfile  redis.dockerfile
├── node.dockerfile                # orphan, istifadə olunmur
├── server/
│   ├── nginx/      nginx.conf, default.conf
│   └── postgres/   postgresql.conf + init skriptləri
├── db/             postgres və redis data qovluqları (bind mount)
└── src/be/         Laravel tətbiqi
    ├── App/Http/Controllers/{Tokens,Transactions,Checks}/   # vertikal dilimlər
    ├── App/Services/{Blockchain,Telegram}/
    ├── config/  database/  routes/  tests/
    └── composer.json  composer.lock  phpunit.xml
```

Router-lər `routes/api.php` tərəfindən avtomatik aşkarlanır: `Controllers` altındakı
bütün `**/Router/*.php` faylları `v1` prefiksi altında yüklənir. Yeni endpoint əlavə
etmək üçün router faylını bu qovluqda saxlayın — `routes/api.php`-i dəyişmək lazım deyil.
