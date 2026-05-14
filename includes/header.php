<?php
/**
 * includes/header.php
 * HEAD + apertura del layout. Recibe: $pageTitle (string)
 */
$pageTitle = $pageTitle ?? 'JAAP';
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($pageTitle) ?> | <?= APP_NAME ?></title>

  <!-- Tailwind CSS v3 via CDN Play -->
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      theme: {
        extend: {
          colors: {
            brand: {
              50:  '#f0fdfa',
              100: '#ccfbf1',
              200: '#99f6e4',
              300: '#5eead4',
              400: '#2dd4bf',
              500: '#14b8a6',
              600: '#0d9488',
              700: '#0f766e',
              800: '#115e59',
              900: '#134e4a',
            }
          }
        }
      }
    }
  </script>

  <!-- Alpine.js (interactividad ligera: dropdowns, toggles) -->
  <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>

  <!-- Chart.js -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>

  <style>
    [x-cloak] { display: none !important; }
    .sidebar-link.active { @apply bg-brand-50 text-brand-700 font-semibold; }
  </style>
</head>
<body class="bg-slate-100 text-gray-800 font-sans antialiased">
<div class="flex h-screen overflow-hidden">
