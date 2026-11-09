<?php
Security::checkRole(['ADMIN']);
$id = (int)($_GET['id'] ?? 0);
$m = ProductosControlador::ctrObtenerMaestro($id);
if(!$m) { echo '<div class="empty-state"><i class="fas fa-box-open"></i><p>Producto no encontrado</p></div>'; return; }
$sedeStock = $_SESSION['sede_id'];
$lotes = ProductosControlador::ctrLotesConStock($id, $sedeStock);
$totalStock = 0; foreach($lotes as $l) $totalStock += (int)$l['stock'];
$esMed = $m['tipo'] === 'MEDICAMENTO';
?>
<div class="mb-6">
    <a href="<?php echo $esMed ? 'medicamentos' : 'dispositivos'; ?>" class="text-xs font-bold text-instRojo hover:underline uppercase tracking-wider"><i class="fas fa-arrow-left mr-1"></i> Volver al catálogo</a>
    <h2 class="page-title mt-2"><?php echo e($m['nombre']); ?></h2>
    <p class="page-subtitle"><?php echo e($m['codigo']); ?> &middot; <?php echo e($m['tipo']); ?></p>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="card lg:col-span-1">
        <div class="card-header"><h3 class="font-bold text-sm text-instNegro"><i class="fas fa-circle-info text-instRojo mr-2"></i>Ficha del producto</h3></div>
        <div class="card-body space-y-2 text-sm">
            <?php
            $campos = $esMed
                ? ['Principio activo'=>'principio_activo','Concentración'=>'concentracion','Forma'=>'forma_farmaceutica','Presentación'=>'presentacion','Registro INVIMA'=>'registro']
                : ['Marca'=>'marca','Modelo'=>'modelo','Registro sanitario'=>'registro','Vida útil'=>'vida_util'];
            foreach($campos as $label=>$k): ?>
            <div class="flex justify-between gap-3 border-b border-gray-50 pb-1.5">
                <span class="text-gray-400 font-medium"><?php echo $label; ?></span>
                <span class="text-gray-800 text-right"><?php echo e($m[$k]) ?: '—'; ?></span>
            </div>
            <?php endforeach; ?>
            <div class="flex justify-between gap-3 pt-2">
                <span class="text-gray-500 font-bold uppercase text-xs">Stock (<?php echo e($_SESSION['sede_nombre'] ?? 'sede'); ?>)</span>
                <span class="font-extrabold text-instRojo text-lg leading-none"><?php echo $totalStock; ?></span>
            </div>
        </div>
    </div>

    <div class="card lg:col-span-2">
        <div class="card-header">
            <h3 class="font-bold text-sm text-instNegro"><i class="fas fa-layer-group text-instRojo mr-2"></i>Lotes</h3>
            <span class="badge badge-gris"><?php echo count($lotes); ?> lotes</span>
        </div>
        <div class="card-body p-0">
            <table class="table-modern">
                <thead><tr><th>Lote</th><th>Origen</th><th class="text-center">Vencimiento</th><th class="text-center">Stock</th></tr></thead>
                <tbody>
                    <?php foreach($lotes as $l):
                        $venc = $l['fecha_vencimiento'];
                        $vencido = $venc && (strtotime($venc) < time());
                    ?>
                    <tr>
                        <td class="font-medium"><?php echo e($l['numero_lote']); ?></td>
                        <td class="text-xs text-gray-400"><?php echo e($l['codigo_origen']); ?></td>
                        <td class="text-center <?php echo $vencido ? 'text-red-600 font-bold' : 'text-gray-600'; ?>">
                            <?php echo $venc ? e($venc) : '—'; ?>
                            <?php if($vencido): ?><span class="badge badge-rojo ml-1">Vencido</span><?php endif; ?>
                        </td>
                        <td class="text-center font-bold <?php echo $l['stock'] > 0 ? 'text-green-600' : 'text-gray-300'; ?>"><?php echo (int)$l['stock']; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php if(empty($lotes)): ?><div class="empty-state"><i class="fas fa-layer-group"></i><p>Sin lotes registrados</p></div><?php endif; ?>
        </div>
    </div>
</div>
