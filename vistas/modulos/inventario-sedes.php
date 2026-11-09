<?php
Security::checkRole(['ADMIN']);
$verSede = isset($_GET['sede']) ? (int)$_GET['sede'] : 0;

if($verSede > 0):
    // ---- Detalle de una sede ----
    $sedeNombre = '';
    foreach(UsuariosModelo::mdlListarSedes() as $s){ if((int)$s['id'] === $verSede){ $sedeNombre = $s['nombre']; break; } }
    $inv = InventarioControlador::ctrInventarioDeSede($verSede);
    $totU = 0; foreach($inv as $r) $totU += (int)$r['stock'];
?>
<div class="mb-6">
    <a href="inventario-sedes" class="text-xs font-bold text-instRojo hover:underline uppercase tracking-wider"><i class="fas fa-arrow-left mr-1"></i> Volver al panel de sedes</a>
    <div class="flex items-center justify-between mt-2">
        <div>
            <h2 class="page-title">Inventario · <?php echo e($sedeNombre); ?></h2>
            <p class="page-subtitle"><?php echo count($inv); ?> productos · <?php echo $totU; ?> unidades</p>
        </div>
        <a href="inventario-sedes?export=sede&sede=<?php echo $verSede; ?>" class="btn btn-ghost btn-sm"><i class="fas fa-download"></i> Descargar CSV</a>
    </div>
</div>
<div class="card">
    <div class="card-header">
        <span class="text-sm font-semibold text-gray-600"><i class="fas fa-boxes-stacked mr-2 text-instRojo"></i>Existencias</span>
        <div class="search-box"><i class="fas fa-search"></i><input type="text" onkeyup="filterTable('tInvSede', event)" placeholder="Buscar producto..."></div>
    </div>
    <div class="card-body p-0">
        <?php if(empty($inv)): ?>
            <div class="empty-state"><i class="fas fa-box-open"></i><p>Sin existencias en esta sede</p></div>
        <?php else: ?>
        <div class="max-h-[600px] overflow-y-auto">
            <table class="table-modern" id="tInvSede">
                <thead class="sticky top-0"><tr><th>Código</th><th>Producto</th><th>Tipo</th><th class="text-center">Stock</th></tr></thead>
                <tbody>
                    <?php foreach($inv as $r): ?>
                    <tr>
                        <td class="font-medium text-gray-500"><?php echo e($r['codigo']); ?></td>
                        <td class="font-semibold text-gray-800"><?php echo e($r['nombre']); ?></td>
                        <td><span class="badge <?php echo $r['tipo']==='MEDICAMENTO'?'badge-rojo':'badge-gris'; ?>"><?php echo $r['tipo']==='MEDICAMENTO'?'Medicamento':'Dispositivo'; ?></span></td>
                        <td class="text-center font-bold text-green-600 text-base"><?php echo (int)$r['stock']; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php else:
    // ---- Panel resumen de todas las sedes ----
    $resumen = InventarioControlador::ctrResumenPorSede();
    $totalSistema = 0; $bodega = 0;
    foreach($resumen as $r){ $totalSistema += $r['unidades']; if((int)$r['id'] === 1) $bodega = $r['unidades']; }
    $enSedes = $totalSistema - $bodega;
    $sedesConStock = 0; foreach($resumen as $r){ if((int)$r['id'] !== 1 && $r['unidades'] > 0) $sedesConStock++; }
?>
<div class="flex items-center justify-between mb-6">
    <div>
        <h2 class="page-title">Inventario por Sede</h2>
        <p class="page-subtitle">Panel consolidado de existencias en bodega central y todas las sedes</p>
    </div>
    <a href="inventario-sedes?export=resumen" class="btn btn-primary"><i class="fas fa-download"></i> Descargar resumen CSV</a>
</div>

<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <div class="stat-card stat-card-acento">
        <div class="flex items-center justify-between mb-3"><div class="w-10 h-10 rounded-xl bg-instRojo/10 flex items-center justify-center"><i class="fas fa-warehouse text-instRojo"></i></div></div>
        <p class="text-2xl font-extrabold text-instNegro"><?php echo $bodega; ?></p>
        <p class="text-xs text-gray-400 font-semibold uppercase tracking-wider mt-1">Unidades en Bodega</p>
    </div>
    <div class="stat-card stat-card-acento">
        <div class="flex items-center justify-between mb-3"><div class="w-10 h-10 rounded-xl bg-green-50 flex items-center justify-center"><i class="fas fa-hospital text-green-600"></i></div></div>
        <p class="text-2xl font-extrabold text-instNegro"><?php echo $enSedes; ?></p>
        <p class="text-xs text-gray-400 font-semibold uppercase tracking-wider mt-1">Unidades en Sedes</p>
    </div>
    <div class="stat-card stat-card-acento">
        <div class="flex items-center justify-between mb-3"><div class="w-10 h-10 rounded-xl bg-gray-900/5 flex items-center justify-center"><i class="fas fa-cubes text-gray-700"></i></div></div>
        <p class="text-2xl font-extrabold text-instNegro"><?php echo $totalSistema; ?></p>
        <p class="text-xs text-gray-400 font-semibold uppercase tracking-wider mt-1">Total en el Sistema</p>
    </div>
    <div class="stat-card stat-card-acento">
        <div class="flex items-center justify-between mb-3"><div class="w-10 h-10 rounded-xl bg-yellow-50 flex items-center justify-center"><i class="fas fa-building text-yellow-600"></i></div></div>
        <p class="text-2xl font-extrabold text-instNegro"><?php echo $sedesConStock; ?></p>
        <p class="text-xs text-gray-400 font-semibold uppercase tracking-wider mt-1">Sedes con Stock</p>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <span class="text-sm font-semibold text-gray-600"><i class="fas fa-list-ul mr-2 text-instRojo"></i>Detalle por Sede</span>
    </div>
    <div class="card-body p-0">
        <table class="table-modern">
            <thead>
                <tr>
                    <th>Sede</th>
                    <th class="text-center">Productos</th>
                    <th class="text-center">Unidades</th>
                    <th class="text-center">Stock Crítico</th>
                    <th class="text-center">Por Vencer (30d)</th>
                    <th class="text-center">Detalle</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($resumen as $r): ?>
                <tr>
                    <td class="font-semibold text-gray-800">
                        <?php if((int)$r['id'] === 1): ?><i class="fas fa-warehouse text-instRojo mr-1 text-xs"></i><?php endif; ?>
                        <?php echo e($r['nombre']); ?>
                    </td>
                    <td class="text-center"><?php echo (int)$r['productos']; ?></td>
                    <td class="text-center font-bold <?php echo $r['unidades']>0?'text-green-600':'text-gray-300'; ?>"><?php echo (int)$r['unidades']; ?></td>
                    <td class="text-center"><?php echo $r['criticos']>0 ? '<span class="badge badge-amarillo">'.(int)$r['criticos'].'</span>' : '<span class="text-gray-300">0</span>'; ?></td>
                    <td class="text-center"><?php echo $r['por_vencer']>0 ? '<span class="badge badge-rojo">'.(int)$r['por_vencer'].'</span>' : '<span class="text-gray-300">0</span>'; ?></td>
                    <td class="text-center"><a href="inventario-sedes?sede=<?php echo (int)$r['id']; ?>" class="btn btn-ghost btn-sm"><i class="fas fa-eye"></i> Ver</a></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>
