<?php Security::checkRole(['ADMIN']); ?>

<div class="page-title">Catálogo de Dispositivos Médicos</div>
<div class="page-subtitle">Gestión y registro de dispositivos médicos del inventario</div>

<div class="flex justify-end mb-6">
    <button onclick="document.getElementById('modalDisp').classList.remove('hidden')" class="bg-purple-600 hover:bg-purple-700 text-white font-bold py-2 px-5 rounded-lg shadow transition-colors flex items-center">
        <i class="fas fa-plus mr-2"></i> Nuevo Dispositivo
    </button>
</div>

<?php
$q = trim($_GET['q'] ?? '');
$porPagina = 15;
$pag = max(1, (int)($_GET['pag'] ?? 1));
$total = ProductosControlador::ctrContarPorTipo('DISPOSITIVO', $q);
$totalPags = max(1, (int)ceil($total / $porPagina));
if($pag > $totalPags) $pag = $totalPags;
$offset = ($pag - 1) * $porPagina;
$productos = ProductosControlador::ctrListarPorTipo('DISPOSITIVO', $q, $porPagina, $offset);
$stockSis = InventarioControlador::ctrStockSistemaTodos();
?>
<div class="card">
    <div class="card-header">
        <form method="get" action="dispositivos" class="search-box">
            <i class="fas fa-search"></i>
            <input type="text" name="q" value="<?php echo e($q); ?>" placeholder="Buscar por descripción, código, marca, lote..." autocomplete="off">
        </form>
        <span class="text-xs text-gray-400 font-medium">
            <?php echo $total; ?> dispositivo(s)<?php if($q !== ''): ?> &middot; <a href="dispositivos" class="text-instRojo hover:underline">limpiar</a><?php endif; ?>
        </span>
    </div>
    <div class="card-body">
        <div class="overflow-x-auto">
            <table class="table-modern" id="tableDisp">
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Descripción</th>
                        <th>Marca</th>
                        <th class="text-center">Stock</th>
                        <th class="text-center">Lotes</th>
                        <th class="text-center">Próx. Venc.</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($productos as $p):
                        $stk = $stockSis[$p["producto_id"]] ?? 0;
                        $venc = $p["prox_venc"];
                        $vencido = $venc && (strtotime($venc) < time());
                    ?>
                    <tr class="cursor-pointer" onclick="location.href='producto?id=<?php echo $p["producto_id"]; ?>'">
                        <td class="font-medium"><?php echo e($p["codigo"]); ?></td>
                        <td class="font-semibold text-gray-800"><?php echo e($p["nombre_descripcion"]); ?></td>
                        <td class="text-gray-600"><?php echo e($p["marca"]); ?></td>
                        <td class="text-center font-bold <?php echo $stk > 0 ? 'text-green-600' : 'text-gray-300'; ?>"><?php echo $stk; ?></td>
                        <td class="text-center"><span class="badge badge-gris"><?php echo (int)$p["num_lotes"]; ?></span></td>
                        <td class="text-center <?php echo $vencido ? 'text-red-600 font-bold' : 'text-gray-500'; ?>"><?php echo $venc ? e($venc) : '—'; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php if(empty($productos)): ?>
            <div class="empty-state"><i class="fas fa-stethoscope"></i><p>No se encontraron dispositivos<?php echo $q !== '' ? ' para "' . e($q) . '"' : ''; ?></p></div>
            <?php endif; ?>
        </div>
        <?php echo paginador('dispositivos', $q, $pag, $totalPags); ?>
    </div>
</div>

<div id="modalDisp" class="modal-overlay hidden">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="text-xl font-bold text-gray-800"><i class="fas fa-syringe mr-2 text-purple-600"></i>Registrar Dispositivo Médico</h3>
            <button onclick="document.getElementById('modalDisp').classList.add('hidden')" class="modal-header-close">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body">
            <form id="formDisp" onsubmit="event.preventDefault(); submitFormAjax('formDisp', 'crearDispositivo');">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div>
                        <label class="form-label">Código Único</label>
                        <input type="text" name="codigoDisp" required class="form-input">
                    </div>
                    <div>
                        <label class="form-label">Marca</label>
                        <input type="text" name="marcaDisp" required class="form-input">
                    </div>
                    <div class="md:col-span-2">
                        <label class="form-label">Descripción General</label>
                        <input type="text" name="descDisp" required class="form-input">
                    </div>
                    <div>
                        <label class="form-label">Modelo</label>
                        <input type="text" name="modeloDisp" class="form-input">
                    </div>
                    <div>
                        <label class="form-label">Registro Sanitario</label>
                        <input type="text" name="sanitarioDisp" required class="form-input">
                    </div>
                    <div>
                        <label class="form-label">Lote</label>
                        <input type="text" name="loteDisp" required class="form-input">
                    </div>
                    <div>
                        <label class="form-label">Vida Útil</label>
                        <input type="text" name="vidaDisp" required placeholder="Ej: 5 años, Un solo uso" class="form-input">
                    </div>
                </div>
                <div class="mt-6 pt-4 border-t border-gray-200 flex justify-end gap-3">
                    <button type="button" onclick="document.getElementById('modalDisp').classList.add('hidden')" class="btn btn-ghost">Cancelar</button>
                    <button type="submit" class="bg-purple-600 hover:bg-purple-700 text-white font-bold py-2 px-5 rounded-lg shadow transition-colors">Guardar Dispositivo</button>
                </div>
            </form>
        </div>
    </div>
</div>
