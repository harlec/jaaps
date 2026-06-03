<?php
/**
 * includes/sidebar.php
 * Barra lateral de navegación.
 * Necesita: $activePage (string) para marcar el ítem activo.
 */
$activePage = $activePage ?? '';

$nav = [
    ['href' => APP_URL . '/dashboard.php',            'icon' => 'grid',        'label' => 'Dashboard',       'key' => 'dashboard'],
    ['href' => APP_URL . '/abonados/index.php',        'icon' => 'users',       'label' => 'Abonados',        'key' => 'abonados'],
    ['href' => APP_URL . '/pagos/index.php',           'icon' => 'currency',    'label' => 'Pagos',           'key' => 'pagos'],
    ['href' => APP_URL . '/inscripciones/index.php',   'icon' => 'clipboard',   'label' => 'Inscripciones',   'key' => 'inscripciones'],
    ['href' => APP_URL . '/conceptos/index.php',       'icon' => 'tag',         'label' => 'Conceptos',       'key' => 'conceptos'],
    ['href' => APP_URL . '/importar/index.php',        'icon' => 'upload',      'label' => 'Importar',        'key' => 'importar'],
    ['href' => APP_URL . '/reportes/deudores.php',     'icon' => 'alert',       'label' => 'Deudores',        'key' => 'reportes'],
];

$icons = [
    'grid'     => '<path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25V6ZM3.75 15.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18v-2.25ZM13.5 6a2.25 2.25 0 0 1 2.25-2.25H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25a2.25 2.25 0 0 1-2.25-2.25V6ZM13.5 15.75a2.25 2.25 0 0 1 2.25-2.25H18a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 18 20.25h-2.25A2.25 2.25 0 0 1 13.5 18v-2.25Z" />',
    'users'    => '<path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />',
    'currency' => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />',
    'clipboard'=> '<path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25ZM6.75 12h.008v.008H6.75V12Zm0 3h.008v.008H6.75V15Zm0 3h.008v.008H6.75V18Z" />',
    'tag'      => '<path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 0 0 3 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 0 0 5.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 0 0 9.568 3Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M6 6h.008v.008H6V6Z" />',
    'upload'   => '<path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5" />',
    'alert'    => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />',
];
?>
<!-- SIDEBAR -->
<aside class="hidden md:flex md:flex-col w-56 bg-white border-r border-gray-100 shadow-sm flex-shrink-0">

  <!-- Logo -->
  <div class="flex items-center gap-3 px-5 py-5 border-b border-gray-100">
    <div class="w-9 h-9 rounded-xl bg-brand-600 flex items-center justify-center">
      <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386-1.591 1.591M21 12h-2.25m-.386 6.364-1.591-1.591M12 18.75V21m-4.773-4.227-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0Z" />
      </svg>
    </div>
    <div>
      <p class="text-xs font-bold text-brand-700 leading-tight">JAAP</p>
      <p class="text-[10px] text-gray-400 leading-tight">Agua Potable</p>
    </div>
  </div>

  <!-- Nav -->
  <nav class="flex-1 px-3 py-4 space-y-0.5 overflow-y-auto">
    <?php foreach ($nav as $item): ?>
      <?php $active = ($activePage === $item['key']); ?>
      <a href="<?= e($item['href']) ?>"
         class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm transition-colors
                <?= $active ? 'bg-brand-50 text-brand-700 font-semibold' : 'text-gray-500 hover:bg-gray-50 hover:text-gray-700' ?>">
        <svg class="w-5 h-5 flex-shrink-0 <?= $active ? 'text-brand-600' : 'text-gray-400' ?>"
             fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
          <?= $icons[$item['icon']] ?>
        </svg>
        <?= e($item['label']) ?>
      </a>
    <?php endforeach; ?>
  </nav>

  <!-- Footer del sidebar -->
  <div class="px-4 py-4 border-t border-gray-100">
    <a href="<?= APP_URL ?>/logout.php"
       class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm text-gray-400 hover:text-red-500 hover:bg-red-50 transition-colors">
      <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 9V5.25A2.25 2.25 0 0 1 10.5 3h6a2.25 2.25 0 0 1 2.25 2.25v13.5A2.25 2.25 0 0 1 16.5 21h-6a2.25 2.25 0 0 1-2.25-2.25V15m-3 0-3-3m0 0 3-3m-3 3H15" />
      </svg>
      Cerrar sesión
    </a>

    <p class="text-[10px] text-gray-300 mt-3 text-center">v<?= APP_VERSION ?></p>
  </div>
</aside>
