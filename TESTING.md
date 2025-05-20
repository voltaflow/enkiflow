# TESTING.md

This document outlines the testing strategy for **EnkiFlow** and explains how to run the different test suites locally.

## Test Pyramid

The project follows a test pyramid with three main layers:

1. **Pest unit tests** – fast tests that cover individual classes and services.
2. **Laravel integration tests** – HTTP and database level tests that exercise the framework.
3. **Playwright end-to-end tests** – full browser tests of the React front‑end.

## Running Tests Locally

```bash
php artisan test        # Runs Pest unit and integration tests
pnpm test:e2e           # Runs Playwright end‑to‑end tests
```

Use these commands from the project root. The `php artisan test` command executes both unit and integration tests using Pest. The `pnpm test:e2e` command runs Playwright to test the React UI in a headless browser.
