<?php
class UsuariosModelo {

    /** Estados validos de una cuenta. ELIMINADO es borrado logico. */
    const ESTADOS = ['ACTIVO', 'INACTIVO', 'SUSPENDIDO', 'ELIMINADO'];

    /** Etiquetas legibles por estado. */
    const ESTADOS_ETIQUETA = [
        'ACTIVO'     => 'Activo',
        'INACTIVO'   => 'Inactivo',
        'SUSPENDIDO' => 'Suspendido',
        'ELIMINADO'  => 'Eliminado'
    ];

    /**
     * Normaliza el valor de estado. Tolera el esquema antiguo (TINYINT 1/0)
     * para que el sistema no se rompa si la migracion aun no se ejecuto.
     */
    public static function normalizarEstado($valor) {
        $v = strtoupper(trim((string)$valor));
        if($v === '' || $v === '1') return 'ACTIVO';
        if($v === '0') return 'INACTIVO';
        return in_array($v, self::ESTADOS, true) ? $v : 'ACTIVO';
    }

    public static function mdlMostrarUsuarioPorDocumento($documento) {
        $stmt = Conexion::conectar()->prepare("SELECT * FROM usuarios WHERE documento = :documento");
        $stmt->bindParam(":documento", $documento, PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetch();
    }

    public static function mdlObtenerUsuarioPorId($id) {
        $stmt = Conexion::conectar()->prepare("SELECT * FROM usuarios WHERE id = :id");
        $stmt->bindValue(":id", (int)$id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch();
    }

    /**
     * Lista usuarios con su sede, rol y datos de estado.
     * Los eliminados quedan fuera salvo que se pidan explicitamente.
     */
    public static function mdlMostrarUsuarios($incluirEliminados = false) {
        $sql = "SELECT u.*,
                       s.nombre   AS sede,
                       r.etiqueta AS rol_etiqueta,
                       r.es_admin AS rol_es_admin,
                       a.nombre   AS estado_cambiado_por_nombre
                  FROM usuarios u
                  LEFT JOIN sedes    s ON u.sede_id = s.id
                  LEFT JOIN roles    r ON r.nombre  = u.rol
                  LEFT JOIN usuarios a ON a.id      = u.estado_cambiado_por";
        if(!$incluirEliminados) {
            $sql .= " WHERE u.estado <> 'ELIMINADO'";
        }
        $sql .= " ORDER BY r.es_admin DESC, u.estado ASC, u.nombre ASC";
        $stmt = Conexion::conectar()->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public static function mdlContarEliminados() {
        return (int)Conexion::conectar()
            ->query("SELECT COUNT(*) FROM usuarios WHERE estado = 'ELIMINADO'")
            ->fetchColumn();
    }

    public static function mdlListarSedes() {
        $stmt = Conexion::conectar()->prepare("SELECT * FROM sedes ORDER BY id ASC");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public static function mdlListarSedesExceptoCentral() {
        $stmt = Conexion::conectar()->prepare("SELECT * FROM sedes WHERE id != 1 ORDER BY id ASC");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    // =====================================================================
    //  Administrador unico de bodega
    // =====================================================================

    /** Nombres de los roles marcados como administradores. */
    public static function mdlRolesAdmin() {
        $rows = Conexion::conectar()
            ->query("SELECT nombre FROM roles WHERE es_admin = 1")
            ->fetchAll(PDO::FETCH_COLUMN);
        if(!in_array('ADMIN', $rows, true)) $rows[] = 'ADMIN'; // red de seguridad
        return $rows;
    }

    /** Indica si un rol concede acceso de administrador. */
    public static function mdlEsRolAdmin($nombreRol) {
        if($nombreRol === 'ADMIN') return true;
        $stmt = Conexion::conectar()->prepare("SELECT es_admin FROM roles WHERE nombre = :n");
        $stmt->bindParam(":n", $nombreRol, PDO::PARAM_STR);
        $stmt->execute();
        return (bool)$stmt->fetchColumn();
    }

    /**
     * Administradores vigentes (no eliminados). Un usuario inactivo o
     * suspendido sigue ocupando el puesto porque puede ser reanudado.
     */
    public static function mdlListarAdministradores($excluirId = null) {
        $sql = "SELECT u.*, s.nombre AS sede
                  FROM usuarios u
                  JOIN roles r ON r.nombre = u.rol
                  LEFT JOIN sedes s ON s.id = u.sede_id
                 WHERE r.es_admin = 1
                   AND u.estado <> 'ELIMINADO'";
        if($excluirId !== null) $sql .= " AND u.id <> :ex";
        $sql .= " ORDER BY u.id ASC";
        $stmt = Conexion::conectar()->prepare($sql);
        if($excluirId !== null) $stmt->bindValue(":ex", (int)$excluirId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public static function mdlContarAdministradores($excluirId = null) {
        return count(self::mdlListarAdministradores($excluirId));
    }

    /** El administrador de bodega actual, o null si todavia no existe. */
    public static function mdlAdministradorActual() {
        $admins = self::mdlListarAdministradores();
        return $admins ? $admins[0] : null;
    }

    /** True si $id es el unico administrador que queda. */
    public static function mdlEsUnicoAdministrador($id) {
        $u = self::mdlObtenerUsuarioPorId($id);
        if(!$u || !self::mdlEsRolAdmin($u['rol'])) return false;
        return self::mdlContarAdministradores($id) === 0;
    }

    /** Cuenta usuarios vigentes asignados a un rol. */
    public static function mdlContarUsuariosDeRol($nombreRol, $incluirEliminados = false) {
        $sql = "SELECT COUNT(*) FROM usuarios WHERE rol = :n";
        if(!$incluirEliminados) $sql .= " AND estado <> 'ELIMINADO'";
        $stmt = Conexion::conectar()->prepare($sql);
        $stmt->bindParam(":n", $nombreRol, PDO::PARAM_STR);
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    }

    // =====================================================================
    //  Ciclo de vida de la cuenta
    // =====================================================================

    /**
     * Aplica un cambio de estado con sus metadatos.
     * $hasta solo se guarda para suspensiones (formato Y-m-d o null).
     */
    public static function mdlCambiarEstado($id, $estado, $motivo, $hasta, $actorId) {
        $sql = "UPDATE usuarios
                   SET estado              = :estado,
                       estado_motivo       = :motivo,
                       suspendido_hasta    = :hasta,
                       estado_cambiado_por = :actor,
                       estado_cambiado_en  = NOW(),
                       eliminado_en        = CASE WHEN :estado2 = 'ELIMINADO' THEN NOW() ELSE NULL END
                 WHERE id = :id";
        $stmt = Conexion::conectar()->prepare($sql);
        $stmt->bindValue(":estado",  $estado, PDO::PARAM_STR);
        $stmt->bindValue(":estado2", $estado, PDO::PARAM_STR);
        if($motivo === null || $motivo === '') {
            $stmt->bindValue(":motivo", null, PDO::PARAM_NULL);
        } else {
            $stmt->bindValue(":motivo", $motivo, PDO::PARAM_STR);
        }
        if($hasta === null || $hasta === '') {
            $stmt->bindValue(":hasta", null, PDO::PARAM_NULL);
        } else {
            $stmt->bindValue(":hasta", $hasta, PDO::PARAM_STR);
        }
        $stmt->bindValue(":actor", (int)$actorId, PDO::PARAM_INT);
        $stmt->bindValue(":id",    (int)$id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    /** Reactiva las suspensiones cuya fecha de fin ya paso. Devuelve filas afectadas. */
    public static function mdlVencerSuspensiones() {
        $sql = "UPDATE usuarios
                   SET estado             = 'ACTIVO',
                       estado_motivo      = 'Suspension vencida: reactivacion automatica',
                       suspendido_hasta   = NULL,
                       estado_cambiado_en = NOW()
                 WHERE estado = 'SUSPENDIDO'
                   AND suspendido_hasta IS NOT NULL
                   AND suspendido_hasta < CURDATE()";
        return (int)Conexion::conectar()->exec($sql);
    }

    public static function mdlCrearUsuario($datos) {
        try {
            $stmt = Conexion::conectar()->prepare(
                "INSERT INTO usuarios (nombre, documento, password, rol, sede_id, estado)
                 VALUES (:nombre, :doc, :pass, :rol, :sede, 'ACTIVO')"
            );
            $ok = $stmt->execute([
                ":nombre" => $datos["nombre"],
                ":doc"    => $datos["documento"],
                ":pass"   => $datos["password"],
                ":rol"    => $datos["rol"],
                ":sede"   => $datos["sede_id"]
            ]);
            // Con el ERRMODE por defecto (silencioso) no hay excepcion: el fallo
            // solo se ve en el valor de retorno y en errorInfo().
            if(!$ok) {
                $info = $stmt->errorInfo();
                return (($info[0] ?? '') === '23000') ? "dup_doc" : "error";
            }
            return "ok";
        } catch (PDOException $ex) {
            return $ex->getCode() == 23000 ? "dup_doc" : "error";
        }
    }

    public static function mdlEditarUsuario($datos) {
        $db = Conexion::conectar();
        if(!empty($datos["password"])) {
            $stmt = $db->prepare("UPDATE usuarios SET nombre = :nombre, password = :pass, rol = :rol, sede_id = :sede WHERE id = :id");
            $stmt->bindValue(":pass", $datos["password"], PDO::PARAM_STR);
        } else {
            $stmt = $db->prepare("UPDATE usuarios SET nombre = :nombre, rol = :rol, sede_id = :sede WHERE id = :id");
        }
        $stmt->bindValue(":nombre", $datos["nombre"], PDO::PARAM_STR);
        $stmt->bindValue(":rol",    $datos["rol"], PDO::PARAM_STR);
        $stmt->bindValue(":sede",   (int)$datos["sede_id"], PDO::PARAM_INT);
        $stmt->bindValue(":id",     (int)$datos["id"], PDO::PARAM_INT);
        return $stmt->execute();
    }

    // ---- Roles ----
    public static function mdlListarRoles() {
        return Conexion::conectar()->query("SELECT * FROM roles ORDER BY es_admin DESC, etiqueta ASC")->fetchAll();
    }
    public static function mdlObtenerRol($nombre) {
        $stmt = Conexion::conectar()->prepare("SELECT * FROM roles WHERE nombre = :n");
        $stmt->bindParam(":n", $nombre, PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetch();
    }
    public static function mdlCrearRol($datos) {
        try {
            $stmt = Conexion::conectar()->prepare("INSERT INTO roles (nombre, etiqueta, servicio, es_admin) VALUES (:n, :e, :s, :a)");
            $ok = $stmt->execute([":n"=>$datos["nombre"], ":e"=>$datos["etiqueta"], ":s"=>$datos["servicio"] ?: null, ":a"=>$datos["es_admin"]]);
            if(!$ok) {
                $info = $stmt->errorInfo();
                return (($info[0] ?? '') === '23000') ? "dup" : "error";
            }
            return "ok";
        } catch (Exception $ex) {
            return $ex->getCode() == 23000 ? "dup" : "error";
        }
    }
    public static function mdlEditarRol($datos) {
        // No se permite cambiar el codigo (nombre) ni es_admin de roles protegidos
        $rol = self::mdlObtenerRol($datos["nombre_actual"]);
        if(!$rol) return "error";
        $es_admin = $rol["protegido"] ? $rol["es_admin"] : $datos["es_admin"];
        $stmt = Conexion::conectar()->prepare("UPDATE roles SET etiqueta = :e, servicio = :s, es_admin = :a WHERE id = :id");
        $stmt->execute([":e"=>$datos["etiqueta"], ":s"=>$datos["servicio"] ?: null, ":a"=>$es_admin, ":id"=>$rol["id"]]);
        return "ok";
    }
    public static function mdlEliminarRol($nombre) {
        $rol = self::mdlObtenerRol($nombre);
        if(!$rol) return "error";
        if($rol["protegido"]) return "protegido";
        // No borrar si hay usuarios con ese rol (incluidos los eliminados logicamente)
        if(self::mdlContarUsuariosDeRol($nombre, true) > 0) return "en_uso";
        Conexion::conectar()->prepare("DELETE FROM roles WHERE id = :id")->execute([":id"=>$rol["id"]]);
        return "ok";
    }

    public static function mdlListarAuditoria() {
        $stmt = Conexion::conectar()->prepare("SELECT a.*, u.nombre FROM auditoria a JOIN usuarios u ON a.usuario_id = u.id ORDER BY a.fecha_hora DESC LIMIT 100");
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
