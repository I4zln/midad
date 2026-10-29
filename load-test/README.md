# Load test

`midad-load.js` is a [k6](https://k6.io) script. It simulates 100 and then 200 visitors on the site at the same time. 70% are guests and 30% are signed in. Each visitor pauses 1–3 seconds between pages, the way a reader does.

The visitors open the home page, the quotes page (also page 2+, a category and a search), a quote, «اكتشف» (scrolling two more batches), the poets, about and المعلّقات pages, and an author. Signed-in visitors also like and then unlike a quote.

**Run it only against your own computer or a staging copy, never the live site.** It creates sessions, and it signs in and likes quotes as the test accounts.

## 1. Install k6 (Windows)

```
winget install k6 --source winget
```

## 2. Prepare a copy of the database with test accounts

Never use the real database for this. Make a copy, then add 200 test accounts to it (`loadtest1@example.test` … `loadtest200@example.test`, password `loadtest-pass-123`):

```
copy database\database.sqlite database\loadtest.sqlite
php load-test\seed-users.php database\loadtest.sqlite
```

## 3. Start the site on the copy

In PowerShell:

```
$env:DB_DATABASE = "$PWD\database\loadtest.sqlite"; $env:SESSION_SECURE_COOKIE = "false"; $env:MAIL_MAILER = "log"
php artisan serve --port=8128
```

`php artisan serve` handles only a few requests at a time, so the numbers are much worse than under Apache. For real numbers, run the site under Apache with the same database copy (see `deploy/windows/`).

## 4. Run

```
k6 run -e QUICK=1 load-test/midad-load.js     # 1-minute check, 50 visitors
k6 run load-test/midad-load.js                # full run: 100 visitors for 2.5 min, then 200 for 2.5 min (~8 min)
```

Other options:

- `-e BASE_URL=http://127.0.0.1:8000` uses another address.
- `-e SUMMARY_JSON=result.json` also saves every number to a file.

## Reading the result

The summary prints:

- the average and p95 response time (95% of requests were faster than this);
- requests per second;
- the error rate;
- one line per page;
- `PASS` or `FAIL` against the targets (**p95 < 500 ms** and **errors < 1%**).

Each visitor sends its own `X-Forwarded-For` address, so per-visitor rate limits behave as they would with real, separate people.
