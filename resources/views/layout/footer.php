        </main>
        <footer class="app-footer">
            <span>Sede principal · Inventario de infraestructura</span>
            <span>Optisys · Consola de Red</span>
        </footer>
    </div>
</div>
<div class="offcanvas offcanvas-start mobile-sidebar d-lg-none" tabindex="-1" id="mobileNavigation" aria-labelledby="mobileNavigationTitle">
    <div class="offcanvas-header">
        <a class="brand-lockup" href="/" id="mobileNavigationTitle">
            <span class="brand-symbol"><i class="bi bi-diagram-3-fill" aria-hidden="true"></i></span><span>Optisys</span>
        </a>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Cerrar menú"></button>
    </div>
    <div class="offcanvas-body d-flex flex-column">
        <nav class="sidebar-nav"><?= $renderNavigation($currentPath) ?></nav>
        <div class="sidebar-bottom mt-auto">
            <div class="network-status">
                <span class="network-indicator" data-health-dot></span>
                <span><strong>Red corporativa</strong><small data-health-label>Verificando conexión…</small></span>
            </div>
            <div class="sidebar-caption">NEXO · CONSOLA DE RED</div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="/assets/js/app.js"></script>
<?php if (!empty($pageScript)): ?>
    <script src="<?= htmlspecialchars((string) $pageScript, ENT_QUOTES, 'UTF-8') ?>"></script>
<?php endif; ?>
</body>
</html>
