# Contributing to EnkiFlow

Welcome to the EnkiFlow community! We appreciate your interest in making our project better. The following guidelines will help you get started.

## Git Workflow

- The default branch is `main` and active development happens on `develop`.
- Create short-lived feature branches from `develop` using the `feature/*` naming pattern.
- Submit pull requests against `develop` unless you are preparing an urgent hotfix for `main`.

## Coding Standards

- **PHP**: Follow the [PSR-12](https://www.php-fig.org/psr/psr-12/) standard. Run `vendor/bin/pint` (Laravel Pint) to fix style issues.
- **React**: Use ESLint and Prettier. Run `pnpm lint` and `pnpm format` before committing.
- **Commit messages**: Use [Conventional Commits](https://www.conventionalcommits.org/) to keep history readable.
- **Hooks**: Husky and commitlint are configured to enforce standards automatically.

## Local Setup

1. **Clone the repo** and install PHP dependencies using [Laravel Sail](https://laravel.com/docs/sail) or [Herd](https://herd.laravel.com).
   ```bash
   # With Sail
   ./vendor/bin/sail up -d
   sail composer install
   sail artisan key:generate
   
   # With Herd
   composer install
   php artisan key:generate
   ```
2. **Install Node packages** with `pnpm install`.
3. **Run the dev server**:
   ```bash
   pnpm dev   # runs vite dev
   ```

## Issue Guidelines

- Provide a clear and minimal reproduction whenever possible.
- Include relevant logs and screenshots.
- Apply appropriate labels such as `bug`, `enhancement`, or `question`.

## Pull Request Checklist

- [ ] Tests added or updated.
- [ ] Documentation updated.
- [ ] GitHub Actions are green.
- [ ] PR linked to its corresponding issue.

## Reviews

- Maintainers aim to review pull requests within **48 hours**.
- See the `CODEOWNERS` file for the list of reviewers responsible for specific paths.

## Getting Started

Look for issues marked with `good first issue` if you are new to the project. Join our [Discord](https://discord.gg/enkiflow) to chat with other contributors.

Thank you for helping improve EnkiFlow!
