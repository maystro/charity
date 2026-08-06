# UI System

## Architecture

```
resources/
├── css/
│   ├── app.css          → Main entry, imports tokens + tailwind
│   ├── tokens.css       → CSS custom properties (design tokens)
│   ├── themes.css       → Accent color themes + primary palette
│   ├── densities.css    → UI density variants
│   └── utilities.css    → Custom utility classes
├── js/
│   ├── app.js           → Main entry, Alpine.js setup
│   ├── theme-manager.js → Client-side theme application
│   ├── connectivity-manager.js → Offline detection
│   ├── session-manager.js → Session expiry handling
│   └── progress-manager.js → Top progress bar
├── views/
│   ├── components/
│   │   ├── ui/          → Reusable UI components (<x-ui.button>)
│   │   ├── layout/      → Layout components
│   │   └── domain/      → Domain-specific components
│   ├── livewire/
│   │   ├── pages/       → Full-page Livewire components
│   │   └── shared/      → Shared Livewire components
│   └── layouts/
│       ├── auth.blade.php  → Login layout (full screen)
│       └── app.blade.php   → Main app layout (sidebar + content)
```

## Layout Separation

1. **Auth Layout** (`auth.blade.php`): Full-screen, no sidebar, brown gradient
2. **App Layout** (`app.blade.php`): Sidebar + content area with all global states

## Navigation

- All internal links use `wire:navigate`
- No full page refreshes
- Browser back/forward works
- Active sidebar item updates
- Breadcrumb updates
- Top loading bar shows during navigation

## Theming

- Primary brand color: Brown (fixed)
- Accent color: User-selectable (8 presets)
- Applied via CSS custom properties on `<html>` data attributes
- Changes instantly without page reload
- Persisted to DB + localStorage
## Client-Side Preference Application

`window.applyPreferences(prefs)` in `resources/js/app.js` sets:

- `data-accent` from `prefs.accent`
- `data-font-size` from `prefs.fontSize`
- `data-ui-density` from `prefs.density`
- `data-reduced-motion` from `prefs.reducedMotion`

And mirrors them to `localStorage` so preferences survive hard refreshes. The
`livewire:preferences-applied` event is dispatched from
`resources/views/livewire/shared/user-preferences.blade.php` whenever a setting
changes.

## Navigation

- All internal links use `wire:navigate` where possible.
- Top loading bar shows during navigation and is skipped when reduced motion is on.
- Browser back/forward works via Livewire’s persistent layout.

## Shared Components

- `resources/views/livewire/shared/sidebar.blade.php` — collapsible sidebar, active item,
  mobile drawer, user menu.
- `resources/views/livewire/shared/user-preferences.blade.php` — modal to change accent,
  font size, density, reduced motion, and sidebar collapsed state.
- `resources/views/livewire/shared/breadcrumb.blade.php` — dynamic breadcrumb that can be
  updated via the `breadcrumb-updated` browser event.
- `resources/views/components/layout/page-header.blade.php` — page header with title,
  subtitle, breadcrumbs slot, and actions slot.

## Localization

Arabic UI strings are centralized in `lang/ar/ui.php` and referenced with
`__('ui.key')` in views. The application uses RTL direction via `<html dir="rtl">`.
