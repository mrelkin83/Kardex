<?php
class UsuariosControlador {

    /**
     * Transiciones permitidas del ciclo de vida de una cuenta.
     * ELIMINADO es borrado logico: solo se puede restaurar a ACTIVO.
     */
    private const TRANSICIONES = [
        'ACTIVO'     => ['INACTIVO', 'SUSPENDIDO', 'ELIMINADO'],
        'INACTIVO'   => ['ACTIVO', 'SUSPENDIDO', 'ELIMINADO'],
        'SUSPENDIDO' => ['ACTIVO', 'INACTIVO', 'ELIMINADO'],
        'ELIMINADO'  => ['ACTIVO']
    ];

    /** Verbo usado en la bitacora de auditoria por estado destino. */
    private const VERBO_AUDITORIA = [
        'ACTIVO'     => 'Reanudó',
        'INACTIVO'   => 'Inactivó',
        'SUSPENDIDO' => 'Suspendió',
        'ELIMINADO'  => 'Eliminó'
    ];

    public function ctrIngresoUsuario() {
        if(isset($_POST["ingDocumento"])) {
            if(Security::verifyCSRF($_POST["csrf_token"])) {
                $respuesta = UsuariosModelo::mdlMostrarUsuarioPorDocumento($_POST["ingDocumento"]);

                if($respuesta && password_verify($_POST["ingPassword"], $respuesta["password"])) {

                    // Las suspensiones con fecha de fin se levantan solas al vencer
                    $estado = UsuariosModelo::normalizarEstado($respuesta["estado"] ?? 'ACTIVO');
                    if($estado === 'SUSPENDIDO'
                        && !empty($respuesta["suspendido_hasta"])
                        && $respuesta["suspendido_hasta"] < date('Y-m-d')) {
                        UsuariosModelo::mdlVencerSuspensiones();
                        $estado = 'ACTIVO';
                    }

                    // Solo las cuentas activas pueden entrar
                    if($estado !== 'ACTIVO') {
                        $this->registrarAuditoria(
                            $respuesta["id"],
                            "Intento de inicio de sesión bloqueado (cuenta " . UsuariosModelo::ESTADOS_ETIQUETA[$estado] . ")",
                            "usuarios"
                        );
                        echo strtolower($estado); exit();
                    }

                    $_SESSION["iniciarSesion"] = "ok";
                    $_SESSION["id"] = $respuesta["id"];
                    $_SESSION["nombre"] = $respuesta["nombre"];
                    $_SESSION["rol"] = $respuesta["rol"];
                    $_SESSION["sede_id"] = $respuesta["sede_id"];
                    $_SESSION["documento"] = $respuesta["documento"];
                    $_SESSION["estado"] = $estado;

                    $rolInfo = UsuariosModelo::mdlObtenerRol($respuesta["rol"]);
                    $_SESSION["es_admin"] = $rolInfo ? (int)$rolInfo["es_admin"] : ($respuesta["rol"] === 'ADMIN' ? 1 : 0);
                    $_SESSION["servicio"] = $rolInfo && $rolInfo["servicio"] ? $rolInfo["servicio"] : 'GENERAL';

                    $db = Conexion::conectar();
                    $stmt = $db->prepare("SELECT nombre FROM sedes WHERE id = :id");
                    $stmt->bindParam(":id", $respuesta["sede_id"], PDO::PARAM_INT);
                    $stmt->execute();
                    $sede = $stmt->fetch();
                    $_SESSION["sede_nombre"] = $sede ? $sede["nombre"] : '';

                    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

                    $this->registrarAuditoria($_SESSION["id"], "Inicio de Sesión Exitoso", "usuarios");

                    echo 'ok'; exit();
                } else { echo 'error'; exit(); }
            } else { echo 'csrf'; exit(); }
        }
    }

    public function ctrCrearUsuario() {
        Security::checkRole(['ADMIN']);
        if(!Security::verifyCSRF($_POST["csrf_token"] ?? '')) { echo 'csrf'; exit(); }

        $nombre = trim($_POST["nombreUsu"] ?? '');
        $doc    = trim($_POST["docUsu"] ?? '');
        $pass   = (string)($_POST["passUsu"] ?? '');
        $rol    = trim($_POST["rolUsu"] ?? '');
        $sede   = (int)($_POST["sedeUsu"] ?? 0);

        if($nombre === '' || $doc === '' || $rol === '' || $sede <= 0) { echo 'datos'; exit(); }
        if(strlen($pass) < 6) { echo 'pass_corta'; exit(); }
        if(!UsuariosModelo::mdlObtenerRol($rol)) { echo 'rol_invalido'; exit(); }

        // El documento es unico, incluso para cuentas con borrado logico
        $existente = UsuariosModelo::mdlMostrarUsuarioPorDocumento($doc);
        if($existente) {
            $estadoPrevio = UsuariosModelo::normalizarEstado($existente["estado"] ?? 'ACTIVO');
            echo $estadoPrevio === 'ELIMINADO' ? 'doc_eliminado' : 'dup_doc';
            exit();
        }

        // Regla de negocio: la bodega la administra un solo usuario
        if(UsuariosModelo::mdlEsRolAdmin($rol) && UsuariosModelo::mdlContarAdministradores() > 0) {
            echo 'admin_existe'; exit();
        }

        $resultado = UsuariosModelo::mdlCrearUsuario([
            "nombre"    => $nombre,
            "documento" => $doc,
            "password"  => password_hash($pass, PASSWORD_DEFAULT),
            "rol"       => $rol,
            "sede_id"   => $sede
        ]);

        if($resultado === 'ok') {
            $this->registrarAuditoria($_SESSION["id"], "Creó usuario: " . $nombre . " (doc " . $doc . ")", "usuarios");
        }
        echo $resultado;
        exit();
    }

    public function ctrEditarUsuario() {
        Security::checkRole(['ADMIN']);
        if(!Security::verifyCSRF($_POST["csrf_token"] ?? '')) { echo 'csrf'; exit(); }

        $id = (int)($_POST["idEditUsu"] ?? 0);
        $usuario = UsuariosModelo::mdlObtenerUsuarioPorId($id);
        if(!$usuario) { echo 'no_existe'; exit(); }
        if(UsuariosModelo::normalizarEstado($usuario["estado"] ?? 'ACTIVO') === 'ELIMINADO') {
            echo 'eliminado'; exit();
        }

        $nombre = trim($_POST["nombreEditUsu"] ?? '');
        $rol    = trim($_POST["rolEditUsu"] ?? '');
        $sede   = (int)($_POST["sedeEditUsu"] ?? 0);
        $pass   = (string)($_POST["passEditUsu"] ?? '');

        if($nombre === '' || $rol === '' || $sede <= 0) { echo 'datos'; exit(); }
        if($pass !== '' && strlen($pass) < 6) { echo 'pass_corta'; exit(); }
        if(!UsuariosModelo::mdlObtenerRol($rol)) { echo 'rol_invalido'; exit(); }

        $eraAdmin    = UsuariosModelo::mdlEsRolAdmin($usuario["rol"]);
        $seraAdmin   = UsuariosModelo::mdlEsRolAdmin($rol);
        $otrosAdmins = UsuariosModelo::mdlContarAdministradores($id);

        // No se puede nombrar un segundo administrador
        if($seraAdmin && $otrosAdmins > 0) { echo 'admin_existe'; exit(); }
        // Ni dejar la bodega sin administrador
        if($eraAdmin && !$seraAdmin && $otrosAdmins === 0) { echo 'admin_requerido'; exit(); }

        $ok = UsuariosModelo::mdlEditarUsuario([
            "id"       => $id,
            "nombre"   => $nombre,
            "rol"      => $rol,
            "sede_id"  => $sede,
            "password" => $pass !== '' ? password_hash($pass, PASSWORD_DEFAULT) : ''
        ]);

        if($ok) {
            // Si el admin se edito a si mismo, la sesion debe reflejarlo de inmediato
            if($id === (int)$_SESSION["id"]) {
                $_SESSION["nombre"]   = $nombre;
                $_SESSION["rol"]      = $rol;
                $_SESSION["sede_id"]  = $sede;
                $rolInfo = UsuariosModelo::mdlObtenerRol($rol);
                $_SESSION["es_admin"] = $rolInfo ? (int)$rolInfo["es_admin"] : 0;
            }
            $this->registrarAuditoria($_SESSION["id"], "Editó usuario: " . $nombre . " (ID " . $id . ")", "usuarios");
            echo 'ok';
        } else { echo 'error'; }
        exit();
    }

    // =====================================================================
    //  Ciclo de vida: inactivar / suspender / reanudar / eliminar / restaurar
    // =====================================================================

    /**
     * Cambia el estado de una cuenta validando la transicion, el motivo,
     * la vigencia de la suspension y la proteccion del administrador unico.
     * Devuelve un codigo de resultado ('ok' o el motivo del rechazo).
     */
    private function aplicarCambioEstado($id, $nuevoEstado, $motivo, $hasta) {
        $id          = (int)$id;
        $nuevoEstado = strtoupper(trim((string)$nuevoEstado));
        $motivo      = trim((string)$motivo);
        $hasta       = trim((string)$hasta);

        if(!in_array($nuevoEstado, UsuariosModelo::ESTADOS, true)) return 'estado_invalido';

        $usuario = UsuariosModelo::mdlObtenerUsuarioPorId($id);
        if(!$usuario) return 'no_existe';

        // El administrador no puede bloquearse ni borrarse a si mismo
        if($id === (int)$_SESSION["id"]) return 'self_estado';

        $actual = UsuariosModelo::normalizarEstado($usuario["estado"] ?? 'ACTIVO');
        if($actual === $nuevoEstado) return 'sin_cambio';
        if(!in_array($nuevoEstado, self::TRANSICIONES[$actual], true)) return 'transicion_invalida';

        $esAdminObjetivo = UsuariosModelo::mdlEsRolAdmin($usuario["rol"]);
        $otrosAdmins     = UsuariosModelo::mdlContarAdministradores($id);

        // El unico administrador de bodega no se inactiva, suspende ni elimina
        if($esAdminObjetivo && $nuevoEstado !== 'ACTIVO' && $otrosAdmins === 0) return 'admin_protegido';
        // Restaurar un administrador eliminado solo si el puesto esta libre
        if($esAdminObjetivo && $nuevoEstado === 'ACTIVO' && $otrosAdmins > 0) return 'admin_existe';

        $fechaHasta = null;
        if($nuevoEstado === 'SUSPENDIDO') {
            if($motivo === '') return 'motivo_requerido';
            if($hasta !== '') {
                $fecha = DateTime::createFromFormat('Y-m-d', $hasta);
                if(!$fecha || $fecha->format('Y-m-d') !== $hasta) return 'fecha_invalida';
                if($hasta <= date('Y-m-d')) return 'fecha_pasada';
                $fechaHasta = $hasta;
            }
        } elseif($nuevoEstado === 'INACTIVO' || $nuevoEstado === 'ELIMINADO') {
            if($motivo === '') return 'motivo_requerido';
        } elseif($motivo === '') {
            $motivo = $actual === 'ELIMINADO'
                ? 'Cuenta restaurada por el administrador'
                : 'Cuenta reanudada por el administrador';
        }

        if(mb_strlen($motivo, 'UTF-8') > 255) $motivo = mb_substr($motivo, 0, 255, 'UTF-8');

        if(!UsuariosModelo::mdlCambiarEstado($id, $nuevoEstado, $motivo, $fechaHasta, $_SESSION["id"])) {
            return 'error';
        }

        $verbo = ($actual === 'ELIMINADO' && $nuevoEstado === 'ACTIVO')
            ? 'Restauró'
            : self::VERBO_AUDITORIA[$nuevoEstado];
        $detalle = $verbo . " al usuario " . $usuario["nombre"] . " (ID " . $id . "). Motivo: " . $motivo;
        if($fechaHasta) $detalle .= ". Hasta: " . $fechaHasta;
        $this->registrarAuditoria($_SESSION["id"], $detalle, "usuarios");

        return 'ok';
    }

    public function ctrCambiarEstadoUsuario() {
        Security::checkRole(['ADMIN']);
        if(!Security::verifyCSRF($_POST["csrf_token"] ?? '')) { echo 'csrf'; exit(); }

        echo $this->aplicarCambioEstado(
            $_POST["idEstado"] ?? 0,
            $_POST["nuevoEstado"] ?? '',
            $_POST["motivoEstado"] ?? '',
            $_POST["suspendidoHasta"] ?? ''
        );
        exit();
    }

    /** Eliminacion = borrado logico, para no perder el historial ni la auditoria. */
    public function ctrEliminarUsuario() {
        Security::checkRole(['ADMIN']);
        if(!Security::verifyCSRF($_POST["csrf_token"] ?? '')) { echo 'csrf'; exit(); }

        $motivo = trim($_POST["motivoEstado"] ?? '');
        if($motivo === '') $motivo = 'Eliminado por el administrador';

        $resultado = $this->aplicarCambioEstado($_POST["idEliminar"] ?? 0, 'ELIMINADO', $motivo, '');
        // 'self_estado' se mantiene como 'self_delete' por compatibilidad con el front
        echo $resultado === 'self_estado' ? 'self_delete' : $resultado;
        exit();
    }

    // ---- Roles ----
    private function codigoRol($etiqueta) {
        $c = mb_strtoupper(trim($etiqueta), 'UTF-8');
        $c = strtr($c, ['Á'=>'A','É'=>'E','Í'=>'I','Ó'=>'O','Ú'=>'U','Ñ'=>'N','Ü'=>'U']);
        $c = preg_replace('/[^A-Z0-9]+/', '_', $c);
        return trim($c, '_');
    }
    public function ctrCrearRol() {
        Security::checkRole(['ADMIN']);
        if(!Security::verifyCSRF($_POST["csrf_token"] ?? '')) { echo 'csrf'; exit(); }
        $etiqueta = trim($_POST["etiquetaRol"] ?? '');
        if($etiqueta === '') { echo 'error'; exit(); }
        $nombre = $this->codigoRol($etiqueta);
        if($nombre === '') { echo 'error'; exit(); }
        $datos = [
            "nombre" => $nombre,
            "etiqueta" => $etiqueta,
            "servicio" => strtoupper(trim($_POST["servicioRol"] ?? '')) ?: null,
            "es_admin" => (isset($_POST["esAdminRol"]) && $_POST["esAdminRol"] == '1') ? 1 : 0
        ];
        $r = UsuariosModelo::mdlCrearRol($datos);
        if($r === 'ok') $this->registrarAuditoria($_SESSION["id"], "Creó rol: ".$nombre, "roles");
        echo $r === 'dup' ? 'dup' : $r;
        exit();
    }
    public function ctrEditarRol() {
        Security::checkRole(['ADMIN']);
        if(!Security::verifyCSRF($_POST["csrf_token"] ?? '')) { echo 'csrf'; exit(); }

        $nombreActual = $_POST["nombreActualRol"] ?? '';
        $rol = UsuariosModelo::mdlObtenerRol($nombreActual);
        if(!$rol) { echo 'error'; exit(); }

        $etiqueta = trim($_POST["etiquetaEditRol"] ?? '');
        if($etiqueta === '') { echo 'error'; exit(); }

        $pedidoAdmin = (isset($_POST["esAdminEditRol"]) && $_POST["esAdminEditRol"] == '1') ? 1 : 0;
        $eraAdmin    = (int)$rol["es_admin"] === 1;
        // Los roles protegidos conservan su nivel de acceso
        $seraAdmin   = $rol["protegido"] ? $eraAdmin : ($pedidoAdmin === 1);

        // Elevar un rol no puede generar un segundo administrador
        if(!$eraAdmin && $seraAdmin) {
            $usuariosDelRol = UsuariosModelo::mdlContarUsuariosDeRol($rol["nombre"]);
            if($usuariosDelRol + UsuariosModelo::mdlContarAdministradores() > 1) {
                echo 'admin_existe'; exit();
            }
        }
        // Degradar un rol no puede dejar la bodega sin administrador
        if($eraAdmin && !$seraAdmin) {
            $otros = 0;
            foreach(UsuariosModelo::mdlListarAdministradores() as $a) {
                if($a["rol"] !== $rol["nombre"]) $otros++;
            }
            if($otros === 0) { echo 'admin_requerido'; exit(); }
        }

        $r = UsuariosModelo::mdlEditarRol([
            "nombre_actual" => $nombreActual,
            "etiqueta" => $etiqueta,
            "servicio" => strtoupper(trim($_POST["servicioEditRol"] ?? '')) ?: null,
            "es_admin" => $seraAdmin ? 1 : 0
        ]);
        if($r === 'ok') $this->registrarAuditoria($_SESSION["id"], "Editó rol: ".$nombreActual, "roles");
        echo $r;
        exit();
    }
    public function ctrEliminarRol() {
        Security::checkRole(['ADMIN']);
        if(!Security::verifyCSRF($_POST["csrf_token"] ?? '')) { echo 'csrf'; exit(); }
        $r = UsuariosModelo::mdlEliminarRol($_POST["idEliminar"] ?? '');
        if($r === 'ok') $this->registrarAuditoria($_SESSION["id"], "Eliminó rol: ".($_POST["idEliminar"] ?? ''), "roles");
        echo $r;
        exit();
    }

    public function ctrCrearSede() {
        Security::checkRole(['ADMIN']);
        if(!Security::verifyCSRF($_POST["csrf_token"] ?? '')) { echo 'csrf'; exit(); }
        $db = Conexion::conectar();
        $stmt = $db->prepare("INSERT INTO sedes (nombre) VALUES (:nombre)");
        $stmt->bindParam(":nombre", $_POST["nombreSede"], PDO::PARAM_STR);
        if($stmt->execute()){
            $this->registrarAuditoria($_SESSION["id"], "Creó nueva sede: ".$_POST["nombreSede"], "sedes");
            echo 'ok';
        } else { echo 'error'; }
        exit();
    }

    public function ctrEditarSede() {
        Security::checkRole(['ADMIN']);
        if(!Security::verifyCSRF($_POST["csrf_token"] ?? '')) { echo 'csrf'; exit(); }
        $db = Conexion::conectar();
        $stmt = $db->prepare("UPDATE sedes SET nombre = :nombre WHERE id = :id");
        $stmt->bindParam(":nombre", $_POST["nombreEditSede"], PDO::PARAM_STR);
        $stmt->bindParam(":id", $_POST["idEditSede"], PDO::PARAM_INT);
        if($stmt->execute()){
            $this->registrarAuditoria($_SESSION["id"], "Editó sede: ".$_POST["nombreEditSede"], "sedes");
            echo 'ok';
        } else { echo 'error'; }
        exit();
    }

    public function ctrEliminarSede() {
        Security::checkRole(['ADMIN']);
        if(!Security::verifyCSRF($_POST["csrf_token"] ?? '')) { echo 'csrf'; exit(); }
        if($_POST["idEliminar"] == 1) { echo 'is_bodega'; exit(); } // No borrar bodega central
        $db = Conexion::conectar();
        $stmt = $db->prepare("DELETE FROM sedes WHERE id = :id");
        $stmt->bindParam(":id", $_POST["idEliminar"], PDO::PARAM_INT);
        try {
            if($stmt->execute()){
                $this->registrarAuditoria($_SESSION["id"], "Eliminó sede ID: ".$_POST["idEliminar"], "sedes");
                echo 'ok';
            } else {
                // Con el ERRMODE por defecto la violacion de clave externa no
                // lanza excepcion: hay que leerla de errorInfo().
                $info = $stmt->errorInfo();
                echo (($info[0] ?? '') === '23000') ? 'has_dependencies' : 'error';
            }
        } catch (Exception $e) {
            echo 'has_dependencies'; // Por integridad referencial
        }
        exit();
    }

    public function ctrActualizarPerfil() {
        if(Security::verifyCSRF($_POST["csrf_token"])) {
            $id = (int)$_POST["idPerfil"];
            if($id !== (int)$_SESSION["id"]) { echo 'error'; exit(); }

            $db = Conexion::conectar();
            $nombre = $_POST["nombrePerfil"];
            $pass = $_POST["passPerfil"];

            if(!empty($pass)) {
                if(strlen($pass) < 6) { echo 'pass_corta'; exit(); }
                $passHash = password_hash($pass, PASSWORD_DEFAULT);
                $stmt = $db->prepare("UPDATE usuarios SET nombre = :nombre, password = :pass WHERE id = :id");
                $stmt->bindParam(":pass", $passHash, PDO::PARAM_STR);
            } else {
                $stmt = $db->prepare("UPDATE usuarios SET nombre = :nombre WHERE id = :id");
            }

            $stmt->bindParam(":nombre", $nombre, PDO::PARAM_STR);
            $stmt->bindParam(":id", $id, PDO::PARAM_INT);

            if($stmt->execute()){
                $_SESSION["nombre"] = $nombre;
                $this->registrarAuditoria($id, "Actualizó su perfil personal", "usuarios");
                echo 'ok';
            } else { echo 'error'; }
        } else { echo 'csrf'; }
        exit();
    }

    private function registrarAuditoria($user_id, $accion, $tabla) {
        $db = Conexion::conectar();
        $stmt = $db->prepare("INSERT INTO auditoria (usuario_id, accion, tabla) VALUES (:u, :a, :t)");
        $stmt->execute(['u'=>$user_id, 'a'=>$accion, 't'=>$tabla]);
    }
}
