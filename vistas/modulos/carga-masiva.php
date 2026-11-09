<?php Security::checkRole(['ADMIN']); ?>
<div class="mb-8">
    <h2 class="page-title">Carga Masiva a Bodega</h2>
    <p class="page-subtitle">Importe productos desde CSV o registre entradas masivas a la bodega central</p>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <div class="card">
        <div class="card-header bg-instRojo text-white">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-lg bg-white/20 flex items-center justify-center"><i class="fas fa-file-csv text-instAmarillo text-sm"></i></div>
                <div>
                    <h3 class="font-bold text-sm">Importar Productos (CSV)</h3>
                    <p class="text-[11px] text-white/70">Medicamentos o dispositivos desde archivo</p>
                </div>
            </div>
        </div>
        <div class="card-body space-y-4">
            <div>
                <label class="form-label">Tipo de producto</label>
                <select id="tipoCargaCSV" class="form-input">
                    <option value="MEDICAMENTO">Medicamentos</option>
                    <option value="DISPOSITIVO">Dispositivos Medicos</option>
                </select>
            </div>
            <div>
                <div class="flex items-center justify-between mb-1">
                    <label class="form-label mb-0">Formato CSV requerido</label>
                    <button type="button" onclick="descargarPlantillaCSV()" class="btn btn-ghost btn-sm"><i class="fas fa-download"></i> Descargar plantilla</button>
                </div>
                <div class="bg-gray-50 rounded-lg p-3 text-xs font-mono text-gray-600 leading-relaxed border border-gray-200">
                    <p class="font-bold text-instNegro mb-1 font-sans text-[11px]">Medicamentos:</p>
                    <p>codigo, nombre, principio_activo, forma_farmaceutica, concentracion, presentacion, registro_invima, lote, fecha_vencimiento</p>
                    <p class="font-bold text-instNegro mb-1 mt-3 font-sans text-[11px]">Dispositivos:</p>
                    <p>codigo, descripcion, marca, modelo, registro_sanitario, lote, vida_util</p>
                </div>
            </div>
            <div>
                <label class="form-label">Seleccionar archivo CSV</label>
                <div id="csvDropZone" class="border-2 border-dashed border-gray-200 rounded-lg p-6 text-center cursor-pointer hover:border-instRojo/40 hover:bg-instRojo/[0.02] transition-all">
                    <i class="fas fa-cloud-arrow-up text-3xl text-gray-300 mb-2"></i>
                    <p class="text-sm font-semibold text-gray-500">Arrastre su archivo aqui o haga clic</p>
                    <p class="text-xs text-gray-400 mt-1" id="csvFileName">Solo archivos .csv</p>
                    <input type="file" id="csvFileInput" accept=".csv" class="hidden">
                </div>
            </div>
            <button onclick="enviarCSV()" class="btn btn-primary w-full"><i class="fas fa-upload"></i> Importar Productos</button>
            <div id="csvResultado" class="hidden"></div>
        </div>
    </div>

    <div class="card">
        <div class="card-header bg-instNegro text-white">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-lg bg-white/20 flex items-center justify-center"><i class="fas fa-boxes-stacked text-instAmarillo text-sm"></i></div>
                <div>
                    <h3 class="font-bold text-sm">Entrada Masiva a Bodega</h3>
                    <p class="text-[11px] text-white/70">Marque productos e indique cuanto ingresa de cada uno</p>
                </div>
            </div>
        </div>
        <div class="card-body space-y-4">
            <div>
                <label class="form-label">Observacion general</label>
                <input type="text" id="obsEntradaMasiva" class="form-input" placeholder="Ej: Factura #1234 - Proveedor XYZ">
            </div>

            <div class="flex flex-col sm:flex-row sm:items-center gap-3">
                <div class="relative flex-1">
                    <input type="text" id="buscarEntrada" class="form-input pl-9" placeholder="Buscar por nombre, codigo o lote..." autocomplete="off" onkeyup="filtrarEntrada()">
                    <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-300 text-sm"></i>
                </div>
                <span class="text-xs font-bold text-instNegro whitespace-nowrap"><span id="contadorEntrada">0</span> seleccionados</span>
            </div>

            <div class="border border-gray-100 rounded-lg overflow-hidden">
                <div class="max-h-[420px] overflow-y-auto">
                    <table class="table-modern">
                        <thead class="sticky top-0 z-10">
                            <tr>
                                <th class="w-12 text-center"><input type="checkbox" id="chkTodosEntrada" onchange="toggleTodosEntrada(this)" class="w-4 h-4 accent-instNegro cursor-pointer"></th>
                                <th>Producto</th>
                                <th class="text-center w-20">Stock</th>
                                <th class="w-32">Lote</th>
                                <th class="w-36">Vencimiento</th>
                                <th class="text-center w-24">Cantidad</th>
                            </tr>
                        </thead>
                        <tbody id="filasEntrada">
                            <?php
                            $productos = ProductosControlador::ctrMostrarProductos();
                            $stockMap = InventarioControlador::ctrCalcularStockTodos(1);
                            if(count($productos) === 0): ?>
                            <tr><td colspan="6" class="text-center text-gray-400 text-xs py-8"><i class="fas fa-inbox text-2xl text-gray-200 block mb-2"></i>No hay productos registrados. Importe primero el catalogo.</td></tr>
                            <?php else:
                                foreach($productos as $p):
                                    $stk = isset($stockMap[$p['producto_id']]) ? $stockMap[$p['producto_id']] : 0;
                                    $busqueda = strtolower($p['codigo'].' '.$p['nombre_descripcion']);
                                ?>
                                <tr class="fila-entrada" data-search="<?php echo e($busqueda); ?>">
                                    <td class="text-center">
                                        <input type="checkbox" class="chk-entrada w-4 h-4 accent-instNegro cursor-pointer"
                                               data-id="<?php echo $p['producto_id']; ?>"
                                               onchange="onCheckEntrada(this)">
                                    </td>
                                    <td>
                                        <p class="font-medium text-gray-800"><?php echo e($p['nombre_descripcion']); ?></p>
                                        <p class="text-[11px] text-gray-400"><?php echo e($p['codigo']); ?> &bull; <?php echo e($p['concentracion'] ?? ''); ?></p>
                                    </td>
                                    <td class="text-center"><span class="badge badge-gris"><?php echo $stk; ?></span></td>
                                    <td><input type="text" class="form-input lote-entrada !py-1.5" placeholder="N° lote" disabled></td>
                                    <td><input type="date" class="form-input venc-entrada !py-1.5" disabled></td>
                                    <td class="text-center">
                                        <input type="number" min="1" class="form-input qty-entrada text-center !py-1.5" placeholder="0" disabled oninput="validarQtyEntrada(this)">
                                    </td>
                                </tr>
                                <?php endforeach;
                            endif; ?>
                        </tbody>
                    </table>
                </div>
                <div id="sinResultadosEntrada" class="hidden text-center text-gray-400 text-xs py-6"><i class="fas fa-search mr-1"></i>Ningun producto coincide con la busqueda</div>
            </div>

            <div class="flex gap-3">
                <button onclick="enviarEntradaMasivaBodega()" class="btn btn-success flex-1"><i class="fas fa-check"></i> Registrar Entradas</button>
                <button onclick="limpiarEntrada()" class="btn btn-ghost"><i class="fas fa-eraser"></i> Limpiar</button>
            </div>
        </div>
    </div>
</div>

<script>
function descargarPlantillaCSV() {
    const tipo = document.getElementById('tipoCargaCSV').value;
    let headers, ejemplo, nombreArchivo;
    if(tipo === 'MEDICAMENTO') {
        headers = ['codigo','nombre','principio_activo','forma_farmaceutica','concentracion','presentacion','registro_invima','lote','fecha_vencimiento'];
        ejemplo = ['MED-001','Acetaminofen 500mg','Acetaminofen','Tableta','500mg','Caja x 100','INVIMA-2024M-001','L2024-001','2026-12-31'];
        nombreArchivo = 'plantilla_medicamentos.csv';
    } else {
        headers = ['codigo','descripcion','marca','modelo','registro_sanitario','lote','vida_util'];
        ejemplo = ['DISP-001','Jeringa 5ml desechable','MarcaX','J5-STD','RS-2024D-001','L2024-100','5 anios'];
        nombreArchivo = 'plantilla_dispositivos.csv';
    }
    const filas = [headers.join(','), ejemplo.join(',')];
    // BOM UTF-8 para que Excel respete las tildes
    const contenido = '﻿' + filas.join('\r\n');
    const blob = new Blob([contenido], { type: 'text/csv;charset=utf-8;' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = nombreArchivo;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
    showToast('Plantilla generada', 'Reemplace la fila de ejemplo con sus datos', 'success');
}

function onCheckEntrada(chk) {
    const tr = chk.closest('tr');
    const qty = tr.querySelector('.qty-entrada');
    const lote = tr.querySelector('.lote-entrada');
    const venc = tr.querySelector('.venc-entrada');
    [qty, lote, venc].forEach(el => el.disabled = !chk.checked);
    if(chk.checked) { lote.focus(); }
    else { qty.value=''; lote.value=''; venc.value=''; qty.classList.remove('!border-red-500'); lote.classList.remove('!border-red-500'); }
    actualizarContadorEntrada();
    sincronizarChkTodosEntrada();
}
function validarQtyEntrada(input) {
    const val = parseInt(input.value);
    if(input.value !== '' && (!val || val <= 0)) { input.classList.add('!border-red-500'); }
    else { input.classList.remove('!border-red-500'); }
}
function toggleTodosEntrada(master) {
    document.querySelectorAll('.fila-entrada').forEach(tr => {
        if(tr.style.display === 'none') return;
        const chk = tr.querySelector('.chk-entrada');
        chk.checked = master.checked;
        onCheckEntrada(chk);
    });
}
function sincronizarChkTodosEntrada() {
    const visibles = [...document.querySelectorAll('.fila-entrada')].filter(tr => tr.style.display !== 'none');
    const marcados = visibles.filter(tr => tr.querySelector('.chk-entrada').checked);
    const master = document.getElementById('chkTodosEntrada');
    master.checked = visibles.length > 0 && marcados.length === visibles.length;
    master.indeterminate = marcados.length > 0 && marcados.length < visibles.length;
}
function actualizarContadorEntrada() {
    document.getElementById('contadorEntrada').textContent = document.querySelectorAll('.chk-entrada:checked').length;
}
function filtrarEntrada() {
    const q = document.getElementById('buscarEntrada').value.toLowerCase().trim();
    let visibles = 0;
    document.querySelectorAll('.fila-entrada').forEach(tr => {
        const match = tr.dataset.search.indexOf(q) !== -1;
        tr.style.display = match ? '' : 'none';
        if(match) visibles++;
    });
    document.getElementById('sinResultadosEntrada').classList.toggle('hidden', visibles > 0);
    sincronizarChkTodosEntrada();
}
function limpiarEntrada() {
    document.querySelectorAll('.chk-entrada').forEach(chk => { chk.checked = false; onCheckEntrada(chk); });
    const master = document.getElementById('chkTodosEntrada');
    if(master) master.checked = false;
}
function enviarEntradaMasivaBodega() {
    const items = [];
    let invalido = false;
    document.querySelectorAll('.chk-entrada:checked').forEach(chk => {
        const tr = chk.closest('tr');
        const qty = tr.querySelector('.qty-entrada');
        const lote = tr.querySelector('.lote-entrada');
        const venc = tr.querySelector('.venc-entrada');
        const cant = parseInt(qty.value);
        if(!cant || cant <= 0) { qty.classList.add('!border-red-500'); invalido = true; return; }
        if(!lote.value.trim()) { lote.classList.add('!border-red-500'); invalido = true; return; }
        items.push({ producto_id: chk.dataset.id, cantidad: cant, lote: lote.value.trim(), venc: venc.value || null });
    });

    if(items.length === 0) { showToast('Error', 'Marque productos e indique cantidades validas', 'error'); return; }
    if(invalido) { showToast('Error', 'Revise cantidades y números de lote', 'error'); return; }

    const fd = new FormData();
    fd.append('action', 'entradaMasivaBodega');
    fd.append('csrf_token', CSRF_TOKEN);
    fd.append('items', JSON.stringify(items));
    fd.append('obsMasiva', document.getElementById('obsEntradaMasiva').value);
    fetch('ajax/inventario.ajax.php', { method: 'POST', body: fd })
    .then(res => res.text())
    .then(data => {
        try {
            const r = JSON.parse(data);
            if(r.status === 'ok') { showToast('Exito', 'Se registraron ' + r.total + ' entradas correctamente', 'success'); setTimeout(()=>{ window.location.reload(); }, 1500); }
            else { showToast('Error', 'No se pudieron registrar las entradas', 'error'); }
        } catch(e) { showToast('Error', 'Error inesperado', 'error'); }
    });
}
</script>
