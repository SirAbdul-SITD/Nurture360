    <footer class="footer">
        <div class="footer-content">
            <div class="footer-left">
                <p>&copy; <?php echo date('Y'); ?> <?php echo APP_NAME; ?>. All rights reserved.</p>
            </div>
            <div class="footer-right">
                <a href="../pages/help.php" class="footer-link">
                    <i class="fas fa-question-circle"></i>
                    Help
                </a>
                <a href="../pages/privacy.php" class="footer-link">
                    <i class="fas fa-shield-alt"></i>
                    Privacy
                </a>
                <a href="../pages/terms.php" class="footer-link">
                    <i class="fas fa-file-contract"></i>
                    Terms
                </a>
                <span class="footer-version">v<?php echo APP_VERSION; ?></span>
            </div>
        </div>
    </footer>
    <?php
      // Collect flash messages set in session by pages/controllers
      $__flash_success = $_SESSION['flash_success'] ?? '';
      $__flash_error   = $_SESSION['flash_error'] ?? '';
      $__flash_info    = $_SESSION['flash_info'] ?? '';
      $__flash_warning = $_SESSION['flash_warning'] ?? '';
      // Clear after reading so they don't persist across navigations
      unset($_SESSION['flash_success'], $_SESSION['flash_error'], $_SESSION['flash_info'], $_SESSION['flash_warning']);
    ?>
    
    <!-- Scripts -->
    <script src="../assets/js/main.js"></script>
    <script src="../assets/js/dashboard.js"></script>
    
    <!-- Page-specific scripts -->
    <?php if (isset($page_scripts)): ?>
        <?php foreach ($page_scripts as $script): ?>
            <script src="<?php echo $script; ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
    
    <script>
        // Footer functionality
        document.addEventListener('DOMContentLoaded', function() {
            // Global toast for PHP flash messages
            try {
                var fSuccess = <?php echo json_encode($__flash_success, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT); ?>;
                var fError   = <?php echo json_encode($__flash_error,   JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT); ?>;
                var fInfo    = <?php echo json_encode($__flash_info,    JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT); ?>;
                var fWarn    = <?php echo json_encode($__flash_warning, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT); ?>;
                if (typeof window.showNotification === 'function') {
                    if (fSuccess) window.showNotification(fSuccess, 'success');
                    if (fError)   window.showNotification(fError,   'error');
                    if (fWarn)    window.showNotification(fWarn,    'warning');
                    if (fInfo)    window.showNotification(fInfo,    'info');
                }
            } catch(e) { /* noop */ }

            // Auto-hide footer on scroll down, show on scroll up
            let lastScrollTop = 0;
            const footer = document.querySelector('.footer');
            
            if (footer) {
                window.addEventListener('scroll', function() {
                    const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
                    
                    if (scrollTop > lastScrollTop && scrollTop > 100) {
                        // Scrolling down
                        footer.style.transform = 'translateY(100%)';
                    } else {
                        // Scrolling up
                        footer.style.transform = 'translateY(0)';
                    }
                    
                    lastScrollTop = scrollTop;
                });
            }
            
            // Add smooth scrolling to footer links
            const footerLinks = document.querySelectorAll('.footer-link');
            footerLinks.forEach(link => {
                link.addEventListener('click', function(e) {
                    const href = this.getAttribute('href');
                    if (href.startsWith('#')) {
                        e.preventDefault();
                        const target = document.querySelector(href);
                        if (target) {
                            target.scrollIntoView({
                                behavior: 'smooth',
                                block: 'start'
                            });
                        }
                    }
                });
            });
        });
    </script>
</body>
</html> 