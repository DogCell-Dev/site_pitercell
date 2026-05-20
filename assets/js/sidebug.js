(function() {
    const savedState = localStorage.getItem('sidebarState');
    // Se o usuário nunca acessou, ela começará aberta. Se preferir que comece fechada, mude !== 'open'
    if (savedState === 'closed') {
      document.documentElement.classList.add('sidebar-is-collapsed');
}
})();

