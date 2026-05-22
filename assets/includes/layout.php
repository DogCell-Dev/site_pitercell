<?php
function renderSidebar(string $active): void {
    $items = [
        'dashboard' => ['Dashboard',       'M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z'],
        'ordens'    => ['Ordens de Serviço','M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2M9 5a2 2 0 0 0 2 2h2a2 2 0 0 0 2-2M9 5a2 2 0 0 0-1 1'],
        'clientes'  => ['Clientes',         'M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2 M23 21v-2a4 4 0 0 0-3-3.87 M16 3.13a4 4 0 0 1 0 7.75'],
        'pagamentos' => ['Pagamentos',  'M12 1v22M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6'],
    ];

    echo '<div id="mainSidebar" class="sidebar">';
    
    // Adicionado o evento de clique na div da logo
    echo '<div class="sidebar-logo" onclick="toggleSidebar()"><img src="../img/logo_pitercell.jpg" alt="Pitercell Admin"></div>';
    
    echo '<nav class="sidebar-nav">';
    echo '<div class="nav-section">Menu</div>';
    
    foreach ($items as $key => [$label, $path]) {
        $cls = $active === $key ? 'nav-item active' : 'nav-item';
        $href = $key === 'dashboard' ? 'tecnico.php' : ''.$key.'.php';
        echo '<a href="'.$href.'" class="'.$cls.'">';
        echo '<svg viewBox="0 0 24 24" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="'.$path.'"/></svg>';
        // O texto precisa estar dentro de um span para podermos ocultá-lo via CSS
        echo '<span class="nav-text">'.$label.'</span>'; 
        echo '</a>';
    }
    echo '</nav>';
    
    echo '<div class="sidebar-footer">';
    echo '<a href="logout.php" class="nav-item"><svg viewBox="0 0 24 24" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9"/></svg><span class="nav-text">Sair</span></a>';
    echo '</div>'; // Fechamento da div footer que faltava no seu fragmento
    echo '</div>'; // Fechamento da sidebar
}
?>
