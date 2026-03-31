# Skill Registry

## User Skills
| Skill | Trigger |
|-------|---------|
| branch-pr | When creating a pull request, opening a PR, or preparing changes for review. |
| go-testing | When writing Go tests, using teatest, or adding test coverage. |
| issue-creation | When creating a GitHub issue, reporting a bug, or requesting a feature. |
| judgment-day | Parallel adversarial review protocol that launches two independent blind judge sub-agents... |
| skill-creator | Creates new AI agent skills following the Agent Skills spec. |

## Project Skills
| Skill | Trigger |
|-------|---------|
| scout-development | Develops full-text search with Laravel Scout. |
| tailwindcss-development | Always invoke when the user's message includes 'tailwind' in any form. |

## Compact Rules
### Foundations
- Project uses PHP 8.4, Laravel 12, Livewire 3, Filament 3, Tailwind 3.
- Use strict typing `declare(strict_types=1);`.
- Use descriptive variable/method names (e.g., `isRegisteredForDiscounts`).
- Prefer PHPDoc blocks over inline comments.

### Architecture & DB
- Eloquent relationships > Raw DB queries. Avoid `DB::`. Prevent N+1.
- Controller validation should use Form Requests.
- Use API Resources for APIs.
- Use `php artisan make:` for boilerplate.

### Testing & Quality
- Write PHPUnit tests. Run minimal subset via `php artisan test --compact`.
- Format code via `vendor/bin/pint --dirty --format agent` before finalizing.
- Use `ShouldQueue` for long-running operations.
- `env()` is forbidden outside config files; use `config()`.