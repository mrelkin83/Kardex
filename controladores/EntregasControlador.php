<?php
class EntregasControlador {
    public function ctrNuevaEntrega() {
        if(isset($_POST["idProductoEntrega"])) {
            if(Security::verifyCSRF($_POST["csrf_token"])) {
                $cantidad = (int)$_POST["cantidadEntrega"];
                if($cantidad <= 0) { echo 'error'; return; }
                $sede = $_SESSION["sede_id"];
                $lote_forzado = !empty($_POST["loteForzadoEntrega"]) ? (int)$_POST["loteForzadoEntrega"] : null;

                $conn = Conexion::conectar();
                $conn->beginTransaction();
                try {
                    // FEFO: consume del sede actual (sede_mov = sede_stock = sede)
                    $r = InventarioModelo::mdlConsumirFEFO($conn, (int)$_POST["idProductoEntrega"], $cantidad, 'ENTREGA_PACIENTE',
                            $sede, $sede, $_SESSION["id"], $_POST["observacionEntrega"] ?? '', $lote_forzado);
                    if(!$r['ok']) { $conn->rollBack(); echo 'stock_insuficiente'; return; }

                    // un registro de entrega por cada movimiento (lote) generado
                    foreach($r['movs'] as $mov) {
                        EntregasModelo::mdlRegistrarEntregaWithConn($conn, array(
                            "movimiento_id" => $mov['mov_id'],
                            "servicio" => $_POST["servicioEntrega"],
                            "profesional" => $_POST["profesionalEntrega"],
                            "nombre_paciente" => $_POST["pacienteEntrega"],
                            "documento" => $_POST["docPacienteEntrega"]
                        ));
                    }
                    $conn->commit();
                    echo 'ok';
                } catch (Exception $e) { $conn->rollBack(); echo 'error'; }
            }
        }
    }
    public static function ctrMostrarEntregas() {
        return EntregasModelo::mdlMostrarEntregas($_SESSION["sede_id"], $_SESSION["rol"]);
    }
}
