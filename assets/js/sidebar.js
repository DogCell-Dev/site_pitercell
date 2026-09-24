function toggleSidebar() {
    const sidebar = document.getElementById('mainSidebar');
    if (!sidebar) return;

    sidebar.classList.toggle('collapsed');
    const isCollapsed = sidebar.classList.contains('collapsed');
    
    if (isCollapsed) {
        document.documentElement.classList.add('sidebar-is-collapsed');
        localStorage.setItem('sidebarState', 'closed');
    } else {
        document.documentElement.classList.remove('sidebar-is-collapsed');
        localStorage.setItem('sidebarState', 'open');
    }
}

// Restaura o estado da página sem quebras estruturais
document.addEventListener("DOMContentLoaded", function() {
    const sidebar = document.getElementById('mainSidebar');
    const savedState = localStorage.getItem('sidebarState');
    
    if (savedState === 'closed') {
        document.documentElement.classList.add('sidebar-is-collapsed');
        if (sidebar) sidebar.classList.add('collapsed');
    }
});