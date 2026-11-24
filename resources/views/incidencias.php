<?php
$titulo = $titulo ?? 'Registro de Incidencias';
$pageScript = '/assets/js/incidencias.js';
require __DIR__ . '/layout/header.php';
?>
<section class="page-heading d-flex flex-column flex-md-row justify-content-between align-items-md-end gap-3">
    <div><p class="eyebrow mb-2">GESTIÓN DE EVENTOS</p><h1 class="h2 mb-1">Incidencias</h1><p class="text-secondary mb-0">Registra eventos y da seguimiento a su resolución.</p></div>
    <button class="btn btn-primary" id="createIncidentButton"><i class="bi bi-plus-lg me-2"></i>Nueva incidencia</button>
</section>
<section class="row g-3 mt-1" aria-label="Métricas de incidencias">
    <div class="col-6 col-xl-3"><article class="metric-card metric-card-compact"><div class="metric-icon icon-blue"><i class="bi bi-collection"></i></div><p class="metric-label">Total</p><p id="incidentMetricTotal" class="metric-value">—</p></article></div>
    <div class="col-6 col-xl-3"><article class="metric-card metric-card-compact"><div class="metric-icon icon-red"><i class="bi bi-exclamation-circle"></i></div><p class="metric-label">Abiertas</p><p id="incidentMetricOpen" class="metric-value">—</p></article></div>
    <div class="col-6 col-xl-3"><article class="metric-card metric-card-compact"><div class="metric-icon icon-amber"><i class="bi bi-hourglass-split"></i></div><p class="metric-label">En proceso</p><p id="incidentMetricProgress" class="metric-value">—</p></article></div>
    <div class="col-6 col-xl-3"><article class="metric-card metric-card-compact"><div class="metric-icon icon-green"><i class="bi bi-check2-circle"></i></div><p class="metric-label">Resueltas</p><p id="incidentMetricResolved" class="metric-value">—</p></article></div>
</section>
<section class="content-card mt-3">
    <div class="section-title-row flex-wrap">
        <div><p class="eyebrow mb-1">SEGUIMIENTO</p><h2 class="h5 mb-0">Incidencias activas</h2></div>
        <div class="incident-toolbar">
            <label class="visually-hidden" for="incidentSearch">Buscar incidencias</label>
            <input class="form-control form-control-sm" id="incidentSearch" type="search" placeholder="Buscar por ID, título o dispositivo">
            <label class="visually-hidden" for="incidentStatusFilter">Filtrar por estado</label>
            <select class="form-select form-select-sm filter-select" id="incidentStatusFilter">
                <option value="active" selected>Activas</option>
                <option value="">Todos los estados</option>
                <option value="A">Abiertas</option>
                <option value="P">En proceso</option>
                <option value="R">Resueltas</option>
            </select>
            <button class="btn btn-sm btn-light" id="reloadIncidents" type="button"><i class="bi bi-arrow-clockwise me-1"></i>Actualizar</button>
        </div>
    </div>
    <div class="table-responsive"><table class="table align-middle app-table mb-0"><thead><tr><th>ID</th><th>Incidencia</th><th>Dispositivo</th><th>Tipo</th><th>Gravedad</th><th>Origen</th><th>Estado</th><th>Fecha de reporte</th><th>Acción</th></tr></thead><tbody id="incidentsTable"><tr><td colspan="9"><div class="loading-state"><span class="spinner-border spinner-border-sm me-2"></span>Cargando incidencias…</div></td></tr></tbody></table></div>
</section>
<div class="modal fade" id="incidentModal" tabindex="-1" aria-labelledby="incidentModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable"><div class="modal-content">
        <form id="incidentForm">
            <div class="modal-header"><div><p class="eyebrow mb-1">REGISTRO</p><h2 class="modal-title fs-5" id="incidentModalTitle">Nueva incidencia</h2></div><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button></div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-md-4"><label class="form-label" for="incid_id">ID de incidencia</label><input class="form-control" id="incid_id" maxlength="10" placeholder="Asignado al guardar" readonly><div class="form-text">Se genera un ID de 10 caracteres al guardar.</div></div>
                    <div class="col-md-8"><label class="form-label" for="incid_dis_id">Dispositivo afectado</label><select class="form-select" id="incid_dis_id" required><option value="">Cargando dispositivos…</option></select></div>
                    <div class="col-12"><label class="form-label" for="incid_titulo">Título</label><input class="form-control" id="incid_titulo" maxlength="150" required></div>
                    <div class="col-12"><label class="form-label" for="incid_desc">Descripción</label><textarea class="form-control" id="incid_desc" rows="3" maxlength="250" required></textarea></div>
                    <div class="col-md-6"><label class="form-label" for="incid_tipo">Tipo</label><select class="form-select" id="incid_tipo" required><option value="NW">Red (NW)</option><option value="HW">Hardware (HW)</option><option value="SW">Software (SW)</option><option value="OT">Otro (OT)</option></select></div>
                    <div class="col-md-6"><label class="form-label" for="incid_prioridad">Gravedad</label><select class="form-select" id="incid_prioridad"><option value="AL">Alta (AL)</option><option value="MD" selected>Media (MD)</option><option value="BJ">Baja (BJ)</option><option value="CR">Crítica (CR)</option></select></div>
                    <div class="col-md-6"><label class="form-label" for="incid_stat">Estado</label><select class="form-select" id="incid_stat"><option value="A">Abierta (A)</option><option value="P">En proceso (P)</option><option value="R">Resuelta (R)</option></select></div>
                    <div class="col-md-6"><label class="form-label" for="incid_origen">Origen</label><select class="form-select" id="incid_origen"><option value="U">Usuario (U)</option><option value="S">Sistema (S)</option></select></div>
                </div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-primary" id="saveIncidentButton"><i class="bi bi-check2 me-1"></i>Registrar incidencia</button></div>
        </form>
    </div></div>
</div>
<?php require __DIR__ . '/layout/footer.php'; ?>
