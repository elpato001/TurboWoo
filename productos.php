<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/header.php';

// 1. SEGURIDAD: Solo Admins y Editores
verificar_permiso(['admin', 'editor']);

// ---------------------------------------------------------
// 2. LÓGICA DE ACCIONES (POST)
// ---------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    // ACCIÓN A: GUARDADO RÁPIDO
    if (isset($_POST['accion']) && $_POST['accion'] === 'guardar_rapido') {
        $id = intval($_POST['id_local']);
        $precio = intval($_POST['precio']);
        $stock = intval($_POST['stock']);
        // Si se guarda algo marcado para borrar, asumimos que se quiere cancelar el borrado
        $stmt = $conn->prepare("UPDATE productos_local SET precio_regular=?, stock=?, cambio_pendiente=1, eliminar_pendiente=0 WHERE id=?");
        $stmt->bind_param("iii", $precio, $stock, $id);
        if ($stmt->execute()) {
            echo "<script>window.location.href='productos.php?msg=Actualizado';</script>";
            exit;
        }
    }

    // ACCIÓN B: ELIMINAR (Lógica Diferida)
    if (isset($_POST['accion']) && $_POST['accion'] === 'eliminar_local') {
        $id_borrar = intval($_POST['id_to_delete']);
        
        // Consultar estado actual
        $query_info = $conn->query("SELECT es_nuevo, ruta_imagen_local FROM productos_local WHERE id=$id_borrar");
        if ($row_info = $query_info->fetch_assoc()) {
            
            // CASO 1: Es un borrador local (Nunca subido) -> BORRADO INMEDIATO
            if ($row_info['es_nuevo'] == 1) {
                $ruta_foto = $row_info['ruta_imagen_local'];
                if (!empty($ruta_foto) && file_exists($ruta_foto)) unlink($ruta_foto); 
                $conn->query("DELETE FROM productos_local WHERE id=$id_borrar");
                $msg = "Borrador eliminado permanentemente.";
            } 
            // CASO 2: Ya existe en WooCommerce -> MARCAR PARA BORRAR
            else {
                $conn->query("UPDATE productos_local SET eliminar_pendiente = 1, cambio_pendiente = 1 WHERE id=$id_borrar");
                $msg = "Producto marcado para eliminar. Se borrará de la web al Sincronizar.";
            }
        }
        echo "<script>window.location.href='productos.php?msg=$msg';</script>";
        exit;
    }

    // ACCIÓN C: RESTAURAR (Deshacer eliminación)
    if (isset($_POST['accion']) && $_POST['accion'] === 'restaurar_local') {
        $id_restaurar = intval($_POST['id_to_restore']);
        $conn->query("UPDATE productos_local SET eliminar_pendiente = 0, cambio_pendiente = 0 WHERE id=$id_restaurar");
        echo "<script>window.location.href='productos.php?msg=Producto restaurado';</script>";
        exit;
    }
}

// ---------------------------------------------------------
// 3. CONSULTAS
// ---------------------------------------------------------
$busqueda = isset($_GET['q']) ? $conn->real_escape_string($_GET['q']) : '';
$pagina_actual = isset($_GET['pag']) ? (int)$_GET['pag'] : 1;
$items_por_pagina = 20;
$offset = ($pagina_actual - 1) * $items_por_pagina;

$sql_base = "FROM productos_local WHERE 1=1";
if (!empty($busqueda)) $sql_base .= " AND (nombre LIKE '%$busqueda%' OR sku LIKE '%$busqueda%')";

$total_items = $conn->query("SELECT COUNT(*) as total $sql_base")->fetch_assoc()['total'];
$total_paginas = ceil($total_items / $items_por_pagina);

// Ordenar: Primero los pendientes de borrar (para verlos facil), luego nuevos, luego nombre
$sql_data = "SELECT * $sql_base ORDER BY eliminar_pendiente DESC, es_nuevo DESC, nombre ASC LIMIT $offset, $items_por_pagina";
$resultado = $conn->query($sql_data);

$pendientes_total = $conn->query("SELECT COUNT(*) as total FROM productos_local WHERE cambio_pendiente = 1")->fetch_assoc()['total'];
?>

<div class="row mb-4 align-items-center">
    <div class="col-md-6">
        <h2 class="fw-bold"><i class="fas fa-tags text-info"></i> Editor Local</h2>
    </div>
    <div class="col-md-6 text-end">
        <a href="crear_local.php" class="btn btn-primary me-2"><i class="fas fa-plus"></i> Nuevo</a>
        <?php if ($pendientes_total > 0): ?>
            <a href="sync_subida.php" class="btn btn-warning fw-bold shadow-sm animate-pulse">
                <i class="fas fa-cloud-upload-alt"></i> SINCRONIZAR (<?php echo $pendientes_total; ?>)
            </a>
        <?php else: ?>
            <button class="btn btn-success disabled"><i class="fas fa-check-circle"></i> Al día</button>
        <?php endif; ?>
    </div>
</div>

<div class="card shadow-sm mb-4 border-0"><div class="card-body p-2">
    <form method="GET" class="row g-1">
        <div class="col-md-10"><input type="text" name="q" class="form-control border-0 shadow-none" placeholder="Buscar..." value="<?php echo e($busqueda); ?>"></div>
        <div class="col-md-2"><button type="submit" class="btn btn-primary w-100">Buscar</button></div>
    </form>
</div></div>

<div class="card shadow-sm border-0 mb-5">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 table-products">
            <thead class="bg-light border-bottom">
                <tr>
                    <th width="40%" class="ps-4">Producto</th>
                    <th width="15%">Precio</th>
                    <th width="10%">Stock</th>
                    <th width="15%">Estado</th>
                    <th width="20%" class="text-end pe-4">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($resultado->num_rows > 0): ?>
                    <?php while($row = $resultado->fetch_assoc()): ?>
                        <?php 
                            // ESTILOS DE FILA
                            $clase_fila = '';
                            if($row['eliminar_pendiente'] == 1) $clase_fila = 'bg-danger bg-opacity-10'; 
                            elseif($row['cambio_pendiente'] == 1) $clase_fila = 'bg-warning-subtle'; 
                            
                            $form_id = "form-row-" . $row['id'];
                        ?>
                        <tr class="<?php echo $clase_fila; ?>">
                            
                            <td class="ps-4">
                                <div class="d-flex align-items-center">
                                    
                                    <?php 
                                    $img_src = '';
                                    $es_local = false;
                                    if ($row['es_nuevo'] == 1 && !empty($row['ruta_imagen_local']) && file_exists($row['ruta_imagen_local'])) {
                                        $img_src = $row['ruta_imagen_local'];
                                        $es_local = true;
                                    } elseif (!empty($row['imagen_url'])) {
                                        $img_src = $row['imagen_url'];
                                    }

                                    if(!empty($img_src)): ?>
                                        <div class="position-relative me-3">
                                            <img src="<?php echo e($img_src); ?>" class="rounded shadow-sm border" style="width: 48px; height: 48px; object-fit: cover;">
                                            <?php if($es_local): ?>
                                                <span class="position-absolute top-0 start-100 translate-middle p-1 bg-primary border border-light rounded-circle" title="Foto Local">
                                                    <span class="visually-hidden">Local</span>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    <?php else: ?>
                                        <div class="rounded me-3 bg-secondary bg-opacity-25 text-secondary d-flex align-items-center justify-content-center shadow-sm border" style="width: 48px; height: 48px;">
                                            <i class="fas fa-camera fa-lg"></i>
                                        </div>
                                    <?php endif; ?>

                                    <div>
                                        <div class="fw-bold text-dark">
                                            <?php echo e($row['nombre']); ?>
                                            <?php if($row['eliminar_pendiente'] == 1): ?>
                                                <span class="badge bg-danger ms-2">A ELIMINAR</span>
                                            <?php endif; ?>
                                        </div>
                                        <small class="text-muted"><?php echo e($row['sku']); ?></small>
                                    </div>
                                </div>
                            </td>

                            <form method="POST" id="<?php echo $form_id; ?>">
                                <input type="hidden" name="accion" value="guardar_rapido">
                                <input type="hidden" name="id_local" value="<?php echo $row['id']; ?>">
                                <td><input type="number" name="precio" class="form-control input-edit fw-bold" value="<?php echo $row['precio_regular']; ?>" <?php echo ($row['eliminar_pendiente'] == 1) ? 'disabled' : ''; ?>></td>
                                <td><input type="number" name="stock" class="form-control input-edit text-center" value="<?php echo $row['stock']; ?>" <?php echo ($row['eliminar_pendiente'] == 1) ? 'disabled' : ''; ?>></td>
                            </form>

                            <td>
                                <?php if($row['eliminar_pendiente'] == 1): ?>
                                    <span class="text-danger fw-bold"><i class="fas fa-trash"></i> Pendiente</span>
                                <?php elseif($row['es_nuevo'] == 1): ?>
                                    <span class="badge bg-primary">Nuevo</span>
                                <?php elseif($row['cambio_pendiente'] == 1): ?>
                                    <span class="badge bg-warning text-dark">Editado</span>
                                <?php else: ?>
                                    <span class="text-success small"><i class="fas fa-check"></i> OK</span>
                                <?php endif; ?>
                            </td>

                            <td class="text-end pe-4">
                                <div class="d-flex justify-content-end gap-2">
                                    
                                    <?php if($row['eliminar_pendiente'] == 1): ?>
                                        <form method="POST">
                                            <input type="hidden" name="accion" value="restaurar_local">
                                            <input type="hidden" name="id_to_restore" value="<?php echo $row['id']; ?>">
                                            <button type="submit" class="btn btn-success btn-sm shadow-sm" title="Cancelar eliminación">
                                                <i class="fas fa-undo"></i> Restaurar
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        
                                        <button type="submit" form="<?php echo $form_id; ?>" class="btn btn-primary btn-sm shadow-sm" title="Guardar Rápido">
                                            <i class="fas fa-save"></i>
                                        </button>
                                        
                                        <a href="editar_local.php?id=<?php echo $row['id']; ?>" class="btn btn-warning btn-sm shadow-sm text-dark" title="Editar Completo">
                                            <i class="fas fa-pencil-alt"></i>
                                        </a>

                                        <form method="POST" onsubmit="return confirm('¿Eliminar producto? Se borrará de la web al sincronizar.');">
                                            <input type="hidden" name="accion" value="eliminar_local">
                                            <input type="hidden" name="id_to_delete" value="<?php echo $row['id']; ?>">
                                            <button type="submit" class="btn btn-danger btn-sm shadow-sm" title="Eliminar">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </form>

                                    <?php endif; ?>

                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php if($total_paginas > 1): ?>
    <div class="card-footer bg-white d-flex justify-content-center py-3 border-top">
        <nav>
            <ul class="pagination pagination-sm mb-0 shadow-sm">
                <li class="page-item <?php echo ($pagina_actual <= 1) ? 'disabled' : ''; ?>">
                    <a class="page-link" href="?pag=<?php echo $pagina_actual - 1; ?>&q=<?php echo urlencode($busqueda); ?>"><i class="fas fa-chevron-left"></i></a>
                </li>
                <?php for($i = 1; $i <= $total_paginas; $i++): ?>
                    <li class="page-item <?php echo ($i == $pagina_actual) ? 'active' : ''; ?>">
                        <a class="page-link" href="?pag=<?php echo $i; ?>&q=<?php echo urlencode($busqueda); ?>"><?php echo $i; ?></a>
                    </li>
                <?php endfor; ?>
                <li class="page-item <?php echo ($pagina_actual >= $total_paginas) ? 'disabled' : ''; ?>">
                    <a class="page-link" href="?pag=<?php echo $pagina_actual + 1; ?>&q=<?php echo urlencode($busqueda); ?>"><i class="fas fa-chevron-right"></i></a>
                </li>
            </ul>
        </nav>
    </div>
    <?php endif; ?>
</div>
<?php require_once 'includes/footer.php'; ?>