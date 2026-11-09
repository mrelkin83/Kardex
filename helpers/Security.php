<?php
function e($string) {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

function paginador($ruta, $q, $pag, $totalPags, $extra = '') {
    if($totalPags <= 1) return '';
    $qs = ($q !== '' ? '&q=' . urlencode($q) : '') . $extra;
    $link = function($p, $label, $activo = false, $disabled = false) use ($ruta, $qs) {
        $base = 'px-3 py-1.5 rounded-lg border text-sm font-medium transition-colors ';
        if($activo) $cls = $base . 'bg-instRojo text-white border-instRojo';
        elseif($disabled) $cls = $base . 'border-gray-200 text-gray-300 pointer-events-none';
        else $cls = $base . 'border-gray-200 text-gray-600 hover:border-instRojo hover:text-instRojo';
        return '<a href="' . e($ruta) . '?pag=' . $p . $qs . '" class="' . $cls . '">' . $label . '</a>';
    };
    $html = '<nav class="flex items-center justify-center gap-1.5 flex-wrap mt-5">';
    $html .= $link(max(1, $pag - 1), '<i class="fas fa-chevron-left text-xs"></i>', false, $pag <= 1);
    $start = max(1, $pag - 2); $end = min($totalPags, $pag + 2);
    if($start > 1) { $html .= $link(1, '1'); if($start > 2) $html .= '<span class="px-1 text-gray-300">&hellip;</span>'; }
    for($i = $start; $i <= $end; $i++) $html .= $link($i, (string)$i, $i == $pag);
    if($end < $totalPags) { if($end < $totalPags - 1) $html .= '<span class="px-1 text-gray-300">&hellip;</span>'; $html .= $link($totalPags, (string)$totalPags); }
    $html .= $link(min($totalPags, $pag + 1), '<i class="fas fa-chevron-right text-xs"></i>', false, $pag >= $totalPags);
    $html .= '</nav>';
    return $html;
}

class Security {
    public static function generateCSRF() {
        if(empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
    public static function verifyCSRF($token) {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
    public static function checkRole($roles) {
        $rol = $_SESSION['rol'] ?? '';
        $esAdmin = !empty($_SESSION['es_admin']) || $rol === 'ADMIN';
        // Pasa si el rol está explícito en la lista, o si la página pide ADMIN y el rol es admin-equivalente
        if(in_array($rol, $roles) || (in_array('ADMIN', $roles) && $esAdmin)) {
            return;
        }
        header('Location: inicio');
        exit;
    }
    public static function esAdmin() {
        return !empty($_SESSION['es_admin']) || ($_SESSION['rol'] ?? '') === 'ADMIN';
    }

    /**
     * Revalida contra la base de datos que la cuenta de la sesion siga activa.
     * Asi, inactivar o suspender a un usuario lo expulsa en su siguiente
     * peticion en vez de esperar a que cierre sesion.
     *
     * $modo 'web' redirige al login con el motivo; 'api' responde 403.
     */
    public static function revalidarSesion($modo = 'web') {
        if(($_SESSION['iniciarSesion'] ?? '') !== 'ok') return;

        if(!class_exists('UsuariosModelo')) {
            require_once __DIR__ . '/../modelos/UsuariosModelo.php';
        }

        $usuario = UsuariosModelo::mdlObtenerUsuarioPorId($_SESSION['id'] ?? 0);
        if(!$usuario) { self::cerrarSesionForzada('eliminado', $modo); }

        $estado = UsuariosModelo::normalizarEstado($usuario['estado'] ?? 'ACTIVO');

        // Suspension con fecha de fin ya vencida: se levanta sola
        if($estado === 'SUSPENDIDO'
            && !empty($usuario['suspendido_hasta'])
            && $usuario['suspendido_hasta'] < date('Y-m-d')) {
            UsuariosModelo::mdlVencerSuspensiones();
            $estado = 'ACTIVO';
        }

        if($estado !== 'ACTIVO') {
            self::cerrarSesionForzada(strtolower($estado), $modo);
        }

        // El rol o el nivel de acceso pueden haber cambiado desde el login
        $_SESSION['estado'] = $estado;
        if(($_SESSION['rol'] ?? '') !== $usuario['rol']) {
            $_SESSION['rol'] = $usuario['rol'];
            $rolInfo = UsuariosModelo::mdlObtenerRol($usuario['rol']);
            $_SESSION['es_admin'] = $rolInfo ? (int)$rolInfo['es_admin'] : 0;
            $_SESSION['servicio'] = $rolInfo && $rolInfo['servicio'] ? $rolInfo['servicio'] : 'GENERAL';
        }
    }

    private static function cerrarSesionForzada($motivo, $modo) {
        $_SESSION = [];
        if(session_status() === PHP_SESSION_ACTIVE) session_destroy();
        if($modo === 'api') {
            http_response_code(403);
            exit($motivo);
        }
        header('Location: ' . URL_APP . 'index.php?sesion=' . urlencode($motivo));
        exit;
    }
}
