<?php Security::checkRole(['ADMIN']); ?>
<div class="mb-6">
    <h2 class="page-title">Distribucion a Sedes</h2>
    <p class="page-subtitle">Envie productos desde bodega central a las sedes (FEFO: sale primero lo que vence antes)</p>
</div>

<div class="max-w-2xl mx-auto">
    <div class="card">
        <div class="card-header bg-instNegro text-white">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-lg bg-white/10 flex items-center justify-center"><i class="fas fa-truck-fast text-instAmarillo text-sm"></i></div>
                <div>
                    <h3 class="font-bold text-sm">Distribucion Individual</h3>
                    <p class="text-[11px] text-white/60">Envie un producto a la sede destino</p>
                </div>
            </div>
        </div>
        <div class="card-body">
            <form id="formDist" onsubmit="event.preventDefault(); submitFormAjax('formDist', 'distribucionSede', 'ajax/inventario.ajax.php');" class="space-y-4">
                <div>
                    <label class="form-label">Producto</label>
                    <div class="relative">
                        <input type="text" id="buscarProductoDist" class="form-input pr-8" placeholder="Buscar por nombre o codigo..." autocomplete="off">
                        <i class="fas fa-search absolute right-3 top-1/2 -translate-y-1/2 text-gray-300 text-sm"></i>
                    </div>
                    <div id="resultadosProductoDist" class="hidden mt-1 border border-gray-200 rounded-lg bg-white shadow-lg max-h-56 overflow-y-auto z-50"></div>
                    <input type="hidden" name="idMaestroDist" id="idMaestroDist" required>
                    <div id="productoDistSeleccionado" class="hidden mt-2 p-3 bg-green-50 border border-green-200 rounded-lg">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="font-semibold text-sm text-green-800" id="nombreProdDist"></p>
                                <p class="text-xs text-green-600" id="detalleProdDist"></p>
                            </div>
                            <button type="button" onclick="limpiarProductoDist()" class="text-green-400 hover:text-red-500 transition-colors"><i class="fas fa-times-circle"></i></button>
                        </div>
                    </div>
                </div>
                <div id="boxLoteDist" class="hidden">
                    <label class="form-label">Lote <span class="text-gray-400 normal-case font-normal">(automático FEFO por defecto)</span></label>
                    <select name="loteForzadoDist" id="loteForzadoDist" class="form-input">
                        <option value="">Automático (vence primero)</option>
                    </select>
                </div>
                <div>
                    <label class="form-label">Sede Destino</label>
                    <select name="idSedeDist" required class="form-input">
                        <option value="">-- Seleccione --</option>
                        <?php foreach(UsuariosModelo::mdlListarSedesExceptoCentral() as $s): ?>
                            <option value="<?php echo $s['id']; ?>"><?php echo e($s['nombre']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="form-label">Cantidad</label>
                    <input type="number" name="cantidadDist" required min="1" class="form-input" placeholder="Cantidad a enviar">
                </div>
                <div>
                    <label class="form-label">Observaciones</label>
                    <input type="text" name="obsDist" class="form-input" placeholder="Observacion de envio">
                </div>
                <button type="submit" class="btn btn-primary w-full"><i class="fas fa-paper-plane"></i> Enviar a Sede</button>
            </form>
        </div>
    </div>
</div>

<script>
(function(){
    <?php
    $stockMap = InventarioControlador::ctrCalcularStockTodos(1);
    $lotesSel = InventarioControlador::ctrLotesParaSelect(1);
    $lotesPorMaestro = [];
    foreach($lotesSel as $l){ $lotesPorMaestro[$l['producto_id']][] = $l; }
    ?>
    var productos = [
        <?php foreach(ProductosControlador::ctrMostrarProductos() as $p):
            $stk = $stockMap[$p['producto_id']] ?? 0; if($stk <= 0) continue; ?>
        {id:<?php echo $p['producto_id']; ?>, nombre:"<?php echo e($p['nombre_descripcion']); ?>", codigo:"<?php echo e($p['codigo']); ?>", tipo:"<?php echo e($p['tipo']); ?>", stock:<?php echo $stk; ?>},
        <?php endforeach; ?>
    ];
    var lotesPorMaestro = <?php echo json_encode($lotesPorMaestro, JSON_UNESCAPED_UNICODE); ?>;
    var input = document.getElementById('buscarProductoDist');
    var resultados = document.getElementById('resultadosProductoDist');

    input.addEventListener('input', function(){
        var q = this.value.toLowerCase().trim();
        if(q.length < 2){ resultados.classList.add('hidden'); return; }
        var f = productos.filter(function(p){ return p.nombre.toLowerCase().indexOf(q)!==-1 || p.codigo.toLowerCase().indexOf(q)!==-1; }).slice(0,50);
        if(f.length===0){ resultados.innerHTML='<div class="px-4 py-3 text-sm text-gray-400"><i class="fas fa-inbox mr-1"></i>Sin resultados</div>'; }
        else { resultados.innerHTML = f.map(function(p){
            var icon = p.tipo==='MEDICAMENTO' ? 'fa-pills text-blue-400':'fa-stethoscope text-purple-400';
            return '<div class="px-4 py-3 hover:bg-instRojo/5 cursor-pointer border-b border-gray-50 last:border-0" data-id="'+p.id+'" data-nombre="'+p.nombre+'" data-detalle="'+p.codigo+' | Stock bodega: '+p.stock+'">' +
                '<p class="font-medium text-sm text-gray-800"><i class="fas '+icon+' mr-2 text-xs"></i>'+p.nombre+'</p>' +
                '<p class="text-xs text-gray-400">'+p.codigo+' &bull; <span class="text-green-600 font-semibold">Stock bodega: '+p.stock+'</span></p></div>';
        }).join(''); }
        resultados.classList.remove('hidden');
    });

    resultados.addEventListener('click', function(e){
        var item = e.target.closest('[data-id]'); if(!item) return;
        var id = item.dataset.id;
        document.getElementById('idMaestroDist').value = id;
        document.getElementById('nombreProdDist').textContent = item.dataset.nombre;
        document.getElementById('detalleProdDist').textContent = item.dataset.detalle;
        document.getElementById('productoDistSeleccionado').classList.remove('hidden');
        // poblar override de lotes
        var sel = document.getElementById('loteForzadoDist');
        sel.innerHTML = '<option value="">Automático (vence primero)</option>';
        (lotesPorMaestro[id]||[]).forEach(function(l){
            var venc = l.fecha_vencimiento ? l.fecha_vencimiento : 'sin venc.';
            sel.innerHTML += '<option value="'+l.lote_id+'">Lote '+l.numero_lote+' · vence '+venc+' · stock '+l.stock+'</option>';
        });
        document.getElementById('boxLoteDist').classList.remove('hidden');
        input.value=''; resultados.classList.add('hidden');
    });
    document.addEventListener('click', function(e){ if(!input.contains(e.target)&&!resultados.contains(e.target)) resultados.classList.add('hidden'); });
})();
function limpiarProductoDist(){
    document.getElementById('idMaestroDist').value='';
    document.getElementById('productoDistSeleccionado').classList.add('hidden');
    document.getElementById('boxLoteDist').classList.add('hidden');
    document.getElementById('buscarProductoDist').value='';
}
</script>
