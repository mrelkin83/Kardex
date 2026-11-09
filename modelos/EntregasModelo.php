<?php
class EntregasModelo {
    public static function mdlRegistrarEntrega($datos) {
        $stmt = Conexion::conectar()->prepare("INSERT INTO entregas_pacientes(movimiento_id, servicio, profesional, nombre_paciente, documento) VALUES (:movimiento_id, :servicio, :profesional, :nombre_paciente, :documento)");
        $stmt->bindParam(":movimiento_id", $datos["movimiento_id"], PDO::PARAM_INT);
        $stmt->bindParam(":servicio", $datos["servicio"], PDO::PARAM_STR);
        $stmt->bindParam(":profesional", $datos["profesional"], PDO::PARAM_STR);
        $stmt->bindParam(":nombre_paciente", $datos["nombre_paciente"], PDO::PARAM_STR);
        $stmt->bindParam(":documento", $datos["documento"], PDO::PARAM_STR);
        if($stmt->execute()){ return "ok"; }else{ return "error"; }
    }
    public static function mdlRegistrarEntregaWithConn($conn, $datos) {
        $stmt = $conn->prepare("INSERT INTO entregas_pacientes(movimiento_id, servicio, profesional, nombre_paciente, documento) VALUES (:movimiento_id, :servicio, :profesional, :nombre_paciente, :documento)");
        $stmt->bindParam(":movimiento_id", $datos["movimiento_id"], PDO::PARAM_INT);
        $stmt->bindParam(":servicio", $datos["servicio"], PDO::PARAM_STR);
        $stmt->bindParam(":profesional", $datos["profesional"], PDO::PARAM_STR);
        $stmt->bindParam(":nombre_paciente", $datos["nombre_paciente"], PDO::PARAM_STR);
        $stmt->bindParam(":documento", $datos["documento"], PDO::PARAM_STR);
        if($stmt->execute()){ return "ok"; }else{ return "error"; }
    }
    public static function mdlMostrarEntregas($sede_id, $rol) {
        $where = $rol == 'ADMIN' ? "" : "WHERE m.sede_id = :sede_id";
        $sql = "SELECT e.*, m.cantidad, m.fecha_hora, vl.nombre_descripcion as producto, vl.numero_lote as lote, s.nombre as sede
                FROM entregas_pacientes e
                JOIN movimientos_inventario m ON e.movimiento_id = m.id
                JOIN v_lotes vl ON m.lote_id = vl.lote_id
                JOIN sedes s ON m.sede_id = s.id
                $where
                ORDER BY m.fecha_hora DESC";
        $stmt = Conexion::conectar()->prepare($sql);
        if($rol != 'ADMIN') {
            $stmt->bindParam(":sede_id", $sede_id, PDO::PARAM_INT);
        }
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
