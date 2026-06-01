  </div><!-- /.flex.h-screen -->

<!-- Toast container -->
<div id="toast-container" class="fixed top-4 right-4 z-50 flex flex-col gap-2 pointer-events-none"></div>

<script>
function showToast(message, type = 'success') {
  const colors = {
    success: 'bg-emerald-600 text-white',
    error:   'bg-red-600 text-white',
    info:    'bg-brand-600 text-white',
  };
  const icons = {
    success: '<path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>',
    error:   '<path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z"/>',
    info:    '<path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z"/>',
  };

  const toast = document.createElement('div');
  toast.className = [
    'pointer-events-auto flex items-center gap-3 px-4 py-3 rounded-xl shadow-lg text-sm font-medium',
    'translate-x-0 opacity-100 transition-all duration-300',
    colors[type] ?? colors.info,
  ].join(' ');
  toast.innerHTML = `
    <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">${icons[type] ?? icons.info}</svg>
    <span>${message}</span>`;

  const container = document.getElementById('toast-container');
  container.appendChild(toast);

  // Salida automática a los 3.5 s
  setTimeout(() => {
    toast.style.opacity = '0';
    toast.style.transform = 'translateX(1.5rem)';
    setTimeout(() => toast.remove(), 300);
  }, 3500);
}
</script>

</body>
</html>
