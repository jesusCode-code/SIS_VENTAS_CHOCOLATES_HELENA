<?php
// Evitar que se muestre si solo hay una página
if ($total_paginas <= 1) {
    return;
}

// Determina el separador de URL (? o &)
$separator = (parse_url($url_base, PHP_URL_QUERY) == NULL) ? '?' : '&';

// Configuración del rango
$rango = 2;
?>

<nav aria-label="Navegación de páginas" class="pagination-container">
    <div class="pagination-wrapper">
        <!-- Info de resultados -->
        <div class="pagination-info-text">
            Mostrando página <strong><?php echo $pagina_actual; ?></strong> de <strong><?php echo $total_paginas; ?></strong>
            <?php if (isset($total_registros)): ?>
                <span class="text-muted ms-2">(<?php echo $total_registros; ?> registros)</span>
            <?php endif; ?>
        </div>

        <!-- Navegación de páginas -->
        <ul class="pagination-modern">
            
            <!-- Botón Primera Página -->
            <?php if ($pagina_actual > 1): ?>
            <li class="pagination-item">
                <a class="pagination-link pagination-first" 
                   href="<?php echo $url_base . $separator; ?>pagina=1"
                   title="Primera página">
                    <i class="bi bi-chevron-double-left"></i>
                </a>
            </li>
            <?php endif; ?>
            
            <!-- Botón Anterior -->
            <li class="pagination-item <?php echo ($pagina_actual <= 1) ? 'disabled' : ''; ?>">
                <?php if ($pagina_actual > 1): ?>
                    <a class="pagination-link pagination-prev" 
                       href="<?php echo $url_base . $separator; ?>pagina=<?php echo $pagina_actual - 1; ?>"
                       title="Página anterior">
                        <i class="bi bi-chevron-left"></i>
                        <span class="d-none d-sm-inline ms-1">Anterior</span>
                    </a>
                <?php else: ?>
                    <span class="pagination-link pagination-disabled">
                        <i class="bi bi-chevron-left"></i>
                        <span class="d-none d-sm-inline ms-1">Anterior</span>
                    </span>
                <?php endif; ?>
            </li>
            
            <!-- LÓGICA INTELIGENTE DE NÚMEROS -->
            <?php
            // Mostrar primera página si estamos lejos
            if ($pagina_actual > ($rango + 2)) {
                echo '<li class="pagination-item">
                        <a class="pagination-link" href="' . $url_base . $separator . 'pagina=1">1</a>
                      </li>';
                echo '<li class="pagination-item">
                        <span class="pagination-ellipsis">...</span>
                      </li>';
            }

            // Calcular rango de páginas
            $inicio_bucle = max(1, $pagina_actual - $rango);
            $fin_bucle = min($total_paginas, $pagina_actual + $rango);

            // Ajustes para equilibrar la visualización
            if ($pagina_actual <= ($rango + 2)) {
                $fin_bucle = min($total_paginas, 5 + ($rango * 2));
            }
            if ($pagina_actual >= ($total_paginas - $rango - 1)) {
                $inicio_bucle = max(1, $total_paginas - (4 + ($rango * 2)));
            }

            // Seguridad extra
            $inicio_bucle = max(1, $inicio_bucle);
            $fin_bucle = min($total_paginas, $fin_bucle);

            // Mostrar páginas del rango
            for ($i = $inicio_bucle; $i <= $fin_bucle; $i++): 
            ?>
                <li class="pagination-item">
                    <?php if ($pagina_actual == $i): ?>
                        <span class="pagination-link pagination-active"><?php echo $i; ?></span>
                    <?php else: ?>
                        <a class="pagination-link" 
                           href="<?php echo $url_base . $separator; ?>pagina=<?php echo $i; ?>"
                           title="Ir a página <?php echo $i; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endif; ?>
                </li>
            <?php endfor; ?>
            
            <?php
            // Mostrar última página si estamos lejos
            if ($pagina_actual < ($total_paginas - $rango - 1)) {
                echo '<li class="pagination-item">
                        <span class="pagination-ellipsis">...</span>
                      </li>';
                echo '<li class="pagination-item">
                        <a class="pagination-link" href="' . $url_base . $separator . 'pagina=' . $total_paginas . '">' . $total_paginas . '</a>
                      </li>';
            }
            ?>
            
            <!-- Botón Siguiente -->
            <li class="pagination-item <?php echo ($pagina_actual >= $total_paginas) ? 'disabled' : ''; ?>">
                <?php if ($pagina_actual < $total_paginas): ?>
                    <a class="pagination-link pagination-next" 
                       href="<?php echo $url_base . $separator; ?>pagina=<?php echo $pagina_actual + 1; ?>"
                       title="Página siguiente">
                        <span class="d-none d-sm-inline me-1">Siguiente</span>
                        <i class="bi bi-chevron-right"></i>
                    </a>
                <?php else: ?>
                    <span class="pagination-link pagination-disabled">
                        <span class="d-none d-sm-inline me-1">Siguiente</span>
                        <i class="bi bi-chevron-right"></i>
                    </span>
                <?php endif; ?>
            </li>

            <!-- Botón Última Página -->
            <?php if ($pagina_actual < $total_paginas): ?>
            <li class="pagination-item">
                <a class="pagination-link pagination-last" 
                   href="<?php echo $url_base . $separator; ?>pagina=<?php echo $total_paginas; ?>"
                   title="Última página">
                    <i class="bi bi-chevron-double-right"></i>
                </a>
            </li>
            <?php endif; ?>

        </ul>

        <!-- Ir a página específica (opcional) -->
        <div class="pagination-goto">
            <form method="GET" action="<?php echo strtok($url_base, '?'); ?>" class="goto-form">
                <?php
                // Mantener parámetros GET existentes
                $query_params = [];
                parse_str(parse_url($url_base, PHP_URL_QUERY), $query_params);
                foreach ($query_params as $key => $value) {
                    if ($key !== 'pagina') {
                        echo '<input type="hidden" name="' . htmlspecialchars($key) . '" value="' . htmlspecialchars($value) . '">';
                    }
                }
                ?>
                <label for="goto-page" class="goto-label">Ir a:</label>
                <input type="number" 
                       id="goto-page" 
                       name="pagina" 
                       class="goto-input" 
                       min="1" 
                       max="<?php echo $total_paginas; ?>" 
                       placeholder="Pág."
                       value="">
                <button type="submit" class="goto-button" title="Ir a la página">
                    <i class="bi bi-arrow-right"></i>
                </button>
            </form>
        </div>
    </div>
</nav>

<style>
/* Pagination Container */
.pagination-container {
    margin-top: 2rem;
    margin-bottom: 1rem;
}

.pagination-wrapper {
    display: flex;
    flex-direction: column;
    gap: 1rem;
    align-items: center;
}

/* Pagination Info Text */
.pagination-info-text {
    color: #666;
    font-size: 0.95rem;
    text-align: center;
}

.pagination-info-text strong {
    color: var(--primary);
    font-weight: 700;
}

/* Pagination Modern */
.pagination-modern {
    display: flex;
    list-style: none;
    padding: 0;
    margin: 0;
    gap: 0.5rem;
    flex-wrap: wrap;
    justify-content: center;
}

.pagination-item {
    margin: 0;
}

/* Pagination Links */
.pagination-link {
    display: flex;
    align-items: center;
    justify-content: center;
    min-width: 40px;
    height: 40px;
    padding: 0 0.75rem;
    background: white;
    border: 2px solid #e0e0e0;
    border-radius: 10px;
    color: var(--primary);
    text-decoration: none;
    font-weight: 600;
    font-size: 0.95rem;
    transition: all 0.2s ease;
    cursor: pointer;
}

.pagination-link:hover:not(.pagination-disabled):not(.pagination-active) {
    background: linear-gradient(135deg, rgba(107, 68, 35, 0.05), rgba(212, 175, 55, 0.05));
    border-color: var(--secondary);
    color: var(--accent);
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

/* Active Page */
.pagination-active {
    background: linear-gradient(135deg, var(--primary), var(--accent));
    border-color: var(--primary);
    color: white;
    cursor: default;
    box-shadow: 0 4px 12px rgba(107, 68, 35, 0.3);
}

/* Disabled State */
.pagination-disabled {
    background: #f8f9fa;
    border-color: #dee2e6;
    color: #adb5bd;
    cursor: not-allowed;
    opacity: 0.6;
}

/* First/Last Buttons */
.pagination-first,
.pagination-last {
    background: linear-gradient(135deg, rgba(212, 175, 55, 0.1), rgba(212, 175, 55, 0.05));
    border-color: rgba(212, 175, 55, 0.3);
}

.pagination-first:hover,
.pagination-last:hover {
    background: var(--secondary);
    border-color: var(--secondary);
    color: var(--dark);
}

/* Prev/Next Buttons */
.pagination-prev,
.pagination-next {
    font-weight: 600;
}

/* Ellipsis */
.pagination-ellipsis {
    display: flex;
    align-items: center;
    justify-content: center;
    min-width: 40px;
    height: 40px;
    color: #adb5bd;
    font-weight: 700;
    font-size: 1.2rem;
    letter-spacing: 2px;
}

/* Go To Page Form */
.pagination-goto {
    display: flex;
    align-items: center;
}

.goto-form {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    background: white;
    padding: 0.5rem;
    border-radius: 10px;
    border: 2px solid #e0e0e0;
}

.goto-label {
    color: #666;
    font-size: 0.9rem;
    font-weight: 600;
    margin: 0;
    white-space: nowrap;
}

.goto-input {
    width: 70px;
    padding: 0.5rem;
    border: 2px solid #e0e0e0;
    border-radius: 8px;
    text-align: center;
    font-weight: 600;
    font-size: 0.9rem;
    transition: border-color 0.2s ease;
}

.goto-input:focus {
    outline: none;
    border-color: var(--secondary);
    box-shadow: 0 0 0 3px rgba(212, 175, 55, 0.1);
}

.goto-button {
    background: linear-gradient(135deg, var(--primary), var(--accent));
    color: white;
    border: none;
    border-radius: 8px;
    width: 36px;
    height: 36px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.2s ease;
}

.goto-button:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(107, 68, 35, 0.3);
}

.goto-button i {
    font-size: 1.1rem;
}

/* Responsive */
@media (max-width: 767px) {
    .pagination-wrapper {
        gap: 1.5rem;
    }
    
    .pagination-modern {
        gap: 0.25rem;
    }
    
    .pagination-link {
        min-width: 36px;
        height: 36px;
        padding: 0 0.5rem;
        font-size: 0.85rem;
    }
    
    .pagination-first,
    .pagination-last {
        display: none;
    }
    
    .pagination-ellipsis {
        min-width: 30px;
        font-size: 1rem;
    }
    
    .goto-form {
        padding: 0.375rem;
    }
    
    .goto-label {
        font-size: 0.85rem;
    }
    
    .goto-input {
        width: 60px;
        padding: 0.375rem;
    }
}

@media (max-width: 576px) {
    .pagination-info-text {
        font-size: 0.85rem;
    }
    
    .pagination-link {
        min-width: 32px;
        height: 32px;
        font-size: 0.8rem;
    }
    
    /* Ocultar algunos números en móvil */
    .pagination-modern li:nth-child(n+8):nth-last-child(n+4) {
        display: none;
    }
}

/* Print Styles */
@media print {
    .pagination-container {
        display: none;
    }
}
</style>

<script>
// Validación del formulario "Ir a página"
document.addEventListener('DOMContentLoaded', function() {
    const gotoForm = document.querySelector('.goto-form');
    if (gotoForm) {
        gotoForm.addEventListener('submit', function(e) {
            const input = this.querySelector('.goto-input');
            const page = parseInt(input.value);
            const max = parseInt(input.max);
            
            if (isNaN(page) || page < 1 || page > max) {
                e.preventDefault();
                alert('Por favor ingresa un número de página válido (1-' + max + ')');
                input.focus();
                return false;
            }
        });
    }
});
</script>