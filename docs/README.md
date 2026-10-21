# Sənədlər

| Sənəd | Məzmun |
|---|---|
| [api-contract.md](api-contract.md) | Endpoint-lər, sorğu və cavb sahələri, süzənlər, xəta formatı |
| [runbook.md](runbook.md) | İstehsal addımları, əməliyyat işçisi, problemlərin həlli |

İlk qurulum repo kökündəki [README](../README.md)-də təsvir olunub — burada
təkrarlanmır. Burada yalnız həmin sənəddən daha dərin olan hissələr var.

Açıq API sözleşməsi həm də maşın-oxuyan formatda mövcuddur: `src/be/storage/api-docs/api-docs.json`.
Onu yeniləmək üçün:

```shell
docker compose run --rm artisan l5-swagger:generate --all
```
