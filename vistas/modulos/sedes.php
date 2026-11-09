<?php Security::checkRole(['ADMIN']); ?>

<div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
    <div>
        <h2 class="page-title">Sedes Universitarias</h2>
        <p class="page-subtitle">Administra las sedes disponibles en el sistema</p>
    </div>
    <button onclick="document.getElementById('modalSede').classList.remove('hidden')" class="btn btn-primary">
        <i class="fas fa-plus"></i> Nueva Sede
    </button>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
    <?php
    $sedes = UsuariosModelo::mdlListarSedes();
    foreach($sedes as $s): ?>
        <div class="card group">
            <div class="card-body">
                <div class="flex items-start justify-between mb-4">
                    <div class="w-11 h-11 rounded-lg bg-instRojo/10 flex items-center justify-center">
                        <i class="fas fa-building text-lg text-instRojo"></i>
                    </div>
                    <div class="flex gap-1.5">
                        <button
                            data-id="<?php echo e($s['id']); ?>" data-nombre="<?php echo e($s['nombre']); ?>"
                            onclick="llenarEditarSede(this.dataset.id, this.dataset.nombre)"
                            class="btn btn-icon btn-ghost" title="Editar">
                            <i class="fas fa-edit text-instRojo"></i>
                        </button>
                        <?php if($s['id'] != 1): ?>
                        <button
                            onclick="eliminarRegistro(<?php echo $s['id']; ?>, 'eliminarSede', 'ajax/usuarios.ajax.php')"
                            class="btn btn-icon btn-ghost" title="Eliminar">
                            <i class="fas fa-trash-alt text-red-500"></i>
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
                <span class="badge badge-gris mb-2">ID: <?php echo e($s['id']); ?></span>
                <h3 class="text-lg font-bold text-gray-900 leading-tight"><?php echo e($s['nombre']); ?></h3>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<div id="modalSede" class="modal-overlay hidden">
    <div class="modal-content max-w-sm">
        <div class="modal-header">
            <h3 class="text-lg font-bold"><i class="fas fa-plus-circle mr-2"></i>Registrar Nueva Sede</h3>
            <button onclick="document.getElementById('modalSede').classList.add('hidden')" class="modal-header-close">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form id="formSede" onsubmit="event.preventDefault(); submitFormAjax('formSede', 'crearSede', 'ajax/usuarios.ajax.php');">
            <div class="modal-body space-y-4">
                <div>
                    <label class="form-label">Nombre de la Sede</label>
                    <input type="text" name="nombreSede" required class="form-input" placeholder="Ej: Facultad de Medicina">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" onclick="document.getElementById('modalSede').classList.add('hidden')" class="btn btn-ghost btn-sm">Cancelar</button>
                <button type="submit" class="btn btn-primary">Crear Sede</button>
            </div>
        </form>
    </div>
</div>

<div id="modalEditSede" class="modal-overlay hidden">
    <div class="modal-content max-w-sm">
        <div class="modal-header">
            <h3 class="text-lg font-bold"><i class="fas fa-edit mr-2"></i>Editar Sede</h3>
            <button onclick="document.getElementById('modalEditSede').classList.add('hidden')" class="modal-header-close">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form id="formEditSede" onsubmit="event.preventDefault(); submitFormAjax('formEditSede', 'editarSede', 'ajax/usuarios.ajax.php');">
            <div class="modal-body space-y-4">
                <input type="hidden" name="idEditSede" id="idEditSede">
                <div>
                    <label class="form-label">Nombre de la Sede</label>
                    <input type="text" name="nombreEditSede" id="nombreEditSede" required class="form-input">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" onclick="document.getElementById('modalEditSede').classList.add('hidden')" class="btn btn-ghost btn-sm">Cancelar</button>
                <button type="submit" class="btn btn-primary">Guardar Cambios</button>
            </div>
        </form>
    </div>
</div>

<script>
function llenarEditarSede(id, nombre) {
    document.getElementById('idEditSede').value = id;
    document.getElementById('nombreEditSede').value = nombre;
    document.getElementById('modalEditSede').classList.remove('hidden');
}
</script>