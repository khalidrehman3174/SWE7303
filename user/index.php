<?php
$pageTitle = 'FinPay Pro - Dashboard';
$activePage = 'dashboard';
require_once 'templates/head.php';
?>

<body>

    <!-- Desktop Sidebar -->
    <?php require_once 'templates/sidebar.php'; ?>

    <main class="main-content">
        
        <!-- Mobile Header -->
        <header class="mobile-header">
            <div class="profile-btn">
                <img src="https://ui-avatars.com/api/?name=John+Doe&background=00d26a&color=fff&bold=true" style="width: 100%; border-radius: 12px;">
            </div>
            <div style="font-weight: 700; letter-spacing: 1px;">FINPAY</div>
            <a href="cards.php" class="profile-btn" style="color: var(--text-primary); text-decoration: none;">
                <i class="fas fa-credit-card"></i>
            </a>
        </header>

        <!-- Main Layout Grid -->
        <div class="content-grid">
            
            <!-- Left Panel: Core Portfolio -->
            <div class="panel-left">
                
                <div class="balance-hero">
                    <div class="balance-label">Total Portfolio Value</div>
                    <div class="balance-amount">
                        <span class="balance-currency">£</span>12,450<span style="color: var(--text-secondary); font-size: 3rem;">.00</span>
                    </div>
                    
                    <div class="action-grid">
                        <button class="btn-pro btn-pro-primary"><i class="fas fa-plus"></i> Add Money</button>
                        <button class="btn-pro btn-pro-secondary"><i class="fas fa-info-circle"></i> Details</button>
                    </div>
                </div>

                <div class="px-3 px-lg-0 mt-2">
                    <h3 class="section-heading">My Assets <a href="#" style="font-size: 0.9rem; color: var(--accent); text-decoration: none;">Manage</a></h3>
                    
                    <div class="list-pro">
                        <!-- Fiat -->
                        <div class="asset-row">
                            <div class="asset-icon icon-gbp"><i class="fas fa-pound-sign"></i></div>
                            <div class="asset-info">
                                <div class="asset-name">British Pound</div>
                                <div class="asset-sub">Primary Account</div>
                            </div>
                            <div class="asset-value">
                                <div class="asset-price">£4,209.50</div>
                            </div>
                        </div>
                        
                        <!-- Crypto -->
                        <div class="asset-row">
                            <div class="asset-icon icon-btc"><i class="fab fa-bitcoin"></i></div>
                            <div class="asset-info">
                                <div class="asset-name">Bitcoin</div>
                                <div class="asset-sub">0.1250 BTC</div>
                            </div>
                            <div class="asset-value">
                                <div class="asset-price">£8,240.50</div>
                                <div class="asset-change text-success">+2.4%</div>
                            </div>
                        </div>

                        <!-- Vault -->
                        <div class="asset-row">
                            <div class="asset-icon icon-vault"><i class="fas fa-layer-group"></i></div>
                            <div class="asset-info">
                                <div class="asset-name">Yield Vault</div>
                                <div class="asset-sub">Earning 5.2% APY</div>
                            </div>
                            <div class="asset-value">
                                <div class="asset-price">£0.00</div>
                                <div class="asset-change" style="color: var(--text-secondary);">Tap to fund</div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Right Panel: Cards & Activity -->
            <div class="panel-right px-3 px-lg-0 mt-4 mt-lg-5">
                
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h3 class="section-heading mb-0">Analytics</h3>
                    <div style="font-size: 0.85rem; color: var(--accent); font-weight: 600; cursor: pointer;">This Week <i class="fas fa-chevron-down ms-1"></i></div>
                </div>
                
                <div class="glass-panel text-center" style="padding: 2.5rem 1rem; margin-bottom: 2rem;">
                    <div style="position: relative; height: 120px; width: 100%; display: flex; align-items: flex-end; justify-content: center; gap: 10px; opacity: 0.8;">
                        <div style="width: 10%; background: var(--text-secondary); height: 30%; border-radius: 6px; opacity: 0.5;"></div>
                        <div style="width: 10%; background: var(--text-secondary); height: 45%; border-radius: 6px; opacity: 0.5;"></div>
                        <div style="width: 10%; background: var(--text-secondary); height: 20%; border-radius: 6px; opacity: 0.5;"></div>
                        <div style="width: 10%; background: var(--accent); height: 60%; border-radius: 6px; box-shadow: 0 0 10px var(--accent-glow);"></div>
                        <div style="width: 10%; background: var(--accent); height: 85%; border-radius: 6px; box-shadow: 0 0 10px var(--accent-glow);"></div>
                        <div style="width: 10%; background: var(--text-secondary); height: 50%; border-radius: 6px; opacity: 0.5;"></div>
                        <div style="width: 10%; background: var(--accent); height: 100%; border-radius: 6px; box-shadow: 0 0 20px var(--accent-glow);"></div>
                    </div>
                    <div class="mt-4">
                        <div style="font-size: 0.95rem; color: var(--text-secondary); font-weight: 500;">Portfolio Performance</div>
                        <div style="font-size: 1.4rem; font-weight: 700; color: var(--text-primary); margin-top: 5px;">+ £450.20 <span class="text-success" style="font-size: 1rem;">(3.8%)</span></div>
                    </div>
                </div>

                <h3 class="section-heading">Activity</h3>
                <div class="list-pro">
                    <div class="asset-row" style="padding: 0.75rem 1rem;">
                        <div class="asset-icon" style="background: var(--icon-bg-default); width: 40px; height: 40px; font-size: 1.1rem;"><i class="fas fa-coffee"></i></div>
                        <div class="asset-info">
                            <div class="asset-name" style="font-size: 0.95rem;">Starbucks</div>
                            <div class="asset-sub">Today</div>
                        </div>
                        <div class="asset-value">
                            <div class="asset-price" style="font-size: 0.95rem;">- £4.50</div>
                        </div>
                    </div>
                    <div class="asset-row" style="padding: 0.75rem 1rem;">
                        <div class="asset-icon" style="background: rgba(0, 210, 106, 0.1); color: var(--accent); width: 40px; height: 40px; font-size: 1.1rem;"><i class="fas fa-arrow-down"></i></div>
                        <div class="asset-info">
                            <div class="asset-name" style="font-size: 0.95rem;">Bank Deposit</div>
                            <div class="asset-sub">Yesterday</div>
                        </div>
                        <div class="asset-value">
                            <div class="asset-price text-success" style="font-size: 0.95rem;">+ £50.00</div>
                        </div>
                    </div>
                </div>

            </div>

        </div>
    </main>

    <!-- Mobile Bottom Nav -->
    <?php require_once 'templates/bottom_nav.php'; ?>

</body>
</html>
