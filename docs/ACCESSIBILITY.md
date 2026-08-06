# Accessibility

## Requirements

### Keyboard Navigation
- All interactive elements are focusable
- Tab order follows visual order
- Focus ring visible on all focusable elements (using accent color)
- Escape closes modals and dropdowns
- Enter/Space activates buttons and links

### ARIA
- `aria-label` on icon-only buttons
- `aria-describedby` for help text and validation messages
- `aria-expanded` on dropdowns and accordions
- `aria-current="page"` on active sidebar item
- `role="dialog"` on modals
- `aria-live="polite"` on toast notifications

### Color & Contrast
- Text contrast ratio minimum 4.5:1 (normal text)
- Text contrast ratio minimum 3:1 (large text)
- Don't rely on color alone to convey meaning
- Status badges include text + icon + color

### Motion
- Respect `prefers-reduced-motion`
- User can enable Reduced Motion in preferences
- Progress bar animation disabled when reduced motion is on

### Forms
- All inputs have associated `<label>`
- Required fields marked with `aria-required`
- Error messages linked via `aria-describedby`
- Help text linked via `aria-describedby`

### RTL
- All components work correctly in RTL
- No CSS that breaks in RTL (use logical properties: margin-inline, padding-inline)
- Icons that indicate direction are mirrored

### Touch Targets
- Minimum 44x44px for touch targets
- Adequate spacing between interactive elements
