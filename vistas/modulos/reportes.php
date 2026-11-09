<div class="mb-8">
    <h1 class="page-title">Reportes</h1>
    <p class="page-subtitle">Genere y descargue reportes del sistema Kardex.</p>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    <div class="card flex flex-col items-center text-center p-0 overflow-hidden">
        <div class="w-full h-2 bg-gradient-to-r from-instRojo to-instNegro"></div>
        <div class="card-body flex flex-col items-center w-full">
            <div class="w-16 h-16 rounded-2xl bg-instRojo/10 flex items-center justify-center mb-5">
                <i class="fas fa-file-csv text-3xl text-instRojo"></i>
            </div>
            <h3 class="text-lg font-bold text-instNegro mb-2">Kardex General</h3>
            <p class="text-sm text-gray-500 mb-6 leading-relaxed">Historial completo de movimientos de su sede en formato CSV compatible con Excel.</p>
            <a href="index.php?ruta=reportes&reporte=kardex" class="btn btn-primary w-full">
                <i class="fas fa-download"></i> Descargar CSV
            </a>
        </div>
    </div>
</div>
<?php
ReportesControlador::ctrDescargarReporte();
?>
