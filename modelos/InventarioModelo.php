<?php
class InventarioModelo {

    // Expresion CASE de stock por sede (sobre alias de movimientos = m)
    private static function caseStock() {
        return "CASE
            WHEN m.tipo_movimiento = 'ENTRADA_BODEGA' AND :sede_id = 1 THEN m.cantidad
            WHEN m.tipo_movimiento = 'SALIDA_A_SEDE' AND :sede_id = m.sede_id THEN m.cantidad
            WHEN m.tipo_movimiento = 'SALIDA_A_SEDE' AND :sede_id = 1 THEN -m.cantidad
            WHEN m.tipo_movimiento = 'ENTREGA_PACIENTE' AND m.sede_id = :sede_id THEN -m.cantidad
            WHEN m.tipo_movimiento = 'BAJA_VENCIDO' AND m.sede_id = :sede_id THEN -m.cantidad
            ELSE 0 END";
    }

    // Stock de un MAESTRO (suma de sus lotes) en una sede
    public static function mdlCalcularStock($sede_id, $maestro_id) {
        $sql = "SELECT COALESCE(SUM(" . self::caseStock() . "),0) stock
                FROM movimientos_inventario m JOIN lotes l ON l.id = m.lote_id
                WHERE l.producto_id = :maestro_id";
        $stmt = Conexion::conectar()->prepare($sql);
        $stmt->bindParam(":sede_id", $sede_id, PDO::PARAM_INT);
        $stmt->bindParam(":maestro_id", $maestro_id, PDO::PARAM_INT);
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    }

    // Map maestro_id => stock en una sede
    public static function mdlCalcularStockTodos($sede_id) {
        $sql = "SELECT l.producto_id maestro_id, COALESCE(SUM(" . self::caseStock() . "),0) stock
                FROM movimientos_inventario m JOIN lotes l ON l.id = m.lote_id
                GROUP BY l.producto_id";
        $stmt = Conexion::conectar()->prepare($sql);
        $stmt->bindParam(":sede_id", $sede_id, PDO::PARAM_INT);
        $stmt->execute();
        $map = array();
        foreach($stmt->fetchAll() as $r) $map[$r["maestro_id"]] = (int)$r["stock"];
        return $map;
    }

    // Map lote_id => stock en una sede
    public static function mdlCalcularStockLotes($sede_id) {
        $sql = "SELECT m.lote_id, COALESCE(SUM(" . self::caseStock() . "),0) stock
                FROM movimientos_inventario m
                GROUP BY m.lote_id";
        $stmt = Conexion::conectar()->prepare($sql);
        $stmt->bindParam(":sede_id", $sede_id, PDO::PARAM_INT);
        $stmt->execute();
        $map = array();
        foreach($stmt->fetchAll() as $r) $map[$r["lote_id"]] = (int)$r["stock"];
        return $map;
    }

    // Lotes de un maestro con stock por lote en una sede (para vista de detalle)
    public static function mdlLotesConStock($maestro_id, $sede_id) {
        $sql = "SELECT l.id, l.numero_lote, l.fecha_vencimiento, l.codigo_origen,
                    COALESCE(SUM(" . self::caseStock() . "),0) stock
                FROM lotes l LEFT JOIN movimientos_inventario m ON m.lote_id = l.id
                WHERE l.producto_id = :maestro_id
                GROUP BY l.id
                ORDER BY (l.fecha_vencimiento IS NULL), l.fecha_vencimiento ASC, l.id ASC";
        $stmt = Conexion::conectar()->prepare($sql);
        $stmt->bindParam(":sede_id", $sede_id, PDO::PARAM_INT);
        $stmt->bindParam(":maestro_id", $maestro_id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    // Map maestro_id => stock fisico total en TODO el sistema (suma de sedes; transferencias netas = 0)
    public static function mdlStockSistemaTodos() {
        $sql = "SELECT l.producto_id maestro_id,
                  COALESCE(SUM(CASE WHEN m.tipo_movimiento='ENTRADA_BODEGA' THEN m.cantidad
                                    WHEN m.tipo_movimiento IN('ENTREGA_PACIENTE','BAJA_VENCIDO') THEN -m.cantidad
                                    ELSE 0 END),0) stock
                FROM lotes l LEFT JOIN movimientos_inventario m ON m.lote_id = l.id
                GROUP BY l.producto_id";
        $map = array();
        foreach(Conexion::conectar()->query($sql) as $r) $map[$r["maestro_id"]] = (int)$r["stock"];
        return $map;
    }

    // Lotes con stock>0 en una sede (para selects de override FEFO)
    public static function mdlLotesParaSelect($sede_id) {
        $sql = "SELECT l.id lote_id, l.producto_id, l.numero_lote, l.fecha_vencimiento,
                  COALESCE(SUM(" . self::caseStock() . "),0) stock
                FROM lotes l LEFT JOIN movimientos_inventario m ON m.lote_id = l.id
                GROUP BY l.id HAVING stock > 0
                ORDER BY (l.fecha_vencimiento IS NULL), l.fecha_vencimiento ASC, l.id ASC";
        $stmt = Conexion::conectar()->prepare($sql);
        $stmt->bindParam(":sede_id", $sede_id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    // ---- Movimientos ----
    public static function mdlRegistrarMovimiento($datos) {
        $stmt = Conexion::conectar()->prepare("INSERT INTO movimientos_inventario(sede_id, lote_id, tipo_movimiento, cantidad, usuario_id, observacion) VALUES (:sede_id, :lote_id, :tipo_movimiento, :cantidad, :usuario_id, :observacion)");
        $stmt->bindParam(":sede_id", $datos["sede_id"], PDO::PARAM_INT);
        $stmt->bindParam(":lote_id", $datos["lote_id"], PDO::PARAM_INT);
        $stmt->bindParam(":tipo_movimiento", $datos["tipo_movimiento"], PDO::PARAM_STR);
        $stmt->bindParam(":cantidad", $datos["cantidad"], PDO::PARAM_INT);
        $stmt->bindParam(":usuario_id", $datos["usuario_id"], PDO::PARAM_INT);
        $stmt->bindParam(":observacion", $datos["observacion"], PDO::PARAM_STR);
        return $stmt->execute() ? Conexion::conectar()->lastInsertId() : false;
    }

    public static function mdlRegistrarMovimientoWithConn($conn, $datos) {
        $stmt = $conn->prepare("INSERT INTO movimientos_inventario(sede_id, lote_id, tipo_movimiento, cantidad, usuario_id, observacion) VALUES (:sede_id, :lote_id, :tipo_movimiento, :cantidad, :usuario_id, :observacion)");
        $stmt->bindParam(":sede_id", $datos["sede_id"], PDO::PARAM_INT);
        $stmt->bindParam(":lote_id", $datos["lote_id"], PDO::PARAM_INT);
        $stmt->bindParam(":tipo_movimiento", $datos["tipo_movimiento"], PDO::PARAM_STR);
        $stmt->bindParam(":cantidad", $datos["cantidad"], PDO::PARAM_INT);
        $stmt->bindParam(":usuario_id", $datos["usuario_id"], PDO::PARAM_INT);
        $stmt->bindParam(":observacion", $datos["observacion"], PDO::PARAM_STR);
        return $stmt->execute() ? $conn->lastInsertId() : false;
    }

    // Busca un lote por (maestro, numero) o lo crea. Devuelve lote_id.
    public static function mdlFindOrCreateLote($conn, $maestro_id, $numero_lote, $fecha_venc) {
        $numero_lote = trim($numero_lote) !== '' ? trim($numero_lote) : 'S/L';
        $sel = $conn->prepare("SELECT id, fecha_vencimiento FROM lotes WHERE producto_id = ? AND numero_lote = ?");
        $sel->execute([$maestro_id, $numero_lote]);
        $row = $sel->fetch();
        if($row) {
            if($fecha_venc && empty($row['fecha_vencimiento'])) {
                $conn->prepare("UPDATE lotes SET fecha_vencimiento = ? WHERE id = ?")->execute([$fecha_venc, $row['id']]);
            }
            return $row['id'];
        }
        $ins = $conn->prepare("INSERT INTO lotes(producto_id, numero_lote, fecha_vencimiento) VALUES(?,?,?)");
        $ins->execute([$maestro_id, $numero_lote, $fecha_venc ?: null]);
        return $conn->lastInsertId();
    }

    /**
     * Consume FEFO: descuenta $cantidad de un maestro generando un movimiento por lote
     * (del que vence primero al ultimo). Devuelve ['ok'=>bool, 'disponible'=>int, 'movs'=>[]].
     * - SALIDA_A_SEDE: sede_mov = destino, sede_stock = 1 (bodega)
     * - ENTREGA_PACIENTE: sede_mov = sede_stock = sede actual
     * Si $lote_forzado, solo usa ese lote (override manual).
     */
    public static function mdlConsumirFEFO($conn, $maestro_id, $cantidad, $tipo, $sede_mov, $sede_stock, $usuario_id, $obs, $lote_forzado = null) {
        // bloquea las filas de lotes del maestro para evitar doble gasto
        $lk = $conn->prepare("SELECT id FROM lotes WHERE producto_id = ? FOR UPDATE");
        $lk->execute([$maestro_id]);

        $sql = "SELECT l.id lote_id,
                    COALESCE(SUM(" . self::caseStock() . "),0) stock
                FROM lotes l LEFT JOIN movimientos_inventario m ON m.lote_id = l.id
                WHERE l.producto_id = :maestro_id"
                . ($lote_forzado ? " AND l.id = :lf" : "") .
                " GROUP BY l.id HAVING stock > 0
                ORDER BY (l.fecha_vencimiento IS NULL), l.fecha_vencimiento ASC, l.id ASC";
        $st = $conn->prepare($sql);
        $st->bindValue(":sede_id", $sede_stock, PDO::PARAM_INT);
        $st->bindValue(":maestro_id", $maestro_id, PDO::PARAM_INT);
        if($lote_forzado) $st->bindValue(":lf", $lote_forzado, PDO::PARAM_INT);
        $st->execute();
        $lotes = $st->fetchAll();

        $disponible = 0;
        foreach($lotes as $L) $disponible += (int)$L['stock'];
        if($disponible < $cantidad) return ['ok' => false, 'disponible' => $disponible];

        $rest = $cantidad; $movs = [];
        $ins = $conn->prepare("INSERT INTO movimientos_inventario(sede_id, lote_id, tipo_movimiento, cantidad, usuario_id, observacion) VALUES(?,?,?,?,?,?)");
        foreach($lotes as $L) {
            if($rest <= 0) break;
            $take = min($rest, (int)$L['stock']);
            $ins->execute([$sede_mov, $L['lote_id'], $tipo, $take, $usuario_id, $obs]);
            $movs[] = ['mov_id' => $conn->lastInsertId(), 'lote_id' => $L['lote_id'], 'cantidad' => $take];
            $rest -= $take;
        }
        return ['ok' => true, 'movs' => $movs];
    }

    // ---- Baja por vencimiento (por lote) ----
    public static function mdlDarBajaVencidos($usuario_id) {
        $db = Conexion::conectar();
        $lotesVenc = $db->query("SELECT id FROM lotes WHERE fecha_vencimiento IS NOT NULL AND fecha_vencimiento < CURDATE()")->fetchAll(PDO::FETCH_COLUMN);
        if(empty($lotesVenc)) return 0;
        $set = array_flip($lotesVenc);
        $sedes = $db->query("SELECT id FROM sedes")->fetchAll(PDO::FETCH_COLUMN);
        $ins = $db->prepare("INSERT INTO movimientos_inventario(sede_id, lote_id, tipo_movimiento, cantidad, usuario_id, observacion) VALUES(?,?,'BAJA_VENCIDO',?,?, 'Baja automatica por vencimiento')");
        $total = 0;
        foreach($sedes as $sid) {
            $map = self::mdlCalcularStockLotes((int)$sid);
            foreach($map as $lid => $stk) {
                if($stk > 0 && isset($set[$lid])) { $ins->execute([(int)$sid, (int)$lid, (int)$stk, $usuario_id]); $total++; }
            }
        }
        if($total > 0) {
            $a = $db->prepare("INSERT INTO auditoria(usuario_id, accion, tabla) VALUES(?,?, 'movimientos_inventario')");
            $a->execute([$usuario_id, "Baja automatica de $total lote(s) vencido(s)"]);
        }
        return $total;
    }

    public static function mdlGetDadosDeBaja($sede_id, $rol) {
        $where = $rol == 'ADMIN' ? "" : "AND m.sede_id = :sede_id";
        $sql = "SELECT m.fecha_hora, m.cantidad, vl.codigo, vl.nombre_descripcion, vl.numero_lote AS lote, vl.fecha_vencimiento, s.nombre AS sede
                FROM movimientos_inventario m
                JOIN v_lotes vl ON vl.lote_id = m.lote_id
                JOIN sedes s ON s.id = m.sede_id
                WHERE m.tipo_movimiento = 'BAJA_VENCIDO' $where
                ORDER BY m.fecha_hora DESC";
        $stmt = Conexion::conectar()->prepare($sql);
        if($rol != 'ADMIN') $stmt->bindParam(":sede_id", $sede_id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    // Lotes proximos a vencer con stock dentro de N dias.
    // $sede_id null = stock fisico de TODO el sistema (admin); con valor = stock de esa sede.
    public static function mdlGetProximosVencer($dias = 30, $sede_id = null) {
        $stockExpr = $sede_id === null
            ? "SUM(CASE WHEN m.tipo_movimiento='ENTRADA_BODEGA' THEN m.cantidad WHEN m.tipo_movimiento IN('ENTREGA_PACIENTE','BAJA_VENCIDO') THEN -m.cantidad ELSE 0 END)"
            : "SUM(" . self::caseStock() . ")";
        $sql = "SELECT vl.codigo, vl.nombre_descripcion, vl.numero_lote AS lote, vl.fecha_vencimiento,
                    DATEDIFF(vl.fecha_vencimiento, CURDATE()) dias_rest,
                    COALESCE($stockExpr,0) stock_total
                FROM v_lotes vl LEFT JOIN movimientos_inventario m ON m.lote_id = vl.lote_id
                WHERE vl.fecha_vencimiento IS NOT NULL
                  AND vl.fecha_vencimiento BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL :dias DAY)
                GROUP BY vl.lote_id
                HAVING stock_total > 0
                ORDER BY vl.fecha_vencimiento ASC";
        $stmt = Conexion::conectar()->prepare($sql);
        $stmt->bindValue(":dias", (int)$dias, PDO::PARAM_INT);
        if($sede_id !== null) $stmt->bindValue(":sede_id", (int)$sede_id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    // Cuenta productos (maestros) con stock > 0 en una sede, por tipo
    public static function mdlContarConStockPorTipo($sede_id) {
        $sql = "SELECT tipo, COUNT(*) n FROM (
                    SELECT p.id, p.tipo, COALESCE(SUM(" . self::caseStock() . "),0) stk
                    FROM productos p JOIN lotes l ON l.producto_id = p.id
                    LEFT JOIN movimientos_inventario m ON m.lote_id = l.id
                    GROUP BY p.id HAVING stk > 0
                ) x GROUP BY tipo";
        $stmt = Conexion::conectar()->prepare($sql);
        $stmt->bindValue(":sede_id", (int)$sede_id, PDO::PARAM_INT);
        $stmt->execute();
        $r = ['MEDICAMENTO' => 0, 'DISPOSITIVO' => 0];
        foreach($stmt->fetchAll() as $row) $r[$row['tipo']] = (int)$row['n'];
        return $r;
    }

    // Resumen de inventario de TODAS las sedes (para panel admin)
    public static function mdlResumenPorSede() {
        $sedes = Conexion::conectar()->query("SELECT id, nombre FROM sedes ORDER BY id ASC")->fetchAll();
        $res = [];
        foreach($sedes as $s) {
            $map = self::mdlCalcularStockTodos((int)$s['id']);
            $total = 0; $nprod = 0; $crit = 0;
            foreach($map as $stk) {
                if($stk > 0) { $total += $stk; $nprod++; if($stk <= 10) $crit++; }
            }
            $prox = count(self::mdlGetProximosVencer(30, (int)$s['id']));
            $res[] = [
                "id" => $s['id'], "nombre" => $s['nombre'],
                "productos" => $nprod, "unidades" => $total,
                "criticos" => $crit, "por_vencer" => $prox
            ];
        }
        return $res;
    }

    // Inventario actual (productos con stock > 0) de una sede
    public static function mdlInventarioSede($sede_id) {
        $sql = "SELECT p.id, p.codigo, p.nombre, p.tipo, COALESCE(SUM(" . self::caseStock() . "),0) stock
                FROM productos p
                JOIN lotes l ON l.producto_id = p.id
                LEFT JOIN movimientos_inventario m ON m.lote_id = l.id
                GROUP BY p.id
                HAVING stock > 0
                ORDER BY p.nombre ASC";
        $stmt = Conexion::conectar()->prepare($sql);
        $stmt->bindValue(":sede_id", (int)$sede_id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    private static function whereKardex($rol, $q, $tipo) {
        $cond = [];
        if($rol != 'ADMIN') $cond[] = "m.sede_id = :sede_id";
        if($q !== '') $cond[] = "(vl.nombre_descripcion LIKE :q OR vl.numero_lote LIKE :q OR u.nombre LIKE :q OR s.nombre LIKE :q)";
        if($tipo !== '') $cond[] = "m.tipo_movimiento = :tipo";
        return $cond ? (" WHERE " . implode(" AND ", $cond)) : "";
    }

    private static function bindKardex($stmt, $sede_id, $rol, $q, $tipo) {
        if($rol != 'ADMIN') $stmt->bindValue(":sede_id", (int)$sede_id, PDO::PARAM_INT);
        if($q !== '') $stmt->bindValue(":q", "%$q%", PDO::PARAM_STR);
        if($tipo !== '') $stmt->bindValue(":tipo", $tipo, PDO::PARAM_STR);
    }

    public static function mdlContarKardex($sede_id, $rol, $q = '', $tipo = '') {
        $sql = "SELECT COUNT(*)
                FROM movimientos_inventario m
                JOIN v_lotes vl ON vl.lote_id = m.lote_id
                JOIN sedes s ON m.sede_id = s.id
                JOIN usuarios u ON m.usuario_id = u.id"
                . self::whereKardex($rol, $q, $tipo);
        $stmt = Conexion::conectar()->prepare($sql);
        self::bindKardex($stmt, $sede_id, $rol, $q, $tipo);
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    }

    // $limit = null devuelve todos los registros (usado por la exportacion CSV).
    public static function mdlMostrarKardex($sede_id, $rol, $q = '', $tipo = '', $limit = null, $offset = 0) {
        $sql = "SELECT m.*, vl.nombre_descripcion AS producto, vl.numero_lote AS lote, s.nombre AS sede, u.nombre AS usuario
                FROM movimientos_inventario m
                JOIN v_lotes vl ON vl.lote_id = m.lote_id
                JOIN sedes s ON m.sede_id = s.id
                JOIN usuarios u ON m.usuario_id = u.id"
                . self::whereKardex($rol, $q, $tipo)
                . " ORDER BY m.fecha_hora DESC";
        if($limit !== null) $sql .= " LIMIT :limit OFFSET :offset";
        $stmt = Conexion::conectar()->prepare($sql);
        self::bindKardex($stmt, $sede_id, $rol, $q, $tipo);
        if($limit !== null) {
            $stmt->bindValue(":limit", (int)$limit, PDO::PARAM_INT);
            $stmt->bindValue(":offset", (int)$offset, PDO::PARAM_INT);
        }
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
