---
name: malisafi-ui-design
description: "Use when: designing or styling UI components, templates, CSS, or HTML for the Malisafi MLS plugin. Covers the design token system, color palette, typography, spacing, grid layout rules, component patterns (buttons, cards, badges, forms), and anti-patterns to avoid. REQUIRED when writing any new CSS, PHP templates, shortcode output, or admin UI."
---

# Malisafi MLS — UI Design Skill

## When to Use This Skill

Invoke for any task involving:
- Writing or editing CSS files in `assets/css/`
- Creating PHP templates in `templates/` or `admin/templates/`
- Building shortcode HTML output
- Designing admin dashboard widgets or pages
- Adding new UI components (cards, modals, filters, forms)

---

## 1. Design Tokens — CSS Variables

All styles **must** use CSS variables from `assets/css/variables.css`. Never hardcode raw values.

### Color Palette

```css
/* Brand Colors */
--color-blue: #1e5277;         /* Primary blue — buttons, links, headers */
--color-dark-gray: #b3b3b3;    /* Secondary text, borders */
--color-light-gray: #e8ebed;   /* Backgrounds, dividers */
--color-ash-white: #f4f5f6;    /* Page backgrounds, subtle fills */
--color-gold: #b4ab74;         /* Accent — h2 headings, highlights, badges */
--color-red: #dc2626;          /* Danger, errors, destructive actions */
```

### Semantic Text Colors

```css
--text-base: #000000;          /* Body text */
--text-links: #b3b3b3;         /* Default link color */
--text-headings: #b3b3b3;      /* h1, h3–h6 */
--text-h2: #b4ab74;            /* h2 — gold accent */
```

### Button Colors

```css
--btn-primary-bg: #1e5277;
--btn-primary-text: #f4f5f6;
--btn-secondary-bg: #e8ebed;
--btn-secondary-text: #1e5277;
--btn-danger: #dc2626;
```

### Depth & Focus

```css
--mls-focus-ring: rgba(30, 82, 119, 0.15);  /* Focus outlines */
--shadow-sm: 0 1px 2px rgba(0, 0, 0, 0.05);
--shadow-md: 0 2px 4px rgba(0, 0, 0, 0.1);
--shadow-lg: 0 4px 8px rgba(0, 0, 0, 0.1);
```

---

## 2. Typography

**Font**: `'Akatab'` (primary), with system fallback stack.

```css
--font-family: 'Akatab', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
--font-size-base: 16px;
```

### Heading Scale

| Variable | Size | Color Token |
|----------|------|-------------|
| `--font-size-h1` | 40px | `--text-h1` (gray) |
| `--font-size-h2` | 35px | `--text-h2` (gold) |
| `--font-size-h3` | 30px | `--text-h3-h6` (gray) |
| `--font-size-h4` | 25px | `--text-h3-h6` |
| `--font-size-h5` | 20px | `--text-h3-h6` |
| `--font-size-h6` | 18px | `--text-h3-h6` |

- Line height body: `1.6`
- Line height headings: `1.3`
- `margin-top: 0` on all headings

---

## 3. Spacing

Use spacing tokens consistently. Never use arbitrary pixel values.

```css
--spacing-xs:  4px;
--spacing-sm:  8px;
--spacing-md:  12px;
--spacing-lg:  16px;
--spacing-xl:  24px;
--spacing-xxl: 32px;
```

---

## 4. Border Radius

```css
--radius-sm: 3px;   /* Tags, small badges */
--radius-md: 4px;   /* Inputs, buttons */
--radius-lg: 6px;   /* Cards, panels */
```

---

## 5. Transitions

```css
--transition-fast:   0.15s ease-in-out;   /* Hover states, micro-interactions */
--transition-normal: 0.2s ease-in-out;    /* Standard transitions */
```

---

## 6. Z-Index Layers

```css
--z-dropdown: 100;
--z-modal:    200;
--z-tooltip:  300;
```

Always use these for layered UI. Never write `z-index: 9999`.

---

## 7. Component Patterns

### Buttons

```css
/* Primary */
.malisafi-btn,
.malisafi-btn-primary {
    background: var(--btn-primary-bg);
    color: var(--btn-primary-text);
    padding: var(--spacing-sm) var(--spacing-xl);
    border-radius: var(--radius-md);
    border: none;
    font-family: var(--font-family);
    font-size: var(--font-size-base);
    cursor: pointer;
    transition: var(--transition-fast);
}
.malisafi-btn-primary:hover {
    background: #164060;  /* darken --color-blue */
    transform: translateY(-1px);
}

/* Secondary */
.malisafi-btn-secondary {
    background: var(--btn-secondary-bg);
    color: var(--btn-secondary-text);
    border: 1px solid var(--color-light-gray);
}

/* Danger */
.malisafi-btn-danger {
    background: var(--btn-danger);
    color: #fff;
}
```

### Cards

```css
.malisafi-card {
    background: #ffffff;
    border: 1px solid var(--color-light-gray);
    border-radius: var(--radius-lg);
    padding: var(--spacing-xl);
    box-shadow: var(--shadow-sm);
}
.malisafi-card:hover {
    box-shadow: var(--shadow-md);
}
```

### Forms & Inputs

```css
.malisafi-input,
.malisafi-select {
    width: 100%;
    padding: var(--spacing-sm) var(--spacing-md);
    border: 1px solid var(--color-dark-gray);
    border-radius: var(--radius-md);
    font-family: var(--font-family);
    font-size: var(--font-size-base);
    color: var(--text-base);
    background: #ffffff;
    transition: var(--transition-fast);
}
.malisafi-input:focus {
    outline: none;
    border-color: var(--color-blue);
    box-shadow: 0 0 0 3px var(--mls-focus-ring);
}
```

### Badges / Status Pills

```css
.malisafi-badge {
    display: inline-block;
    padding: 2px var(--spacing-sm);
    border-radius: var(--radius-sm);
    font-size: 12px;
    font-weight: 600;
}
.malisafi-badge-primary  { background: var(--color-blue);  color: var(--color-ash-white); }
.malisafi-badge-gold     { background: var(--color-gold);  color: #ffffff; }
.malisafi-badge-success  { background: #00a32a;            color: #ffffff; }
.malisafi-badge-warning  { background: #f0c33c;            color: var(--text-base); }
.malisafi-badge-danger   { background: var(--color-red);   color: #ffffff; }
.malisafi-badge-neutral  { background: var(--color-light-gray); color: var(--color-dark-gray); }
```

---

## 8. Property Grid Layout Rules

**CRITICAL RULE**: Property cards must be **direct children** of `.malisafi-properties-grid`. No wrapper divs between the grid container and cards.

```html
<!-- ✅ CORRECT -->
<div class="malisafi-properties-grid">
    <div class="property-card">...</div>
    <div class="property-card">...</div>
</div>

<!-- ❌ WRONG — wrapper breaks CSS grid -->
<div class="malisafi-properties-grid">
    <div class="properties-container">
        <div class="property-card">...</div>
    </div>
</div>
```

Grid styles live in `assets/css/property-grids-unified.css`.

---

## 9. Responsive Design

- Mobile-first CSS — base styles for small screens, `@media (min-width: ...)` for larger
- Standard breakpoints: `576px`, `768px`, `992px`, `1200px`
- All containers: `max-width: 100%; overflow-x: hidden;`
- Use `box-sizing: border-box` on all elements (already set globally)

```css
/* Prevent horizontal scroll */
.malisafi-section {
    max-width: 100%;
    overflow-x: hidden;
}
```

---

## 10. Template Structure Conventions

### PHP Template (public)

```php
<?php
/**
 * Template: [Component Name]
 * Used by shortcode: [malisafi_shortcode_name]
 */
if ( ! defined( 'ABSPATH' ) ) exit;
?>
<div class="malisafi-[component-name]">

    <div class="malisafi-[component-name]__header">
        <h2><?php esc_html_e( 'Title', 'malisafi-mls' ); ?></h2>
    </div>

    <div class="malisafi-[component-name]__body">
        <!-- content -->
    </div>

</div>
```

### CSS File Header

```css
/**
 * Malisafi MLS — [Component Name] Styles
 * Depends on: variables.css (must be loaded first)
 */
```

---

## 11. Anti-Patterns — Never Do These

| ❌ Wrong | ✅ Right |
|---------|---------|
| `color: #1e5277` | `color: var(--color-blue)` |
| `font-size: 16px` (inline) | `font-size: var(--font-size-base)` |
| `margin: 8px` | `margin: var(--spacing-sm)` |
| `border-radius: 4px` | `border-radius: var(--radius-md)` |
| `z-index: 9999` | `z-index: var(--z-modal)` |
| `transition: 0.2s` | `transition: var(--transition-normal)` |
| Wrapper div inside grid | Direct children only |
| `echo` in shortcode | `return` buffered output |

---

## 12. Admin UI Guidance

- Use WordPress admin color tokens (`--wp-blue`, `--wp-gray-*`) only in admin-facing pages
- Malisafi brand tokens can appear in admin dashboard widgets for on-brand look
- Follow WP admin layout with `.wrap` container
- Use `.notice`, `.notice-success`, `.notice-error` for admin messages (native WP classes)

---

## Key Files Reference

| File | Purpose |
|------|---------|
| [assets/css/variables.css](../../../assets/css/variables.css) | All CSS tokens — load this first |
| [assets/css/property-grids-unified.css](../../../assets/css/property-grids-unified.css) | Property grid layout |
| [assets/css/public.css](../../../assets/css/public.css) | Public-facing styles |
| [assets/css/dashboards.css](../../../assets/css/dashboards.css) | Dashboard styles |
| [assets/css/admin.css](../../../assets/css/admin.css) | Admin area styles |
| [DESIGN-SYSTEM.md](../../../DESIGN-SYSTEM.md) | Design system documentation |
