<?php
class InventarioControlador {

    public function ctrEntradaBodega() {
        if(isset($_POST["idMaestroBodega"])) {
            if(Security::verifyCSRF($_POST["csrf_token"])) {
                $cantidad = (int)$_POST["cantidadBodega"];
                if($cantidad <= 0) { echo 'error'; return; }
                $conn = Conexion::conectar();
                $conn->beginTransaction();
                try {
                    $lote_id = InventarioModelo::mdlFindOrCreateLote($conn, (int)$_POST["idMaestroBodega"], $_POST["numeroLoteBodega"] ?? '', $_POST["fechaVencBodega"] ?? null);
                    $datos = array(
                        "sede_id" => 1,
                        "lote_id" => $lote_id,
                        "tipo_movimiento" => "ENTRADA_BODEGA",
                        "cantidad" => $cantidad,
                        "usuario_id" => $_SESSION["id"],
                        "observacion" => $_POST["obsBodega"] ?? ''
                    );
                    $ok = InventarioModelo::mdlRegistrarMovimientoWithConn($conn, $datos);
                    if($ok) { $conn->commit(); echo 'ok'; } else { $conn->rollBack(); echo 'error'; }
                } catch(Exception $e) { $conn->rollBack(); echo 'error'; }
            }
        }
    }

    public function ctrDistribucionSede() {
        if(isset($_POST["idMaestroDist"])) {
            if(Security::verifyCSRF($_POST["csrf_token"])) {
                $cantidad = (int)$_POST["cantidadDist"];
                if($cantidad <= 0) { echo 'error'; return; }
                $lote_forzado = !empty($_POST["loteForzadoDist"]) ? (int)$_POST["loteForzadoDist"] : null;
                $conn = Conexion::conectar();
                $conn->beginTransaction();
                try {
                    $r = InventarioModelo::mdlConsumirFEFO($conn, (int)$_POST["idMaestroDist"], $cantidad, 'SALIDA_A_SEDE',
                            (int)$_POST["idSedeDist"], 1, $_SESSION["id"], $_POST["obsDist"] ?? '', $lote_forzado);
                    if($r['ok']) { $conn->commit(); echo 'ok'; }
                    else { $conn->rollBack(); echo 'stock_insuficiente'; }
                } catch(Exception $e) { $conn->rollBack(); echo 'error'; }
            }
        }
    }

    public function ctrDistribucionMasiva() {
        if(!isset($_POST["items"])) { echo 'sin_datos'; return; }
        if(!Security::verifyCSRF($_POST["csrf_token"] ?? '')) { echo 'csrf'; return; }
        $items = json_decode($_POST["items"], true);
        $sede_id = (int)$_POST["idSedeMasiva"];
        if(!is_array($items) || $sede_id <= 0) { echo 'sin_datos'; return; }
        $obs = $_POST["obsMasiva"] ?? "Distribucion masiva";
        $conn = Conexion::conectar();
        $conn->beginTransaction();
        try {
            $n = 0;
            foreach($items as $item) {
                $mid = (int)$item['producto_id']; $cant = (int)$item['cantidad'];
                if($mid <= 0 || $cant <= 0) continue;
                $r = InventarioModelo::mdlConsumirFEFO($conn, $mid, $cant, 'SALIDA_A_SEDE', $sede_id, 1, $_SESSION["id"], $obs);
                if(!$r['ok']) {
                    $conn->rollBack();
                    echo json_encode(['status'=>'stock_insuficiente', 'producto_id'=>$mid, 'stock'=>$r['disponible'], 'pedido'=>$cant]);
                    return;
                }
                $n++;
            }
            $conn->commit();
            echo json_encode(['status'=>'ok', 'total'=>$n]);
        } catch(Exception $e) { $conn->rollBack(); echo 'error'; }
    }

    public function ctrEntradaMasivaBodega() {
        if(!isset($_POST["items"])) { echo 'sin_datos'; return; }
        if(!Security::verifyCSRF($_POST["csrf_token"] ?? '')) { echo 'csrf'; return; }
        $items = json_decode($_POST["items"], true);
        if(!is_array($items)) { echo 'sin_datos'; return; }
        $obs = $_POST["obsMasiva"] ?? "Entrada masiva bodega";
        $conn = Conexion::conectar();
        $conn->beginTransaction();
        try {
            $n = 0;
            foreach($items as $item) {
                $mid = (int)$item['producto_id']; $cant = (int)$item['cantidad'];
                if($mid <= 0 || $cant <= 0) continue;
                $lote_id = InventarioModelo::mdlFindOrCreateLote($conn, $mid, $item['lote'] ?? '', $item['venc'] ?? null);
                $datos = array("sede_id"=>1, "lote_id"=>$lote_id, "tipo_movimiento"=>"ENTRADA_BODEGA",
                               "cantidad"=>$cant, "usuario_id"=>$_SESSION["id"], "observacion"=>$obs);
                if(!InventarioModelo::mdlRegistrarMovimientoWithConn($conn, $datos)) { $conn->rollBack(); echo 'error'; return; }
                $n++;
            }
            $conn->commit();
            echo json_encode(['status'=>'ok', 'total'=>$n]);
        } catch(Exception $e) { $conn->rollBack(); echo 'error'; }
    }

    // ---- passthroughs ----
    public static function ctrCalcularStockSede($sede_id, $maestro_id) {
        return InventarioModelo::mdlCalcularStock($sede_id, $maestro_id);
    }
    public static function ctrCalcularStockTodos($sede_id) {
        return InventarioModelo::mdlCalcularStockTodos($sede_id);
    }
    public static function ctrCalcularStockLotes($sede_id) {
        return InventarioModelo::mdlCalcularStockLotes($sede_id);
    }
    public static function ctrStockSistemaTodos() {
        return InventarioModelo::mdlStockSistemaTodos();
    }
    public static function ctrLotesParaSelect($sede_id) {
        return InventarioModelo::mdlLotesParaSelect($sede_id);
    }
    public static function ctrMostrarKardex($q = '', $tipo = '', $limit = null, $offset = 0) {
        return InventarioModelo::mdlMostrarKardex($_SESSION["sede_id"], $_SESSION["rol"], $q, $tipo, $limit, $offset);
    }
    public static function ctrContarKardex($q = '', $tipo = '') {
        return InventarioModelo::mdlContarKardex($_SESSION["sede_id"], $_SESSION["rol"], $q, $tipo);
    }
    public static function ctrDarBajaVencidos() {
        return InventarioModelo::mdlDarBajaVencidos($_SESSION["id"] ?? 1);
    }
    public static function ctrGetDadosDeBaja() {
        return InventarioModelo::mdlGetDadosDeBaja($_SESSION["sede_id"] ?? 1, $_SESSION["rol"] ?? 'ADMIN');
    }
    public static function ctrGetProximosVencer($dias = 30) {
        $sede = ($_SESSION["rol"] ?? 'ADMIN') === 'ADMIN' ? null : ($_SESSION["sede_id"] ?? null);
        return InventarioModelo::mdlGetProximosVencer($dias, $sede);
    }
    public static function ctrInventarioSede() {
        return InventarioModelo::mdlInventarioSede($_SESSION["sede_id"] ?? 1);
    }
    public static function ctrResumenPorSede() {
        Security::checkRole(['ADMIN']);
        return InventarioModelo::mdlResumenPorSede();
    }
    public static function ctrInventarioDeSede($sede_id) {
        Security::checkRole(['ADMIN']);
        return InventarioModelo::mdlInventarioSede((int)$sede_id);
    }
    // Exporta el inventario de la sede del usuario en sesion (cualquier rol). Antes de renderizar plantilla.
    public static function ctrExportarMiSede() {
        if(!isset($_SESSION["iniciarSesion"]) || $_SESSION["iniciarSesion"] !== "ok") { header('Location: inicio'); exit; }
        $sid = (int)($_SESSION["sede_id"] ?? 0);
        $nombre = preg_replace('/[^A-Za-z0-9]+/', '_', $_SESSION["sede_nombre"] ?? ('sede_'.$sid));
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=inventario_'.$nombre.'.csv');
        $out = fopen('php://output', 'w');
        fwrite($out, "\xEF\xBB\xBF");
        fputcsv($out, ['Codigo', 'Producto', 'Tipo', 'Stock']);
        foreach(InventarioModelo::mdlInventarioSede($sid) as $r) fputcsv($out, [$r['codigo'], $r['nombre'], $r['tipo'], $r['stock']]);
        fclose($out); exit;
    }

    // Exporta CSV (resumen de sedes o inventario de una sede). Se llama ANTES de renderizar la plantilla.
    public static function ctrExportarInventarioCSV() {
        if(!isset($_SESSION["iniciarSesion"]) || $_SESSION["iniciarSesion"] !== "ok" || ($_SESSION["rol"] ?? '') !== 'ADMIN') {
            header('Location: inicio'); exit;
        }
        header('Content-Type: text/csv; charset=utf-8');
        $out = fopen('php://output', 'w');
        fwrite($out, "\xEF\xBB\xBF"); // BOM UTF-8 para Excel
        if(($_GET['export'] ?? '') === 'sede' && isset($_GET['sede'])) {
            $sid = (int)$_GET['sede'];
            header('Content-Disposition: attachment; filename=inventario_sede_'.$sid.'.csv');
            fputcsv($out, ['Codigo', 'Producto', 'Tipo', 'Stock']);
            foreach(InventarioModelo::mdlInventarioSede($sid) as $r) fputcsv($out, [$r['codigo'], $r['nombre'], $r['tipo'], $r['stock']]);
        } else {
            header('Content-Disposition: attachment; filename=resumen_inventario_sedes.csv');
            fputcsv($out, ['Sede', 'Productos con stock', 'Unidades totales', 'Stock critico', 'Por vencer (30d)']);
            foreach(InventarioModelo::mdlResumenPorSede() as $r) fputcsv($out, [$r['nombre'], $r['productos'], $r['unidades'], $r['criticos'], $r['por_vencer']]);
        }
        fclose($out); exit;
    }
}
