<?php


$info = array(
  // Available colors and color labels used in theme.
  'fields' => array(
    'bg' => t('Main background'),
    'menu' => t('Menu'),
    'menu_hover' => t('Menu Hover'),
    'link' => t('Link'),
    'link_hover' => t('Link Hover'),
    'button' => t('Button'),
    'button_hover' => t('Button Hover'),
  ),

  // Pre-defined color schemes.
  'schemes' => array(
    'default' => array(
      'title' => t('Default'),
      'colors' => array(
        'bg' => '#eeeeee',
        'menu' => '#b81007',
        'menu_hover' => '#b81005',
        'link' => '#2e70b1',
        'link_hover' => '#b81008',
        'button' => '#b81010',
        'button_hover' => '#cf5240',
      ),
    ),
    // New Scheme
  ),

  // CSS files (excluding @import) to rewrite with new color scheme.
  'css' => array(
    'color/colors.css',
  ),

  // Files to copy.
  'copy' => array(
    'logo.png',
  ),

  // Gradient definitions.
  'gradients' => array(),

  // Color areas to fill (x, y, width, height).
  'fill' => array(),

  // Coordinates of all the theme slices (x, y, width, height)
  // with their filename as used in the stylesheet.
  'slices' => array(),

  // Reference color used for blending. Matches the base.png's colors.
  'blend_target' => '#ffffff',

  // Preview files.
  'preview_css' => 'color/preview.css',
  'preview_js' => 'color/preview.js',
  'preview_html' => 'color/preview.html',

  // Base file for image generation.
  'base_image' => 'color/base.png',
);
