# Project Guide: Laravel Application (Eloquent & Blade)

This document contains strict rules, guidelines, and commands that Claude MUST follow during development, testing, and automated code reviews.

1. ## System Commands & Workflow

Before finalizing any task, creating a commit, or finishing a review, you must execute verification.

- **Backend & Feature Tests:** `php artisan test` (or `./vendor/bin/pest`)
- **Static Analysis:** `php artisan lounge:check` (if using Larastan) or `./vendor/bin/phpstan analyse`
- **Code Style Formatting:** `composer exec php-cs-fixer fix` or `php artisan pint`
- **Frontend Assets:** `npm run build` (Vite compilation for Blade assets)
- **Cache Clearing:** `php artisan optimize:clear`

CRITICAL: Never commit code that breaks the test suite or fails the code style check. Fix errors autonomously.

## 2. Automated Code Review Checklist

### Laravel & Architecture Guidelines

- **Skinny Controllers, Fat Models:** Controllers must only handle HTTP routing and request validation. Delegate business logic to Service Classes, Action Classes, or Eloquent Models.
- **Form Requests:** Always use dedicated Form Request classes (`php artisan make:request`) for incoming data validation. Do not validate directly inside the controller method.
- **Blade Views:** Keep Blade files purely structural. Do not execute heavy SQL queries or complex logic inside Blade. Use Blade Components (`<x-alert />`) for reusable UI elements.
- **Database Transactions:** Use explicit database transactions (`DB::transaction(...)`) for multi-step write operations to ensure data integrity.
- **Queues & Background Jobs:** Heavy or slow tasks (emails, PDF generation, external API calls) must be dispatched to queues (`implements ShouldQueue`).
- **API Resources:** Use API Resources for JSON responses instead of returning raw models.

### Eloquent ORM Best Practices

- **N+1 Query Prevention:** Always eager-load relationships using `with()` when loading data that will be looped over. (Tip: Ensure `Model::preventLazyLoading()` is enabled in development).
- **Mass Assignment:** Define `$fillable` or `$guarded` properties on every model. Never use unsafe `request()->all()` directly in `create()` without validation.
- **Database Migrations:** Every migration must be reversible (must contain a working `down()` method). Never modify an existing, committed migration file—always create a new one.
- **Return Parameters from Backend:** Use `compact('variable')` instead of `$variable` where appropriate in controllers.

### Security & Privacy Guidelines (GDPR / DSGVO)

- **XSS Protection:** Use `{{ $variable }}` in Blade for automatic escaping. Only use `{!! $variable !!}` when rendering trusted, sanitized HTML, and explicitly justify its use.
- **CSRF:** Ensure every Blade form includes the `@csrf` directive.
- **SQL-Injection:** Ensure that the code is secure against SQL injection by utilizing Eloquent/Query Builder parameter binding.
- **Local Font Hosting (Google Fonts):** Always self-host Google Fonts (download and serve locally via public assets/CSS) to prevent IP transmission to third parties (GDPR compliance).

### Legal Requirements (Impressum & Privacy)

- **Mandatory Legal Pages:** Whenever an application goes live (e.g., on netcup), it must always include a legal notice (**Impressum**) and a privacy policy (**Datenschutzerklärung**).
- **LEGAL Placeholders:** When creating an Impressum and/or Privacy Policy, always prepare distinct `LEGAL` configuration variables or placeholders (e.g., in config or env) for company details, address, contact, and VAT ID.

### Frontend & Component Guidelines (Tailwind, Alpine.js)

- **Alpine.js State:** Keep UI state localized in `x-data` components. Avoid unnecessary global stores.
- **Tailwind CSS:** Use clean utility classes without inline styles. Extract repetitive UI patterns into reusable Blade components.
- **Custom Dialogs:** Always use custom UI dialogs (e.g., custom Alpine.js / Tailwind modal components like a `confirm-dialog`) instead of native browser dialogs (`alert()`, `confirm()`, `prompt()`).

### Testing Requirements

- Every new feature or bug fix must have a corresponding Feature Test (`php artisan make:test WorkTest`).
- Use Laravel's built-in HTTP testing assertions or Pest expectations (e.g., `expect($response)->toBeSuccessful()`).
- Use database transactions (`use RefreshDatabase;` or `use LazilyRefreshDatabase;`) to keep tests isolated.
- Mock external HTTP requests using `Http::fake()`.

## 3. Naming & Style Conventions

- **Laravel Standard:** Models are Singular (User), Tables are Plural (users), Controllers are PascalCase (UserController).
- **Coding Style:** Strictly follow Laravel Pint / PSR-12 coding standards.
- **Responsive Design:** Design usable for Desktop, Laptop, Tablet and Smartphone.
- **Accessibility:** Pay attention to accessibility for every HTML-element (ARIA attributes, semantic tags).
- **Font-Size:** Select a legible font size. Minimum 12px.

### Hosting, Deployment & Automation (Netcup & Apache Specifics)

- **Forbidden Directory Names (`/icon` / `/icons`):** Never name asset directories, route prefixes, or public paths `icon` or `icons`. Netcup (and default Apache setups) uses internal aliases for system icons on `/icons/`, which will override and block public access to project assets. Always use alternative names like `app-icon` or `app-icons`.
- **Public Directory Security:** Keep strict `.htaccess` rules for Apache host environments to prevent directory browsing.
- **Deployment Script (`deploy.sh`):** Deployment must be handled exclusively via a `deploy.sh` script in the project root. Claude-Code is explicitly authorized and expected to execute `deploy.sh` for deployments.

## 4. Git & Commit Guidelines

- **Format:** Conventional Commits (e.g., `feat(blade): design new checkout component`, `fix(eloquent): eager load relationships to fix N+1 issue`).

## 5. General & Helpers

- **Sign-up/Registration:** If there is a sign-up/registration function, ensure that the user also has the option to change his password or forget it.
- **start.bat**: Create a start.bat in projectfolder for starting the app - frontend and backend (Vite + PHP server).
- **stop.bat**: Create a stop.bat in projectfolder for stopping the app - frontend and backend processes.
