<?php
class ReportesControlador {
    public static function ctrDescargarReporte() {
        if(isset($_GET["reporte"])) {
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename=reporte_inventario.csv');
            $salida = fopen('php://output', 'w');
            fputcsv($salida, array('Fecha', 'Sede', 'Producto', 'Lote', 'Movimiento', 'Cantidad', 'Usuario', 'Observacion'));
            
            $kardex = InventarioModelo::mdlMostrarKardex($_SESSION["sede_id"], $_SESSION["rol"]);
            foreach($kardex as $k) {
                fputcsv($salida, array(
                    $k["fecha_hora"],
                    $k["sede"],
                    $k["producto"],
                    $k["lote"],
                    $k["tipo_movimiento"],
                    $k["cantidad"],
                    $k["usuario"],
                    $k["observacion"]
                ));
            }
            fclose($salida);
            exit;
        }
    }
}
