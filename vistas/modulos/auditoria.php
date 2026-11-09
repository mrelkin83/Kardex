<?php Security::checkRole(['ADMIN']); ?>
<div class="animate-fade-in-up">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="page-title">Bitácora de Auditoría</h1>
            <p class="page-subtitle">Registro de acciones de los usuarios en el sistema</p>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <div class="flex items-center gap-3">
                <i class="fas fa-shield-alt text-instRojo"></i>
                <span class="font-semibold text-sm text-gray-700">Registro de Actividad</span>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="table-modern">
                <thead>
                    <tr>
                        <th>Fecha/Hora</th>
                        <th>Usuario</th>
                        <th>Acción Realizada</th>
                        <th>Módulo</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $logs = UsuariosModelo::mdlListarAuditoria();
                    foreach($logs as $l):
                    ?>
                    <tr>
                        <td class="whitespace-nowrap font-mono text-xs text-gray-500"><?php echo e($l['fecha_hora']); ?></td>
                        <td class="font-bold text-gray-900"><?php echo e($l['nombre']); ?></td>
                        <td><?php echo e($l['accion']); ?></td>
                        <td><span class="badge badge-gris"><?php echo e($l['tabla']); ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php if(empty($logs)): ?>
            <div class="empty-state">
                <i class="fas fa-clipboard-list"></i>
                <p>No hay registros de auditoría</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
