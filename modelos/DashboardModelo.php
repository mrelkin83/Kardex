<?php
class DashboardModelo {
    public static function mdlGetStats($sede_id, $rol) {
        $db = Conexion::conectar();
        $stats = [];

        if ($rol == 'ADMIN') {
            // Admin gestiona el catálogo completo
            $stats['total_med'] = $db->query("SELECT COUNT(*) FROM productos WHERE tipo = 'MEDICAMENTO'")->fetchColumn();
            $stats['total_disp'] = $db->query("SELECT COUNT(*) FROM productos WHERE tipo = 'DISPOSITIVO'")->fetchColumn();
        } else {
            // Usuario de sede: solo lo que realmente tiene existencias en su sede
            $c = InventarioModelo::mdlContarConStockPorTipo($sede_id);
            $stats['total_med'] = $c['MEDICAMENTO'];
            $stats['total_disp'] = $c['DISPOSITIVO'];
        }

        if ($rol == 'ADMIN') {
            $stmt = $db->prepare("SELECT COALESCE(SUM(cantidad), 0) FROM movimientos_inventario WHERE tipo_movimiento = 'ENTREGA_PACIENTE' AND DATE(fecha_hora) = CURDATE()");
            $stmt->execute();
        } else {
            $stmt = $db->prepare("SELECT COALESCE(SUM(cantidad), 0) FROM movimientos_inventario WHERE tipo_movimiento = 'ENTREGA_PACIENTE' AND DATE(fecha_hora) = CURDATE() AND sede_id = :sede_id");
            $stmt->bindParam(":sede_id", $sede_id, PDO::PARAM_INT);
            $stmt->execute();
        }
        $stats['entregas_hoy'] = $stmt->fetchColumn();

        if ($rol == 'ADMIN') {
            $stmt = $db->prepare("SELECT a.*, u.nombre FROM auditoria a JOIN usuarios u ON a.usuario_id = u.id ORDER BY a.fecha_hora DESC LIMIT 5");
            $stmt->execute();
        } else {
            $stmt = $db->prepare("SELECT a.*, u.nombre FROM auditoria a JOIN usuarios u ON a.usuario_id = u.id WHERE a.usuario_id = :uid ORDER BY a.fecha_hora DESC LIMIT 5");
            $stmt->bindParam(":uid", $_SESSION['id'], PDO::PARAM_INT);
            $stmt->execute();
        }
        $stats['actividad'] = $stmt->fetchAll();

        // Productos dados de baja por vencimiento
        if ($rol == 'ADMIN') {
            $stmt = $db->prepare("SELECT COUNT(*) lotes, COALESCE(SUM(cantidad),0) unidades FROM movimientos_inventario WHERE tipo_movimiento = 'BAJA_VENCIDO'");
            $stmt->execute();
        } else {
            $stmt = $db->prepare("SELECT COUNT(*) lotes, COALESCE(SUM(cantidad),0) unidades FROM movimientos_inventario WHERE tipo_movimiento = 'BAJA_VENCIDO' AND sede_id = :sede_id");
            $stmt->bindParam(":sede_id", $sede_id, PDO::PARAM_INT);
            $stmt->execute();
        }
        $baja = $stmt->fetch();
        $stats['baja_lotes'] = $baja['lotes'];
        $stats['baja_unidades'] = $baja['unidades'];

        $sedeProx = $rol === 'ADMIN' ? null : $sede_id;
        $stats['prox_vencer'] = count(InventarioModelo::mdlGetProximosVencer(30, $sedeProx));

        return $stats;
    }

    public static function mdlGetStockBajo($sede_id, $rol) {
        // Una sola consulta para el stock de todos los maestros (sin N+1)
        $stockMap = InventarioModelo::mdlCalcularStockTodos($sede_id);
        $productos = Conexion::conectar()->query("SELECT id AS producto_id, nombre AS nombre_descripcion FROM productos WHERE estado = 1")->fetchAll();
        $criticos = [];
        foreach($productos as $p) {
            $stock = $stockMap[$p['producto_id']] ?? 0;
            if($stock > 0 && $stock <= 10) { // Stock bajo real (presente pero por agotarse)
                $criticos[] = [
                    "nombre" => $p['nombre_descripcion'],
                    "lote" => '',
                    "stock" => $stock
                ];
            }
        }
        return $criticos;
    }
}
