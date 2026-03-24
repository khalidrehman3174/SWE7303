<?php
$pageTitle = 'FinPay Pro - Cards';
$activePage = 'cards';
require_once 'templates/head.php';
?>

<body>

    <?php require_once 'templates/sidebar.php'; ?>

    <main class="main-content">
        
        <header class="mobile-header">
            <div class="profile-btn"><i class="fas fa-chevron-left"></i></div>
            <div style="font-weight: 700; letter-spacing: 1px;">CARDS</div>
            <div class="profile-btn"><i class="fas fa-plus"></i></div>
        </header>

        <div class="cards-layout pt-lg-4 px-lg-4">
            
            <div class="d-none d-lg-flex justify-content-between align-items-center mb-4">
                <h2 class="fw-bold mb-0">Card Management</h2>
                <button class="btn btn-dark pb-2 pt-2 px-4 rounded-pill" style="background: var(--text-primary); color: var(--bg-body); font-weight: 600;"><i class="fas fa-plus me-2"></i> Get New Card</button>
            </div>

            <div class="row">
                <!-- Left Column (Cards) -->
                <div class="col-lg-6">
                    <div class="card-carousel-container">
                        <div class="card-carousel">
                            <!-- Virtual Card -->
                            <div class="carousel-item-card">
                                <div class="pro-card-widget">
                                    <div class="card-inner">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <span class="card-badge"><i class="fas fa-cube me-1"></i> Virtual</span>
                                            <i class="fas fa-wifi fs-4" style="transform: rotate(90deg); color: rgba(255,255,255,0.6);"></i>
                                        </div>
                                        <div>
                                            <div class="card-number">5412 8842 1923 4092</div>
                                            <div class="card-meta">
                                                <div>
                                                    <div style="font-size: 0.7rem; color: rgba(255,255,255,0.5); text-transform: uppercase;">JOHN DOE</div>
                                                    <div style="font-size: 0.9rem; font-weight: 500;">12/28 <span class="ms-3 text-secondary">CVV ***</span></div>
                                                </div>
                                                <div class="text-end">
                                                    <i class="fab fa-cc-visa fs-1" style="color: #fff;"></i>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!-- Physical Card -->
                            <div class="carousel-item-card">
                                <div class="pro-card-widget physical">
                                    <div class="card-inner">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <span class="card-badge" style="background: rgba(99, 102, 241, 0.2);"><i class="fas fa-wallet me-1"></i> Physical</span>
                                            <i class="fas fa-wifi fs-4" style="transform: rotate(90deg); color: rgba(255,255,255,0.6);"></i>
                                        </div>
                                        <div>
                                            <div class="card-number">4413 **** **** 9192</div>
                                            <div class="card-meta">
                                                <div>
                                                    <div style="font-size: 0.7rem; color: rgba(255,255,255,0.5); text-transform: uppercase;">JOHN DOE</div>
                                                    <div style="font-size: 0.9rem; font-weight: 500;">06/29</div>
                                                </div>
                                                <div class="text-end">
                                                    <i class="fab fa-cc-mastercard fs-1"></i>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="quick-actions">
                        <div class="btn-action">
                            <i class="fas fa-eye text-primary"></i>
                            <span style="font-size: 0.85rem;">Show Details</span>
                        </div>
                        <div class="btn-action">
                            <i class="fas fa-snowflake text-info"></i>
                            <span style="font-size: 0.85rem;">Freeze</span>
                        </div>
                        <div class="btn-action">
                            <i class="fas fa-sliders-h text-warning"></i>
                            <span style="font-size: 0.85rem;">Limits</span>
                        </div>
                        <div class="btn-action">
                            <i class="fas fa-cog text-secondary"></i>
                            <span style="font-size: 0.85rem;">Settings</span>
                        </div>
                    </div>
                </div>

                <!-- Right Column (Settings) -->
                <div class="col-lg-6 px-3 px-lg-4">
                    
                    <div class="apple-pay-btn px-3 d-lg-none">
                        <i class="fab fa-apple"></i> Add to Apple Wallet
                    </div>

                    <div class="section-header">Security Controls</div>
                    <div class="settings-list">
                        <div class="setting-row">
                            <div class="d-flex align-items-center gap-3">
                                <div class="setting-icon"><i class="fas fa-globe"></i></div>
                                <div>
                                    <div style="font-weight: 600; font-size: 1rem;">Online Transactions</div>
                                    <div style="font-size: 0.85rem; color: var(--text-secondary);">Allow card to be used online</div>
                                </div>
                            </div>
                            <label class="feature-toggle">
                                <input type="checkbox" checked>
                                <span class="slider"></span>
                            </label>
                        </div>
                        <div class="setting-row">
                            <div class="d-flex align-items-center gap-3">
                                <div class="setting-icon"><i class="fas fa-wifi"></i></div>
                                <div>
                                    <div style="font-weight: 600; font-size: 1rem;">Contactless Payments</div>
                                    <div style="font-size: 0.85rem; color: var(--text-secondary);">Allow tap-to-pay via POS</div>
                                </div>
                            </div>
                            <label class="feature-toggle">
                                <input type="checkbox" checked>
                                <span class="slider"></span>
                            </label>
                        </div>
                        <div class="setting-row">
                            <div class="d-flex align-items-center gap-3">
                                <div class="setting-icon"><i class="fas fa-map-marker-alt"></i></div>
                                <div>
                                    <div style="font-weight: 600; font-size: 1rem;">Location Security</div>
                                    <div style="font-size: 0.85rem; color: var(--text-secondary);">Block usage outside your country</div>
                                </div>
                            </div>
                            <label class="feature-toggle">
                                <input type="checkbox">
                                <span class="slider"></span>
                            </label>
                        </div>
                        <div class="setting-row">
                            <div class="d-flex align-items-center gap-3">
                                <div class="setting-icon"><i class="fas fa-money-bill-wave"></i></div>
                                <div>
                                    <div style="font-weight: 600; font-size: 1rem;">ATM Withdrawals</div>
                                    <div style="font-size: 0.85rem; color: var(--text-secondary);">Cash withdrawal support</div>
                                </div>
                            </div>
                            <label class="feature-toggle">
                                <input type="checkbox">
                                <span class="slider"></span>
                            </label>
                        </div>
                    </div>

                    <div class="section-header">Card Information</div>
                    <div class="settings-list">
                        <div class="setting-row" style="cursor: pointer;">
                            <div class="d-flex align-items-center gap-3">
                                <div class="setting-icon"><i class="fas fa-key"></i></div>
                                <div>
                                    <div style="font-weight: 600; font-size: 1rem;">View PIN</div>
                                    <div style="font-size: 0.85rem; color: var(--text-secondary);">Req. Face ID verification</div>
                                </div>
                            </div>
                            <i class="fas fa-chevron-right text-secondary"></i>
                        </div>
                        <div class="setting-row" style="cursor: pointer;">
                            <div class="d-flex align-items-center gap-3">
                                <div class="setting-icon"><i class="fas fa-undo"></i></div>
                                <div>
                                    <div style="font-weight: 600; font-size: 1rem;">Terminate Card</div>
                                    <div style="font-size: 0.85rem; color: #ef4444;">Permanently delete this card</div>
                                </div>
                            </div>
                            <i class="fas fa-chevron-right text-secondary"></i>
                        </div>
                    </div>

                </div>
            </div>
        </div>

    </main>

    <?php require_once 'templates/bottom_nav.php'; ?>

</body>
</html>
