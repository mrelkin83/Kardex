<header class="h-16 bg-white border-b border-gray-100 flex items-center justify-between px-6 z-10 shrink-0">
    <div class="flex items-center gap-4">
        <button onclick="document.getElementById('sidebar').classList.toggle('hidden'); document.getElementById('sidebar').classList.toggle('flex');" class="text-gray-400 hover:text-gray-600 focus:outline-none md:hidden">
            <i class="fas fa-bars text-lg"></i>
        </button>
        <div>
            <p class="text-xs text-gray-400 font-semibold uppercase tracking-widest">Sede</p>
            <p class="text-sm font-bold text-instNegro leading-none"><?php echo e($_SESSION['sede_nombre']); ?></p>
        </div>
    </div>
    <div class="flex items-center gap-4">
        <a href="perfil" class="flex items-center gap-3 px-3 py-1.5 rounded-lg hover:bg-gray-50 transition-colors group">
            <div class="w-8 h-8 rounded-lg bg-instRojo/10 text-instRojo flex items-center justify-center font-bold text-xs group-hover:bg-instRojo group-hover:text-instAmarillo transition-colors">
                <?php echo e(substr($_SESSION['nombre'], 0, 1)); ?>
            </div>
            <div class="hidden sm:block">
                <p class="text-xs font-semibold text-gray-700 leading-none"><?php echo e($_SESSION['nombre']); ?></p>
                <p class="text-[10px] text-gray-400 font-medium uppercase"><?php echo e($_SESSION['rol']); ?></p>
            </div>
        </a>
    </div>
</header>
