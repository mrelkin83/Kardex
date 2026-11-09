<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kardex UD - Bienestar Universitario</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        instAmarillo: '#ffdf05',
                        instRojo: '#96000b',
                        instNegro: '#0a0a0a',
                    }
                }
            }
        }
    </script>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
    <?php
    if(isset($_SESSION["iniciarSesion"]) && $_SESSION["iniciarSesion"] == "ok"){
        echo '<div class="flex h-screen bg-[#f9fafb]">';
        include "vistas/modulos/menu.php";
        echo '<div class="flex-1 flex flex-col overflow-hidden sidebar-transition">';
        include "vistas/modulos/cabezote.php";
        echo '<main class="flex-1 overflow-x-hidden overflow-y-auto bg-[#f9fafb] p-6 animate-fade-in">';
        if(isset($_GET["ruta"])){
            $rutasValidas = [
                "inicio", "medicamentos", "dispositivos", "bodega",
                "distribucion", "kardex", "entregas", "reportes", "salir",
                "usuarios", "sedes", "auditoria", "perfil", "carga-masiva", "distribucion-masiva", "producto", "inventario-sedes", "mi-inventario"
            ];
            if(in_array($_GET["ruta"], $rutasValidas)){
                include "vistas/modulos/".$_GET["ruta"].".php";
            } else {
                include "vistas/modulos/404.php";
            }
        } else {
            include "vistas/modulos/inicio.php";
        }
        echo '</main></div></div>';
    } else {
        include "vistas/modulos/login.php";
    }
    ?>
    <div id="toast" class="fixed top-5 right-5 z-[100] transform transition-all duration-300 translate-x-full opacity-0">
        <div class="bg-white border-l-4 rounded-lg shadow-xl p-4 flex items-center min-w-[320px]">
            <div id="toast-icon" class="mr-3"></div>
            <div>
                <p id="toast-title" class="font-bold text-sm"></p>
                <p id="toast-message" class="text-sm text-gray-500"></p>
            </div>
        </div>
    </div>
    <script>const CSRF_TOKEN = <?php echo json_encode(isset($_SESSION['csrf_token']) ? $_SESSION['csrf_token'] : ''); ?>;</script>
    <script src="js/app.js"></script>
</body>
</html>
