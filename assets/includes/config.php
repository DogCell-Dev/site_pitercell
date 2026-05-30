<?php
// ── php/config.php ──────────────────────────────────────────
// Configurações globais do sistema PiterCell

define('DB_HOST', 'localhost:3307');
define('DB_NAME', 'pitercell');
define('DB_USER', 'root');        // altere para seu usuário
define('DB_PASS', '');            // altere para sua senha
define('DB_CHARSET', 'utf8mb4');

define('TAXA_KM', 1.50);          // R$ por km para delivery
define('SITE_URL', 'http://localhost/site_pitercell'); // URL base do site
define('SITE_NAME', 'PiterCell');

// ── Conexão PDO ──────────────────────────────────────────────
function db(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = sprintf('mysql:host=%s;dbname=%s;charset=%s', DB_HOST, DB_NAME, DB_CHARSET);
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    }
    return $pdo;
}

// ── Sessão ───────────────────────────────────────────────────
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ── Helpers ──────────────────────────────────────────────────
function h(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

function redirect(string $url): void {
    header('Location: ' . $url);
    exit;
}

function isLoggedCliente(): bool {
    return !empty($_SESSION['cliente_id']);
}

function isLoggedAdmin(): bool {
    return !empty($_SESSION['admin_id']);
}

function requireCliente(): void {
    if (!isLoggedCliente()) redirect(SITE_URL . '/assets/pages/login.php');
}

function requireAdmin(): void {
    if (!isLoggedAdmin()) redirect(SITE_URL . '/assets/pages/login.php');
}

function gerarNumeroOS(): string {
    $stmt = db()->query("SELECT COUNT(*) AS total FROM ordens");
    $total = (int)$stmt->fetch()['total'];
    return 'OS-' . str_pad($total + 1, 4, '0', STR_PAD_LEFT);
}

function statusLabel(string $s): string {
    $map = [
        'aguardando'  => 'Aguardando',
        'em_analise'  => 'Em análise',
        'em_reparo'   => 'Em reparo',
        'pronto'      => 'Pronto p/ retirada',
        'entregue'    => 'Entregue',
        'cancelado'   => 'Cancelado',
    ];
    return $map[$s] ?? ucfirst($s);
}

function statusColor(string $s): string {
    $map = [
        'aguardando' => '#b84fff',
        'em_analise' => '#f59e0b',
        'em_reparo'  => '#3b82f6',
        'pronto'     => '#10b981',
        'entregue'   => '#6b7280',
        'cancelado'  => '#ef4444',
    ];
    return $map[$s] ?? '#888';
} 

function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $pdo = new PDO(
            'mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset=utf8mb4',
            DB_USER, DB_PASS,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
             PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
        );
    }
    return $pdo;
}

function requireLogin() {
    // 1. Garante que a sessão está ativa para ler os dados corretamente
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // 2. Verifica se as variáveis de login NÃO existem
    if (empty($_SESSION['usuario_id']) && empty($_SESSION['admin_id'])) {
        
        // Descobre se estamos na pasta do cliente para ajustar a rota do redirecionamento
        if (strpos($_SERVER['REQUEST_URI'], '/cliente/') !== false) {
            header('Location: ../../login.php'); // Volta duas pastas se estiver em cliente/dashboard.php
        } else {
            header('Location: ../login.php'); // Volta uma pasta se estiver em tecnico.php
        }
        exit;
    }
}

function statusClass(string $s): string {
    return match($s) {
        'aguardando'  => 'status-wait',
        'em_analise'  => 'status-analysis',
        'em_reparo'   => 'status-repair',
        'pronto'      => 'status-ready',
        'entregue'    => 'status-done',
        'cancelado'   => 'status-cancel',
        default       => ''
    };
}


