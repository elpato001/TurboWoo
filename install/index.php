<?php if (file_exists('../includes/config.php')) die("Sistema ya instalado."); ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instalación TurboWoo v2.2</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { background: #f0f2f5; padding: 20px; }
        .install-card { max-width: 800px; margin: 0 auto; background: white; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); overflow: hidden; }
        .card-header { background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%); color: white; padding: 2rem; text-align: center; }
        /* Estilo para inputs de claves API para que se diferencien */
        .input-code { font-family: 'Courier New', Courier, monospace; font-weight: bold; color: #2a5298; }
    </style>
</head>
<body>

    <div class="install-card">
        <div class="card-header">
            <h1><i class="fas fa-bolt"></i> TurboWoo</h1>
            <p class="mb-0">Configuración Inicial</p>
        </div>
        
        <div class="card-body p-4">
            <div class="alert alert-light border small mb-4">
                <i class="fas fa-eye"></i> <strong>Nota:</strong> Las claves de API se muestran en texto plano para facilitar la verificación. Asegúrate de no dejar espacios en blanco al inicio o al final.
            </div>

            <form id="formInstalacion" action="setup.php" method="POST">
                
                <h5 class="text-primary mt-3">1. Base de Datos Local</h5>
                <div class="row g-2">
                    <div class="col-md-6"><input type="text" name="db_host" class="form-control" value="localhost" required></div>
                    <div class="col-md-6"><input type="text" name="db_name" class="form-control" value="gestion_insis" required></div>
                    <div class="col-md-6"><input type="text" name="db_user" class="form-control" value="root" required></div>
                    <div class="col-md-6"><input type="password" name="db_pass" class="form-control" placeholder="Contraseña BD"></div>
                </div>

                <h5 class="text-primary mt-4">2. WooCommerce</h5>
                <input type="url" name="woo_url" class="form-control mb-2" placeholder="https://tutienda.com" required>
                <div class="row g-2">
                    <div class="col-md-6">
                        <label class="form-label small text-muted">Consumer Key (CK)</label>
                        <input type="text" name="woo_ck" class="form-control input-code" placeholder="ck_xxxxxxxx..." required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small text-muted">Consumer Secret (CS) - Visible</label>
                        <input type="text" name="woo_cs" class="form-control input-code" placeholder="cs_xxxxxxxx..." required>
                    </div>
                </div>

                <h5 class="text-primary mt-4">3. WordPress (Fotos)</h5>
                <div class="row g-2">
                    <div class="col-md-6">
                        <label class="form-label small text-muted">Usuario Admin WP</label>
                        <input type="text" name="wp_user" class="form-control" placeholder="Ej: admin" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small text-muted">Contraseña de Aplicación - Visible</label>
                        <input type="text" name="wp_pass" class="form-control input-code" placeholder="xxxx xxxx xxxx xxxx" required>
                    </div>
                </div>

                <h5 class="text-primary mt-4">4. Admin Local</h5>
                <input type="text" name="app_name" class="form-control mb-2" value="Mi Tienda" required>
                <div class="row g-2 mb-4">
                    <div class="col-md-6"><input type="text" name="admin_user" class="form-control" placeholder="Usuario Local" required></div>
                    <div class="col-md-6"><input type="password" name="admin_pass" class="form-control" placeholder="Contraseña Local" required></div>
                </div>

                <hr>
                
                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" id="checkForzar">
                    <label class="form-check-label text-danger fw-bold" for="checkForzar">
                        Omitir validación y forzar instalación
                    </label>
                </div>

                <button type="submit" id="btnSubmit" class="btn btn-success btn-lg w-100 fw-bold">
                    <i class="fas fa-check-circle"></i> Validar e Instalar
                </button>
            </form>
        </div>
    </div>

    <div class="modal fade" id="modalTest" data-bs-backdrop="static" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Comprobando...</h5>
                </div>
                <div class="modal-body">
                    <div class="step-item mb-2" id="step-sql">
                        <span><i class="fas fa-database me-2"></i> SQL Local</span>
                        <span class="status"><div class="spinner-border spinner-border-sm"></div></span>
                    </div>
                    <div id="error-sql" class="text-danger small ms-4 mb-2 d-none"></div>

                    <div class="step-item mb-2" id="step-woo">
                        <span><i class="fas fa-shopping-cart me-2"></i> WooCommerce</span>
                        <span class="status"><i class="fas fa-clock text-muted"></i></span>
                    </div>
                    <div id="error-woo" class="text-danger small ms-4 mb-2 d-none"></div>

                    <div class="step-item" id="step-wp">
                        <span><i class="fas fa-images me-2"></i> WordPress</span>
                        <span class="status"><i class="fas fa-clock text-muted"></i></span>
                    </div>
                    <div id="error-wp" class="text-danger small ms-4 mb-2 d-none"></div>

                    <div id="final-msg" class="alert alert-success mt-3 d-none">¡Correcto! Instalando...</div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary d-none" id="btnCloseModal" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const checkForzar = document.getElementById('checkForzar');
        const btnSubmit = document.getElementById('btnSubmit');
        const form = document.getElementById('formInstalacion');

        checkForzar.addEventListener('change', function() {
            if(this.checked) {
                btnSubmit.classList.remove('btn-success');
                btnSubmit.classList.add('btn-danger');
                btnSubmit.innerHTML = '<i class="fas fa-exclamation-triangle"></i> INSTALAR A CIEGAS';
            } else {
                btnSubmit.classList.remove('btn-danger');
                btnSubmit.classList.add('btn-success');
                btnSubmit.innerHTML = '<i class="fas fa-check-circle"></i> Validar e Instalar';
            }
        });

        form.addEventListener('submit', function(e) {
            if(checkForzar.checked) return true; 
            e.preventDefault();
            runTests();
        });

        function runTests() {
            const modal = new bootstrap.Modal(document.getElementById('modalTest'));
            modal.show();
            resetUI();

            const formData = new FormData(form);

            fetch('test_connection.php', { method: 'POST', body: formData })
            .then(r => r.json())
            .then(data => {
                let allOk = true;
                
                if(data.sql.status === 'ok') markSuccess('step-sql');
                else { markError('step-sql', 'error-sql', data.sql.msg); allOk = false; }

                if(data.woo.status === 'ok') markSuccess('step-woo');
                else { markError('step-woo', 'error-woo', data.woo.msg); allOk = false; }

                if(data.wp.status === 'ok') markSuccess('step-wp');
                else { markError('step-wp', 'error-wp', data.wp.msg); allOk = false; }

                if(allOk) {
                    document.getElementById('final-msg').classList.remove('d-none');
                    setTimeout(() => { form.submit(); }, 1000);
                } else {
                    document.getElementById('btnCloseModal').classList.remove('d-none');
                }
            })
            .catch(err => {
                alert("Error de sistema (JSON). Usa 'Forzar instalación'.");
                modal.hide();
            });
        }

        function resetUI() {
            document.getElementById('btnCloseModal').classList.add('d-none');
            document.getElementById('final-msg').classList.add('d-none');
            ['sql', 'woo', 'wp'].forEach(type => {
                document.querySelector(`#step-${type} .status`).innerHTML = (type === 'sql') ? 
                    '<div class="spinner-border spinner-border-sm text-primary"></div>' : '<i class="fas fa-clock text-muted"></i>';
                document.getElementById(`error-${type}`).classList.add('d-none');
            });
        }
        function markSuccess(id) { document.querySelector(`#${id} .status`).innerHTML = '<i class="fas fa-check text-success"></i>'; }
        function markError(id, errId, msg) { 
            document.querySelector(`#${id} .status`).innerHTML = '<i class="fas fa-times text-danger"></i>'; 
            const el = document.getElementById(errId);
            el.innerText = msg;
            el.classList.remove('d-none');
        }
    </script>
</body>
</html>