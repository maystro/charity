# Components

## Naming Convention

All UI components use the `<x-ui.*>` prefix:

```blade
<x-ui.button>Click</x-ui.button>
<x-ui.input name="email" />
<x-ui.modal />
```

## Component Categories

### Buttons
- `<x-ui.button>` — Primary, secondary, outline, ghost, danger, success
- Sizes: sm, md, lg
- States: default, hover, focus, active, disabled, loading

### Form Controls
- `<x-ui.input>` — Text, password, number, money, phone, national ID
- `<x-ui.textarea>`
- `<x-ui.select>` — Single, searchable, multi
- `<x-ui.checkbox>`
- `<x-ui.radio>`
- `<x-ui.switch>`
- `<x-ui.date-input>`
- `<x-ui.file-upload>`
- `<x-ui.validation-message>`

### Data Display
- `<x-ui.card>` — Standard and statistics variants
- `<x-ui.badge>` — Status badges
- `<x-ui.avatar>`
- `<x-ui.table>` — Responsive data table
- `<x-ui.pagination>`
- `<x-ui.empty-state>`
- `<x.ui.skeleton>`

### Interaction
- `<x-ui.modal>`
- `<x-ui.confirm-modal>`
- `<x-ui.drawer>`
- `<x-ui.dropdown>`
- `<x-ui.tooltip>`
- `<x-ui.alert>`
- `<x-ui.tabs>`
- `<x-ui.accordion>`
- `<x-ui.stepper>`
- `<x-ui.breadcrumb>`
- `<x-ui.toast>` (Livewire)

### Layout
- `<x-layout.app>` — Main app layout
- `<x-layout.auth>` — Auth layout

## Rules

1. All components support RTL
2. All components support three sizes (sm, md, lg)
3. All components use design tokens
4. All components have accessible labels
5. All components support keyboard focus
6. No business logic in UI components
