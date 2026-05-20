<?php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'pitercell');

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

define('WA_NUMBER', '5511999999999');
define('ADMIN_PASS', 'pitercell2026');

session_start();

function requireLogin() {
    if (empty($_SESSION['logado'])) {
        header('Location: ../login.php');
        exit;
    }
}

function statusLabel(string $s): string {
    return match($s) {
        'aguardando'  => 'Aguardando',
        'em_analise'  => 'Em Análise',
        'em_reparo'   => 'Em Reparo',
        'pronto'      => 'Pronto',
        'entregue'    => 'Entregue',
        'cancelado'   => 'Cancelado',
        default       => $s
    };
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
