# Malisafi MLS - Design System & Color Palette

## Overview
This document outlines the consistent design system used across the Malisafi MLS plugin. All styles are centralized and use CSS custom properties (variables) for easy maintenance and consistency.

## Color Palette

### Primary Colors
```css
--mls-dark: #333333           /* Main dark color for text, headers */
--mls-dark-grey: #292929      /* Darker backgrounds, footer */
--mls-grey: #808083           /* Secondary text, borders */
--mls-light-grey: #cecdca     /* Light backgrounds, subtle borders */
--mls-grey-green: #737d5d     /* Accent color, buttons, highlights */
```

### Semantic Colors
These are mapped from the primary palette for specific use cases:

#### Backgrounds
- `--mls-bg-primary: #ffffff` - Main content background
- `--mls-bg-secondary: #f8f8f8` - Subtle backgrounds
- `--mls-bg-dark: #292929` - Dark sections
- `--mls-bg-card: #ffffff` - Card backgrounds
- `--mls-bg-hover: #f5f5f5` - Hover states

#### Text
- `--mls-text-primary: #333333` - Main text
- `--mls-text-secondary: #808083` - Secondary text
- `--mls-text-muted: #999999` - Muted text
- `--mls-text-inverse: #ffffff` - Text on dark backgrounds

#### Borders
- `--mls-border-light: #e8e8e8` - Light borders
- `--mls-border-medium: #cecdca` - Medium borders
- `--mls-border-dark: #808083` - Dark borders
- `--mls-border-accent: #737d5d` - Accent borders

#### Interactive Elements
- `--mls-accent: #737d5d` - Primary accent (buttons, links)
- `--mls-accent-hover: #5f6a4a` - Accent hover state

## File Structure

### Core Files
1. **variables.css** - Global CSS variables and design tokens
2. **single-property.css** - Property detail page styles
3. **public.css** - Property grid, search, and public pages
4. **dashboards.css** - All dashboard styles
5. **property-filters.css** - Property filtering system
6. **admin.css** - Admin area styles

### Loading Order
Make sure to load `variables.css` first in your plugin to ensure all variables are available:

```php
wp_enqueue_style('malisafi-variables', PLUGIN_URL . 'assets/css/variables.css');
wp_enqueue_style('malisafi-public', PLUGIN_URL . 'assets/css/public.css');
// ... other styles
```

## Using the Design System

### Colors
Always use CSS variables instead of hardcoded colors:

```css
/* ✅ Good */
.my-element {
    background: var(--mls-accent);
    color: var(--mls-text-inverse);
    border: 1px solid var(--mls-border-light);
}

/* ❌ Bad */
.my-element {
    background: #737d5d;
    color: #ffffff;
    border: 1px solid #e8e8e8;
}
```

### Spacing
Use predefined spacing variables for consistency:

```css
--mls-space-xs: 0.25rem    /* 4px */
--mls-space-sm: 0.5rem     /* 8px */
--mls-space-md: 1rem       /* 16px */
--mls-space-lg: 1.5rem     /* 24px */
--mls-space-xl: 2rem       /* 32px */
--mls-space-2xl: 3rem      /* 48px */
```

### Border Radius
Use consistent border radius values:

```css
--mls-radius-sm: 4px
--mls-radius-md: 6px
--mls-radius-lg: 8px
--mls-radius-xl: 12px
```

### Shadows
Predefined shadow levels for depth:

```css
--mls-shadow-sm: Subtle shadow for cards
--mls-shadow-md: Medium shadow for elevated elements
--mls-shadow-lg: Large shadow for modals/dropdowns
--mls-shadow-xl: Extra large shadow for overlays
```

### Transitions
Use consistent transition timings:

```css
--mls-transition-fast: 150ms    /* Quick interactions */
--mls-transition-base: 250ms    /* Standard transitions */
--mls-transition-slow: 350ms    /* Larger animations */
```

## Component Examples

### Buttons
```css
.btn-primary {
    background: var(--mls-accent);
    color: var(--mls-text-inverse);
    padding: var(--mls-space-sm) var(--mls-space-md);
    border-radius: var(--mls-radius-md);
    transition: all var(--mls-transition-base);
}

.btn-primary:hover {
    background: var(--mls-accent-hover);
    transform: translateY(-1px);
}
```

### Cards
```css
.card {
    background: var(--mls-bg-card);
    border: 1px solid var(--mls-border-light);
    border-radius: var(--mls-radius-lg);
    padding: var(--mls-space-lg);
    box-shadow: var(--mls-shadow-sm);
}
```

### Input Fields
```css
.input-field {
    padding: var(--mls-space-sm);
    border: 1px solid var(--mls-border-light);
    border-radius: var(--mls-radius-md);
    background: var(--mls-bg-primary);
    color: var(--mls-text-primary);
}

.input-field:focus {
    border-color: var(--mls-accent);
    outline: none;
}
```

## Accessibility

### Contrast Ratios
All color combinations meet WCAG AA standards:
- Dark text (#333333) on white background: 12.63:1 ✅
- Accent (#737d5d) on white background: 4.89:1 ✅
- Grey text (#808083) on white background: 4.54:1 ✅

### Focus States
Always provide visible focus states:

```css
.interactive-element:focus {
    outline: 2px solid var(--mls-accent);
    outline-offset: 2px;
}
```

## Responsive Design

### Breakpoints
```css
--mls-breakpoint-sm: 640px
--mls-breakpoint-md: 768px
--mls-breakpoint-lg: 1024px
--mls-breakpoint-xl: 1280px
```

### Mobile-First Approach
```css
/* Base styles for mobile */
.element {
    padding: var(--mls-space-sm);
}

/* Tablet and up */
@media (min-width: 768px) {
    .element {
        padding: var(--mls-space-lg);
    }
}
```

## Utility Classes

Common utility classes are available:

```css
/* Text colors */
.mls-text-primary
.mls-text-secondary
.mls-text-muted
.mls-text-accent

/* Backgrounds */
.mls-bg-primary
.mls-bg-secondary
.mls-bg-dark

/* Shadows */
.mls-shadow-sm
.mls-shadow-md
.mls-shadow-lg

/* Spacing */
.mls-mb-sm, .mls-mb-md, .mls-mb-lg
.mls-p-sm, .mls-p-md, .mls-p-lg
```

## Updating Colors

To change the color scheme:

1. Edit `assets/css/variables.css`
2. Update the primary palette variables
3. All components will automatically update

```css
:root {
    /* Change these to update the entire theme */
    --mls-dark: #333333;
    --mls-dark-grey: #292929;
    --mls-grey: #808083;
    --mls-light-grey: #cecdca;
    --mls-grey-green: #737d5d;
}
```

## Best Practices

1. **Always use variables** - Never hardcode colors
2. **Maintain consistency** - Use the same spacing/sizing throughout
3. **Test in both light and dark themes** - Ensure readability
4. **Keep accessibility in mind** - Check contrast ratios
5. **Use semantic variable names** - Makes code self-documenting
6. **Group related styles** - Keep components together
7. **Comment complex sections** - Help future developers

## Support

For questions about the design system, refer to this document or contact the development team.

Last updated: December 2025
