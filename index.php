<?php
require_once "config/config.php";
require_once "config/database.php";
require_once "helpers/Security.php";

require_once "controladores/PlantillaControlador.php";
require_once "controladores/UsuariosControlador.php";
require_once "controladores/ProductosControlador.php";
require_once "controladores/InventarioControlador.php";
require_once "controladores/EntregasControlador.php";
require_once "controladores/ReportesControlador.php";

require_once "modelos/UsuariosModelo.php";
require_once "modelos/ProductosModelo.php";
require_once "modelos/InventarioModelo.php";
require_once "modelos/EntregasModelo.php";
require_once "modelos/DashboardModelo.php";

// La cuenta de la sesion puede haber sido inactivada, suspendida o eliminada
// por el administrador: se revalida en cada peticion antes de renderizar.
Security::revalidarSesion("web");

// Exportaciones CSV: deben ejecutarse antes de renderizar la plantilla (evita "headers already sent")
if(isset($_GET["ruta"]) && $_GET["ruta"] === "inventario-sedes" && isset($_GET["export"])) {
    InventarioControlador::ctrExportarInventarioCSV();
}
if(isset($_GET["ruta"]) && $_GET["ruta"] === "inicio" && ($_GET["export"] ?? '') === "misede") {
    InventarioControlador::ctrExportarMiSede();
}

$plantilla = new PlantillaControlador();
$plantilla->ctrPlantilla();
