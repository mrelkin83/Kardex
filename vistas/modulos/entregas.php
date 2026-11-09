<div class="animate-fade-in-up">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="page-title">Entregas a Pacientes</h1>
            <p class="page-subtitle">Registro de entregas de medicamentos e insumos</p>
        </div>
        <button onclick="abrirModalEntrega()" class="btn btn-primary">
            <i class="fas fa-hand-holding-medical"></i> Nueva Entrega
        </button>
    </div>

    <div class="card">
        <div class="card-header">
            <div class="flex items-center gap-3">
                <i class="fas fa-file-medical text-instRojo"></i>
                <span class="font-semibold text-sm text-gray-700">Historial de Entregas</span>
            </div>
            <div class="search-box">
                <i class="fas fa-search"></i>
                <input type="text" id="searchEntregas" onkeyup="filterTable('tableEntregas', event)" placeholder="Buscar por paciente o producto..." class="form-input">
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="table-modern" id="tableEntregas">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Paciente / Doc</th>
                        <th>Producto</th>
                        <th>Cant.</th>
                        <th>Servicio / Prof.</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $entregas = EntregasControlador::ctrMostrarEntregas();
                    foreach($entregas as $en){
                        echo "<tr>";
                        echo "<td class='whitespace-nowrap font-mono text-xs text-gray-500'>".e($en["fecha_hora"])."</td>";
                        echo "<td><p class='font-semibold text-gray-900'>".e($en["nombre_paciente"])."</p><p class='text-xs text-gray-400'>".e($en["documento"])."</p></td>";
                        echo "<td class='font-medium'>".e($en["producto"])."</td>";
                        echo "<td class='font-bold text-red-600'>-".e($en["cantidad"])."</td>";
                        echo "<td>".e($en["servicio"])."<br><span class='text-xs text-gray-400'>".e($en["profesional"])."</span></td>";
                        echo "</tr>";
                    }
                    ?>
                </tbody>
            </table>
            <?php if(empty($entregas)): ?>
            <div class="empty-state">
                <i class="fas fa-hand-holding-medical"></i>
                <p>No hay entregas registradas</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<div id="modalEntrega" class="modal-overlay hidden">
    <div class="modal-content" style="max-width:42rem;">
        <div class="modal-header">
            <h3 class="text-lg font-bold flex items-center gap-2">
                <i class="fas fa-prescription"></i> Registrar Entrega
            </h3>
            <button onclick="document.getElementById('modalEntrega').classList.add('hidden')" class="modal-header-close">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body">
            <form id="formEntrega" onsubmit="event.preventDefault(); submitFormAjax('formEntrega', 'nuevaEntrega', 'ajax/entregas.ajax.php');">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div>
                        <label class="form-label">Servicio</label>
                        <input type="text" name="servicioEntrega" id="servicioEntrega" required class="form-input bg-gray-50 cursor-not-allowed" readonly>
                    </div>
                    <div>
                        <label class="form-label">Profesional a cargo</label>
                        <input type="text" name="profesionalEntrega" id="profesionalEntrega" required class="form-input bg-gray-50 cursor-not-allowed" readonly>
                    </div>
                    <input type="hidden" name="idUsuarioEntrega" id="idUsuarioEntrega">
                    <div class="border-t col-span-2 my-2"></div>
                    <div>
                        <label class="form-label">Documento Paciente</label>
                        <input type="text" name="docPacienteEntrega" required class="form-input" placeholder="Numero de documento">
                    </div>
                    <div>
                        <label class="form-label">Nombre Paciente</label>
                        <input type="text" name="pacienteEntrega" required class="form-input" placeholder="Nombre completo">
                    </div>
                    <div class="border-t col-span-2 my-2"></div>
                    <div class="md:col-span-2">
                        <label class="form-label">Buscar Producto (medicamento o dispositivo)</label>
                        <div class="relative">
                            <input type="text" id="buscarProductoEntrega" class="form-input pr-8" placeholder="Escriba nombre, codigo o lote..." autocomplete="off">
                            <i class="fas fa-search absolute right-3 top-1/2 -translate-y-1/2 text-gray-300 text-sm"></i>
                        </div>
                        <div id="resultadosProducto" class="hidden mt-1 border border-gray-200 rounded-lg bg-white shadow-lg max-h-56 overflow-y-auto z-50"></div>
                        <input type="hidden" name="idProductoEntrega" id="idProductoEntrega" required>
                        <div id="productoSeleccionado" class="hidden mt-2 p-3 bg-green-50 border border-green-200 rounded-lg">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="font-semibold text-sm text-green-800" id="nombreProdSelec"></p>
                                    <p class="text-xs text-green-600" id="detalleProdSelec"></p>
                                </div>
                                <button type="button" onclick="limpiarProductoEntrega()" class="text-green-400 hover:text-red-500 transition-colors"><i class="fas fa-times-circle"></i></button>
                            </div>
                        </div>
                    </div>
                    <div id="boxLoteEntrega" class="hidden md:col-span-2">
                        <label class="form-label">Lote <span class="text-gray-400 normal-case font-normal">(automático FEFO por defecto)</span></label>
                        <select name="loteForzadoEntrega" id="loteForzadoEntrega" class="form-input">
                            <option value="">Automático (vence primero)</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label">Cantidad</label>
                        <input type="number" name="cantidadEntrega" required min="1" class="form-input" placeholder="1">
                    </div>
                    <div>
                        <label class="form-label">Observacion / Diagnostico</label>
                        <input type="text" name="observacionEntrega" class="form-input" placeholder="Opcional">
                    </div>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" onclick="document.getElementById('modalEntrega').classList.add('hidden')" class="btn btn-ghost">Cancelar</button>
            <button type="submit" form="formEntrega" class="btn btn-primary">
                <i class="fas fa-save"></i> Confirmar Entrega
            </button>
        </div>
    </div>
</div>

<script>
<?php
// Servicio segun el rol del usuario (con fallback para sesiones previas a roles dinamicos)
$servicioSesion = $_SESSION['servicio'] ?? '';
if($servicioSesion === '') {
    $mapServ = ['ENFERMERA'=>'ENFERMERIA','ODONTOLOGO'=>'ODONTOLOGIA','FISIOTERAPEUTA'=>'FISIOTERAPIA'];
    $servicioSesion = $mapServ[$_SESSION['rol'] ?? ''] ?? 'GENERAL';
}
?>
function abrirModalEntrega(){
    var nombre = <?php echo json_encode($_SESSION['nombre']); ?>;
    var id = <?php echo json_encode($_SESSION['id']); ?>;
    document.getElementById('servicioEntrega').value = <?php echo json_encode($servicioSesion); ?>;
    document.getElementById('profesionalEntrega').value = nombre;
    document.getElementById('idUsuarioEntrega').value = id;
    document.getElementById('modalEntrega').classList.remove('hidden');
}

(function(){
    <?php
    $sedeE = $_SESSION["sede_id"];
    $stockMapE = InventarioControlador::ctrCalcularStockTodos($sedeE);
    $lotesSelE = InventarioControlador::ctrLotesParaSelect($sedeE);
    $lotesPorMaestroE = [];
    foreach($lotesSelE as $l){ $lotesPorMaestroE[$l['producto_id']][] = $l; }
    ?>
    var productos = [
        <?php foreach(ProductosControlador::ctrMostrarProductos() as $p):
            $stock = $stockMapE[$p['producto_id']] ?? 0;
            if($stock <= 0) continue; ?>
        {id:<?php echo $p['producto_id']; ?>, nombre:"<?php echo e($p['nombre_descripcion']); ?>", codigo:"<?php echo e($p['codigo']); ?>", stock:<?php echo $stock; ?>},
        <?php endforeach; ?>
    ];
    var lotesPorMaestro = <?php echo json_encode($lotesPorMaestroE, JSON_UNESCAPED_UNICODE); ?>;
    var input = document.getElementById('buscarProductoEntrega');
    var resultados = document.getElementById('resultadosProducto');

    input.addEventListener('input', function(){
        var q = this.value.toLowerCase().trim();
        if(q.length < 2){ resultados.classList.add('hidden'); return; }
        var filtrados = productos.filter(function(p){
            return p.nombre.toLowerCase().indexOf(q) !== -1 || p.codigo.toLowerCase().indexOf(q) !== -1;
        }).slice(0,50);
        if(filtrados.length === 0){
            resultados.innerHTML = '<div class="px-4 py-3 text-sm text-gray-400"><i class="fas fa-inbox mr-1"></i>Sin resultados</div>';
        } else {
            resultados.innerHTML = filtrados.map(function(p){
                return '<div class="px-4 py-3 hover:bg-instRojo/5 cursor-pointer border-b border-gray-50 last:border-0 transition-colors" data-id="'+p.id+'" data-nombre="'+p.nombre+'" data-detalle="'+p.codigo+' | Stock: '+p.stock+'">' +
                '<p class="font-medium text-sm text-gray-800">'+p.nombre+'</p>' +
                '<p class="text-xs text-gray-400">'+p.codigo+' &bull; <span class="text-green-600 font-semibold">Disp: '+p.stock+'</span></p>' +
                '</div>';
            }).join('');
        }
        resultados.classList.remove('hidden');
    });

    resultados.addEventListener('click', function(e){
        var item = e.target.closest('[data-id]');
        if(!item) return;
        var id = item.dataset.id;
        document.getElementById('idProductoEntrega').value = id;
        document.getElementById('nombreProdSelec').textContent = item.dataset.nombre;
        document.getElementById('detalleProdSelec').textContent = item.dataset.detalle;
        document.getElementById('productoSeleccionado').classList.remove('hidden');
        var sel = document.getElementById('loteForzadoEntrega');
        sel.innerHTML = '<option value="">Automático (vence primero)</option>';
        (lotesPorMaestro[id]||[]).forEach(function(l){
            var venc = l.fecha_vencimiento ? l.fecha_vencimiento : 'sin venc.';
            sel.innerHTML += '<option value="'+l.lote_id+'">Lote '+l.numero_lote+' · vence '+venc+' · stock '+l.stock+'</option>';
        });
        document.getElementById('boxLoteEntrega').classList.remove('hidden');
        input.value = '';
        resultados.classList.add('hidden');
    });

    document.addEventListener('click', function(e){
        if(!input.contains(e.target) && !resultados.contains(e.target)) resultados.classList.add('hidden');
    });
})();

function limpiarProductoEntrega(){
    document.getElementById('idProductoEntrega').value = '';
    document.getElementById('productoSeleccionado').classList.add('hidden');
    document.getElementById('boxLoteEntrega').classList.add('hidden');
    document.getElementById('buscarProductoEntrega').value = '';
}
</script>
