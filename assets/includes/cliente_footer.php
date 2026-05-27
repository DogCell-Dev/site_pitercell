<?php
// ── php/includes/cliente_footer.php ─────────────────────────
$active = $activePage ?? 'dashboard';
$navPages = [
  'dashboard' => ['href' => 'dashboard.php',     'label' => 'Início',    'icon' => '<rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/>'],
  'ordens'    => ['href' => 'minhas_ordens.php',  'label' => 'Ordens',    'icon' => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/>'],
  'orcamento' => ['href' => 'novo_orcamento.php', 'label' => 'Orçamento', 'icon' => '<circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/>'],
  'delivery'  => ['href' => 'delivery.php',       'label' => 'Entrega',   'icon' => '<rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/>'],
  'perfil'    => ['href' => 'perfil.php',         'label' => 'Perfil',    'icon' => '<path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>'],
];
?>
  </main><!-- /.main -->
</div><!-- /.wrapper -->

<!-- BARRA INFERIOR MOBILE -->
<nav class="bottom-nav">
  <?php foreach ($navPages as $key => $item): ?>
  <a href="<?= $item['href'] ?>"
     class="bottom-nav-item <?= $active === $key ? 'active' : '' ?>">
    <svg viewBox="0 0 24 24" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
      <?= $item['icon'] ?>
    </svg>
    <?= $item['label'] ?>
  </a>
  <?php endforeach; ?>
</nav>

</body>
</html>
