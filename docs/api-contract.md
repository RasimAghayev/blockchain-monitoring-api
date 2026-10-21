# API kontraktı

Bütün endpoint-lər `/api/v1` prefiksi altında, `throttle:60,1` middleware-i ilə
verilir. Maşın-oxuyan sözleşmə `src/be/storage/api-docs/api-docs.json` faylındadır;
bu sənəd onun insan oxuyan qısa formasıdır.

---

## Ümumi cavb formatı

Hər cavb eyni quruluşdadır:

```json
{
  "timestamp": "2026-10-20T18:24:14+04:00",
  "path": "api/v1/transactions",
  "method": "GET",
  "error": null,
  "result": {}
}
```

| Sahə | Tip | Qeyd |
|---|---|---|
| `timestamp` | string | Cavabun yaranma anı, ISO-8601 |
| `path` | string | Sorğunun yolu (prefiksiz) |
| `method` | string | HTTP metodu |
| `error` | null \| string \| object | Xəta olduqda mesaj və ya xəta xəritəsi |
| `result` | object \| array | Uğurlu cavabın gövdəsi |

### Xəta formatı

| Status | `error` | Haradan gəlir |
|---|---|---|
| 422 | `{ "<sahə>": ["<mesaj>"] }` | Validasiya xətası |
| 404 | `"Resource not found: <səbəb>"` | Model tapılmadı |
| 403 | `"Authorization failed: <səbəb>"` | Authorization xətası |
| 500 | `"An error occurred: <səbəb>"` | Gözlənilməz xəta |
| 503 | `"Blockchain API is not configured"` | Xarici blockchain API-si təyin edilməyib |

`result` xəta zaman hər zaman boş obyektdir.

---

## Transactions

`transactions` cədvəli beş sütun saxlayır: `hash`, `from_address`,
`to_address`, `amount`, `gas_fee`. API bu sütunları camelCase adlarla qəbul edir və
qaytarır — `transactionHash`, `transactionFromAddress` və s.

### Endpoint-lər

| Metod | Yol | Qaydası |
|---|---|---|
| GET | `/api/v1/transactions` | Siyahı, süzəni və pagination dəstəkləyir |
| POST | `/api/v1/transactions` | Yeni əməliyyat yaradır (201) |
| GET | `/api/v1/transactions/{id}` | Bir əməliyyat |
| PATCH | `/api/v1/transactions/{id}` | Yalnız göndərilən sahələri yeniləyir |
| PUT | `/api/v1/transactions/{id}` | Bütün məcburi sahələri əvəz edir |
| DELETE | `/api/v1/transactions/{id}` | Silir (204) |

### Sorğu sahələri

| Sahə | Tip | Məcburi | Qeyd |
|---|---|---|---|
| `transactionHash` | string (3–255) | həm | Unikaldır; təkrar cədvəl məhdudiyyatını pozur |
| `transactionFromAddress` | string (3–255) | həm | Göndərənin ünvanı |
| `transactionToAddress` | string (≤255) | yox | Kontrat yaradılmasında boşdur |
| `transactionAmount` | rəqəm (≥0) | həm | Ötürülən məbləğ |
| `transactionGasFee` | rəqəm (≥0) | həm | Ödənilən gas haqqı |

PUT bütün məcburi sahələri tələb edir, PATCH isə yalnız göndəriləni. PATCH
zaman göndərilməyən sahələr mövcud sətrdəki qiymətləri saxlayır.

### Cavb sahələri

| Sahə | Tip | Qeyd |
|---|---|---|
| `id` | integer | |
| `transactionHash` | string | |
| `transactionFromAddress` | string | |
| `transactionToAddress` | string | Kontrat yaradılmasında boş |
| `transactionAmount` | number | |
| `transactionGasFee` | number | |
| `type` | object | `{ "value": int, "label": string }` |
| `createdAt` | string | `Y-m-d H:i:s` |
| `updatedAt` | string | `Y-m-d H:i:s` |

`type` cədvəldə əlavə sütun deyil — cədvəlin özündən təyin olunur:

| `value` | `label` | Necə təyin olunur |
|---|---|---|
| 1 | `Transfer` | `transactionToAddress` boş deyil |
| 2 | `Contract creation` | `transactionToAddress` boşdur (kontrat yaradılması ünvan almır) |

### Süzənlər

`GET /api/v1/transactions` aşağıdakı süzənləri dəstəkləyir. Operator
mətnlər `[...]` içində yazılır və bir neçə operator eyni anda verilə bilər.

| Parametr | Cədvəl sütunu | Operatorlar |
|---|---|---|
| `transactionHash` | `hash` | `eq`, `lk`, `nlk`, `ilk`, `inlk` |
| `transactionFromAddress` | `from_address` | `eq`, `lk`, `nlk`, `ilk`, `inlk` |
| `transactionToAddress` | `to_address` | `eq`, `lk`, `nlk`, `ilk`, `inlk` |
| `transactionAmount` | `amount` | `eq`, `lt`, `lte`, `gt`, `gte`, `ne`, `bt`, `nbt`, `in`, `nin` |

Nümunə:

```
GET /api/v1/transactions?transactionHash[lk]=0x9f2c&transactionAmount[gte]=1
```

`per_page` (default 15, maksimum 100) və `page` parametrləri də dəstəklənir.
Siyahının cavabı `message`, `data`, `meta` və `links` hissələrindən ibarətdir.

### Nümunə cavab

```json
{
  "timestamp": "2026-10-20T18:24:14+04:00",
  "path": "api/v1/transactions/1",
  "method": "GET",
  "error": null,
  "result": {
    "message": "Transaction successfully show: 1",
    "data": {
      "id": 1,
      "transactionHash": "0x9f2c1b7c9d4e5f60718293a4b5c6d7e8f9012345",
      "transactionFromAddress": "0x1111111111111111111111111111111111111111",
      "transactionToAddress": "0x2222222222222222222222222222222222222222",
      "transactionAmount": 1.5,
      "transactionGasFee": 0.0004,
      "type": { "value": 1, "label": "Transfer" },
      "createdAt": "2026-10-20 18:20:01",
      "updatedAt": "2026-10-20 18:20:01"
    }
  }
}
```

---

## Tokens

Token endpoint-ləri eyni formatı istifadə edir. Sahə adları `tokenAddress`,
`tokenName`, `tokenSymbol`, `tokenTotalSupply`; süzən parametri `tokenSymbol`.

`GET /api/v1/tokens/{address}/info` və `GET /api/v1/tokens/{address}/top-holders`
xarici blockchain API-sindən məlumat çəkir və 10 dəqiqə keşdə saxlayır. Bu
endpoint-lər ümumi zarfı istifadə etmir — cavab birbaşa xarici API-nin JSON-u
kimi qaytarılır.

---

## Xarici blockchain API-si

| Mühit dəyişəni | Default | İstifadə |
|---|---|---|
| `API_BLOCKCHAIN_URL` | `https://api.blockchain.com/v3` | Xarici API-nin kök ünvanı |

Dəyişəni boş (`API_BLOCKCHAIN_URL=`) təyin etmək olar. Bu halda xarici API-yə
gedən endpoint-lər 503 qaytarır və `transactions:fetch` işçisi heç nə etmir.
