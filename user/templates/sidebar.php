    <aside class="sidebar">
        <div class="brand">
            <div class="brand-icon"><i class="fas fa-bolt text-dark fs-6"></i></div>
            FinPay
        </div>
        <nav class="sidebar-menu">
            <a href="index.php" class="nav-link-pro <?php echo (isset($activePage) && $activePage == 'dashboard') ? 'active' : ''; ?>"><i class="fas fa-layer-group"></i> Dashboard</a>
            <a href="payments.php" class="nav-link-pro <?php echo (isset($activePage) && $activePage == 'payments') ? 'active' : ''; ?>"><i class="fas fa-paper-plane"></i> Payments</a>
            <a href="assets.php" class="nav-link-pro <?php echo (isset($activePage) && $activePage == 'assets') ? 'active' : ''; ?>"><i class="fas fa-wallet"></i> Assets</a>
            <a href="cards.php" class="nav-link-pro <?php echo (isset($activePage) && $activePage == 'cards') ? 'active' : ''; ?>"><i class="fas fa-credit-card"></i> Cards</a>
            <a href="#" class="nav-link-pro"><i class="fas fa-shield-alt"></i> Security</a>
        </nav>
        <div style="padding: 2rem 1.5rem;">
            <div class="glass-panel" style="padding: 1rem; display: flex; align-items: center; gap: 15px;">
                <img src="https://ui-avatars.com/api/?name=John+Doe&background=00d26a&color=fff&bold=true" alt="User" style="width: 40px; border-radius: 12px;">
                <div>
                    <div style="font-weight: 600; font-size: 0.95rem;">John Doe</div>
                    <div style="font-size: 0.8rem; color: var(--text-secondary);">Pro Member</div>
                </div>
            </div>
        </div>
    </aside>
