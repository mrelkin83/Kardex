<?php
// Baja automatica de vencidos (mantenimiento del sistema): solo la dispara el ADMIN. Idempotente.
$bajasNuevas = $_SESSION['rol'] === 'ADMIN' ? InventarioControlador::ctrDarBajaVencidos() : 0;
$stats = DashboardModelo::mdlGetStats($_SESSION['sede_id'], $_SESSION['rol']);
$criticos = DashboardModelo::mdlGetStockBajo($_SESSION['sede_id'], $_SESSION['rol']);
$dadosBaja = InventarioControlador::ctrGetDadosDeBaja();
$proxVencer = InventarioControlador::ctrGetProximosVencer(30);
$inventarioSede = InventarioControlador::ctrInventarioSede();
?>
<?php if($bajasNuevas > 0): ?>
<div class="mb-4 bg-amber-50 border border-amber-200 rounded-lg p-3 flex items-center gap-3 animate-fade-in">
    <i class="fas fa-circle-exclamation text-amber-500"></i>
    <p class="text-sm text-amber-800"><span class="font-bold"><?php echo (int)$bajasNuevas; ?></span> lote(s) vencido(s) se dieron de baja automaticamente en esta carga.</p>
</div>
<?php endif; ?>

<div class="mb-8">
    <h2 class="page-title">Panel de Control</h2>
    <p class="page-subtitle">Resumen operativo - <?php echo e($_SESSION['sede_nombre']); ?></p>
</div>

<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4 mb-8">
    <div class="stat-card stat-card-acento">
        <div class="flex items-center justify-between mb-3">
            <div class="w-10 h-10 rounded-xl bg-instRojo/10 flex items-center justify-center">
                <i class="fas fa-pills text-instRojo"></i>
            </div>
            <span class="badge badge-rojo">Inventario</span>
        </div>
        <p class="text-2xl font-extrabold text-instNegro"><?php echo e($stats['total_med']); ?></p>
        <p class="text-xs text-gray-400 font-semibold uppercase tracking-wider mt-1"><?php echo $_SESSION['rol']==='ADMIN' ? 'Medicamentos' : 'Medicamentos con stock'; ?></p>
    </div>
    <div class="stat-card stat-card-acento">
        <div class="flex items-center justify-between mb-3">
            <div class="w-10 h-10 rounded-xl bg-gray-900/5 flex items-center justify-center">
                <i class="fas fa-stethoscope text-gray-700"></i>
            </div>
            <span class="badge badge-gris">Inventario</span>
        </div>
        <p class="text-2xl font-extrabold text-instNegro"><?php echo e($stats['total_disp']); ?></p>
        <p class="text-xs text-gray-400 font-semibold uppercase tracking-wider mt-1"><?php echo $_SESSION['rol']==='ADMIN' ? 'Dispositivos' : 'Dispositivos con stock'; ?></p>
    </div>
    <div class="stat-card stat-card-acento">
        <div class="flex items-center justify-between mb-3">
            <div class="w-10 h-10 rounded-xl bg-green-50 flex items-center justify-center">
                <i class="fas fa-hand-holding-medical text-green-600"></i>
            </div>
            <span class="badge badge-verde">Hoy</span>
        </div>
        <p class="text-2xl font-extrabold text-instNegro"><?php echo e($stats['entregas_hoy']); ?></p>
        <p class="text-xs text-gray-400 font-semibold uppercase tracking-wider mt-1">Entregas Hoy</p>
    </div>
    <div class="stat-card stat-card-acento">
        <div class="flex items-center justify-between mb-3">
            <div class="w-10 h-10 rounded-xl bg-yellow-50 flex items-center justify-center">
                <i class="fas fa-triangle-exclamation text-yellow-600"></i>
            </div>
            <span class="badge badge-amarillo">Alerta</span>
        </div>
        <p class="text-2xl font-extrabold text-instNegro"><?php echo count($criticos); ?></p>
        <p class="text-xs text-gray-400 font-semibold uppercase tracking-wider mt-1">Stock Critico</p>
    </div>
    <div class="stat-card stat-card-acento">
        <div class="flex items-center justify-between mb-3">
            <div class="w-10 h-10 rounded-xl bg-red-50 flex items-center justify-center">
                <i class="fas fa-ban text-red-600"></i>
            </div>
            <span class="badge badge-rojo">Vencidos</span>
        </div>
        <p class="text-2xl font-extrabold text-instNegro"><?php echo e($stats['baja_lotes']); ?></p>
        <p class="text-xs text-gray-400 font-semibold uppercase tracking-wider mt-1">Dados de Baja (<?php echo e($stats['baja_unidades']); ?> u.)</p>
    </div>
</div>

<div class="card mb-6">
    <div class="card-header">
        <div>
            <h3 class="font-bold text-sm text-instNegro"><i class="fas fa-boxes-stacked text-instRojo mr-2"></i>Inventario de <?php echo e($_SESSION['sede_nombre']); ?></h3>
            <p class="text-xs text-gray-400 mt-0.5">Existencias actuales en su sede</p>
        </div>
        <div class="flex items-center gap-2">
            <div class="search-box">
                <i class="fas fa-search"></i>
                <input type="text" onkeyup="filterTable('tableInvSede', event)" placeholder="Buscar producto...">
            </div>
            <?php if(!empty($inventarioSede)): ?>
            <a href="inicio?export=misede" class="btn btn-ghost btn-sm whitespace-nowrap"><i class="fas fa-download"></i> CSV</a>
            <?php endif; ?>
        </div>
    </div>
    <div class="card-body p-0">
        <?php if(empty($inventarioSede)): ?>
            <div class="empty-state"><i class="fas fa-box-open"></i><p class="font-semibold text-gray-600">Sin existencias</p><p class="text-xs text-gray-400 mt-1">No hay productos con stock en esta sede</p></div>
        <?php else: ?>
        <div class="max-h-96 overflow-y-auto">
            <table class="table-modern" id="tableInvSede">
                <thead class="sticky top-0"><tr><th>Código</th><th>Producto</th><th>Tipo</th><th class="text-center">Stock</th></tr></thead>
                <tbody>
                    <?php foreach($inventarioSede as $iv): ?>
                    <tr>
                        <td class="font-medium text-gray-500"><?php echo e($iv['codigo']); ?></td>
                        <td class="font-semibold text-gray-800"><?php echo e($iv['nombre']); ?></td>
                        <td><span class="badge <?php echo $iv['tipo']==='MEDICAMENTO'?'badge-rojo':'badge-gris'; ?>"><?php echo $iv['tipo']==='MEDICAMENTO'?'Medicamento':'Dispositivo'; ?></span></td>
                        <td class="text-center font-bold text-green-600 text-base"><?php echo (int)$iv['stock']; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2 card">
        <div class="card-header">
            <div>
                <h3 class="font-bold text-sm text-instNegro"><i class="fas fa-bell text-instRojo mr-2"></i>Alertas de Reabastecimiento</h3>
                <p class="text-xs text-gray-400 mt-0.5">Productos con menos de 10 unidades</p>
            </div>
            <span class="badge badge-rojo">Urgente</span>
        </div>
        <div class="card-body p-0">
            <?php if(empty($criticos)): ?>
                <div class="empty-state">
                    <i class="fas fa-check-circle text-green-300"></i>
                    <p class="font-semibold text-gray-600">Sin alertas criticas</p>
                    <p class="text-xs text-gray-400 mt-1">Todos los productos tienen stock suficiente</p>
                </div>
            <?php else: ?>
            <table class="table-modern">
                <thead>
                    <tr>
                        <th>Producto</th>
                        <th>Lote</th>
                        <th class="text-center">Stock</th>
                        <th class="text-center">Estado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($criticos as $c): ?>
                    <tr>
                        <td class="font-semibold"><?php echo e($c['nombre']); ?></td>
                        <td class="text-gray-500"><?php echo e($c['lote']); ?></td>
                        <td class="text-center font-bold text-instRojo"><?php echo e($c['stock']); ?></td>
                        <td class="text-center"><span class="badge badge-rojo">Critico</span></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </div>

    <div class="card flex flex-col">
        <div class="card-header">
            <h3 class="font-bold text-sm text-instNegro"><i class="fas fa-clock-rotate-left text-gray-400 mr-2"></i>Actividad Reciente</h3>
        </div>
        <div class="card-body flex-1 p-4">
            <div class="space-y-4">
                <?php foreach($stats['actividad'] as $a): ?>
                <div class="flex gap-3">
                    <div class="w-2 h-2 rounded-full bg-instRojo mt-1.5 shrink-0"></div>
                    <div class="min-w-0">
                        <p class="text-xs font-semibold text-instNegro truncate"><?php echo e($a['accion']); ?></p>
                        <p class="text-[11px] text-gray-400 mt-0.5"><?php echo e($a['nombre']); ?> &middot; <?php echo e($a['tabla']); ?></p>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="px-4 py-3 border-t border-gray-50">
            <a href="auditoria" class="text-xs font-bold text-instRojo hover:text-instRojo/70 transition-colors uppercase tracking-wider">Ver todo &rarr;</a>
        </div>
    </div>
</div>

<div class="card mt-6">
    <div class="card-header">
        <div>
            <h3 class="font-bold text-sm text-instNegro"><i class="fas fa-hourglass-half text-amber-500 mr-2"></i>Próximos a Vencer (30 días)</h3>
            <p class="text-xs text-gray-400 mt-0.5">Lotes con stock que vencen pronto — priorice su distribución/entrega</p>
        </div>
        <span class="badge badge-amarillo"><?php echo count($proxVencer); ?> lotes</span>
    </div>
    <div class="card-body p-0">
        <?php if(empty($proxVencer)): ?>
            <div class="empty-state"><i class="fas fa-circle-check text-green-300"></i><p class="font-semibold text-gray-600">Nada por vencer en 30 días</p></div>
        <?php else: ?>
        <div class="max-h-80 overflow-y-auto">
            <table class="table-modern">
                <thead class="sticky top-0"><tr><th>Producto</th><th>Lote</th><th class="text-center">Vence</th><th class="text-center">Días</th><th class="text-center">Stock</th></tr></thead>
                <tbody>
                    <?php foreach($proxVencer as $v): ?>
                    <tr>
                        <td class="font-semibold"><?php echo e($v['nombre_descripcion']); ?></td>
                        <td class="text-gray-500"><?php echo e($v['lote']); ?></td>
                        <td class="text-center text-amber-600 font-medium"><?php echo e($v['fecha_vencimiento']); ?></td>
                        <td class="text-center"><span class="badge <?php echo $v['dias_rest'] <= 7 ? 'badge-rojo' : 'badge-amarillo'; ?>"><?php echo (int)$v['dias_rest']; ?>d</span></td>
                        <td class="text-center font-bold text-instNegro"><?php echo (int)$v['stock_total']; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<div class="card mt-6">
    <div class="card-header">
        <div>
            <h3 class="font-bold text-sm text-instNegro"><i class="fas fa-ban text-red-600 mr-2"></i>Productos Dados de Baja por Vencimiento</h3>
            <p class="text-xs text-gray-400 mt-0.5">Lotes retirados del inventario al superar su fecha de vencimiento</p>
        </div>
        <span class="badge badge-rojo"><?php echo count($dadosBaja); ?> registros</span>
    </div>
    <div class="card-body p-0">
        <?php if(empty($dadosBaja)): ?>
            <div class="empty-state">
                <i class="fas fa-circle-check text-green-300"></i>
                <p class="font-semibold text-gray-600">Sin productos vencidos</p>
                <p class="text-xs text-gray-400 mt-1">No hay lotes dados de baja por vencimiento</p>
            </div>
        <?php else: ?>
        <div class="max-h-96 overflow-y-auto">
            <table class="table-modern">
                <thead class="sticky top-0">
                    <tr>
                        <th>Producto</th>
                        <th>Lote</th>
                        <th class="text-center">Vencio</th>
                        <th>Sede</th>
                        <th class="text-center">Cantidad</th>
                        <th class="text-center">Baja</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($dadosBaja as $b): ?>
                    <tr>
                        <td class="font-semibold"><?php echo e($b['nombre_descripcion']); ?></td>
                        <td class="text-gray-500"><?php echo e($b['lote']); ?></td>
                        <td class="text-center text-red-500 font-medium"><?php echo e($b['fecha_vencimiento']); ?></td>
                        <td class="text-gray-500"><?php echo e($b['sede']); ?></td>
                        <td class="text-center font-bold text-instRojo"><?php echo e($b['cantidad']); ?></td>
                        <td class="text-center text-xs text-gray-400"><?php echo e(date('Y-m-d', strtotime($b['fecha_hora']))); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>
