<?php Security::checkRole(['ADMIN']); ?>

<div class="page-title">Catálogo de Medicamentos</div>
<div class="page-subtitle">Gestión y registro de medicamentos del inventario</div>

<div class="flex justify-end mb-6">
    <button onclick="document.getElementById('modalMed').classList.remove('hidden')" class="btn btn-primary">
        <i class="fas fa-plus mr-2"></i> Nuevo Medicamento
    </button>
</div>

<?php
$q = trim($_GET['q'] ?? '');
$porPagina = 15;
$pag = max(1, (int)($_GET['pag'] ?? 1));
$total = ProductosControlador::ctrContarPorTipo('MEDICAMENTO', $q);
$totalPags = max(1, (int)ceil($total / $porPagina));
if($pag > $totalPags) $pag = $totalPags;
$offset = ($pag - 1) * $porPagina;
$productos = ProductosControlador::ctrListarPorTipo('MEDICAMENTO', $q, $porPagina, $offset);
$stockSis = InventarioControlador::ctrStockSistemaTodos();
?>
<div class="card">
    <div class="card-header">
        <form method="get" action="medicamentos" class="search-box">
            <i class="fas fa-search"></i>
            <input type="text" name="q" value="<?php echo e($q); ?>" placeholder="Buscar por nombre, código, principio, lote..." autocomplete="off">
        </form>
        <span class="text-xs text-gray-400 font-medium">
            <?php echo $total; ?> medicamento(s)<?php if($q !== ''): ?> &middot; <a href="medicamentos" class="text-instRojo hover:underline">limpiar</a><?php endif; ?>
        </span>
    </div>
    <div class="card-body">
        <div class="overflow-x-auto">
            <table class="table-modern" id="tableMed">
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Nombre</th>
                        <th>Principio Activo</th>
                        <th>Presentación</th>
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
                        <td class="font-semibold text-gray-800"><?php echo e($p["nombre_descripcion"]); ?> <span class="text-xs font-normal text-gray-400"><?php echo e($p["concentracion"]); ?></span></td>
                        <td class="text-gray-600"><?php echo e($p["principio_activo"]); ?></td>
                        <td class="text-gray-600"><?php echo e($p["presentacion"]); ?></td>
                        <td class="text-center font-bold <?php echo $stk > 0 ? 'text-green-600' : 'text-gray-300'; ?>"><?php echo $stk; ?></td>
                        <td class="text-center"><span class="badge badge-gris"><?php echo (int)$p["num_lotes"]; ?></span></td>
                        <td class="text-center <?php echo $vencido ? 'text-red-600 font-bold' : 'text-gray-500'; ?>"><?php echo $venc ? e($venc) : '—'; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php if(empty($productos)): ?>
            <div class="empty-state"><i class="fas fa-pills"></i><p>No se encontraron medicamentos<?php echo $q !== '' ? ' para "' . e($q) . '"' : ''; ?></p></div>
            <?php endif; ?>
        </div>
        <?php echo paginador('medicamentos', $q, $pag, $totalPags); ?>
    </div>
</div>

<div id="modalMed" class="modal-overlay hidden">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="text-xl font-bold text-gray-800"><i class="fas fa-pills mr-2 text-instRojo"></i>Registrar Medicamento</h3>
            <button onclick="document.getElementById('modalMed').classList.add('hidden')" class="modal-header-close">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body">
            <form id="formMed" onsubmit="event.preventDefault(); submitFormAjax('formMed', 'crearMedicamento');">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div>
                        <label class="form-label">Código Único</label>
                        <input type="text" name="codigoMed" required class="form-input">
                    </div>
                    <div>
                        <label class="form-label">Nombre Comercial</label>
                        <input type="text" name="nombreMed" required class="form-input">
                    </div>
                    <div>
                        <label class="form-label">Principio Activo</label>
                        <input type="text" name="principioMed" required class="form-input">
                    </div>
                    <div>
                        <label class="form-label">Forma Farmacéutica</label>
                        <input type="text" name="formaMed" required placeholder="Ej: Tableta, Jarabe" class="form-input">
                    </div>
                    <div>
                        <label class="form-label">Concentración</label>
                        <input type="text" name="concentracionMed" required placeholder="Ej: 500mg" class="form-input">
                    </div>
                    <div>
                        <label class="form-label">Presentación</label>
                        <input type="text" name="presentacionMed" required placeholder="Ej: Caja x 10" class="form-input">
                    </div>
                    <div>
                        <label class="form-label">Registro INVIMA</label>
                        <input type="text" name="invimaMed" required class="form-input">
                    </div>
                    <div>
                        <label class="form-label">Lote</label>
                        <input type="text" name="loteMed" required class="form-input">
                    </div>
                    <div class="md:col-span-2">
                        <label class="form-label">Fecha de Vencimiento</label>
                        <input type="date" name="fechaVencMed" required class="form-input">
                    </div>
                </div>
                <div class="mt-6 pt-4 border-t border-gray-200 flex justify-end gap-3">
                    <button type="button" onclick="document.getElementById('modalMed').classList.add('hidden')" class="btn btn-ghost">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar Medicamento</button>
                </div>
            </form>
        </div>
    </div>
</div>
