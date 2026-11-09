<?php
// Inventario local de la sede del usuario en sesion.
$inv = InventarioControlador::ctrInventarioSede();
$totUnidades = 0; $medicamentos = 0; $dispositivos = 0;
foreach($inv as $r){
    $totUnidades += (int)$r['stock'];
    if($r['tipo'] === 'MEDICAMENTO') $medicamentos++; else $dispositivos++;
}
?>
<div class="animate-fade-in-up">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="page-title">Mi Inventario</h1>
            <p class="page-subtitle">Existencias actuales en <?php echo e($_SESSION['sede_nombre']); ?></p>
        </div>
        <a href="inicio?export=misede" class="btn btn-ghost btn-sm"><i class="fas fa-download"></i> Descargar CSV</a>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
        <div class="stat-card stat-card-acento">
            <div class="flex items-center justify-between mb-3"><div class="w-10 h-10 rounded-xl bg-instRojo/10 flex items-center justify-center"><i class="fas fa-cubes text-instRojo"></i></div></div>
            <p class="text-2xl font-extrabold text-instNegro"><?php echo $totUnidades; ?></p>
            <p class="text-xs text-gray-400 font-semibold uppercase tracking-wider mt-1">Unidades en existencia</p>
        </div>
        <div class="stat-card stat-card-acento">
            <div class="flex items-center justify-between mb-3"><div class="w-10 h-10 rounded-xl bg-instRojo/10 flex items-center justify-center"><i class="fas fa-pills text-instRojo"></i></div></div>
            <p class="text-2xl font-extrabold text-instNegro"><?php echo $medicamentos; ?></p>
            <p class="text-xs text-gray-400 font-semibold uppercase tracking-wider mt-1">Medicamentos</p>
        </div>
        <div class="stat-card stat-card-acento">
            <div class="flex items-center justify-between mb-3"><div class="w-10 h-10 rounded-xl bg-gray-900/5 flex items-center justify-center"><i class="fas fa-stethoscope text-gray-700"></i></div></div>
            <p class="text-2xl font-extrabold text-instNegro"><?php echo $dispositivos; ?></p>
            <p class="text-xs text-gray-400 font-semibold uppercase tracking-wider mt-1">Dispositivos</p>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <span class="text-sm font-semibold text-gray-600"><i class="fas fa-boxes-stacked mr-2 text-instRojo"></i>Existencias</span>
            <div class="search-box"><i class="fas fa-search"></i><input type="text" onkeyup="filterTable('tMiInv', event)" placeholder="Buscar producto..."></div>
        </div>
        <div class="card-body p-0">
            <?php if(empty($inv)): ?>
                <div class="empty-state"><i class="fas fa-box-open"></i><p>Tu sede no tiene existencias registradas</p></div>
            <?php else: ?>
            <div class="max-h-[600px] overflow-y-auto">
                <table class="table-modern" id="tMiInv">
                    <thead class="sticky top-0"><tr><th>Código</th><th>Producto</th><th>Tipo</th><th class="text-center">Stock</th></tr></thead>
                    <tbody>
                        <?php foreach($inv as $r): ?>
                        <tr>
                            <td class="font-medium text-gray-500"><?php echo e($r['codigo']); ?></td>
                            <td class="font-semibold text-gray-800"><?php echo e($r['nombre']); ?></td>
                            <td><span class="badge <?php echo $r['tipo']==='MEDICAMENTO'?'badge-rojo':'badge-gris'; ?>"><?php echo $r['tipo']==='MEDICAMENTO'?'Medicamento':'Dispositivo'; ?></span></td>
                            <td class="text-center font-bold <?php echo (int)$r['stock'] <= 10 ? 'text-yellow-600' : 'text-green-600'; ?> text-base"><?php echo (int)$r['stock']; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
