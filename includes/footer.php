    </div> <!-- End Main Content Container -->
    
    <!-- Footer -->
    <?php if (isLoggedIn()): ?>
    <footer class="footer mt-5 py-3 bg-light">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-6 text-start">
                    <span class="text-muted">&copy; <?php echo date('Y'); ?> Makumbura Multimodal Center</span>
                </div>
                <div class="col-md-6 text-end">
                    <span class="text-muted">
                        Logged in as: <strong><?php echo getRoleName($_SESSION['user_role']); ?></strong>
                    </span>
                </div>
            </div>
        </div>
    </footer>
    <?php endif; ?>
    
    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    
    <!-- Custom JS -->
    <script src="<?php echo SITE_URL; ?>/assets/js/main.js"></script>
    
    <?php if (isset($extra_js)) echo $extra_js; ?>
    
</body>
</html>
