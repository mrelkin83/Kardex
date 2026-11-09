<?php Security::checkRole(['ADMIN']); ?>
<div class="max-w-3xl mx-auto animate-fade-in-up">
    <div class="mb-6">
        <h2 class="page-title"><i class="fas fa-box-open mr-2 text-instRojo"></i>Ingreso a Bodega Central</h2>
        <p class="page-subtitle">Registre el ingreso de un lote de producto al inventario central</p>
    </div>
    <div class="card">
        <div class="card-header bg-instRojo">
            <div class="flex items-center">
                <div class="w-10 h-10 rounded-lg bg-white/10 flex items-center justify-center mr-3">
                    <i class="fas fa-warehouse text-instAmarillo"></i>
                </div>
                <div>
                    <h3 class="text-white font-bold text-sm">Formulario de Entrada</h3>
                    <p class="text-white/60 text-xs">Seleccione el producto e indique el lote</p>
                </div>
            </div>
        </div>
        <div class="card-body">
            <form id="formBodega" onsubmit="event.preventDefault(); submitFormAjax('formBodega', 'entradaBodega', 'ajax/inventario.ajax.php');">
                <div class="space-y-5">
                    <div>
                        <label class="form-label">Producto</label>
                        <div class="relative">
                            <input type="text" id="buscarProductoBodega" class="form-input pr-8" placeholder="Buscar por nombre o codigo..." autocomplete="off">
                            <i class="fas fa-search absolute right-3 top-1/2 -translate-y-1/2 text-gray-300 text-sm"></i>
                        </div>
                        <div id="resultadosProductoBodega" class="hidden mt-1 border border-gray-200 rounded-lg bg-white shadow-lg max-h-56 overflow-y-auto z-50"></div>
                        <input type="hidden" name="idMaestroBodega" id="idMaestroBodega" required>
                        <div id="productoBodegaSeleccionado" class="hidden mt-2 p-3 bg-green-50 border border-green-200 rounded-lg">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="font-semibold text-sm text-green-800" id="nombreProdBodega"></p>
                                    <p class="text-xs text-green-600" id="detalleProdBodega"></p>
                                </div>
                                <button type="button" onclick="limpiarProductoBodega()" class="text-green-400 hover:text-red-500 transition-colors"><i class="fas fa-times-circle"></i></button>
                            </div>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div>
                            <label class="form-label">Número de Lote</label>
                            <input type="text" name="numeroLoteBodega" required class="form-input" placeholder="Ej: L2024-001">
                        </div>
                        <div>
                            <label class="form-label">Fecha de Vencimiento</label>
                            <input type="date" name="fechaVencBodega" class="form-input">
                        </div>
                    </div>
                    <div>
                        <label class="form-label">Cantidad que Ingresa</label>
                        <input type="number" name="cantidadBodega" required min="1" class="form-input" placeholder="Ej: 100">
                    </div>
                    <div>
                        <label class="form-label">Observaciones / Factura / Proveedor</label>
                        <textarea name="obsBodega" rows="2" class="form-input" placeholder="Numero de factura o proveedor..."></textarea>
                    </div>
                </div>
                <div class="mt-6 pt-5 border-t border-gray-100 flex justify-end">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-check"></i> Registrar Ingreso</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
(function(){
    var productos = [
        <?php foreach(ProductosControlador::ctrMostrarProductos() as $p): ?>
        {id:<?php echo $p['producto_id']; ?>, nombre:"<?php echo e($p['nombre_descripcion']); ?>", codigo:"<?php echo e($p['codigo']); ?>", tipo:"<?php echo e($p['tipo']); ?>", extra:"<?php echo e(trim(($p['concentracion'] ?? '').' '.($p['presentacion'] ?? ''))); ?>"},
        <?php endforeach; ?>
    ];
    var input = document.getElementById('buscarProductoBodega');
    var resultados = document.getElementById('resultadosProductoBodega');
    input.addEventListener('input', function(){
        var q = this.value.toLowerCase().trim();
        if(q.length < 2){ resultados.classList.add('hidden'); return; }
        var f = productos.filter(function(p){ return p.nombre.toLowerCase().indexOf(q)!==-1 || p.codigo.toLowerCase().indexOf(q)!==-1; }).slice(0,50);
        if(f.length===0){ resultados.innerHTML = '<div class="px-4 py-3 text-sm text-gray-400"><i class="fas fa-inbox mr-1"></i>Sin resultados</div>'; }
        else { resultados.innerHTML = f.map(function(p){
            var icon = p.tipo==='MEDICAMENTO' ? 'fa-pills text-blue-400' : 'fa-stethoscope text-purple-400';
            return '<div class="px-4 py-3 hover:bg-instRojo/5 cursor-pointer border-b border-gray-50 last:border-0" data-id="'+p.id+'" data-nombre="'+p.nombre+'" data-detalle="'+p.codigo+' | '+p.extra+'">' +
                '<p class="font-medium text-sm text-gray-800"><i class="fas '+icon+' mr-2 text-xs"></i>'+p.nombre+'</p>' +
                '<p class="text-xs text-gray-400">'+p.codigo+' &bull; '+p.extra+'</p></div>';
        }).join(''); }
        resultados.classList.remove('hidden');
    });
    resultados.addEventListener('click', function(e){
        var item = e.target.closest('[data-id]'); if(!item) return;
        document.getElementById('idMaestroBodega').value = item.dataset.id;
        document.getElementById('nombreProdBodega').textContent = item.dataset.nombre;
        document.getElementById('detalleProdBodega').textContent = item.dataset.detalle;
        document.getElementById('productoBodegaSeleccionado').classList.remove('hidden');
        input.value = ''; resultados.classList.add('hidden');
    });
    document.addEventListener('click', function(e){ if(!input.contains(e.target) && !resultados.contains(e.target)) resultados.classList.add('hidden'); });
})();
function limpiarProductoBodega(){
    document.getElementById('idMaestroBodega').value = '';
    document.getElementById('productoBodegaSeleccionado').classList.add('hidden');
    document.getElementById('buscarProductoBodega').value = '';
}
</script>
