# PHP regression tests

The PHPUnit configuration at the repository root discovers `test-*.php` files
recursively. Run `phpunit` from the plugin root, or use the persistent Laragon
runner from the sibling `noptin-premium` checkout:

```sh
php scripts/phpunit-local.php
php scripts/phpunit-local.php --filter Test_Unsubscribe_Links
php scripts/phpunit-local.php --multisite
```

The Laragon runner's default covers the cached WordPress 6.4 and latest environments.
Setup, version selection, and prerequisites are documented in the sibling
`noptin-premium/tests/README.md`.

First-party regressions belong here so CI runs them on both supported WordPress
versions and in single-site and multisite modes. Database checks use WordPress test
fixtures and the shared `Noptin_Regression_Test_Case`, which restores hooks and email
context and blocks external HTTP and mail delivery. Tests must restore any additional
static registries they mutate. Never bootstrap the developer's local site here.

Six regression fixtures under `fixtures/isolated` define WordPress doubles and run
in clean PHP subprocesses through `Test_Noptin_Isolated_Regressions`. They load only
this repository's packaged `build` code, do not load WordPress, and do not require
sibling source checkouts or installed add-ons. An assertion failure or PHP diagnostic
in a child process fails the parent PHPUnit test.

Third-party plugin checks, connection-library checks, and browser fixture helpers
remain in `noptin-premium/tests`; its README lists the retained scripts and reasons.
