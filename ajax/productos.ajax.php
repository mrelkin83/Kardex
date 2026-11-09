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

require_once "../controladores/ProductosControlador.php";
require_once "../modelos/ProductosModelo.php";

if(isset($_POST["action"])) {
    if($_POST["action"] == "crearMedicamento") {
        $ctrl = new ProductosControlador();
        $ctrl->ctrCrearMedicamento();
    } elseif($_POST["action"] == "crearDispositivo") {
        $ctrl = new ProductosControlador();
        $ctrl->ctrCrearDispositivo();
    } elseif($_POST["action"] == "cargaMasivaCSV") {
        $ctrl = new ProductosControlador();
        $ctrl->ctrCargaMasivaCSV();
    }
}
