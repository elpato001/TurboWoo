<?php // Footer ?> 
</div> <footer class="mt-5 py-4 text-center text-muted border-top bg-light no-print">
        <div class="container">
            <p class="mb-1">
                &copy; <?php echo date('Y'); ?> 
                <strong><?php echo defined('APP_NAME') ? APP_NAME : 'TurboWoo'; ?></strong>
            </p>
            <small style="font-size: 0.8rem;">
                <i class="fas fa-code-branch"></i> v<?php echo defined('APP_VER') ? APP_VER : '1.0'; ?> 
                &bull; Desarrollado para gestión local
            </small>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script src="assets/js/app.js"></script>

</body>
</html>

<?php
// 3. LIMPIEZA DE RECURSOS
// Si la conexión a la base de datos sigue abierta, la cerramos aquí.
if (isset($conn) && $conn instanceof mysqli) {
    $conn->close();
}
?>