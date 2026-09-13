# Changelog

All notable changes to `laravel-ai-evaluation` will be documented in this file

## Unreleased

- Changed eval scaffolding and documentation to use the Artisan eval runner exclusively.
- Removed the Pest-specific `make:ai-evals --type=pest` generator mode.

## 1.0.0 - 2026-04-18

- Initial release of Laravel AI Evaluation
- Added fluent eval API with deterministic and LLM-as-judge expectations
- Added standalone runner via `php artisan ai-evals:run`
