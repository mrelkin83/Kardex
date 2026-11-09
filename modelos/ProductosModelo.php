<?php
class ProductosModelo {
    // Maestros (para selects de productos). nombre_descripcion para compatibilidad de vistas.
    public static function mdlMostrarProductos() {
        $stmt = Conexion::conectar()->prepare("SELECT id AS producto_id, codigo, nombre AS nombre_descripcion, tipo, principio_activo, presentacion, concentracion, marca FROM productos WHERE estado = 1 ORDER BY nombre ASC");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    private static function whereTipoBusqueda($tipo, $q) {
        $sql = " WHERE p.tipo = :tipo AND p.estado = 1";
        if($q !== '') {
            $sql .= " AND (p.codigo LIKE :q OR p.nombre LIKE :q OR p.principio_activo LIKE :q OR p.presentacion LIKE :q OR p.marca LIKE :q
                          OR EXISTS (SELECT 1 FROM lotes l WHERE l.producto_id = p.id AND (l.numero_lote LIKE :q OR l.codigo_origen LIKE :q)))";
        }
        return $sql;
    }

    public static function mdlContarPorTipo($tipo, $q = '') {
        $sql = "SELECT COUNT(*) FROM productos p" . self::whereTipoBusqueda($tipo, $q);
        $stmt = Conexion::conectar()->prepare($sql);
        $stmt->bindValue(":tipo", $tipo, PDO::PARAM_STR);
        if($q !== '') $stmt->bindValue(":q", "%$q%", PDO::PARAM_STR);
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    }

    // Lista maestros + nº de lotes + vencimiento mas proximo
    public static function mdlListarPorTipo($tipo, $q, $limit, $offset) {
        $sql = "SELECT p.id AS producto_id, p.codigo, p.nombre AS nombre_descripcion, p.tipo,
                       p.principio_activo, p.forma_farmaceutica, p.concentracion, p.presentacion,
                       p.registro, p.marca, p.modelo, p.vida_util,
                       (SELECT COUNT(*) FROM lotes l WHERE l.producto_id = p.id) AS num_lotes,
                       (SELECT MIN(l.fecha_vencimiento) FROM lotes l WHERE l.producto_id = p.id AND l.fecha_vencimiento IS NOT NULL) AS prox_venc
                FROM productos p" . self::whereTipoBusqueda($tipo, $q) .
                " ORDER BY p.nombre ASC LIMIT :limit OFFSET :offset";
        $stmt = Conexion::conectar()->prepare($sql);
        $stmt->bindValue(":tipo", $tipo, PDO::PARAM_STR);
        if($q !== '') $stmt->bindValue(":q", "%$q%", PDO::PARAM_STR);
        $stmt->bindValue(":limit", (int)$limit, PDO::PARAM_INT);
        $stmt->bindValue(":offset", (int)$offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public static function mdlObtenerMaestro($id) {
        $stmt = Conexion::conectar()->prepare("SELECT * FROM productos WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    // Crea un maestro nuevo + su primer lote. Devuelve 'ok' o mensaje de error.
    public static function mdlIngresarMedicamento($datos) {
        try {
            $conn = Conexion::conectar();
            $conn->beginTransaction();
            $stmt = $conn->prepare("INSERT INTO productos (tipo, codigo, nombre, principio_activo, forma_farmaceutica, concentracion, presentacion, registro) VALUES ('MEDICAMENTO', :codigo, :nombre, :principio, :forma, :concentracion, :presentacion, :registro)");
            $stmt->execute([
                ":codigo" => $datos["codigo"], ":nombre" => $datos["nombre"], ":principio" => $datos["principio_activo"],
                ":forma" => $datos["forma_farmaceutica"], ":concentracion" => $datos["concentracion"],
                ":presentacion" => $datos["presentacion"], ":registro" => $datos["registro_invima"]
            ]);
            $maestro_id = $conn->lastInsertId();
            $lote = trim($datos["lote"]) !== '' ? trim($datos["lote"]) : 'S/L';
            $conn->prepare("INSERT INTO lotes (producto_id, numero_lote, fecha_vencimiento) VALUES (?,?,?)")
                 ->execute([$maestro_id, $lote, $datos["fecha_vencimiento"] ?: null]);
            $conn->commit();
            return "ok";
        } catch (Exception $e) {
            if($conn->inTransaction()) $conn->rollBack();
            if($e->getCode() == 23000) return "error: codigo duplicado";
            return "error: " . $e->getMessage();
        }
    }

    public static function mdlIngresarDispositivo($datos) {
        try {
            $conn = Conexion::conectar();
            $conn->beginTransaction();
            $stmt = $conn->prepare("INSERT INTO productos (tipo, codigo, nombre, marca, modelo, registro, vida_util) VALUES ('DISPOSITIVO', :codigo, :nombre, :marca, :modelo, :registro, :vida_util)");
            $stmt->execute([
                ":codigo" => $datos["codigo"], ":nombre" => $datos["descripcion"], ":marca" => $datos["marca"],
                ":modelo" => $datos["modelo"], ":registro" => $datos["registro_sanitario"], ":vida_util" => $datos["vida_util"]
            ]);
            $maestro_id = $conn->lastInsertId();
            $lote = trim($datos["lote"]) !== '' ? trim($datos["lote"]) : 'S/L';
            $conn->prepare("INSERT INTO lotes (producto_id, numero_lote, fecha_vencimiento) VALUES (?,?,?)")
                 ->execute([$maestro_id, $lote, null]);
            $conn->commit();
            return "ok";
        } catch (Exception $e) {
            if($conn->inTransaction()) $conn->rollBack();
            if($e->getCode() == 23000) return "error: codigo duplicado";
            return "error: " . $e->getMessage();
        }
    }
}
