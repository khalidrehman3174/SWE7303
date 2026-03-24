    <div class="bottom-nav">
        <a href="index.php" class="bottom-nav-item <?php echo (isset($activePage) && $activePage == 'dashboard') ? 'active' : ''; ?>">
            <i class="fas fa-layer-group"></i>
            <span>Home</span>
        </a>
        <a href="payments.php" class="bottom-nav-item <?php echo (isset($activePage) && $activePage == 'payments') ? 'active' : ''; ?>">
            <i class="fas fa-paper-plane"></i>
            <span>Pay</span>
        </a>
        <a href="assets.php" class="bottom-nav-item <?php echo (isset($activePage) && $activePage == 'assets') ? 'active' : ''; ?>">
            <i class="fas fa-wallet"></i>
            <span>Assets</span>
        </a>
        <a href="#" class="bottom-nav-item">
            <i class="fas fa-bell"></i>
            <span>Alerts</span>
        </a>
    </div>
