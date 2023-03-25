# Laravel Translate

Translate missing keys in JSON file, CI friendly.

## Summary

- [About](#about)
- [Features](#features)
- [Prerequisites](#prerequisites)
- [Installation](#installation)
- [Examples](#examples)
- [Tests](#tests)

## About

I have a web app and I always find myself pushing code where I added a new translation key that I forgot to translate in other of my supported languages. I also often forget to add my "static" model field that I should translate (which are most of the time linked to reference tables).

I create this package to help me find out which keys I missed to translate before pushing and deploying in production. I use it on my CI as well in case I forget to run the check locally.

## Features

- Can add missing translation by parsing on Blade and PHP files (supports `__()`, `trans()`, `trans_choice()`, `@lang()` and `@choice()` functions)
- Can use a `--dry-run` option to only check for missing keys (and returns a **non zero code**, useful to put in a CI)
- Can sort translated keys
- can remove unused keys
- Can translate Models's column values

## Prerequisites

This package assumes you heavily rely on JSON files instead of PHP files to store your translations. It will pull all non [short-keys](https://laravel.com/docs/10.x/localization#using-short-keys) translations (like `validation.required`, `home.headline`, ...) and put them in your "lang/[lang].json` file.

I also advice to use plain English as your translation key, for various reasons:

- Your files are more understandable using `__("You must subscribe to the premium plan to use this feature.")` instead of `__("plan.subscribe.warn_message")`
- You don't spend minutes trying to figure out the best short key name to represent the text, you just use the text as the key
- You can easily pass the "[lang].json" file to a company (or a third-party service) to translate the keys in the lang of your choice
- If you have no countable translations, you can omit to configure the "en" translation since your keys are exactly your English translation (Laravel prints the key if the lang file is not found)

## Installation

Install the package:

```bash
composer require --dev khalyomede/laravel-translate
```

Publish the configuration file:

```bash
php artisan vendor:publish --tag "translate"
```

## Examples

- [1. Check for missing translations](#1-check-for-missing-translations)
- [2. Add missing translations](#2-add-missing-translations)
- [3. Customize which folders to parse](#3-customize-which-folders-to-parse)
- [4. Remove translation keys not found](#4-remove-translation-keys-not-found)
- [5. Translate models data](#5-translate-models-data)
- [6. Add raw array of keys](#6-add-raw-array-of-keys)

### 1. Check for missing translations

Call the command using `--dry-run` to only check for missing keys without writing the missing keys.

```bash
php artisan translate --dry-run
```

You can add more langs by editing the `config/translate.php` file.

```php
return [
  "langs" => [
    "fr",
    "en",
    "pt",
  ],
  // ...
];
```

### 2. Add missing translations

Call the command without flags to actually write the missing keys.

```bash
php artisan translate
```

You can add more langs by editing the `config/translate.php` file.

```php
return [
  "langs" => [
    "fr",
    "en",
    "pt",
  ],
  // ...
];
```

### 3. Customize which folders to parse

By default, the folders "app" and "resources/views" are the only that are parsed. If you need to add more, just change the folders in the config file at "config/translate.php".

```php
return [
  // ...
  "include" => [
    "app",
    "resources/views",
  ],
  // ...
];
```

This will instruct the command to find every files ending with `.php` or `.blade.php` in the folders (by searching recursively).

### 4. Remove translation keys not found

If you are sure all the keys are "findable" by the command in your code, you can enable a configuration to automatically remove keys that are not found. Head to the configuration file at "config/translate.php".

```php
return [
  "remove_missing_keys" => true,
  // ...
];
```

### 5. Translate models data

When you use any translation function to display the translated text of a model column, and this model appears to be a statically seeded table that will not be changed otherwise, you might want to pull all texts from the desired column to be translated.

Using `translate.models` config key, you can do it. Head to "config/translate.php":

```php
use App\Models\UserType;

return [
  // ...
  "models" => [
    UserType::class => "name",
  ],
  // ...
];
```

You pass it the name of the model as key, and the name of the column to pull translation from as the value.

This assumes you translate your model like this for example:

```blade
@php
  $userTypes = App\Models\UserType::all();
@endphp

<select name="user_type">
  <option>All</option>
  @foreach ($userTypes as $userType)
    <option value="{{ $userType->id }}>@lang($userType->name)</option>
  @endforeach
</select>
```

### 6. Add raw array of keys

When you have something translation keys not in an Eloquent model nor a hard-coded string like `@lang("Hello world")`, but you still want to include it in the list of translation keys, you can build a custom array or Collection to give to the command.

Add it like this in your "config/translate.php" file:

```php
use App\Constants\InvoiceStatus;

return [
  // ...
  "translatables" => [
    fn () => [ InvoiceStatus::PENDING, InvoiceStatus::SENT, InvoiceStatus::PAID ],
  ]
];
```

You can also return a Collection if you prefer:

```php
use App\Constants\InvoiceStatus;

return [
  // ...
  "translatables" => [
    fn () => collect([InvoiceStatus::PENDING, InvoiceStatus::SENT, InvoiceStatus::PAID]),
  ]
];
```

You can have more than one translatable:

```php
use App\Constants\InvoiceStatus;
use App\Enums\UserRole;

return [
  // ...
  "translatables" => [
    fn () => collect(InvoiceStatus::PENDING, InvoiceStatus::SENT, InvoiceStatus::PAID]),
    fn () => collect(UserRole::cases())->map(fn (UserRole $userRole): string => $userRole->toString()),
  ]
];
```

## Tests

```bash
composer run test
composer run analyse
composer run lint
composer run scan
composer run security
composer run updates
```

or

```bash
composer run all
```
