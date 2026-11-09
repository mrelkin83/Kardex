<?php
require_once "../config/config.php";
require_once "../config/database.php";
require_once "../helpers/Security.php";

if(!isset($_SESSION["iniciarSesion"]) || $_SESSION["iniciarSesion"] !== "ok") {
    http_response_code(403);
    exit("unauthorized");
}

// Bloquea de inmediato a quien fue inactivado, suspendido o eliminado
Security::revalidarSesion("api");

require_once "../controladores/InventarioControlador.php";
require_once "../modelos/InventarioModelo.php";

if(isset($_POST["action"])) {
    if($_POST["action"] == "entradaBodega") {
        $ctrl = new InventarioControlador();
        $ctrl->ctrEntradaBodega();
    } elseif($_POST["action"] == "distribucionSede") {
        $ctrl = new InventarioControlador();
        $ctrl->ctrDistribucionSede();
    } elseif($_POST["action"] == "distribucionMasiva") {
        $ctrl = new InventarioControlador();
        $ctrl->ctrDistribucionMasiva();
    } elseif($_POST["action"] == "entradaMasivaBodega") {
        $ctrl = new InventarioControlador();
        $ctrl->ctrEntradaMasivaBodega();
    }
}
