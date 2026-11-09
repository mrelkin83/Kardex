<?php Security::checkRole(['ADMIN']); ?>
<div class="mb-8">
    <h2 class="page-title">Distribucion Masiva</h2>
    <p class="page-subtitle">Marque los productos, indique cantidades y envielos a una sede en una sola operacion</p>
</div>

<div class="max-w-5xl mx-auto">
    <div class="card">
        <div class="card-header bg-instRojo text-white">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-lg bg-white/20 flex items-center justify-center"><i class="fas fa-truck-ramp-box text-instAmarillo text-sm"></i></div>
                <div>
                    <h3 class="font-bold text-sm">Envio Masivo a Sede</h3>
                    <p class="text-[11px] text-white/70">Todos los productos se envian en una transaccion unica</p>
                </div>
            </div>
        </div>
        <div class="card-body space-y-5">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="form-label">Sede Destino</label>
                    <select id="sedeDistMasiva" class="form-input">
                        <option value="">-- Seleccione Sede --</option>
                        <?php
                        $sedes = UsuariosModelo::mdlListarSedesExceptoCentral();
                        foreach($sedes as $s): ?>
                            <option value="<?php echo $s['id']; ?>"><?php echo e($s['nombre']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="form-label">Observacion</label>
                    <input type="text" id="obsDistMasiva" class="form-input" placeholder="Ej: Envio semanal programado">
                </div>
            </div>

            <div>
                <div class="flex flex-col sm:flex-row sm:items-center gap-3 mb-3">
                    <div class="relative flex-1">
                        <input type="text" id="buscarDist" class="form-input pl-9" placeholder="Buscar por nombre, codigo o lote..." autocomplete="off" onkeyup="filtrarDist()">
                        <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-300 text-sm"></i>
                    </div>
                    <span class="text-xs font-bold text-instRojo whitespace-nowrap"><span id="contadorDist">0</span> seleccionados</span>
                </div>

                <div class="border border-gray-100 rounded-lg overflow-hidden">
                    <div class="max-h-[420px] overflow-y-auto">
                        <table class="table-modern">
                            <thead class="sticky top-0 z-10">
                                <tr>
                                    <th class="w-12 text-center"><input type="checkbox" id="chkTodosDist" onchange="toggleTodosDist(this)" class="w-4 h-4 accent-instRojo cursor-pointer"></th>
                                    <th>Producto</th>
                                    <th class="text-center w-32">Stock Bodega</th>
                                    <th class="text-center w-32">Cantidad</th>
                                </tr>
                            </thead>
                            <tbody id="filasDist">
                                <?php
                                $productos = ProductosControlador::ctrMostrarProductos();
                                $stockMap = InventarioControlador::ctrCalcularStockTodos(1);
                                $hayStock = false;
                                foreach($productos as $p):
                                    $stk = isset($stockMap[$p['producto_id']]) ? $stockMap[$p['producto_id']] : 0;
                                    if($stk <= 0) continue;
                                    $hayStock = true;
                                    $busqueda = strtolower($p['codigo'].' '.$p['nombre_descripcion']);
                                ?>
                                <tr class="fila-dist" data-search="<?php echo e($busqueda); ?>">
                                    <td class="text-center">
                                        <input type="checkbox" class="chk-dist w-4 h-4 accent-instRojo cursor-pointer"
                                               data-id="<?php echo $p['producto_id']; ?>"
                                               data-stock="<?php echo $stk; ?>"
                                               onchange="onCheckDist(this)">
                                    </td>
                                    <td>
                                        <p class="font-medium text-gray-800"><?php echo e($p['nombre_descripcion']); ?></p>
                                        <p class="text-[11px] text-gray-400"><?php echo e($p['codigo']); ?> &bull; <?php echo e($p['concentracion'] ?? ''); ?></p>
                                    </td>
                                    <td class="text-center"><span class="badge badge-verde"><?php echo $stk; ?></span></td>
                                    <td class="text-center">
                                        <input type="number" min="1" max="<?php echo $stk; ?>" class="form-input qty-dist text-center !py-1.5" placeholder="0" disabled oninput="validarQtyDist(this)">
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if(!$hayStock): ?>
                                <tr><td colspan="4" class="text-center text-gray-400 text-xs py-8"><i class="fas fa-inbox text-2xl text-gray-200 block mb-2"></i>No hay productos con stock en bodega</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <div id="sinResultadosDist" class="hidden text-center text-gray-400 text-xs py-6"><i class="fas fa-search mr-1"></i>Ningun producto coincide con la busqueda</div>
                </div>
            </div>

            <div class="flex gap-3 pt-2">
                <button onclick="enviarDistMasiva()" class="btn btn-primary flex-1"><i class="fas fa-paper-plane"></i> Enviar Seleccionados a Sede</button>
                <button onclick="limpiarDist()" class="btn btn-ghost"><i class="fas fa-eraser"></i> Limpiar</button>
            </div>
        </div>
    </div>
</div>

<script>
function onCheckDist(chk) {
    const qty = chk.closest('tr').querySelector('.qty-dist');
    qty.disabled = !chk.checked;
    if(chk.checked) { qty.focus(); }
    else { qty.value = ''; qty.classList.remove('!border-red-500'); }
    actualizarContadorDist();
    sincronizarChkTodosDist();
}
function validarQtyDist(input) {
    const stock = parseInt(input.max);
    const val = parseInt(input.value);
    if(val > stock) { input.classList.add('!border-red-500'); }
    else { input.classList.remove('!border-red-500'); }
}
function toggleTodosDist(master) {
    document.querySelectorAll('.fila-dist').forEach(tr => {
        if(tr.style.display === 'none') return;
        const chk = tr.querySelector('.chk-dist');
        chk.checked = master.checked;
        onCheckDist(chk);
    });
}
function sincronizarChkTodosDist() {
    const visibles = [...document.querySelectorAll('.fila-dist')].filter(tr => tr.style.display !== 'none');
    const marcados = visibles.filter(tr => tr.querySelector('.chk-dist').checked);
    const master = document.getElementById('chkTodosDist');
    master.checked = visibles.length > 0 && marcados.length === visibles.length;
    master.indeterminate = marcados.length > 0 && marcados.length < visibles.length;
}
function actualizarContadorDist() {
    const n = document.querySelectorAll('.chk-dist:checked').length;
    document.getElementById('contadorDist').textContent = n;
}
function filtrarDist() {
    const q = document.getElementById('buscarDist').value.toLowerCase().trim();
    let visibles = 0;
    document.querySelectorAll('.fila-dist').forEach(tr => {
        const match = tr.dataset.search.indexOf(q) !== -1;
        tr.style.display = match ? '' : 'none';
        if(match) visibles++;
    });
    document.getElementById('sinResultadosDist').classList.toggle('hidden', visibles > 0);
    sincronizarChkTodosDist();
}
function limpiarDist() {
    document.querySelectorAll('.chk-dist').forEach(chk => { chk.checked = false; onCheckDist(chk); });
    document.getElementById('chkTodosDist').checked = false;
}
function enviarDistMasiva() {
    const sede = document.getElementById('sedeDistMasiva').value;
    if(!sede) { showToast('Error', 'Seleccione una sede destino', 'error'); return; }

    const items = [];
    let excede = false;
    document.querySelectorAll('.chk-dist:checked').forEach(chk => {
        const tr = chk.closest('tr');
        const qty = tr.querySelector('.qty-dist');
        const cant = parseInt(qty.value);
        const stock = parseInt(chk.dataset.stock);
        if(!cant || cant <= 0) { qty.classList.add('!border-red-500'); excede = true; return; }
        if(cant > stock) { qty.classList.add('!border-red-500'); excede = true; return; }
        items.push({ producto_id: chk.dataset.id, cantidad: cant });
    });

    if(items.length === 0) { showToast('Error', 'Marque productos e indique cantidades validas', 'error'); return; }
    if(excede) { showToast('Error', 'Hay cantidades invalidas o que superan el stock', 'error'); return; }

    const fd = new FormData();
    fd.append('action', 'distribucionMasiva');
    fd.append('csrf_token', CSRF_TOKEN);
    fd.append('idSedeMasiva', sede);
    fd.append('items', JSON.stringify(items));
    fd.append('obsMasiva', document.getElementById('obsDistMasiva').value);
    fetch('ajax/inventario.ajax.php', { method: 'POST', body: fd })
    .then(res => res.text())
    .then(data => {
        try {
            const r = JSON.parse(data);
            if(r.status === 'ok') { showToast('Exito', r.total + ' productos distribuidos correctamente', 'success'); setTimeout(()=>{ window.location.reload(); }, 1500); }
            else if(r.status === 'stock_insuficiente') { showToast('Error', 'Stock insuficiente (disponible: ' + r.stock + ', pedido: ' + r.pedido + ')', 'error'); }
            else { showToast('Error', 'Error en la distribucion', 'error'); }
        } catch(e) { showToast('Error', 'Error inesperado', 'error'); }
    });
}
</script>
