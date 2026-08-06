# Decisions

## D001: Database Engine
- **Date**: 2026-07-14
- **Decision**: Use SQLite (already configured) instead of MySQL as mentioned in the prompt
- **Reason**: Project already configured with SQLite, no MySQL available locally
- **Impact**: No schema differences for this phase

## D002: Auth System
- **Date**: 2026-07-14
- **Decision**: Build custom Livewire-based auth instead of using Breeze
- **Reason**: Prompt forbids external SPA frameworks, Breeze uses Inertia which is forbidden
- **Impact**: More custom code but full control over RTL and UI

## D003: Alpine.js Usage
- **Date**: 2026-07-14
- **Decision**: Include Alpine.js for minimal client-side interactivity
- **Reason**: Tooltips, dropdowns, and small UI interactions need client-side JS
- **Impact**: Alpine.js is lightweight and Livewire-compatible

## D004: CSS Architecture
- **Date**: 2026-07-14
- **Decision**: Use CSS custom properties (tokens) with Tailwind v4 @theme
- **Reason**: Tailwind v4 supports CSS-first config, tokens work for dynamic theming
- **Impact**: Accent color changes without page reload via CSS variables

## D005: Font Choice
- **Date**: 2026-07-30
- **Decision**: Use Tajawal as the primary interface font
- **Reason**: The application is Arabic RTL and Tajawal provides better Arabic readability and visual consistency.
- **Impact**: Tajawal is loaded through the Laravel Vite Fonts integration and used by the Tailwind sans token and document body.

## D006: Naming Convention for Components
- **Date**: 2026-07-14
- **Decision**: Use `<x-ui.*>` for UI components, `<x-layout.*>` for layout components
- **Reason**: Follows prompt specification, clean separation
- **Impact**: All UI components under resources/views/components/ui/

## D007: Re-Assessment Versioning Pattern
- **Date**: 2026-07-17
- **Decision**: Use `families.current_assessment_id` → `family_assessments` (round 1,2,3) with sub-tables carrying `family_assessment_id`
- **Reason**: Keeps historical data immutable, allows comparison between rounds, family stays approved until new assessment is approved
- **Impact**: All sub-tables (members, income, resources, burdens, housing, aids) now have `family_assessment_id` FK; `ReAssessmentService` copies data on start

## D008: Polymorphic Alerts
- **Date**: 2026-07-17
- **Decision**: Use `alertable_type`/`alertable_id` morphTo on `alerts` table
- **Reason**: Allows future alert types beyond re-assessment (aid requests, visits, etc.) without schema changes
- **Impact**: `Alert::alertable()` returns the related model (Family, etc.)

## D009: System Settings as Key-Value Store
- **Date**: 2026-07-17
- **Decision**: Use a single `system_settings` table with key/value/type columns instead of a config file
- **Reason**: Settings need to be editable at runtime via UI; type casting (integer/boolean/string) handles different value types
- **Impact**: `SystemSetting::get()` and `set()` with automatic type casting

## D010: Alert Generation via Scheduled Command
- **Date**: 2026-07-17
- **Decision**: Generate alerts via `php artisan app:generate-alerts` scheduled daily at 02:00
- **Reason**: Decouples alert detection from user requests; runs consistently even without traffic; avoids per-request overhead
- **Impact**: `ReAssessmentAlertService::generate()` is idempotent — safe to run repeatedly without duplicates
