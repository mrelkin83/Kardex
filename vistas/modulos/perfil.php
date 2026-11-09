<div class="max-w-2xl mx-auto">
    <div class="card overflow-hidden p-0">
        <div class="px-8 py-6 bg-gradient-to-r from-instRojo to-instNegro text-white flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold tracking-tight">Ajustes de Perfil</h2>
                <p class="text-instAmarillo text-sm mt-1">Actualice su información personal y contraseña</p>
            </div>
            <i class="fas fa-user-shield text-4xl opacity-20"></i>
        </div>

        <div class="card-body">
            <form id="formPerfil" onsubmit="event.preventDefault(); submitFormAjax('formPerfil', 'actualizarPerfil', 'ajax/usuarios.ajax.php');">
                <input type="hidden" name="idPerfil" value="<?php echo $_SESSION['id']; ?>">

                <div class="space-y-5">
                    <div>
                        <label class="form-label">Nombre Completo</label>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"><i class="fas fa-signature"></i></span>
                            <input type="text" name="nombrePerfil" value="<?php echo e($_SESSION['nombre']); ?>" required class="form-input pl-10">
                        </div>
                    </div>

                    <div>
                        <label class="form-label">Documento <span class="normal-case font-normal text-gray-400">(no editable)</span></label>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"><i class="fas fa-id-card"></i></span>
                            <input type="text" disabled value="<?php echo e($_SESSION['documento'] ?? ''); ?>" class="form-input pl-10 bg-gray-50 text-gray-400 cursor-not-allowed border-gray-100">
                        </div>
                    </div>

                    <div class="border-t border-gray-100 pt-5">
                        <h3 class="text-sm font-bold text-instNegro uppercase tracking-wide mb-4">
                            <i class="fas fa-shield-halved text-instRojo mr-2"></i>Seguridad
                        </h3>
                        <div>
                            <label class="form-label">Nueva Contraseña</label>
                            <div class="relative">
                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"><i class="fas fa-key"></i></span>
                                <input type="password" name="passPerfil" placeholder="Deje en blanco para no cambiar" class="form-input pl-10">
                            </div>
                            <p class="mt-2 text-xs text-gray-400 italic">Use letras, números y símbolos para mayor seguridad.</p>
                        </div>
                    </div>
                </div>

                <div class="mt-8 flex justify-end">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Guardar Cambios
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
