(function () {
  const root = document.documentElement;
  const savedTheme = localStorage.getItem('pitercell-theme');
  if (savedTheme === 'dark') root.dataset.theme = 'dark';

  function updateButton(button) {
    if (!button) return;
    const dark = root.dataset.theme === 'dark';
    button.textContent = dark ? '☀️' : '🌙';
    button.setAttribute('aria-label', dark ? 'Ativar tema claro' : 'Ativar tema escuro');
    button.title = dark ? 'Tema claro' : 'Tema escuro';
  }

  document.addEventListener('DOMContentLoaded', function () {
    const button = document.querySelector('[data-theme-toggle]');
    updateButton(button);
    if (!button) return;
    button.addEventListener('click', function () {
      const dark = root.dataset.theme !== 'dark';
      root.dataset.theme = dark ? 'dark' : 'light';
      localStorage.setItem('pitercell-theme', dark ? 'dark' : 'light');
      updateButton(button);
    });
  });
})();
