# UI Implementation Plan

## Project Overview

Charity Management System - Arabic RTL admin panel built with Laravel 13, Livewire, Blade Components, and Tailwind CSS v4.

## Current State

- **Framework**: Laravel 13.20.0, PHP 8.4
- **Frontend**: Tailwind CSS v4 (CSS-first config), Vite 8
- **Database**: SQLite (default)
- **Missing**: Livewire, Alpine.js, Auth scaffolding, all views/layouts/components

## Implementation Phases

### Phase 1: Inspection & Documentation ✅
- [x] Inspect project structure
- [x] Create implementation plan
- [x] Document decisions

### Phase 2: Foundation
- [ ] Install Livewire
- [ ] Install Alpine.js
- [ ] Run default migrations
- [ ] Create user_preferences migration
- [ ] Create auth scaffolding (manual Livewire-based)
- [ ] Set up Arabic locale and RTL

### Phase 3: Design Tokens
- [ ] Create tokens.css with CSS custom properties
- [ ] Define primary brown palette
- [ ] Define accent color presets
- [ ] Define semantic colors
- [ ] Typography scale
- [ ] Spacing scale
- [ ] Border radius tokens
- [ ] Shadow tokens
- [ ] Glass effect tokens
- [ ] Motion/transition tokens
- [ ] z-index layers
- [ ] UI density tokens

### Phase 4: Login UI
- [ ] Auth layout (full-screen, separate from app)
- [ ] Brown gradient background
- [ ] Glass card login form
- [ ] Username/password fields with show/hide
- [ ] Validation messages
- [ ] Loading state on button
- [ ] Auto-complete attributes

### Phase 5: App Shell
- [ ] App layout with sidebar + content
- [ ] RTL collapsible sidebar (320px → ~90px)
- [ ] Sidebar navigation with groups
- [ ] Mobile drawer with overlay
- [ ] User footer in sidebar
- [ ] wire:navigate for SPA-like navigation
- [ ] Top loading progress bar
- [ ] Breadcrumb / page header

### Phase 6: Global States
- [ ] Top progress bar (Livewire navigate events)
- [ ] Toast notification system
- [ ] Offline overlay with health ping
- [ ] Session expired modal (intercept 419)
- [ ] Global confirm modal

### Phase 7: Core UI Components
- [ ] Button (primary, secondary, outline, ghost, danger, sizes)
- [ ] Input (text, password, number, money, phone, national ID)
- [ ] Textarea
- [ ] Select / Searchable Select / Multi Select
- [ ] Checkbox / Radio / Switch
- [ ] Date inputs
- [ ] File/Image upload
- [ ] Validation message component
- [ ] Card / Statistics Card
- [ ] Status Badge
- [ ] Avatar
- [ ] Data Table / Responsive Table
- [ ] Pagination
- [ ] Search Bar / Filters
- [ ] Empty State / Error State / Skeleton
- [ ] Modal / Confirm Modal / Drawer
- [ ] Dropdown / Tooltip / Popover
- [ ] Alert
- [ ] Tabs / Accordion / Stepper
- [ ] Breadcrumb
- [ ] Timeline / Activity Log

### Phase 8: UI Kit
- [ ] /developer/ui-kit route (protected)
- [ ] Display all components with all states

### Phase 9: Demo Screens
- [ ] Dashboard (stats, urgent items, activity, quick actions)
- [ ] Families List (search, filters, table/cards, pagination)
- [ ] Research Wizard (multi-step form with stepper)
- [ ] Family Details (header card, tabs with mock data)

### Phase 10: Review & Polish
- [ ] Fix bugs
- [ ] Unify components
- [ ] Update documentation
- [ ] Run tests
- [ ] Manual testing (Desktop, Tablet, Mobile, RTL)

## Key Decisions

1. **Database**: Using SQLite as configured (prompt mentions MySQL but project uses SQLite)
2. **Auth**: Custom Livewire-based auth (no Breeze - prompt forbids external SPA frameworks)
3. **Alpine.js**: Only for minimal client-side interactivity (tooltips, dropdowns)
4. **Font**: Use Tajawal as the Arabic-optimized primary interface font
5. **RTL**: Set `dir="rtl"` and `lang="ar"` on html element globally

## Routes Structure

```
GET  /login                    → Login page (guest)
POST /login                    → Handle login
POST /logout                   → Handle logout
GET  /                         → Dashboard (auth)
GET  /dashboard                → Dashboard (auth)
GET  /families                 → Families list (auth)
GET  /families/create          → Create family (auth)
GET  /families/{family}        → Family details (auth)
GET  /research/create          → Research wizard (auth)
GET  /developer/ui-kit         → UI Kit (auth + admin)
```

Placeholder routes for all sidebar navigation items.
