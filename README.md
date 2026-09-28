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
