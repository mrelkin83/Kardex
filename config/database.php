<?php
// Credenciales por servidor: si existe config/credenciales.php manda ese archivo
// (se crea en cada despliegue y no se versiona). Si no existe, se usan los
// valores de desarrollo de Laragon.
$archivoCredenciales = __DIR__ . '/credenciales.php';
if (is_file($archivoCredenciales)) {
    require_once $archivoCredenciales;
}
if (!defined('DB_HOST')) define('DB_HOST', 'localhost');
if (!defined('DB_NAME')) define('DB_NAME', 'kardex');
if (!defined('DB_USER')) define('DB_USER', 'root');
if (!defined('DB_PASS')) define('DB_PASS', '');

class Conexion {
    public static function conectar() {
        // OJO: no activar PDO::ATTR_EMULATE_PREPARES => false. Varias consultas
        // de InventarioModelo repiten el placeholder :sede_id, y eso solo
        // funciona con prepares emulados (el modo por defecto).
        $link = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
            DB_USER,
            DB_PASS
        );
        $link->exec("set names utf8mb4");
        return $link;
    }
}
