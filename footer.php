    </div>
    
    <footer class="mt-5 py-3 bg-light text-center">
        <div class="container">
            <p class="mb-0 text-muted">
                Billing Software &copy; <?php echo date('Y'); ?> | 
                <i class="fas fa-code"></i> Developed with PHP & MySQL
            </p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-hide messages after 3 seconds
        setTimeout(function() {
            var alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                var bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 3000);
        
        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            // Ctrl + N for new bill
            if (e.ctrlKey && e.key === 'n') {
                e.preventDefault();
                window.location.href = 'index.php';
            }
            // Ctrl + P for products
            if (e.ctrlKey && e.key === 'p') {
                e.preventDefault();
                window.location.href = 'products.php';
            }
            // Ctrl + H for history
            if (e.ctrlKey && e.key === 'h') {
                e.preventDefault();
                window.location.href = 'history.php';
            }
        });
    </script>
</body>
</html>
<?php $conn->close(); ?>