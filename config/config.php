<?php
session_start();

// URL base de la aplicacion, derivada de la peticion para que el mismo codigo
// funcione en http://localhost/Kardex/ y en un dominio o IP servido en la raiz.
// Se calcula comparando la carpeta del proyecto con el DOCUMENT_ROOT (y no con
// SCRIPT_NAME) porque los endpoints de ajax/ corren desde un subdirectorio.
$esquemaApp = (!empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off') ? 'https' : 'http';
$hostApp    = $_SERVER['HTTP_HOST'] ?? 'localhost';

$raizDoc = $_SERVER['DOCUMENT_ROOT'] ?? '';
$raizDoc = $raizDoc ? str_replace('\\', '/', rtrim(realpath($raizDoc) ?: $raizDoc, '/\\')) : '';
$raizApp = str_replace('\\', '/', dirname(__DIR__));

$baseApp = '';
if ($raizDoc !== '' && strpos($raizApp, $raizDoc) === 0) {
    $baseApp = rtrim(substr($raizApp, strlen($raizDoc)), '/');
}

define('BASE_APP', $baseApp . '/');
define('URL_APP', $esquemaApp . '://' . $hostApp . BASE_APP);
