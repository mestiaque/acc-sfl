# AccSfl Package

Auto-generated Laravel package by ME Utility.

## Installation

```bash
composer require mestiaque/accsfl
```

## Usage

After installing the package, the service provider will be auto-discovered by Laravel.

### Routes

- Web: `/accsfl`
- API: `/api/accsfl`

### Views

```php
view('accsfl::index');
```

### Translations

```php
trans('accsfl::message.welcome');
```

### Config

Publish the config file:

```bash
php artisan vendor:publish --tag=accsfl-config
```

### Expense approval emails

Approval emails go to every user who has the `ac_expense.approve` permission (assigned from Roles Setup) and a valid email address. No `.env` setting is needed. SMTP is taken from the host application's mail settings.

Each manually created or imported expense sends an approval email with a link to that expense in the filtered expense list. Recipients need to sign in with an account that can access the expense list and has expense approval permission. The expense is posted to the account ledger only after it is approved.






### Cash receive and expense IOU approval emails

Same as expenses: a cash receive (manual or imported) mails every user with `ac_balance_receive.approve`, and an expense IOU mails every user with `ac_expense_iou.approve`. Nothing is posted to the ledger until it is approved.

### Day end report (cash in hand + day expenses)

Page: **Reports > Day End Report** (`/admin/acc-sfl/reports/day-end`). It shows each account's opening, received, expenses, IOUs and cash in hand from the posted ledger, the pending (waiting approval) in / out with the balance after approval, the day's expenses, and the list of everything waiting for approval.

The same report is mailed as an HTML email every day to every user with the `ac_day_end_report.view` permission (Roles Setup) and a valid email. It can be turned off, or sent to hand-picked people instead, from **Settings > Mail Notifications > Accounts: Day End Report**.

> If `APPROVAL_TEST_RECIPIENTS` is set in `.env`, every other mail goes only to those addresses — remove it on the live server. The Day End Report is the exception: it still goes to its normal recipients and the test addresses are added to them (needs the `X-Keep-Recipients` handling in erp-suhana's `AppServiceProvider::redirectAllMailInTestMode()`).
> Several addresses are separated by commas, e.g. `APPROVAL_TEST_RECIPIENTS=info@mestiaque.com,accounts@example.com`.

#### Cron setup (required for the daily mail)

The mail is sent by Laravel's scheduler, so the server needs this single cron entry (run `crontab -e` as the user that owns the project):

```cron
* * * * * cd /home/erpsuhan/public_html && php artisan schedule:run >> /dev/null 2>&1
```

Use the full PHP path if `php` is not on cron's PATH (for example `/usr/local/bin/php`; check with `which php`). On cPanel: **Cron Jobs > Add New Cron Job**, set "Once Per Minute", and paste the command part.

The report is sent at **21:00** by default. To change the time, set it in `.env` and clear the config cache:

```env
ACC_DAY_END_REPORT_AT=20:00
```

```bash
php artisan config:clear
```

Check and test:

```bash
php artisan schedule:list                                    # should list acc-sfl:day-end-report
php artisan acc-sfl:day-end-report --to=you@example.com     # send now to one address
php artisan acc-sfl:day-end-report --date=2026-10-17        # send a past day to the normal recipients
```

The page also has a **Send** button that mails the selected day right away.
