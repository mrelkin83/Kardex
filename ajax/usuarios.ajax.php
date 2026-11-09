<?php
require_once "../config/config.php";
require_once "../config/database.php";
require_once "../helpers/Security.php";
require_once "../controladores/UsuariosControlador.php";
require_once "../modelos/UsuariosModelo.php";

$ctrl = new UsuariosControlador();

if(isset($_POST["ingDocumento"])) {
    $ctrl->ctrIngresoUsuario();
    exit;
}

if(!isset($_SESSION["iniciarSesion"]) || $_SESSION["iniciarSesion"] !== "ok") {
    http_response_code(403);
    exit("unauthorized");
}

// Bloquea de inmediato a quien fue inactivado, suspendido o eliminado
Security::revalidarSesion("api");

if(isset($_POST["action"])) {
    if($_POST["action"] == "crearUsuario") {
        $ctrl->ctrCrearUsuario();
    } elseif($_POST["action"] == "editarUsuario") {
        $ctrl->ctrEditarUsuario();
    } elseif($_POST["action"] == "eliminarUsuario") {
        $ctrl->ctrEliminarUsuario();
    } elseif($_POST["action"] == "cambiarEstadoUsuario") {
        $ctrl->ctrCambiarEstadoUsuario();
    } elseif($_POST["action"] == "crearSede") {
        $ctrl->ctrCrearSede();
    } elseif($_POST["action"] == "editarSede") {
        $ctrl->ctrEditarSede();
    } elseif($_POST["action"] == "eliminarSede") {
        $ctrl->ctrEliminarSede();
    } elseif($_POST["action"] == "actualizarPerfil") {
        $ctrl->ctrActualizarPerfil();
    } elseif($_POST["action"] == "crearRol") {
        $ctrl->ctrCrearRol();
    } elseif($_POST["action"] == "editarRol") {
        $ctrl->ctrEditarRol();
    } elseif($_POST["action"] == "eliminarRol") {
        $ctrl->ctrEliminarRol();
    }
}
