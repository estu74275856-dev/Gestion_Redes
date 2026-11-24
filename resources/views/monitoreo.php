<?php
$titulo = $titulo ?? 'Monitoreo · Optisys';
$pageScript = '/assets/js/monitoreo.js';
require __DIR__ . '/layout/header.php';
?>
<header class="page-heading">
    <div><h1>Monitoreo</h1><p>Consulta el estado operativo de cada equipo de la red.</p></div>
    <button class="btn btn-light btn-icon" type="button" id="pingAllButton"><i class="bi bi-arrow-repeat"></i>Comprobar toda la red</button>
</header>

<section class="monitor-search-card" aria-label="Buscar dispositivo para monitoreo">
    <form id="monitorSearchForm" class="monitor-search-form">
        <div>
            <label class="form-label" for="monitorDeviceCode">Código del dispositivo</label>
            <input id="monitorDeviceCode" class="form-control mono" maxlength="5" placeholder="Ej. D4K2P" autocomplete="off" aria-describedby="monitorSearchHint">
        </div>
        <button class="btn btn-primary btn-icon" type="submit"><i class="bi bi-search"></i>Buscar</button>
        <small id="monitorSearchHint" class="form-hint">Ingresa el código completo para consultar si el equipo existe y revisar su estado operativo.</small>
    </form>
</section>

<div class="monitor-summary">
    <span id="monitorDeviceCount">Cargando dispositivos…</span>
    <div class="monitor-counts"><span class="count-active"><strong id="monitorActiveCount">0</strong> activos</span><span class="count-inactive"><strong id="monitorInactiveCount">0</strong> inactivo</span><span class="count-failure"><strong id="monitorFailureCount">0</strong> en falla</span></div>
</div>

<section class="monitor-table-card" aria-label="Dispositivos en monitoreo">
    <div class="table-responsive">
        <table class="table app-table">
            <thead><tr><th>CÓDIGO</th><th>NOMBRE</th><th>DIRECCIÓN IP</th><th>ESTADO</th><th>ACCIONES</th></tr></thead>
            <tbody id="monitorDevicesTable"><tr><td colspan="5"><div class="loading-state"><span class="spinner-border spinner-border-sm me-2" aria-hidden="true"></span>Cargando dispositivos…</div></td></tr></tbody>
        </table>
    </div>
    <div class="monitor-table-footer"><span id="monitorRowsCount">Cargando dispositivos…</span><span>Telemetría / Estado de red</span></div>
</section>

<div class="alert alert-soft-info mt-3 mb-0" role="note"><i class="bi bi-info-circle me-1"></i>Activo: equipo operativo. Inactivo: fuera de operación o sin comprobación. En falla: presenta un problema de conectividad. Selecciona «Ver estado» para consultar un dispositivo.</div>

<div class="modal fade" id="monitorDetailsModal" tabindex="-1" aria-labelledby="monitorDetailsTitle" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable"><div class="modal-content">
        <div class="modal-header"><div><p class="page-eyebrow mb-1">ESTADO DE RED</p><h2 class="modal-title fs-6" id="monitorDetailsTitle">Detalle de monitoreo</h2></div><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button></div>
        <div class="modal-body">
            <div id="monitorDetailsSummary" class="monitor-detail-summary"></div>
            <h3 class="h6 fw-semibold mb-2">Historial reciente</h3>
            <div id="monitorHistoryList" class="history-list"><div class="loading-state">Cargando historial…</div></div>
        </div>
        <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cerrar</button></div>
    </div></div>
</div>
<?php require __DIR__ . '/layout/footer.php'; ?>
