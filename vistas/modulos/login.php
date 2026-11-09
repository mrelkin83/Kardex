<?php
// Motivo por el que la sesion fue cerrada desde el servidor (ver Security::revalidarSesion)
$motivosSesion = [
    'inactivo'   => 'Su cuenta fue inactivada por el administrador de la bodega.',
    'suspendido' => 'Su cuenta fue suspendida por el administrador de la bodega.',
    'eliminado'  => 'Su cuenta fue eliminada por el administrador de la bodega.'
];
$motivoSesion = $motivosSesion[$_GET['sesion'] ?? ''] ?? null;
?>
<div class="min-h-screen flex items-center justify-center relative overflow-hidden" style="background: linear-gradient(135deg, #0a0a0a 0%, #96000b 100%);">
    <div class="absolute inset-0 opacity-[0.03]" style="background-image: url('img/bienestar.jpeg'); background-size: cover; background-position: center;"></div>
    <div class="relative z-10 w-full max-w-md mx-4 animate-fade-in-up">
        <div class="bg-white rounded-2xl shadow-2xl overflow-hidden">
            <div class="px-8 pt-10 pb-6 text-center">
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-instRojo mb-5 shadow-lg shadow-instRojo/20">
                    <i class="fas fa-hospital text-2xl text-instAmarillo"></i>
                </div>
                <h2 class="text-2xl font-extrabold text-instNegro tracking-tight">Kardex <span class="text-instRojo">UD</span></h2>
                <p class="text-gray-400 text-sm mt-1 font-medium">Bienestar Universitario - Enfermeria</p>
            </div>
            <?php if($motivoSesion): ?>
            <div class="mx-8 mb-4 bg-amber-50 border border-amber-200 text-amber-800 p-3 rounded-lg text-sm font-medium">
                <i class="fas fa-user-lock mr-1"></i> <?php echo e($motivoSesion); ?>
                <span class="block text-xs font-normal mt-1">Comuníquese con el administrador para reactivar su acceso.</span>
            </div>
            <?php endif; ?>
            <form id="formLogin" onsubmit="event.preventDefault(); loginAjax();" class="px-8 pb-8 space-y-4">
                <input type="hidden" name="csrf_token" value="<?php echo Security::generateCSRF(); ?>">
                <div>
                    <label class="form-label">Documento</label>
                    <div class="relative">
                        <i class="fas fa-user absolute left-3 top-1/2 -translate-y-1/2 text-gray-300 text-sm"></i>
                        <input class="form-input pl-9" id="ingDocumento" type="text" name="ingDocumento" required placeholder="Ingrese su documento">
                    </div>
                </div>
                <div>
                    <label class="form-label">Contrasena</label>
                    <div class="relative">
                        <i class="fas fa-lock absolute left-3 top-1/2 -translate-y-1/2 text-gray-300 text-sm"></i>
                        <input class="form-input pl-9" id="ingPassword" type="password" name="ingPassword" required placeholder="Ingrese su contrasena">
                    </div>
                </div>
                <button id="btnLogin" class="btn btn-primary w-full btn-lg mt-2" type="submit">
                    Ingresar al Sistema
                </button>
                <div id="loginAlert" class="hidden bg-red-50 border border-red-100 text-red-600 p-3 rounded-lg text-sm font-medium">
                    <i class="fas fa-exclamation-circle mr-1"></i> Credenciales incorrectas.
                </div>
            </form>
        </div>
        <p class="text-center text-white/20 text-xs mt-6 font-medium">&copy; <?php echo date('Y'); ?> Universidad Distrital</p>
    </div>
</div>
