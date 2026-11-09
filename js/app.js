
function showToast(title, message, type = 'success') {
    const toast = document.getElementById('toast');
    const toastTitle = document.getElementById('toast-title');
    const toastMessage = document.getElementById('toast-message');
    const toastIcon = document.getElementById('toast-icon');

    toastTitle.innerText = title;
    toastMessage.innerText = message;

    if(type === 'success') {
        toast.classList.replace('border-red-500', 'border-green-500');
        if(!toast.classList.contains('border-green-500')) toast.classList.add('border-green-500');
        toastIcon.innerHTML = '<i class="fas fa-check-circle text-green-500 text-xl"></i>';
    } else {
        toast.classList.replace('border-green-500', 'border-red-500');
        if(!toast.classList.contains('border-red-500')) toast.classList.add('border-red-500');
        toastIcon.innerHTML = '<i class="fas fa-exclamation-circle text-red-500 text-xl"></i>';
    }

    toast.classList.remove('translate-x-full', 'opacity-0');
    setTimeout(() => { toast.classList.add('translate-x-full', 'opacity-0'); }, 4000);
}

// Mensajes para los codigos de respuesta del backend
const MENSAJES_RESPUESTA = {
    csrf:                ['Sesion expirada', 'Recargue la pagina e intente de nuevo.'],
    unauthorized:        ['Sesion', 'Debe iniciar sesion de nuevo.'],
    datos:               ['Datos incompletos', 'Revise los campos obligatorios.'],
    pass_corta:          ['Contrasena invalida', 'Debe tener al menos 6 caracteres.'],
    rol_invalido:        ['Rol invalido', 'El rol seleccionado no existe.'],
    dup_doc:             ['Documento duplicado', 'Ya existe un usuario con ese documento.'],
    doc_eliminado:       ['Documento en uso', 'Ese documento pertenece a un usuario eliminado. Restaurelo desde "Ver eliminados".'],
    admin_existe:        ['Administrador unico', 'La bodega ya tiene su administrador. Cambie primero el rol del administrador actual.'],
    admin_requerido:     ['Administrador requerido', 'La bodega no puede quedar sin administrador.'],
    admin_protegido:     ['Administrador protegido', 'El administrador de la bodega no se puede inactivar, suspender ni eliminar.'],
    self_estado:         ['Seguridad', 'No puede cambiar el estado de su propia cuenta.'],
    self_delete:         ['Seguridad', 'No puede eliminarse a si mismo.'],
    no_existe:           ['No encontrado', 'El usuario ya no existe. Recargue la pagina.'],
    eliminado:           ['Usuario eliminado', 'Restaure la cuenta antes de editarla.'],
    estado_invalido:     ['Estado invalido', 'El estado solicitado no es valido.'],
    transicion_invalida: ['Cambio no permitido', 'Ese cambio no es posible desde el estado actual.'],
    sin_cambio:          ['Sin cambios', 'La cuenta ya esta en ese estado.'],
    motivo_requerido:    ['Motivo requerido', 'Debe indicar el motivo del cambio.'],
    fecha_invalida:      ['Fecha invalida', 'Seleccione la fecha desde el calendario.'],
    fecha_pasada:        ['Fecha invalida', 'La fecha de fin de suspension debe ser posterior a hoy.'],
    stock_insuficiente:  ['Error', 'Stock insuficiente para esta operacion.'],
    is_bodega:           ['Seguridad', 'La Bodega Central no puede ser eliminada.'],
    has_dependencies:    ['Error', 'No se puede eliminar: tiene registros asociados.'],
    dup:                 ['Error', 'Ya existe un rol con ese nombre.'],
    protegido:           ['Seguridad', 'Es un rol del sistema y no puede eliminarse.'],
    en_uso:              ['Error', 'Hay usuarios con ese rol asignado.']
};

function mostrarErrorRespuesta(codigo, fallback) {
    const m = MENSAJES_RESPUESTA[codigo];
    showToast(
        m ? m[0] : 'Error',
        m ? m[1] : (fallback || 'No se pudo completar la operacion.'),
        'error'
    );
}

// Motivos por los que el acceso queda bloqueado segun el estado de la cuenta
const MENSAJES_ACCESO = {
    inactivo:   'Su cuenta esta inactiva. Comuniquese con el administrador de la bodega.',
    suspendido: 'Su cuenta esta suspendida temporalmente. Comuniquese con el administrador de la bodega.',
    eliminado:  'Su cuenta fue eliminada. Comuniquese con el administrador de la bodega.',
    csrf:       'Su sesion expiro. Recargue la pagina e intente de nuevo.'
};

function loginAjax() {
    const form = document.getElementById('formLogin');
    const formData = new FormData(form);
    const btn = document.getElementById('btnLogin');
    
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Procesando...';

    fetch('ajax/usuarios.ajax.php', { method: 'POST', body: formData })
    .then(res => res.text())
    .then(data => {
        const result = data.trim();
        if(result === 'ok') {
            window.location = 'inicio';
        } else {
            const alerta = document.getElementById('loginAlert');
            alerta.innerHTML = '<i class="fas fa-exclamation-circle mr-1"></i> ' +
                (MENSAJES_ACCESO[result] || 'Credenciales incorrectas.');
            alerta.classList.remove('hidden');
            btn.disabled = false;
            btn.innerHTML = 'Ingresar al Sistema';
        }
    })
    .catch(() => { btn.disabled = false; btn.innerHTML = 'Ingresar al Sistema'; });
}

function submitFormAjax(formId, actionName, url = 'ajax/productos.ajax.php') {
    const form = document.getElementById(formId);
    const formData = new FormData(form);
    formData.append('action', actionName);
    formData.append('csrf_token', CSRF_TOKEN);

    fetch(url, { method: 'POST', body: formData })
    .then(res => res.text())
    .then(data => {
        const result = data.trim();
        if(result === 'ok') {
            showToast('Exito', 'Operacion realizada correctamente.', 'success');
            setTimeout(() => { window.location.reload(); }, 1000);
        } else {
            mostrarErrorRespuesta(result);
        }
    })
    .catch(() => { showToast('Error', 'Problema de conexion.', 'error'); });
}

function eliminarRegistro(id, action, url) {
    if(!confirm('Esta seguro de eliminar este registro?')) return;
    const formData = new FormData();
    formData.append('idEliminar', id);
    formData.append('action', action);
    formData.append('csrf_token', CSRF_TOKEN);

    fetch(url, { method: 'POST', body: formData })
    .then(res => res.text())
    .then(data => {
        const result = data.trim();
        if(result === 'ok') {
            showToast('Eliminado', 'El registro ha sido borrado.', 'success');
            setTimeout(() => { window.location.reload(); }, 1000);
        } else {
            mostrarErrorRespuesta(result, 'No se pudo eliminar.');
        }
    });
}

function filterTable(tableId, evt) {
    const filter = evt.target.value.toLowerCase();
    const table = document.getElementById(tableId);
    const tr = table.getElementsByTagName("tr");
    for (let i = 1; i < tr.length; i++) {
        let textContent = tr[i].textContent || tr[i].innerText;
        tr[i].style.display = textContent.toLowerCase().indexOf(filter) > -1 ? "" : "none";
    }
}

document.addEventListener('DOMContentLoaded', () => {
    const dropZone = document.getElementById('csvDropZone');
    const fileInput = document.getElementById('csvFileInput');
    if(dropZone && fileInput) {
        dropZone.addEventListener('click', () => fileInput.click());
        fileInput.addEventListener('change', () => {
            if(fileInput.files.length > 0) {
                document.getElementById('csvFileName').textContent = fileInput.files[0].name;
                dropZone.classList.add('border-instRojo', 'bg-instRojo/5');
            }
        });
    }
});

function enviarCSV() {
    const fileInput = document.getElementById('csvFileInput');
    if(!fileInput.files.length) { showToast('Error', 'Seleccione un archivo CSV', 'error'); return; }

    const formData = new FormData();
    formData.append('csvFile', fileInput.files[0]);
    formData.append('tipoCarga', document.getElementById('tipoCargaCSV').value);
    formData.append('action', 'cargaMasivaCSV');
    formData.append('csrf_token', CSRF_TOKEN);

    fetch('ajax/productos.ajax.php', { method: 'POST', body: formData })
    .then(res => res.text())
    .then(data => {
        try {
            const r = JSON.parse(data);
            const div = document.getElementById('csvResultado');
            div.classList.remove('hidden');
            if(r.status === 'ok') {
                let html = `<div class="bg-green-50 border border-green-200 rounded-lg p-4">
                    <p class="font-bold text-green-700 text-sm"><i class="fas fa-check-circle mr-1"></i> Importacion completada</p>
                    <p class="text-xs text-green-600 mt-1">${r.exitos} de ${r.total} registros importados correctamente.</p>`;
                if(r.errores.length > 0) {
                    html += `<div class="mt-2 text-xs text-red-600"><p class="font-bold">Errores:</p>${r.errores.map(e => '<p>'+e+'</p>').join('')}</div>`;
                }
                html += '</div>';
                div.innerHTML = html;
                showToast('Exito', `${r.exitos} productos importados`, 'success');
            } else {
                div.innerHTML = `<div class="bg-red-50 border border-red-200 rounded-lg p-4"><p class="text-red-700 text-sm font-bold"><i class="fas fa-times-circle mr-1"></i> ${r.msg || 'Error en importacion'}</p></div>`;
            }
        } catch(e) { showToast('Error', 'Error procesando respuesta', 'error'); }
    });
}
