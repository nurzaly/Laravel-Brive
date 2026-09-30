# composer.json upgrade diff: Laravel 11 → 12 → 13

Apply these as two separate commits/PRs (Hop 1, then Hop 2), running
`composer update` and your test suite after each. Version numbers below
reflect what the official Laravel upgrade guides specify — adjust any
package you don't actually use.

---

## Hop 1: Laravel 11.x → 12.x

```diff
  "require": {
      "php": "^8.2",
-     "laravel/framework": "^11.0",
+     "laravel/framework": "^12.0",
      "inertiajs/inertia-laravel": "^1.3",
      "tightenco/ziggy": "^1.8"
  },
  "require-dev": {
-     "phpunit/phpunit": "^10.5",
+     "phpunit/phpunit": "^11.0",
      "pestphp/pest": "^2.0",
-     "pestphp/pest-plugin-laravel": "^2.4"
+     "pestphp/pest-plugin-laravel": "^3.0"
  }
```

Then:

```bash
composer update
php artisan test   # or: vendor/bin/pest
```

Notes for this hop:
- Carbon 2.x support is dropped — Carbon 3.x will install automatically as a
  transitive dependency, no action needed unless you pin Carbon directly.
- `nunomaduro/collision` should move to `^8.1` if it's a direct dependency.
- This is intentionally close to a no-op upgrade for most apps.

---

## Hop 2: Laravel 12.x → 13.x

```diff
  "require": {
-     "php": "^8.2",
+     "php": "^8.3",
-     "laravel/framework": "^12.0",
+     "laravel/framework": "^13.0",
      "inertiajs/inertia-laravel": "^1.3",
      "tightenco/ziggy": "^1.8"
  },
  "require-dev": {
-     "phpunit/phpunit": "^11.0",
+     "phpunit/phpunit": "^12.0",
-     "pestphp/pest": "^3.0",
+     "pestphp/pest": "^4.0",
-     "pestphp/pest-plugin-laravel": "^3.0",
+     "pestphp/pest-plugin-laravel": "^5.0",
+     "laravel/tinker": "^3.0"
  }
```

If you use Laravel Boost for the AI-assisted upgrade flow, also add:

```diff
+ "laravel/boost": "^2.0"
```

Then:

```bash
composer update
php artisan test
```

Notes for this hop:
- `laravel/tinker` must be `^3.0` — bump it even if not explicitly listed
  before, since some setups pin it directly.
- If `composer update` still reports `inertiajs/inertia-laravel` or
  `tightenco/ziggy` as blockers, check their changelogs for a version that
  declares support for `laravel/framework ^13.0` and bump the constraint
  explicitly, e.g.:
  ```bash
  composer require inertiajs/inertia-laravel:^2.0 tightenco/ziggy:^2.0
  ```
  (exact version numbers depend on what those projects have shipped by the
  time you upgrade — check Packagist for the latest tagged release that
  supports Laravel 13).

---

## After both hops

Run the code-scan script (`scan-laravel13-changes.sh`) to catch the two
code-level changes composer.json alone won't fix:
- `VerifyCsrfToken::class` / `ValidateCsrfToken::class` references
- `->upsert(` calls missing a `uniqueBy` argument
