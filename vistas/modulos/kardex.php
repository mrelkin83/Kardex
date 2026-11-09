<div class="animate-fade-in-up">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="page-title">Kardex General</h1>
            <p class="page-subtitle">Historial de movimientos de inventario</p>
        </div>
    </div>

    <?php
    $q = trim($_GET['q'] ?? '');
    $tipo = trim($_GET['tipo'] ?? '');
    $tiposValidos = ['ENTRADA_BODEGA', 'SALIDA_A_SEDE', 'ENTREGA_PACIENTE', 'BAJA_VENCIDO'];
    if($tipo !== '' && !in_array($tipo, $tiposValidos)) $tipo = '';
    $porPagina = 25;
    $pag = max(1, (int)($_GET['pag'] ?? 1));
    $total = InventarioControlador::ctrContarKardex($q, $tipo);
    $totalPags = max(1, (int)ceil($total / $porPagina));
    if($pag > $totalPags) $pag = $totalPags;
    $offset = ($pag - 1) * $porPagina;
    $kardex = InventarioControlador::ctrMostrarKardex($q, $tipo, $porPagina, $offset);
    $extra = $tipo !== '' ? '&tipo=' . urlencode($tipo) : '';
    ?>

    <div class="card">
        <div class="card-header">
            <form method="get" action="kardex" class="flex items-center gap-3 flex-wrap">
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" name="q" value="<?php echo e($q); ?>" placeholder="Buscar producto, lote, usuario, sede..." autocomplete="off">
                </div>
                <select name="tipo" onchange="this.form.submit()" class="form-input" style="width:auto">
                    <option value="">Todos los movimientos</option>
                    <?php foreach($tiposValidos as $t): ?>
                    <option value="<?php echo $t; ?>" <?php echo $tipo === $t ? 'selected' : ''; ?>><?php echo e(str_replace('_', ' ', $t)); ?></option>
                    <?php endforeach; ?>
                </select>
            </form>
            <span class="text-xs text-gray-400 font-medium">
                <?php echo $total; ?> movimiento(s)<?php if($q !== '' || $tipo !== ''): ?> &middot; <a href="kardex" class="text-instRojo hover:underline">limpiar</a><?php endif; ?>
            </span>
        </div>
        <div class="overflow-x-auto">
            <table class="table-modern" id="tableKardex">
                <thead>
                    <tr>
                        <th>Fecha/Hora</th>
                        <th>Movimiento</th>
                        <th>Producto</th>
                        <th>Lote</th>
                        <th>Cant.</th>
                        <th>Sede</th>
                        <th>Usuario</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    foreach($kardex as $k){
                        $badgeClass = 'badge-gris';
                        $signo = '';
                        if($k["tipo_movimiento"] == 'ENTRADA_BODEGA') { $badgeClass = 'badge-verde'; $signo='+'; }
                        if($k["tipo_movimiento"] == 'SALIDA_A_SEDE') { $badgeClass = 'badge-rojo'; $signo='-'; }
                        if($k["tipo_movimiento"] == 'ENTREGA_PACIENTE') { $badgeClass = 'badge-gris'; $signo='-'; }
                        if($k["tipo_movimiento"] == 'BAJA_VENCIDO') { $badgeClass = 'badge-rojo'; $signo='-'; }

                        echo "<tr>";
                        echo "<td class='whitespace-nowrap font-mono text-xs text-gray-500'>".e($k["fecha_hora"])."</td>";
                        echo "<td><span class='badge {$badgeClass}";
                        if($k["tipo_movimiento"] == 'ENTREGA_PACIENTE') echo " text-red-600";
                        echo "'>".e(str_replace('_', ' ', $k["tipo_movimiento"]))."</span></td>";
                        echo "<td class='font-semibold text-gray-900'>".e($k["producto"])."</td>";
                        echo "<td class='text-gray-600'>".e($k["lote"])."</td>";
                        echo "<td class='font-bold ";
                        if($signo == '+') echo 'text-green-600';
                        else echo 'text-red-600';
                        echo "'>".$signo.e($k["cantidad"])."</td>";
                        echo "<td>".e($k["sede"])."</td>";
                        echo "<td class='text-xs text-gray-500'>".e($k["usuario"])."</td>";
                        echo "</tr>";
                    }
                    ?>
                </tbody>
            </table>
            <?php if(empty($kardex)): ?>
            <div class="empty-state">
                <i class="fas fa-box-open"></i>
                <p>No se encontraron movimientos<?php echo ($q !== '' || $tipo !== '') ? ' con los filtros aplicados' : ''; ?></p>
            </div>
            <?php endif; ?>
        </div>
        <?php echo paginador('kardex', $q, $pag, $totalPags, $extra); ?>
    </div>
</div>
