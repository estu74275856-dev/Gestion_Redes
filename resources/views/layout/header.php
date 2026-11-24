<?php
$pageTitle = isset($titulo) ? (string) $titulo : 'Optisys · Consola de Red';
$currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$navItems = [
    '/' => ['Dashboard', 'bi-speedometer2'],
    '/dispositivos' => ['Dispositivos', 'bi-hdd-stack'],
    '/monitoreo' => ['Monitoreo', 'bi-activity'],
    '/incidencias' => ['Incidencias', 'bi-exclamation-triangle'],
];
$renderNavigation = static function (string $activePath) use ($navItems): void {
    foreach ($navItems as $path => [$label, $icon]) {
        $active = $activePath === $path;
        ?>
        <a class="sidebar-link<?= $active ? ' active' : '' ?>" href="<?= htmlspecialchars($path, ENT_QUOTES, 'UTF-8') ?>"<?= $active ? ' aria-current="page"' : '' ?>>
            <i class="bi <?= htmlspecialchars($icon, ENT_QUOTES, 'UTF-8') ?>" aria-hidden="true"></i>
            <span><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></span>
        </a>
        <?php
    }
};
$renderTopNavigation = static function (string $activePath) use ($navItems): void {
    foreach ($navItems as $path => [$label, $icon]) {
        $active = $activePath === $path;
        ?>
        <a class="top-nav-link<?= $active ? ' active' : '' ?>" href="<?= htmlspecialchars($path, ENT_QUOTES, 'UTF-8') ?>"<?= $active ? ' aria-current="page"' : '' ?>>
            <i class="bi <?= htmlspecialchars($icon, ENT_QUOTES, 'UTF-8') ?>" aria-hidden="true"></i>
            <span><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></span>
        </a>
        <?php
    }
};
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#0b162c">
    <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="/assets/css/app.css" rel="stylesheet">
</head>
<body>
<div class="app-layout">
    <aside class="app-sidebar d-none d-lg-flex" aria-label="Navegación principal">
        <a class="brand-lockup" href="/" aria-label="Optisys, Inicio">
            <span class="brand-symbol"><i class="bi bi-diagram-3-fill" aria-hidden="true"></i></span>
            <span>Optisys</span>
        </a>
        <nav class="sidebar-nav"><?= $renderNavigation($currentPath) ?></nav>
        <div class="sidebar-bottom">
            <div class="network-status">
                <span class="network-indicator" data-health-dot></span>
                <span><strong>Red corporativa</strong><small data-health-label>Verificando conexión…</small></span>
            </div>
            <div class="sidebar-caption">NEXO · CONSOLA DE RED</div>
        </div>
    </aside>

    <div class="main-column">
        <header class="topbar">
            <div class="topbar-workspace">
                <button class="btn mobile-menu-button d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobileNavigation" aria-controls="mobileNavigation" aria-label="Abrir menú">
                    <i class="bi bi-list"></i>
                </button>
                <div class="workspace-label">Espacio de trabajo</div>
                <button class="workspace-switch" type="button" aria-label="Sede actual: Sede principal">
                    Sede principal <i class="bi bi-chevron-down" aria-hidden="true"></i>
                </button>
            </div>
            <div class="topbar-account">
                <span id="apiHealthBadge" class="api-status" role="status" aria-live="polite">
                    <span class="network-indicator" data-health-dot></span><span data-health-label>Verificando conexión</span>
                </span>
                <span class="topbar-divider" aria-hidden="true"></span>
                <span class="avatar" aria-hidden="true">AM</span>
                <span class="account-copy"><strong>Ana Martínez</strong><small>Administradora de red</small></span>
            </div>
        </header>
        <nav class="primary-navigation d-none d-lg-flex" aria-label="Navegación principal">
            <?= $renderTopNavigation($currentPath) ?>
        </nav>

        <main class="app-content">
            <div id="globalAlerts" class="global-alerts" aria-live="polite"></div>
