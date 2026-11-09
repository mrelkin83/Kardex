<?php
class ProductosControlador {
    public static function ctrMostrarProductos() {
        return ProductosModelo::mdlMostrarProductos();
    }
    public static function ctrContarPorTipo($tipo, $q = '') {
        return ProductosModelo::mdlContarPorTipo($tipo, $q);
    }
    public static function ctrListarPorTipo($tipo, $q, $limit, $offset) {
        return ProductosModelo::mdlListarPorTipo($tipo, $q, $limit, $offset);
    }
    public static function ctrObtenerMaestro($id) {
        return ProductosModelo::mdlObtenerMaestro($id);
    }
    public static function ctrLotesConStock($maestro_id, $sede_id) {
        return InventarioModelo::mdlLotesConStock($maestro_id, $sede_id);
    }

    public function ctrCrearMedicamento() {
        if(isset($_POST["codigoMed"])) {
            if(Security::verifyCSRF($_POST["csrf_token"])) {
                $datos = array(
                    "codigo" => $_POST["codigoMed"],
                    "nombre" => $_POST["nombreMed"],
                    "principio_activo" => $_POST["principioMed"],
                    "forma_farmaceutica" => $_POST["formaMed"],
                    "concentracion" => $_POST["concentracionMed"],
                    "presentacion" => $_POST["presentacionMed"],
                    "registro_invima" => $_POST["invimaMed"],
                    "lote" => $_POST["loteMed"],
                    "fecha_vencimiento" => $_POST["fechaVencMed"]
                );
                echo ProductosModelo::mdlIngresarMedicamento($datos);
            }
        }
    }
    public function ctrCrearDispositivo() {
        if(isset($_POST["codigoDisp"])) {
            if(Security::verifyCSRF($_POST["csrf_token"])) {
                $datos = array(
                    "codigo" => $_POST["codigoDisp"],
                    "descripcion" => $_POST["descDisp"],
                    "marca" => $_POST["marcaDisp"],
                    "modelo" => $_POST["modeloDisp"],
                    "registro_sanitario" => $_POST["sanitarioDisp"],
                    "lote" => $_POST["loteDisp"],
                    "vida_util" => $_POST["vidaDisp"]
                );
                echo ProductosModelo::mdlIngresarDispositivo($datos);
            }
        }
    }

    public function ctrCargaMasivaCSV() {
        if(!isset($_FILES['csvFile']) || $_FILES['csvFile']['error'] !== UPLOAD_ERR_OK) {
            echo json_encode(['status'=>'error', 'msg'=>'No se recibio archivo']); return;
        }
        if(!Security::verifyCSRF($_POST["csrf_token"] ?? '')) { echo json_encode(['status'=>'csrf']); return; }

        $tipo = $_POST['tipoCarga'] ?? 'MEDICAMENTO';
        $handle = fopen($_FILES['csvFile']['tmp_name'], 'r');
        if(!$handle) { echo json_encode(['status'=>'error', 'msg'=>'No se pudo leer archivo']); return; }

        $conn = Conexion::conectar();
        $fila = 0; $exitos = 0; $errores = [];
        $findMaestro = $conn->prepare("SELECT id FROM productos WHERE codigo = ?");

        while(($data = fgetcsv($handle, 2000, ',')) !== false) {
            $fila++;
            if($fila === 1) continue; // encabezado
            $data = array_map('trim', $data);
            if(count(array_filter($data)) === 0) continue;
            try {
                $conn->beginTransaction();
                if($tipo === 'MEDICAMENTO') {
                    if(count($data) < 9) { throw new Exception("datos insuficientes"); }
                    $findMaestro->execute([$data[0]]);
                    $mid = $findMaestro->fetchColumn();
                    if(!$mid) {
                        $ins = $conn->prepare("INSERT INTO productos(tipo,codigo,nombre,principio_activo,forma_farmaceutica,concentracion,presentacion,registro) VALUES('MEDICAMENTO',?,?,?,?,?,?,?)");
                        $ins->execute([$data[0],$data[1],$data[2],$data[3],$data[4],$data[5],$data[6]]);
                        $mid = $conn->lastInsertId();
                    }
                    InventarioModelo::mdlFindOrCreateLote($conn, $mid, $data[7], $data[8] ?: null);
                } else {
                    if(count($data) < 7) { throw new Exception("datos insuficientes"); }
                    $findMaestro->execute([$data[0]]);
                    $mid = $findMaestro->fetchColumn();
                    if(!$mid) {
                        $ins = $conn->prepare("INSERT INTO productos(tipo,codigo,nombre,marca,modelo,registro,vida_util) VALUES('DISPOSITIVO',?,?,?,?,?,?)");
                        $ins->execute([$data[0],$data[1],$data[2],$data[3],$data[4],$data[6]]);
                        $mid = $conn->lastInsertId();
                    }
                    InventarioModelo::mdlFindOrCreateLote($conn, $mid, $data[5], null);
                }
                $conn->commit();
                $exitos++;
            } catch(Exception $e) {
                if($conn->inTransaction()) $conn->rollBack();
                $errores[] = "Fila $fila: " . $e->getMessage();
            }
        }
        fclose($handle);
        echo json_encode(['status'=>'ok', 'exitos'=>$exitos, 'errores'=>$errores, 'total'=>$fila-1]);
    }
}
