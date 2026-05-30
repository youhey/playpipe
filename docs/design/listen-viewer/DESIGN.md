---
name: Underground Protocol
colors:
  surface: '#131313'
  surface-dim: '#131313'
  surface-bright: '#393939'
  surface-container-lowest: '#0e0e0e'
  surface-container-low: '#1b1b1b'
  surface-container: '#1f1f1f'
  surface-container-high: '#2a2a2a'
  surface-container-highest: '#353535'
  on-surface: '#e2e2e2'
  on-surface-variant: '#e7bdb6'
  inverse-surface: '#e2e2e2'
  inverse-on-surface: '#303030'
  outline: '#ad8882'
  outline-variant: '#5d3f3b'
  surface-tint: '#ffb4a8'
  primary: '#ffb4a8'
  on-primary: '#690000'
  primary-container: '#c00000'
  on-primary-container: '#ffcdc5'
  inverse-primary: '#c00000'
  secondary: '#92d2d2'
  on-secondary: '#003737'
  secondary-container: '#025253'
  on-secondary-container: '#84c3c3'
  tertiary: '#ffb2bb'
  on-tertiary: '#670021'
  tertiary-container: '#bc0444'
  on-tertiary-container: '#ffcbd0'
  error: '#ffb4ab'
  on-error: '#690005'
  error-container: '#93000a'
  on-error-container: '#ffdad6'
  primary-fixed: '#ffdad4'
  primary-fixed-dim: '#ffb4a8'
  on-primary-fixed: '#410000'
  on-primary-fixed-variant: '#930000'
  secondary-fixed: '#aeeeee'
  secondary-fixed-dim: '#92d2d2'
  on-secondary-fixed: '#002020'
  on-secondary-fixed-variant: '#004f50'
  tertiary-fixed: '#ffd9dc'
  tertiary-fixed-dim: '#ffb2bb'
  on-tertiary-fixed: '#400011'
  on-tertiary-fixed-variant: '#910032'
  background: '#131313'
  on-background: '#e2e2e2'
  surface-variant: '#353535'
  signal-crimson: '#AE0036'
  void-black: '#000000'
  highlight-mint: '#AEEEEE'
  subculture-pink: '#E6325F'
  paper-white: '#F4F6F9'
typography:
  headline-xl:
    fontFamily: Anton
    fontSize: 64px
    fontWeight: '400'
    lineHeight: 72px
    letterSpacing: 0.02em
  headline-lg:
    fontFamily: Anton
    fontSize: 32px
    fontWeight: '400'
    lineHeight: 40px
    letterSpacing: 0.05em
  headline-lg-mobile:
    fontFamily: Anton
    fontSize: 24px
    fontWeight: '400'
    lineHeight: 32px
  body-lg:
    fontFamily: Metropolis
    fontSize: 18px
    fontWeight: '400'
    lineHeight: 28px
  body-md:
    fontFamily: Metropolis
    fontSize: 16px
    fontWeight: '400'
    lineHeight: 24px
  label-md:
    fontFamily: JetBrains Mono
    fontSize: 14px
    fontWeight: '500'
    lineHeight: 20px
  label-sm:
    fontFamily: JetBrains Mono
    fontSize: 12px
    fontWeight: '500'
    lineHeight: 16px
spacing:
  unit: 4px
  gutter: 16px
  margin: 24px
  stack-sm: 8px
  stack-md: 16px
  stack-lg: 32px
  container-max: 1280px
---

## Brand & Style

This design system embodies the "Punk-Geek" aesthetic, a fusion of Jirai-kei fashion sensibilities and digital "Underground Radio" subculture. The brand personality is rebellious, technical, and high-energy. It targets an audience that appreciates the intersection of subculture aesthetics and modern digital utility.

The design style is a hybrid of **High-Contrast / Bold** and **Brutalism**, layered with **Tactile** decorative elements. It uses raw structural layouts, aggressive borders, and digital "noise" textures to create a sense of urgency and non-conformity. Key visual motifs include tartan patterns, chain-link dividers, and pixel-art iconography that bridges the gap between physical street fashion and digital broadcast environments.

## Colors

The palette is anchored in a high-contrast dark mode. **Pitch Black** provides the "void" background, allowing the **Deep Crimson Red** to serve as a primary signal color for high-importance actions and headers. 

A sharp **Mint Green/Light Blue** highlight is used sparingly for technical data, links, and digital "glitch" effects, providing a cool counterpoint to the aggressive reds. **Subculture Pink** is reserved for decorative elements, such as ribbons or "Jirai" style accents, maintaining a connection to the source material's fashion roots. All chromatic colors should be used against the dark neutral background to maintain maximum readability and edge.

## Typography

The typography system relies on a triple-font strategy to balance aggression with technical precision. 

**Headlines (Anton):** Used for primary titles and loud callouts. Its condensed, bold nature evokes the urgency of a broadcast or a punk poster. Headlines should often be presented in uppercase with slight tracking to increase impact.

**Body (Metropolis):** A geometric sans-serif that provides a clean, modern, and professional contrast to the headlines. It ensures long-form text remains legible against high-contrast backgrounds.

**Technical/Labels (JetBrains Mono):** This monospaced font is used for metadata, system status, "noise" elements, and UI labels. It reinforces the "geek" and "underground radio" aspect of the system, suggesting a terminal-like interface.

## Layout & Spacing

This design system utilizes a **Fixed Grid** model for desktop and a **Fluid Grid** for mobile. The layout is structured on a strict 4px baseline grid to ensure alignment for technical elements and pixel art decorations.

**Desktop:** A 12-column grid with 16px gutters. Large content areas should be broken up with "chain-link" or "dashed" vertical dividers to maintain the punk-industrial aesthetic.
**Mobile:** A 4-column grid with 12px gutters and 16px margins.
**Rhythm:** Spacing should be tight and dense, mimicking a control panel or a busy editorial spread. Use "stacks" of elements to create a sense of technical complexity. Avoid excessive whitespace; the interface should feel "full" and active.

## Elevation & Depth

Hierarchy is established through **Bold Borders** and **Tonal Layers** rather than soft shadows. 

1.  **Surfaces:** The primary background is #000000. Secondary containers use #1B2533 or a dark red gradient to lift content.
2.  **Borders:** Use 2px or 3px solid borders for all containers. Buttons and active states should use high-contrast borders in Mint Green or Crimson.
3.  **Digital Noise:** Use semi-transparent grain or scan-line textures as overlays on secondary surfaces to create a sense of depth and "signal interference."
4.  **Tartan Overlays:** Use a subtle red/black tartan pattern for container headers or sidebar backgrounds to ground the UI in the Jirai-kei style.

## Shapes

The shape language is **Sharp (0px)**. All containers, buttons, and input fields should have square corners to emphasize the Brutalist and technical nature of the system. 

The only exceptions to this rule are "Band-Aid" style chips or "Ribbon" tags, which may use angled cuts or decorative "cross" motifs. Pixel art elements must never be rounded or smoothed; they should retain their blocky, aliased appearance. Borders are mandatory for almost all shape-based components to maintain structural integrity.

## Components

**Buttons:** Solid black background with a 2px Crimson border and Crimson text. On hover, the button should invert to a solid Crimson background with Black text. For secondary actions, use Mint Green borders.

**Chips/Tags:** Styled as "Band-Aids" (light beige/tan with a subtle texture) or "Ribbon" tags (ends styled with a 'V' notch). Text in monospaced font.

**Lists:** Use chain-link icons as bullet points or dividers between list items. Hover states should trigger a "glitch" color-shift effect.

**Input Fields:** Ghost-style inputs with 1px dashed borders that turn solid upon focus. Labels are always positioned top-left in monospaced uppercase.

**Cards:** Containers with a 3px solid border. Headers should have a Red/Black tartan background strip with a title in Anton. Include small "screws" or "crosses" in the corners of cards as decorative pixel art.

**Checkboxes & Radios:** Sharp square boxes. When checked, use a pixelated "X" for checkboxes and a solid square for radio buttons.

**Additional Components:**
- **Signal Meter:** A technical status bar indicating "Connection" or "Frequency" to lean into the Radio theme.
- **Noise Overlay:** A global CSS filter or SVG noise pattern applied to certain high-level containers for atmospheric grit.