<?php
Security::checkRole(['ADMIN']);

// Las suspensiones con fecha vencida se levantan al abrir el modulo
UsuariosModelo::mdlVencerSuspensiones();

$roles = UsuariosModelo::mdlListarRoles();
$rolesMap = [];
foreach($roles as $r) $rolesMap[$r['nombre']] = $r['etiqueta'];

$verEliminados   = isset($_GET['eliminados']) && $_GET['eliminados'] == '1';
$usuarios        = UsuariosModelo::mdlMostrarUsuarios($verEliminados);
$totalEliminados = UsuariosModelo::mdlContarEliminados();
$sedes           = UsuariosModelo::mdlListarSedes();

// Administrador unico de la bodega
$administradores = UsuariosModelo::mdlListarAdministradores();
$admin           = $administradores ? $administradores[0] : null;
$hayAdmin        = $admin !== null;
$totalAdmins     = count($administradores);
$adminIds        = [];
foreach($administradores as $a) $adminIds[] = (int)$a['id'];

// Presentacion de cada estado
$badgeEstado = [
    'ACTIVO'     => ['badge-verde',    'fa-circle-check'],
    'INACTIVO'   => ['badge-gris',     'fa-user-slash'],
    'SUSPENDIDO' => ['badge-amarillo', 'fa-user-clock'],
    'ELIMINADO'  => ['badge-rojo',     'fa-trash-can']
];

// Transiciones ofrecidas en la fila (espejo de las validadas en el controlador)
$transiciones = [
    'ACTIVO'     => ['SUSPENDIDO', 'INACTIVO', 'ELIMINADO'],
    'INACTIVO'   => ['ACTIVO', 'SUSPENDIDO', 'ELIMINADO'],
    'SUSPENDIDO' => ['ACTIVO', 'INACTIVO', 'ELIMINADO'],
    'ELIMINADO'  => ['ACTIVO']
];
$accionBoton = [
    'ACTIVO'     => ['fa-circle-play', 'Reanudar',  'text-green-600'],
    'INACTIVO'   => ['fa-user-slash',  'Inactivar', 'text-gray-500'],
    'SUSPENDIDO' => ['fa-user-clock',  'Suspender', 'text-amber-500'],
    'ELIMINADO'  => ['fa-trash-alt',   'Eliminar',  'text-red-500']
];
?>

<div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
    <div>
        <h2 class="page-title">Gestión de Usuarios</h2>
        <p class="page-subtitle">Administra las cuentas, su estado y los roles del sistema</p>
    </div>
    <div class="flex items-center gap-2">
        <a href="usuarios<?php echo $verEliminados ? '' : '?eliminados=1'; ?>" class="btn btn-ghost btn-sm">
            <i class="fas <?php echo $verEliminados ? 'fa-eye-slash' : 'fa-trash-can'; ?>"></i>
            <?php echo $verEliminados ? 'Ocultar eliminados' : 'Ver eliminados (' . $totalEliminados . ')'; ?>
        </a>
        <button onclick="document.getElementById('modalUsuario').classList.remove('hidden')" class="btn btn-primary">
            <i class="fas fa-user-plus"></i> Nuevo Usuario
        </button>
    </div>
</div>

<?php if($hayAdmin): ?>
<div class="card mb-6">
    <div class="card-body">
        <div class="flex flex-col sm:flex-row sm:items-center gap-4">
            <div class="w-11 h-11 rounded-xl bg-instRojo/10 flex items-center justify-center shrink-0">
                <i class="fas fa-warehouse text-instRojo"></i>
            </div>
            <div class="flex-1">
                <p class="text-[10px] font-bold text-gray-400 uppercase tracking-[0.15em]">Administrador de la bodega</p>
                <p class="font-bold text-gray-900 text-sm mt-0.5">
                    <?php echo e($admin['nombre']); ?>
                    <span class="font-mono text-gray-400 font-normal">· <?php echo e($admin['documento']); ?></span>
                </p>
                <p class="text-xs text-gray-500 mt-0.5">
                    <?php echo e($rolesMap[$admin['rol']] ?? $admin['rol']); ?> ·
                    <?php echo e($admin['sede'] ?? '—'); ?> —
                    único usuario con acceso a bodega, distribución, usuarios y auditoría.
                </p>
            </div>
            <span class="badge <?php echo $totalAdmins > 1 ? 'badge-amarillo' : 'badge-negro'; ?> shrink-0">
                <i class="fas fa-user-shield mr-1"></i><?php echo $totalAdmins; ?> de 1
            </span>
        </div>
        <?php if($totalAdmins > 1): ?>
        <p class="text-xs text-amber-700 bg-amber-50 border border-amber-200 rounded-lg p-2 mt-3">
            <i class="fas fa-triangle-exclamation mr-1"></i>
            Hay <?php echo $totalAdmins; ?> usuarios con acceso de administrador. Deje solo uno: cambie el rol de los demás o elimínelos
            (inactivarlos no libera el puesto, porque una cuenta inactiva puede reanudarse).
        </p>
        <?php endif; ?>
    </div>
</div>
<?php else: ?>
<div class="bg-amber-50 border border-amber-200 text-amber-800 rounded-xl p-4 mb-6 text-sm">
    <i class="fas fa-triangle-exclamation mr-1"></i>
    <strong>No hay un administrador de bodega asignado.</strong>
    Asigne un rol con acceso de administrador a un usuario para habilitar la gestión de la bodega.
</div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <span class="text-sm font-semibold text-gray-600 uppercase tracking-wider"><i class="fas fa-users mr-2 text-instRojo"></i>Usuarios Registrados</span>
    </div>
    <div class="overflow-x-auto">
        <table class="table-modern">
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Documento</th>
                    <th>Rol</th>
                    <th>Sede</th>
                    <th>Estado</th>
                    <th class="text-center">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($usuarios as $u):
                    $estado      = UsuariosModelo::normalizarEstado($u['estado'] ?? 'ACTIVO');
                    $clases      = $badgeEstado[$estado];
                    $esYo        = (int)$u['id'] === (int)$_SESSION['id'];
                    // Para mostrar el escudo basta el rol; para proteger, que sea admin vigente
                    $esAdminFila = !empty($u['rol_es_admin']) || $u['rol'] === 'ADMIN';
                    $adminVigente = in_array((int)$u['id'], $adminIds, true);
                    // Nadie toca su propia cuenta, ni al unico administrador de bodega
                    $bloqueada   = $esYo || ($adminVigente && $totalAdmins <= 1);
                    $motivo      = $u['estado_motivo'] ?? '';
                    $hasta       = $u['suspendido_hasta'] ?? '';
                    // Solo lo que necesitan los modales: nunca el hash de la contraseña
                    $datosJs = htmlspecialchars(json_encode([
                        'id'           => (int)$u['id'],
                        'nombre'       => $u['nombre'],
                        'documento'    => $u['documento'],
                        'rol'          => $u['rol'],
                        'rol_etiqueta' => $u['rol_etiqueta'] ?? $u['rol'],
                        'sede_id'      => (int)$u['sede_id'],
                        'estado'       => $estado
                    ]), ENT_QUOTES);
                ?>
                    <tr class="<?php echo $estado === 'ELIMINADO' ? 'opacity-60' : ''; ?>">
                        <td class="font-semibold text-gray-900">
                            <?php echo e($u['nombre']); ?>
                            <?php if($esAdminFila): ?>
                                <i class="fas fa-user-shield text-instRojo ml-1 text-[10px]" title="Administrador de la bodega"></i>
                            <?php endif; ?>
                            <?php if($esYo): ?>
                                <span class="text-[10px] text-gray-400 font-normal ml-1">(usted)</span>
                            <?php endif; ?>
                        </td>
                        <td class="font-mono text-gray-600"><?php echo e($u['documento']); ?></td>
                        <td>
                            <span class="badge <?php echo $esAdminFila ? 'badge-negro' : ($u['rol']=='ENFERMERA'?'badge-rojo':'badge-gris'); ?>">
                                <?php echo e($rolesMap[$u['rol']] ?? $u['rol']); ?>
                            </span>
                        </td>
                        <td><?php echo e($u['sede'] ?? '—'); ?></td>
                        <td>
                            <span class="badge <?php echo $clases[0]; ?>">
                                <i class="fas <?php echo $clases[1]; ?> mr-1"></i><?php echo UsuariosModelo::ESTADOS_ETIQUETA[$estado]; ?>
                            </span>
                            <?php if($estado === 'SUSPENDIDO'): ?>
                                <p class="text-[11px] text-amber-600 mt-1">
                                    <?php echo $hasta ? 'Hasta el ' . e($hasta) : 'Suspensión indefinida'; ?>
                                </p>
                            <?php endif; ?>
                            <?php if($estado !== 'ACTIVO' && $motivo !== ''): ?>
                                <p class="text-[11px] text-gray-400 mt-0.5 max-w-[240px] truncate" title="<?php echo e($motivo); ?>">
                                    <?php echo e($motivo); ?>
                                </p>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="flex justify-center gap-1.5">
                                <?php if($estado !== 'ELIMINADO'): ?>
                                <button onclick="llenarEditarUsuario(<?php echo $datosJs; ?>)"
                                        class="btn btn-icon btn-ghost" title="Editar">
                                    <i class="fas fa-edit text-instRojo"></i>
                                </button>
                                <?php endif; ?>

                                <?php if($bloqueada): ?>
                                    <span class="btn btn-icon btn-ghost pointer-events-none opacity-40"
                                          title="<?php echo $esYo
                                              ? 'No puede cambiar el estado de su propia cuenta'
                                              : 'El administrador de la bodega no se puede inactivar, suspender ni eliminar'; ?>">
                                        <i class="fas fa-lock text-gray-400"></i>
                                    </span>
                                <?php else: ?>
                                    <?php foreach($transiciones[$estado] as $destino):
                                        $btn         = $accionBoton[$destino];
                                        $esRestaurar = ($estado === 'ELIMINADO' && $destino === 'ACTIVO');
                                        $icono       = $esRestaurar ? 'fa-rotate-left' : $btn[0];
                                        $etiqueta    = $esRestaurar ? 'Restaurar' : $btn[1];
                                    ?>
                                    <button onclick="abrirModalEstado(<?php echo $datosJs; ?>, '<?php echo $destino; ?>')"
                                            class="btn btn-icon btn-ghost" title="<?php echo $etiqueta; ?>">
                                        <i class="fas <?php echo $icono; ?> <?php echo $btn[2]; ?>"></i>
                                    </button>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php if(empty($usuarios)): ?>
        <div class="empty-state">
            <i class="fas fa-users"></i>
            <p>No hay usuarios para mostrar</p>
        </div>
        <?php endif; ?>
    </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-4 gap-3 mt-4">
    <div class="bg-white border border-gray-100 rounded-xl p-3">
        <p class="text-xs font-bold text-green-600"><i class="fas fa-circle-check mr-1"></i>Activo</p>
        <p class="text-[11px] text-gray-500 mt-1">Puede iniciar sesión y operar con normalidad.</p>
    </div>
    <div class="bg-white border border-gray-100 rounded-xl p-3">
        <p class="text-xs font-bold text-gray-500"><i class="fas fa-user-slash mr-1"></i>Inactivo</p>
        <p class="text-[11px] text-gray-500 mt-1">Bloqueo sin fecha de fin. Se reanuda manualmente.</p>
    </div>
    <div class="bg-white border border-gray-100 rounded-xl p-3">
        <p class="text-xs font-bold text-amber-600"><i class="fas fa-user-clock mr-1"></i>Suspendido</p>
        <p class="text-[11px] text-gray-500 mt-1">Bloqueo temporal. Al vencer la fecha se reactiva solo.</p>
    </div>
    <div class="bg-white border border-gray-100 rounded-xl p-3">
        <p class="text-xs font-bold text-instRojo"><i class="fas fa-trash-can mr-1"></i>Eliminado</p>
        <p class="text-[11px] text-gray-500 mt-1">Borrado lógico: conserva historial y se puede restaurar.</p>
    </div>
</div>

<div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6 mt-10">
    <div>
        <h2 class="page-title">Gestión de Roles</h2>
        <p class="page-subtitle">Crea y edita los roles del sistema</p>
    </div>
    <button onclick="document.getElementById('modalRol').classList.remove('hidden')" class="btn btn-primary">
        <i class="fas fa-user-tag"></i> Nuevo Rol
    </button>
</div>

<div class="card">
    <div class="card-header">
        <span class="text-sm font-semibold text-gray-600 uppercase tracking-wider"><i class="fas fa-user-shield mr-2 text-instRojo"></i>Roles Registrados</span>
    </div>
    <div class="overflow-x-auto">
        <table class="table-modern">
            <thead>
                <tr>
                    <th>Rol</th>
                    <th>Código</th>
                    <th>Servicio (entregas)</th>
                    <th class="text-center">Acceso admin</th>
                    <th class="text-center">Usuarios</th>
                    <th class="text-center">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($roles as $r): ?>
                <tr>
                    <td class="font-semibold text-gray-900">
                        <?php echo e($r['etiqueta']); ?>
                        <?php if($r['protegido']): ?><i class="fas fa-lock text-gray-300 ml-1 text-[10px]" title="Rol del sistema"></i><?php endif; ?>
                    </td>
                    <td class="font-mono text-xs text-gray-500"><?php echo e($r['nombre']); ?></td>
                    <td class="text-gray-600"><?php echo e($r['servicio'] ?: '—'); ?></td>
                    <td class="text-center"><?php echo $r['es_admin'] ? '<span class="badge badge-negro">Sí</span>' : '<span class="text-gray-300">No</span>'; ?></td>
                    <td class="text-center text-gray-600"><?php echo UsuariosModelo::mdlContarUsuariosDeRol($r['nombre']); ?></td>
                    <td>
                        <div class="flex justify-center gap-2">
                            <button onclick="llenarEditarRol(<?php echo htmlspecialchars(json_encode($r), ENT_QUOTES); ?>)" class="btn btn-icon btn-ghost" title="Editar"><i class="fas fa-edit text-instRojo"></i></button>
                            <?php if(!$r['protegido']): ?>
                            <button onclick="eliminarRegistro('<?php echo e($r['nombre']); ?>', 'eliminarRol', 'ajax/usuarios.ajax.php')" class="btn btn-icon btn-ghost" title="Eliminar"><i class="fas fa-trash-alt text-red-500"></i></button>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal: cambio de estado (inactivar / suspender / reanudar / eliminar / restaurar) -->
<div id="modalEstado" class="modal-overlay hidden">
    <div class="modal-content max-w-md">
        <div class="modal-header">
            <h3 class="text-lg font-bold"><i id="iconoEstado" class="fas fa-user-clock mr-2"></i><span id="tituloEstado">Cambiar estado</span></h3>
            <button onclick="cerrarModalEstado()" class="modal-header-close"><i class="fas fa-times"></i></button>
        </div>
        <form id="formEstado" onsubmit="event.preventDefault(); submitFormAjax('formEstado', 'cambiarEstadoUsuario', 'ajax/usuarios.ajax.php');">
            <div class="modal-body space-y-4">
                <input type="hidden" name="idEstado" id="idEstado">
                <input type="hidden" name="nuevoEstado" id="nuevoEstado">

                <div class="bg-gray-50 border border-gray-100 rounded-lg p-3">
                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-[0.15em]">Usuario</p>
                    <p id="nombreEstado" class="font-bold text-gray-900 text-sm mt-0.5"></p>
                    <p id="detalleEstado" class="text-xs text-gray-500"></p>
                </div>

                <p id="descEstado" class="text-sm text-gray-600"></p>

                <div id="boxMotivoEstado">
                    <label class="form-label">Motivo <span id="motivoObligatorio" class="text-instRojo">*</span></label>
                    <textarea name="motivoEstado" id="motivoEstado" rows="2" maxlength="255" class="form-input"
                              placeholder="Ej: licencia no remunerada, retiro del cargo, incumplimiento de protocolo"></textarea>
                    <p class="text-[11px] text-gray-400 mt-1">Queda registrado en la bitácora de auditoría. Máximo 255 caracteres.</p>
                </div>

                <div id="boxHastaEstado" class="hidden">
                    <label class="form-label">Suspender hasta <span class="text-gray-400 normal-case font-normal">(opcional)</span></label>
                    <input type="date" name="suspendidoHasta" id="suspendidoHasta" class="form-input">
                    <p class="text-[11px] text-gray-400 mt-1">Si la deja vacía la suspensión es indefinida. Con fecha, la cuenta se reactiva automáticamente al vencer.</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" onclick="cerrarModalEstado()" class="btn btn-ghost btn-sm">Cancelar</button>
                <button type="submit" id="btnEstado" class="btn btn-primary">Confirmar</button>
            </div>
        </form>
    </div>
</div>

<div id="modalRol" class="modal-overlay hidden">
    <div class="modal-content max-w-md">
        <div class="modal-header">
            <h3 class="text-lg font-bold"><i class="fas fa-user-tag mr-2"></i>Crear Nuevo Rol</h3>
            <button onclick="document.getElementById('modalRol').classList.add('hidden')" class="modal-header-close"><i class="fas fa-times"></i></button>
        </div>
        <form id="formRol" onsubmit="event.preventDefault(); submitFormAjax('formRol', 'crearRol', 'ajax/usuarios.ajax.php');">
            <div class="modal-body space-y-4">
                <div>
                    <label class="form-label">Nombre del Rol</label>
                    <input type="text" name="etiquetaRol" required class="form-input" placeholder="Ej: Nutricionista">
                    <p class="text-[11px] text-gray-400 mt-1">El código interno se genera automáticamente.</p>
                </div>
                <div>
                    <label class="form-label">Servicio para entregas <span class="text-gray-400 normal-case font-normal">(opcional)</span></label>
                    <input type="text" name="servicioRol" class="form-input" placeholder="Ej: NUTRICION">
                </div>
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="esAdminRol" value="1" class="w-4 h-4 accent-instRojo">
                    <span class="text-sm text-gray-700">Acceso de administrador (gestiona bodega, distribución, usuarios)</span>
                </label>
                <?php if($hayAdmin): ?>
                <p class="text-[11px] text-amber-600"><i class="fas fa-circle-info mr-1"></i>La bodega la administra un solo usuario: podrá crear el rol, pero no asignarlo mientras <?php echo e($admin['nombre']); ?> siga siendo el administrador.</p>
                <?php endif; ?>
            </div>
            <div class="modal-footer">
                <button type="button" onclick="document.getElementById('modalRol').classList.add('hidden')" class="btn btn-ghost btn-sm">Cancelar</button>
                <button type="submit" class="btn btn-primary">Guardar Rol</button>
            </div>
        </form>
    </div>
</div>

<div id="modalEditRol" class="modal-overlay hidden">
    <div class="modal-content max-w-md">
        <div class="modal-header">
            <h3 class="text-lg font-bold"><i class="fas fa-user-edit mr-2"></i>Editar Rol</h3>
            <button onclick="document.getElementById('modalEditRol').classList.add('hidden')" class="modal-header-close"><i class="fas fa-times"></i></button>
        </div>
        <form id="formEditRol" onsubmit="event.preventDefault(); submitFormAjax('formEditRol', 'editarRol', 'ajax/usuarios.ajax.php');">
            <div class="modal-body space-y-4">
                <input type="hidden" name="nombreActualRol" id="nombreActualRol">
                <div>
                    <label class="form-label">Código (No editable)</label>
                    <input type="text" id="codigoEditRol" disabled class="form-input bg-gray-100 text-gray-500 cursor-not-allowed">
                </div>
                <div>
                    <label class="form-label">Nombre del Rol</label>
                    <input type="text" name="etiquetaEditRol" id="etiquetaEditRol" required class="form-input">
                </div>
                <div>
                    <label class="form-label">Servicio para entregas <span class="text-gray-400 normal-case font-normal">(opcional)</span></label>
                    <input type="text" name="servicioEditRol" id="servicioEditRol" class="form-input">
                </div>
                <label class="flex items-center gap-2 cursor-pointer" id="boxEsAdminEdit">
                    <input type="checkbox" name="esAdminEditRol" id="esAdminEditRol" value="1" class="w-4 h-4 accent-instRojo">
                    <span class="text-sm text-gray-700">Acceso de administrador</span>
                </label>
                <p id="rolProtegidoAviso" class="hidden text-[11px] text-amber-600"><i class="fas fa-lock mr-1"></i>Rol del sistema: el código y el acceso admin no se pueden cambiar.</p>
            </div>
            <div class="modal-footer">
                <button type="button" onclick="document.getElementById('modalEditRol').classList.add('hidden')" class="btn btn-ghost btn-sm">Cancelar</button>
                <button type="submit" class="btn btn-primary">Guardar Cambios</button>
            </div>
        </form>
    </div>
</div>

<div id="modalUsuario" class="modal-overlay hidden">
    <div class="modal-content max-w-md">
        <div class="modal-header">
            <h3 class="text-lg font-bold"><i class="fas fa-user-plus mr-2"></i>Crear Nuevo Usuario</h3>
            <button onclick="document.getElementById('modalUsuario').classList.add('hidden')" class="modal-header-close">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form id="formUsuario" onsubmit="event.preventDefault(); submitFormAjax('formUsuario', 'crearUsuario', 'ajax/usuarios.ajax.php');">
            <div class="modal-body space-y-4">
                <div>
                    <label class="form-label">Nombre Completo</label>
                    <input type="text" name="nombreUsu" required class="form-input">
                </div>
                <div>
                    <label class="form-label">Documento (User ID)</label>
                    <input type="text" name="docUsu" required class="form-input">
                </div>
                <div>
                    <label class="form-label">Contraseña</label>
                    <input type="password" name="passUsu" required minlength="6" class="form-input">
                    <p class="text-[11px] text-gray-400 mt-1">Mínimo 6 caracteres.</p>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="form-label">Rol</label>
                        <select name="rolUsu" required class="form-input">
                            <?php foreach($roles as $r):
                                $bloqueado = ((int)$r['es_admin'] === 1) && $hayAdmin;
                            ?>
                            <option value="<?php echo e($r['nombre']); ?>" <?php echo $bloqueado ? 'disabled' : ''; ?>>
                                <?php echo e($r['etiqueta']); ?><?php echo $bloqueado ? ' — admin ya asignado' : ''; ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="form-label">Sede</label>
                        <select name="sedeUsu" required class="form-input">
                            <?php foreach($sedes as $s): ?>
                                <option value="<?php echo $s['id']; ?>"><?php echo e($s['nombre']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" onclick="document.getElementById('modalUsuario').classList.add('hidden')" class="btn btn-ghost btn-sm">Cancelar</button>
                <button type="submit" class="btn btn-primary">Guardar Usuario</button>
            </div>
        </form>
    </div>
</div>

<div id="modalEditUsuario" class="modal-overlay hidden">
    <div class="modal-content max-w-md">
        <div class="modal-header">
            <h3 class="text-lg font-bold"><i class="fas fa-user-edit mr-2"></i>Editar Usuario</h3>
            <button onclick="document.getElementById('modalEditUsuario').classList.add('hidden')" class="modal-header-close">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form id="formEditUsuario" onsubmit="event.preventDefault(); submitFormAjax('formEditUsuario', 'editarUsuario', 'ajax/usuarios.ajax.php');">
            <div class="modal-body space-y-4">
                <input type="hidden" name="idEditUsu" id="idEditUsu">
                <div>
                    <label class="form-label">Nombre Completo</label>
                    <input type="text" name="nombreEditUsu" id="nombreEditUsu" required class="form-input">
                </div>
                <div>
                    <label class="form-label">Documento (No editable)</label>
                    <input type="text" id="docEditUsu" disabled class="form-input bg-gray-100 text-gray-500 cursor-not-allowed">
                </div>
                <div>
                    <label class="form-label">Nueva Contraseña</label>
                    <input type="password" name="passEditUsu" minlength="6" placeholder="Dejar en blanco para no cambiar" class="form-input">
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="form-label">Rol</label>
                        <select name="rolEditUsu" id="rolEditUsu" required class="form-input">
                            <?php foreach($roles as $r): ?>
                            <option value="<?php echo e($r['nombre']); ?>" data-admin="<?php echo (int)$r['es_admin']; ?>"><?php echo e($r['etiqueta']); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <p id="avisoRolAdmin" class="hidden text-[11px] text-amber-600 mt-1"><i class="fas fa-circle-info mr-1"></i>Los roles de administrador están bloqueados: la bodega ya tiene su administrador.</p>
                    </div>
                    <div>
                        <label class="form-label">Sede</label>
                        <select name="sedeEditUsu" id="sedeEditUsu" required class="form-input">
                            <?php foreach($sedes as $s): ?>
                                <option value="<?php echo $s['id']; ?>"><?php echo e($s['nombre']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" onclick="document.getElementById('modalEditUsuario').classList.add('hidden')" class="btn btn-ghost btn-sm">Cancelar</button>
                <button type="submit" class="btn btn-primary">Guardar Cambios</button>
            </div>
        </form>
    </div>
</div>

<script>
// ID del administrador de bodega actual (null si todavia no hay)
const ADMIN_BODEGA_ID = <?php echo $hayAdmin ? (int)$admin['id'] : 'null'; ?>;

const ETIQUETA_ESTADO = {
    ACTIVO: 'Activo', INACTIVO: 'Inactivo', SUSPENDIDO: 'Suspendido', ELIMINADO: 'Eliminado'
};

// Configuracion del modal segun el estado destino
const CONFIG_ESTADO = {
    INACTIVO: {
        titulo: 'Inactivar usuario', icono: 'fa-user-slash', boton: 'Inactivar',
        motivo: true, fecha: false,
        desc: 'La cuenta queda bloqueada sin fecha de fin: no podrá iniciar sesión hasta que usted la reanude. Se conserva todo su historial.'
    },
    SUSPENDIDO: {
        titulo: 'Suspender usuario', icono: 'fa-user-clock', boton: 'Suspender',
        motivo: true, fecha: true,
        desc: 'Bloqueo temporal. Si indica una fecha, la cuenta se reactiva sola al vencer; si no, queda suspendida hasta que la reanude.'
    },
    ELIMINADO: {
        titulo: 'Eliminar usuario', icono: 'fa-trash-can', boton: 'Eliminar',
        motivo: true, fecha: false,
        desc: 'Borrado lógico: la cuenta sale del listado y no podrá iniciar sesión, pero se conserva su historial de entregas y auditoría. Podrá restaurarla después.'
    },
    ACTIVO: {
        titulo: 'Reanudar usuario', icono: 'fa-circle-play', boton: 'Reanudar',
        motivo: false, fecha: false,
        desc: 'La cuenta vuelve a estar activa y podrá iniciar sesión de inmediato.'
    }
};

function abrirModalEstado(user, destino) {
    const cfg = Object.assign({}, CONFIG_ESTADO[destino]);
    const estadoActual = (user.estado || 'ACTIVO').toUpperCase();

    if(estadoActual === 'ELIMINADO' && destino === 'ACTIVO') {
        cfg.titulo = 'Restaurar usuario';
        cfg.icono  = 'fa-rotate-left';
        cfg.boton  = 'Restaurar';
        cfg.desc   = 'La cuenta vuelve al listado en estado Activo y podrá iniciar sesión de nuevo.';
    }

    document.getElementById('idEstado').value    = user.id;
    document.getElementById('nuevoEstado').value = destino;
    document.getElementById('tituloEstado').textContent = cfg.titulo;
    document.getElementById('iconoEstado').className    = 'fas ' + cfg.icono + ' mr-2';
    document.getElementById('nombreEstado').textContent = user.nombre;
    document.getElementById('detalleEstado').textContent =
        'Doc. ' + user.documento + ' · ' + (user.rol_etiqueta || user.rol) +
        ' · Estado actual: ' + (ETIQUETA_ESTADO[estadoActual] || estadoActual);
    document.getElementById('descEstado').textContent = cfg.desc;
    document.getElementById('btnEstado').textContent  = cfg.boton;

    const motivo = document.getElementById('motivoEstado');
    motivo.value = '';
    motivo.required = cfg.motivo;
    document.getElementById('motivoObligatorio').classList.toggle('hidden', !cfg.motivo);

    const hasta = document.getElementById('suspendidoHasta');
    hasta.value = '';
    // Solo fechas futuras
    hasta.min = new Date(Date.now() + 86400000).toISOString().slice(0, 10);
    document.getElementById('boxHastaEstado').classList.toggle('hidden', !cfg.fecha);

    document.getElementById('modalEstado').classList.remove('hidden');
}

function cerrarModalEstado() {
    document.getElementById('modalEstado').classList.add('hidden');
}

function llenarEditarUsuario(user) {
    document.getElementById('idEditUsu').value = user.id;
    document.getElementById('nombreEditUsu').value = user.nombre;
    document.getElementById('docEditUsu').value = user.documento;
    document.getElementById('sedeEditUsu').value = user.sede_id;

    // Un solo administrador: los roles admin solo se ofrecen si este usuario ya lo es
    const esElAdmin = ADMIN_BODEGA_ID !== null && Number(user.id) === ADMIN_BODEGA_ID;
    const select = document.getElementById('rolEditUsu');
    let hayBloqueados = false;
    Array.from(select.options).forEach(op => {
        op.disabled = op.dataset.admin === '1' && ADMIN_BODEGA_ID !== null && !esElAdmin;
        if(op.disabled) hayBloqueados = true;
    });
    document.getElementById('avisoRolAdmin').classList.toggle('hidden', !hayBloqueados);

    select.value = user.rol;
    document.getElementById('modalEditUsuario').classList.remove('hidden');
}

function llenarEditarRol(rol) {
    document.getElementById('nombreActualRol').value = rol.nombre;
    document.getElementById('codigoEditRol').value = rol.nombre;
    document.getElementById('etiquetaEditRol').value = rol.etiqueta;
    document.getElementById('servicioEditRol').value = rol.servicio || '';
    var chk = document.getElementById('esAdminEditRol');
    chk.checked = (rol.es_admin == 1);
    // Roles del sistema (protegidos): no se puede cambiar el acceso admin
    var protegido = (rol.protegido == 1);
    chk.disabled = protegido;
    document.getElementById('rolProtegidoAviso').classList.toggle('hidden', !protegido);
    document.getElementById('modalEditRol').classList.remove('hidden');
}
</script>
