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

require_once "../controladores/EntregasControlador.php";
require_once "../modelos/EntregasModelo.php";
require_once "../modelos/InventarioModelo.php";

if(isset($_POST["action"])) {
    if($_POST["action"] == "nuevaEntrega") {
        $ctrl = new EntregasControlador();
        $ctrl->ctrNuevaEntrega();
    }
}
