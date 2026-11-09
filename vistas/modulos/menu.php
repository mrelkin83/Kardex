<aside id="sidebar" class="w-64 flex flex-col hidden md:flex sidebar-transition" style="background: linear-gradient(180deg, #F9FAFB 0%, #E1E7EC 100%);">
    <div class="h-16 flex items-center px-5 border-b border-gray-200 bg-instRojo shrink-0">
        <div class="flex items-center gap-3">
            <div class="w-9 h-9 rounded-lg bg-white/20 flex items-center justify-center">
                <i class="fas fa-hospital text-instAmarillo"></i>
            </div>
            <div>
                <h1 class="text-base font-extrabold tracking-wider text-white leading-none">KARDEX <span class="text-instAmarillo">UD</span></h1>
                <p class="text-[9px] text-white/50 font-medium tracking-widest uppercase">Bienestar Universitario</p>
            </div>
        </div>
    </div>
    <nav class="flex-1 px-3 py-4 space-y-1 overflow-y-auto">
        <a href="inicio" class="flex items-center gap-3 px-3 py-2.5 text-sm font-medium rounded-lg transition-all <?php echo (!isset($_GET['ruta']) || $_GET['ruta']=='inicio') ? 'bg-instRojo text-white shadow-sm' : 'text-gray-600 hover:text-instRojo hover:bg-white/60'; ?>">
            <i class="fas fa-chart-pie w-5 text-center text-xs"></i><span>Dashboard</span>
        </a>

        <div class="pt-3 pb-1 px-3">
            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-[0.15em]">Operacion</p>
        </div>
        <a href="kardex" class="flex items-center gap-3 px-3 py-2.5 text-sm font-medium rounded-lg transition-all <?php echo (isset($_GET['ruta']) && $_GET['ruta']=='kardex') ? 'bg-instRojo text-white shadow-sm' : 'text-gray-600 hover:text-instRojo hover:bg-white/60'; ?>">
            <i class="fas fa-clipboard-list w-5 text-center text-xs"></i><span>Kardex</span>
        </a>
        <?php if(!Security::esAdmin()): ?>
        <a href="mi-inventario" class="flex items-center gap-3 px-3 py-2.5 text-sm font-medium rounded-lg transition-all <?php echo (isset($_GET['ruta']) && $_GET['ruta']=='mi-inventario') ? 'bg-instRojo text-white shadow-sm' : 'text-gray-600 hover:text-instRojo hover:bg-white/60'; ?>">
            <i class="fas fa-warehouse w-5 text-center text-xs"></i><span>Inventario</span>
        </a>
        <?php endif; ?>
        <a href="entregas" class="flex items-center gap-3 px-3 py-2.5 text-sm font-medium rounded-lg transition-all <?php echo (isset($_GET['ruta']) && $_GET['ruta']=='entregas') ? 'bg-instRojo text-white shadow-sm' : 'text-gray-600 hover:text-instRojo hover:bg-white/60'; ?>">
            <i class="fas fa-hand-holding-medical w-5 text-center text-xs"></i><span>Entregas</span>
        </a>

        <?php if(Security::esAdmin()): ?>
        <div class="pt-4 pb-1 px-3">
            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-[0.15em]">Administracion</p>
        </div>
        <a href="usuarios" class="flex items-center gap-3 px-3 py-2.5 text-sm font-medium rounded-lg transition-all <?php echo (isset($_GET['ruta']) && $_GET['ruta']=='usuarios') ? 'bg-instRojo text-white shadow-sm' : 'text-gray-600 hover:text-instRojo hover:bg-white/60'; ?>">
            <i class="fas fa-users w-5 text-center text-xs"></i><span>Usuarios</span>
        </a>
        <a href="sedes" class="flex items-center gap-3 px-3 py-2.5 text-sm font-medium rounded-lg transition-all <?php echo (isset($_GET['ruta']) && $_GET['ruta']=='sedes') ? 'bg-instRojo text-white shadow-sm' : 'text-gray-600 hover:text-instRojo hover:bg-white/60'; ?>">
            <i class="fas fa-building w-5 text-center text-xs"></i><span>Sedes</span>
        </a>

        <div class="pt-4 pb-1 px-3">
            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-[0.15em]">Inventario</p>
        </div>
        <a href="medicamentos" class="flex items-center gap-3 px-3 py-2.5 text-sm font-medium rounded-lg transition-all <?php echo (isset($_GET['ruta']) && $_GET['ruta']=='medicamentos') ? 'bg-instRojo text-white shadow-sm' : 'text-gray-600 hover:text-instRojo hover:bg-white/60'; ?>">
            <i class="fas fa-pills w-5 text-center text-xs"></i><span>Medicamentos</span>
        </a>
        <a href="dispositivos" class="flex items-center gap-3 px-3 py-2.5 text-sm font-medium rounded-lg transition-all <?php echo (isset($_GET['ruta']) && $_GET['ruta']=='dispositivos') ? 'bg-instRojo text-white shadow-sm' : 'text-gray-600 hover:text-instRojo hover:bg-white/60'; ?>">
            <i class="fas fa-stethoscope w-5 text-center text-xs"></i><span>Dispositivos</span>
        </a>
        <a href="bodega" class="flex items-center gap-3 px-3 py-2.5 text-sm font-medium rounded-lg transition-all <?php echo (isset($_GET['ruta']) && $_GET['ruta']=='bodega') ? 'bg-instRojo text-white shadow-sm' : 'text-gray-600 hover:text-instRojo hover:bg-white/60'; ?>">
            <i class="fas fa-boxes-stacked w-5 text-center text-xs"></i><span>Entrada Bodega</span>
        </a>
        <a href="distribucion" class="flex items-center gap-3 px-3 py-2.5 text-sm font-medium rounded-lg transition-all <?php echo (isset($_GET['ruta']) && $_GET['ruta']=='distribucion') ? 'bg-instRojo text-white shadow-sm' : 'text-gray-600 hover:text-instRojo hover:bg-white/60'; ?>">
            <i class="fas fa-truck-fast w-5 text-center text-xs"></i><span>Distribucion</span>
        </a>
        <a href="carga-masiva" class="flex items-center gap-3 px-3 py-2.5 text-sm font-medium rounded-lg transition-all <?php echo (isset($_GET['ruta']) && $_GET['ruta']=='carga-masiva') ? 'bg-instRojo text-white shadow-sm' : 'text-gray-600 hover:text-instRojo hover:bg-white/60'; ?>">
            <i class="fas fa-boxes-stacked w-5 text-center text-xs"></i><span>Carga Masiva Bodega</span>
        </a>
        <a href="distribucion-masiva" class="flex items-center gap-3 px-3 py-2.5 text-sm font-medium rounded-lg transition-all <?php echo (isset($_GET['ruta']) && $_GET['ruta']=='distribucion-masiva') ? 'bg-instRojo text-white shadow-sm' : 'text-gray-600 hover:text-instRojo hover:bg-white/60'; ?>">
            <i class="fas fa-truck-ramp-box w-5 text-center text-xs"></i><span>Distribucion Masiva</span>
        </a>
        <a href="inventario-sedes" class="flex items-center gap-3 px-3 py-2.5 text-sm font-medium rounded-lg transition-all <?php echo (isset($_GET['ruta']) && $_GET['ruta']=='inventario-sedes') ? 'bg-instRojo text-white shadow-sm' : 'text-gray-600 hover:text-instRojo hover:bg-white/60'; ?>">
            <i class="fas fa-warehouse w-5 text-center text-xs"></i><span>Inventario por Sede</span>
        </a>
        <?php endif; ?>

        <div class="pt-4 pb-1 px-3">
            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-[0.15em]">Sistema</p>
        </div>
        <?php if(Security::esAdmin()): ?>
        <a href="auditoria" class="flex items-center gap-3 px-3 py-2.5 text-sm font-medium rounded-lg transition-all <?php echo (isset($_GET['ruta']) && $_GET['ruta']=='auditoria') ? 'bg-instRojo text-white shadow-sm' : 'text-gray-600 hover:text-instRojo hover:bg-white/60'; ?>">
            <i class="fas fa-shield-halved w-5 text-center text-xs"></i><span>Auditoria</span>
        </a>
        <?php endif; ?>
        <a href="reportes" class="flex items-center gap-3 px-3 py-2.5 text-sm font-medium rounded-lg transition-all <?php echo (isset($_GET['ruta']) && $_GET['ruta']=='reportes') ? 'bg-instRojo text-white shadow-sm' : 'text-gray-600 hover:text-instRojo hover:bg-white/60'; ?>">
            <i class="fas fa-file-export w-5 text-center text-xs"></i><span>Reportes</span>
        </a>
    </nav>
    <div class="p-3 border-t border-gray-200 shrink-0">
        <a href="salir" class="flex items-center gap-3 px-3 py-2.5 text-sm font-medium rounded-lg text-red-500 hover:bg-red-50 transition-all w-full">
            <i class="fas fa-right-from-bracket w-5 text-center text-xs"></i><span>Cerrar Sesion</span>
        </a>
    </div>
</aside>
