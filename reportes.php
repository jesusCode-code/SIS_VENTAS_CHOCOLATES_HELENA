<?php
// 1. Seguridad y Header
include 'includes/seguridad.php'; 
include 'includes/header.php';

// ==================================================
// 2. LÓGICA DE SELECCIÓN DE REPORTE POWER BI
// ==================================================
$reporte_id = isset($_GET['id']) ? (int)$_GET['id'] : 1; // Por defecto carga el reporte 1

switch ($reporte_id) {
    case 1:
        $powerBiLink = "https://app.powerbi.com/view?r=eyJrIjoiNjhhZTNlZmEtZWRkYS00NTZkLTg5MmQtODQyNjJjYjhkOTU0IiwidCI6IjEzODQxZDVmLTk2OGQtNDYyNC1hN2RhLWQ2OGE2MDA2YTg0YSIsImMiOjR9&pageName=c05081c11b662989e000"; 
        break;
        
    case 2:
        // (Uso el mismo link para que pruebes, cámbialo cuando crees el reporte específico)
        $powerBiLink = "https://app.powerbi.com/view?r=eyJrIjoiNjhhZTNlZmEtZWRkYS00NTZkLTg5MmQtODQyNjJjYjhkOTU0IiwidCI6IjEzODQxZDVmLTk2OGQtNDYyNC1hN2RhLWQ2OGE2MDA2YTg0YSIsImMiOjR9&pageName=7326ec8a07e8e502a9b9"; 
        break;
        
    case 3:
        $powerBiLink = "https://app.powerbi.com/view?r=eyJrIjoiNjhhZTNlZmEtZWRkYS00NTZkLTg5MmQtODQyNjJjYjhkOTU0IiwidCI6IjEzODQxZDVmLTk2OGQtNDYyNC1hN2RhLWQ2OGE2MDA2YTg0YSIsImMiOjR9&pageName=c5edd92f65260ec95964"; 
        break;
        
    case 4:
        $powerBiLink = "https://app.powerbi.com/view?r=eyJrIjoiNjhhZTNlZmEtZWRkYS00NTZkLTg5MmQtODQyNjJjYjhkOTU0IiwidCI6IjEzODQxZDVmLTk2OGQtNDYyNC1hN2RhLWQ2OGE2MDA2YTg0YSIsImMiOjR9&pageName=88ef8e0a8046c720dbe6"; 
        break;
        
    case 5:
        $powerBiLink = "https://app.powerbi.com/view?r=eyJrIjoiNjhhZTNlZmEtZWRkYS00NTZkLTg5MmQtODQyNjJjYjhkOTU0IiwidCI6IjEzODQxZDVmLTk2OGQtNDYyNC1hN2RhLWQ2OGE2MDA2YTg0YSIsImMiOjR9&pageName=4e94090ce017a0962005"; 
        break;
        
    default:
        $powerBiLink = "https://app.powerbi.com/view?r=eyJrIjoiNjhhZTNlZmEtZWRkYS00NTZkLTg5MmQtODQyNjJjYjhkOTU0IiwidCI6IjEzODQxZDVmLTk2OGQtNDYyNC1hN2RhLWQ2OGE2MDA2YTg0YSIsImMiOjR9"; 
        break;
}
?>

<div class="container-fluid px-4">
    
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-3 mb-3 border-bottom">

        <div class="btn-toolbar mb-2 mb-md-0">
            <div class="btn-group me-2 shadow-sm">
                <a href="reportes.php?id=1" class="btn btn-outline-primary <?php echo ($reporte_id==1)?'active':''; ?>">
                    <i class="fas fa-home"></i> General
                </a>
                <a href="reportes.php?id=2" class="btn btn-outline-primary <?php echo ($reporte_id==2)?'active':''; ?>">
                    <i class="fas fa-box"></i> Productos
                </a>
                <a href="reportes.php?id=3" class="btn btn-outline-primary <?php echo ($reporte_id==3)?'active':''; ?>">
                    <i class="fas fa-users"></i> Clientes
                </a>
                <a href="reportes.php?id=4" class="btn btn-outline-primary <?php echo ($reporte_id==4)?'active':''; ?>">
                    <i class="fas fa-user-tie"></i> RRHH
                </a>
                <a href="reportes.php?id=5" class="btn btn-outline-primary <?php echo ($reporte_id==5)?'active':''; ?>">
                    <i class="fas fa-chart-line"></i> KPIs
                </a>
            </div>
            
            <button type="button" class="btn btn-dark shadow-sm" onclick="window.print()">
                <i class="fas fa-print"></i> PDF
            </button>
        </div>
    </div>

    <div class="card shadow-lg border-0 mb-4" style="border-radius: 10px; overflow: hidden;">
        <div class="card-body p-0">
            <div class="ratio ratio-16x9">
                <iframe title="<?php echo $titulo_reporte; ?>" 
                        src="<?php echo $powerBiLink; ?>" 
                        frameborder="0" 
                        allowFullScreen="true">
                </iframe>
            </div>
        </div>
    </div>

</div>

<?php include 'includes/footer.php'; ?>
