<?php ?>

<div class="historial-container">
    <div class="historial-header">
        <div class="header-content">
            <h5 class="header-title">
                <i class="bi bi-clock-history me-2"></i>
                Tu Historial de Compras
            </h5>
            <span class="pagination-badge">
                Página <?php echo $pagina_actual; ?> de <?php echo $total_paginas; ?>
            </span>
        </div>
        <?php if ($total_registros > 0): ?>
        <div class="stats-summary">
            <div class="stat-item">
                <i class="bi bi-bag-check"></i>
                <div>
                    <span class="stat-number"><?php echo $total_registros; ?></span>
                    <span class="stat-label">Compras totales</span>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <?php if ($total_registros == 0): ?>
        <!-- Estado Vacío -->
        <div class="empty-state-historial">
            <div class="empty-icon">
                <i class="bi bi-bag-x"></i>
            </div>
            <h4>No tienes compras aún</h4>
            <p>Explora nuestro catálogo y realiza tu primera compra</p>
            <a href="tienda.php" class="btn btn-explore">
                <i class="bi bi-shop me-2"></i>Ir a la Tienda
            </a>
        </div>
    <?php else: ?>
        <!-- Lista de Compras -->
        <div class="purchases-list">
            <?php foreach ($ventas as $row): ?>
                <?php
                $estado_clase = ($row['ESTADO'] == '1') ? 'status-completed' : 'status-cancelled';
                $estado_texto = ($row['ESTADO'] == '1') ? 'Completada' : 'Anulada';
                $estado_icono = ($row['ESTADO'] == '1') ? 'bi-check-circle-fill' : 'bi-x-circle-fill';
                
                $comprobante_clase = '';
                $comprobante_texto = '';
                $comprobante_icono = '';
                
                if ($row['TipoComprobante'] == 'Factura') {
                    $comprobante_clase = 'doc-factura';
                    $comprobante_texto = 'Factura';
                    $comprobante_icono = 'bi-file-earmark-text';
                } elseif ($row['TipoComprobante'] == 'Boleta') {
                    $comprobante_clase = 'doc-boleta';
                    $comprobante_texto = 'Boleta';
                    $comprobante_icono = 'bi-receipt';
                } else {
                    $comprobante_clase = 'doc-pending';
                    $comprobante_texto = 'Pendiente';
                    $comprobante_icono = 'bi-hourglass-split';
                }
                
                $id_collapse = "detalle-" . $row['IDVENTA'];
                ?>
                
                <div class="purchase-card">
                    <!-- Header de la compra -->
                    <div class="purchase-header" data-bs-toggle="collapse" data-bs-target="#<?php echo $id_collapse; ?>" role="button">
                        <div class="purchase-info">
                            <div class="purchase-id">
                                <i class="bi bi-receipt-cutoff me-2"></i>
                                <span>Pedido #<?php echo $row['IDVENTA']; ?></span>
                            </div>
                            <div class="purchase-date">
                                <i class="bi bi-calendar3 me-1"></i>
                                <?php echo $row['FECHAVENTA']->format('d/m/Y'); ?>
                            </div>
                        </div>
                        
                        <div class="purchase-details">
                            <div class="purchase-amount">
                                <span class="amount-label">Total</span>
                                <span class="amount-value">S/ <?php echo number_format($row['TOTAL'], 2); ?></span>
                            </div>
                            
                            <div class="purchase-status">
                                <span class="status-badge <?php echo $estado_clase; ?>">
                                    <i class="<?php echo $estado_icono; ?> me-1"></i>
                                    <?php echo $estado_texto; ?>
                                </span>
                            </div>
                        </div>
                        
                        <div class="purchase-actions">
                            <div class="document-badge <?php echo $comprobante_clase; ?>">
                                <i class="<?php echo $comprobante_icono; ?> me-1"></i>
                                <?php echo $comprobante_texto; ?>
                                <?php if ($row['NumeroComprobante']): ?>
                                    <small class="d-block"><?php echo htmlspecialchars($row['NumeroComprobante']); ?></small>
                                <?php endif; ?>
                            </div>
                            
                            <button class="btn-expand" type="button">
                                <i class="bi bi-chevron-down"></i>
                            </button>
                        </div>
                    </div>
                    
                    <!-- Detalle expandible -->
                    <div class="collapse purchase-detail" id="<?php echo $id_collapse; ?>">
                        <div class="detail-container">
                            <h6 class="detail-title">
                                <i class="bi bi-box-seam me-2"></i>
                                Productos de tu pedido
                            </h6>
                            
                            <div class="products-table-wrapper">
                                <table class="products-table">
                                    <thead>
                                        <tr>
                                            <th>Producto</th>
                                            <th class="text-center">Cantidad</th>
                                            <th class="text-end">Precio Unit.</th>
                                            <th class="text-end">Subtotal</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (isset($detalles[$row['IDVENTA']])): ?>
                                            <?php foreach ($detalles[$row['IDVENTA']] as $producto): ?>
                                            <tr>
                                                <td class="product-name">
                                                    <i class="bi bi-box me-2 text-muted"></i>
                                                    <?php echo htmlspecialchars($producto['NOMPRODUCTO']); ?>
                                                </td>
                                                <td class="text-center">
                                                    <span class="quantity-badge">
                                                        <?php echo $producto['CANTIDAD']; ?>x
                                                    </span>
                                                </td>
                                                <td class="text-end">S/ <?php echo number_format($producto['PRECIO_UNITARIO'], 2); ?></td>
                                                <td class="text-end fw-bold">S/ <?php echo number_format($producto['SUBTOTAL'], 2); ?></td>
                                            </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                            
                            <div class="purchase-summary">
                                <div class="summary-row">
                                    <span>Subtotal:</span>
                                    <span>S/ <?php echo number_format($row['SUBTOTAL'], 2); ?></span>
                                </div>
                                <div class="summary-row">
                                    <span>IGV (18%):</span>
                                    <span>S/ <?php echo number_format($row['IGV'], 2); ?></span>
                                </div>
                                <div class="summary-row summary-total">
                                    <span>Total:</span>
                                    <span>S/ <?php echo number_format($row['TOTAL'], 2); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Paginación -->
        <div class="pagination-container">
            <?php 
            $url_base = "mi_cuenta.php?seccion=historial&"; 
            include 'includes/paginador.php'; 
            ?>
        </div>
        
    <?php endif; ?>
</div>

<link rel="stylesheet" href="css/mi_cuenta_historial.css">

<script>
// Animación smooth para el colapso
document.addEventListener('DOMContentLoaded', function() {
    const collapseButtons = document.querySelectorAll('[data-bs-toggle="collapse"]');
    
    collapseButtons.forEach(button => {
        button.addEventListener('click', function() {
            const icon = this.querySelector('.btn-expand i');
            if (icon) {
                setTimeout(() => {
                    icon.style.transform = icon.style.transform === 'rotate(180deg)' ? 'rotate(0deg)' : 'rotate(180deg)';
                }, 50);
            }
        });
    });
});
</script>