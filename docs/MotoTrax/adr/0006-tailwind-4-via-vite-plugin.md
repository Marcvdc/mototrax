# ADR 0006: Tailwind 4 via de Vite-plugin, configuratie in CSS

- **Status:** Accepted
- **Datum:** 2026-11-14
- **Context:** #32 (frontend upgraden naar Tailwind 4)

## Beslissing

De frontend gebruikt Tailwind 4 via **`@tailwindcss/vite`** in `vite.config.js`. Er is geen
`tailwind.config.js` en geen `postcss.config.js` meer. Alle configuratie staat in
`resources/css/app.css`:

- `@import 'tailwindcss'` en `@plugin '@tailwindcss/forms'`;
- `@source` voor de pagination-views in `vendor/` en voor `storage/framework/views`, omdat de
  automatische detectie alles in `.gitignore` overslaat;
- `@theme` voor het Figtree-font;
- een base-blok dat drie standaarden van Tailwind 3 aanhoudt: de border-kleur gray-200, de
  browserstijl van de bestandskiezer (`::file-selector-button`) en `cursor: pointer` op knoppen.

## Alternatieven overwogen

1. **`@tailwindcss/postcss` met een `postcss.config.js`**
   - **Pro:** dichter bij de oude opzet; dit is ook wat `@tailwindcss/upgrade` standaard kiest.
   - **Con:** een extra configbestand en een extra dependency, terwijl de build al door Vite loopt.
     De Vite-plugin is sneller en `@tailwindcss/vite` stond al in `package.json`.
2. **`tailwind.config.js` houden via `@config`**
   - **Con:** halverwege-oplossing. Twee plekken voor configuratie, en de JS-config is in v4
     alleen nog voor compatibiliteit.

## Gevolgen

- **`autoprefixer` en `postcss` zijn weg als devDependencies.** Tailwind 4 doet de prefixing zelf.
- **Klassen in de views zijn hernoemd** (`shadow-sm` naar `shadow-xs`, `rounded` naar
  `rounded-sm`, `outline-none` naar `outline-hidden`, enzovoort). Nieuwe views volgen de v4-namen.
- **`space-x-*` en `space-y-*` werken in v4 met een marge onder elk kind behalve het laatste.** Een
  verborgen laatste kind (zoals een `x-modal`) geeft dan loze ruimte; gebruik daar `gap` of een
  eigen marge.
- **Het kleurpalet is oklch.** Tinten verschillen een paar procent van v3; de layout niet.
- **`welcome.blade.php` is niet gemigreerd.** Die view is de standaard van Laravel 13, al geschreven
  voor Tailwind 4, met inline fallback-CSS die bij de klassen moet blijven passen.

## Openstaande punten

- Het base-blok voor border-kleur, bestandskiezer en cursor kan weg zodra elk element met `border` een eigen kleur
  heeft en de pointer niet meer gewenst is. Dat is een eigen keuze, los van deze upgrade.
