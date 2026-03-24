<?php
$pageTitle = 'FinPay Pro - Payments';
$activePage = 'payments';
require_once 'templates/head.php';
?>

<body>

    <?php require_once 'templates/sidebar.php'; ?>

    <main class="main-content">
        
        <header class="mobile-header">
            <div class="profile-btn"><i class="fas fa-qrcode"></i></div>
            <div style="font-weight: 700; letter-spacing: 1px;">PAYMENTS</div>
            <div class="profile-btn" data-bs-toggle="modal" data-bs-target="#newPaymentModal"><i class="fas fa-plus"></i></div>
        </header>

        <!-- Desktop Title -->
        <div class="d-none d-lg-flex justify-content-between align-items-center pt-5 px-lg-5 pb-2">
            <h2 class="fw-bold mb-0" style="font-family: 'Outfit';">Payments Center</h2>
            <div style="font-size: 0.95rem; color: var(--text-secondary); font-weight: 500;"><i class="fas fa-shield-alt text-success me-2"></i>Bank-grade Security</div>
        </div>

        <div class="content-grid px-lg-5 mt-lg-3">
            
            <!-- Left Panel -->
            <div class="panel-left">

                <!-- Premium Master Search Bar -->
                <div class="glass-panel mx-3 mx-lg-0 mb-4 mb-lg-5 d-flex align-items-center" style="padding: 8px 8px 8px 20px; border-radius: 100px;">
                    <i class="fas fa-search" style="color: var(--text-secondary); font-size: 1.2rem;"></i>
                    <input type="text" placeholder="Search people, tags, or banks..." style="flex: 1; background: transparent; border: none; color: var(--text-primary); font-family: 'Outfit', sans-serif; font-size: 1.05rem; outline: none; padding-left: 15px;">
                    <button class="btn-pro btn-pro-primary d-none d-md-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#newPaymentModal" style="flex: 0 0 auto; padding: 12px 24px; border-radius: 100px; font-size: 0.95rem;"><i class="fas fa-plus"></i> New</button>
                </div>
                
                <!-- Mobile Action Buttons -->
                <div class="d-flex px-3 mb-5 d-lg-none gap-3">
                    <button class="btn-pro btn-pro-primary" style="border-radius: 100px;" data-bs-toggle="modal" data-bs-target="#newPaymentModal"><i class="fas fa-arrow-up"></i> Send</button>
                    <button class="btn-pro btn-pro-secondary" style="border-radius: 100px;"><i class="fas fa-arrow-down text-info"></i> Request</button>
                </div>

                <div class="d-flex justify-content-between align-items-center mb-3 px-3 px-lg-0">
                    <h3 class="section-heading mb-0">Recent Activity</h3>
                    <div style="font-size: 0.85rem; color: var(--accent); font-weight: 600; cursor: pointer;">See All <i class="fas fa-chevron-right ms-1"></i></div>
                </div>
                
                <!-- Vertical Chat/Send List inside Glass Panel -->
                <div class="glass-panel mx-3 mx-lg-0 mb-5" style="border-radius: 24px; padding: 1rem 1.5rem;">
                    
                    <!-- Send to New -->
                    <div class="asset-row px-0" data-bs-toggle="modal" data-bs-target="#newPaymentModal" style="border-radius: 0; padding-bottom: 1rem !important; padding-top: 0.5rem !important; border-bottom: 1px solid var(--border-light);">
                        <div style="width: 48px; height: 48px; border-radius: 50%; background: var(--list-bg); display: flex; align-items: center; justify-content: center; font-size: 1.2rem; color: var(--accent); border: 1px dashed var(--accent);"><i class="fas fa-plus"></i></div>
                        <div class="asset-info ml-3">
                            <div class="asset-name" style="font-size: 1.05rem; color: var(--accent);">Send to New Contact</div>
                        </div>
                    </div>

                    <!-- Alice -->
                    <div class="asset-row px-0" data-bs-toggle="offcanvas" data-bs-target="#chatPaymentModal" style="border-radius: 0; padding: 1rem 0 !important; border-bottom: 1px solid var(--border-light);">
                        <img src="https://ui-avatars.com/api/?name=Alice+Smith&background=3b82f6&color=fff&bold=true" style="width: 48px; height: 48px; border-radius: 50%;">
                        <div class="asset-info ml-3">
                            <div class="asset-name" style="font-size: 1.05rem;">Alice Smith</div>
                            <div class="asset-sub"><i class="fas fa-arrow-up text-danger me-1" style="font-size: 0.75rem; opacity: 0.8;"></i>You sent £12.50</div>
                        </div>
                        <div class="asset-value text-end">
                            <div class="asset-sub" style="font-size: 0.8rem; margin-top: 0;">Yesterday</div>
                        </div>
                    </div>

                    <!-- Bob -->
                    <div class="asset-row px-0" data-bs-toggle="offcanvas" data-bs-target="#chatPaymentModal" style="border-radius: 0; padding: 1rem 0 !important; border-bottom: 1px solid var(--border-light);">
                        <img src="https://ui-avatars.com/api/?name=Bob+Jones&background=8b5cf6&color=fff&bold=true" style="width: 48px; height: 48px; border-radius: 50%;">
                        <div class="asset-info ml-3">
                            <div class="asset-name" style="font-size: 1.05rem;">Bob Jones</div>
                            <div class="asset-sub"><i class="fas fa-arrow-down text-success me-1" style="font-size: 0.75rem; opacity: 0.8;"></i>Bob sent £45.00</div>
                        </div>
                        <div class="asset-value text-end">
                            <div class="asset-sub" style="font-size: 0.8rem; margin-top: 0;">Tuesday</div>
                        </div>
                    </div>

                    <!-- Charlie -->
                    <div class="asset-row px-0" data-bs-toggle="offcanvas" data-bs-target="#chatPaymentModal" style="border-radius: 0; padding: 1rem 0 !important; border-bottom: 1px solid var(--border-light);">
                        <img src="https://ui-avatars.com/api/?name=Charlie+Day&background=f59e0b&color=fff&bold=true" style="width: 48px; height: 48px; border-radius: 50%;">
                        <div class="asset-info ml-3">
                            <div class="asset-name" style="font-size: 1.05rem;">Charlie Day</div>
                            <div class="asset-sub"><i class="fas fa-arrow-up text-danger me-1" style="font-size: 0.75rem; opacity: 0.8;"></i>You sent £5.00</div>
                        </div>
                        <div class="asset-value text-end">
                            <div class="asset-sub" style="font-size: 0.8rem; margin-top: 0;">15 Mar</div>
                        </div>
                    </div>

                    <!-- Dana -->
                    <div class="asset-row px-0" data-bs-toggle="offcanvas" data-bs-target="#chatPaymentModal" style="border-radius: 0; padding-top: 1rem !important; padding-bottom: 0.5rem !important; border: none;">
                        <img src="https://ui-avatars.com/api/?name=Dana+White&background=10b981&color=fff&bold=true" style="width: 48px; height: 48px; border-radius: 50%;">
                        <div class="asset-info ml-3">
                            <div class="asset-name" style="font-size: 1.05rem;">Dana White</div>
                            <div class="asset-sub"><i class="fas fa-arrow-up text-danger me-1" style="font-size: 0.75rem; opacity: 0.8;"></i>You sent £120.00</div>
                        </div>
                        <div class="asset-value text-end">
                            <div class="asset-sub" style="font-size: 0.8rem; margin-top: 0;">10 Mar</div>
                        </div>
                    </div>

                </div>
                
            </div>

            <!-- Right Panel: Upcoming Payments -->
            <div class="panel-right px-3 px-lg-0 mt-2 mt-lg-0">
                
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h3 class="section-heading mb-0">Upcoming</h3>
                    <div style="font-size: 0.85rem; color: var(--accent); font-weight: 600; cursor: pointer;">Manage <i class="fas fa-cog ms-1"></i></div>
                </div>

                <div class="glass-panel" style="padding: 1.5rem; border-radius: 24px; margin-bottom: 3rem;">
                    
                    <div class="asset-row px-0 pt-0" style="padding-bottom: 1rem; border-bottom: 1px solid var(--border-light); border-radius: 0;">
                        <div class="asset-icon" style="background: rgba(239, 68, 68, 0.1); color: #ef4444; width: 44px; height: 44px; font-size: 1.2rem; border-radius: 12px;"><i class="fab fa-spotify"></i></div>
                        <div class="asset-info">
                            <div class="asset-name" style="font-size: 1rem;">Spotify Premium</div>
                            <div class="asset-sub">Direct Debit • Tomorrow</div>
                        </div>
                        <div class="asset-value">
                            <div class="asset-price" style="font-size: 1.05rem;">£10.99</div>
                        </div>
                    </div>

                    <div class="asset-row px-0" style="padding: 1rem 0; border-bottom: 1px solid var(--border-light); border-radius: 0;">
                        <div class="asset-icon" style="background: rgba(59, 130, 246, 0.1); color: #3b82f6; width: 44px; height: 44px; font-size: 1.2rem; border-radius: 12px;"><i class="fas fa-bolt"></i></div>
                        <div class="asset-info">
                            <div class="asset-name" style="font-size: 1rem;">Electricity Bill</div>
                            <div class="asset-sub">Direct Debit • 28th March</div>
                        </div>
                        <div class="asset-value">
                            <div class="asset-price" style="font-size: 1.05rem;">£48.20</div>
                        </div>
                    </div>

                    <!-- Add New Sub -->
                    <div class="asset-row px-0 pb-0" style="padding-top: 1rem; border-bottom: none; border-radius: 0;">
                        <div class="asset-icon" style="background: var(--list-bg); color: var(--text-secondary); width: 44px; height: 44px; font-size: 1.2rem; border-radius: 12px;"><i class="fas fa-plus"></i></div>
                        <div class="asset-info">
                            <div class="asset-name" style="font-size: 1rem; color: var(--text-primary);">Add Scheduled Payment</div>
                        </div>
                        <div class="asset-value">
                            <i class="fas fa-chevron-right text-secondary"></i>
                        </div>
                    </div>

                </div>
            </div>

        </div>

    </main>

    <?php require_once 'templates/bottom_nav.php'; ?>

    <!-- Chat Payment Modal (Offcanvas) -->
    <div class="offcanvas offcanvas-end chat-modal" tabindex="-1" id="chatPaymentModal">
        <div class="chat-header">
            <div data-bs-dismiss="offcanvas" style="cursor: pointer; width: 44px; height: 44px; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; border-radius: 14px; border: 1px solid var(--border-light); background: var(--bg-surface-light); transition: background 0.2s;"><i class="fas fa-chevron-down" style="transform: rotate(90deg);"></i></div>
            <img src="https://ui-avatars.com/api/?name=Alice+Smith&background=3b82f6&color=fff&bold=true" style="width: 44px; height: 44px; border-radius: 14px;">
            <div>
                <div style="font-weight: 700; font-size: 1.05rem;">Alice Smith</div>
                <div style="font-size: 0.8rem; color: var(--text-secondary);">@alicesmith</div>
            </div>
        </div>
        
        <div class="chat-body" id="chatHistoryBox">
            <div class="text-center" style="font-size: 0.8rem; color: var(--text-secondary); margin-bottom: 0.5rem; font-weight: 500;">Yesterday, 14:30</div>
            
            <div class="chat-bubble received">
                <div style="font-weight: 600; margin-bottom: 4px; font-size: 1.05rem;">Request: Pizza 🍕</div>
                <div style="color: var(--text-secondary);">Can you send £12.50 for the pizza yesterday?</div>
            </div>
            
            <div class="chat-bubble sent">
                <div style="font-weight: 700; font-size: 1.2rem; margin-bottom: 2px;">£ 12.50</div>
                <div style="font-size: 0.85rem; opacity: 0.8;">Paid • 15:40</div>
            </div>
        </div>

        <div class="chat-footer">
            <input type="text" class="chat-amount" placeholder="£ 0.00">
            <div style="display: flex; gap: 10px; align-items: stretch;">
                <div class="search-wrap" style="flex: 1;">
                    <input type="text" placeholder="Add a note..." style="padding-left: 20px; border-radius: 20px; background: var(--list-bg);">
                </div>
                <button class="btn-pro btn-pro-primary" style="flex: 0 0 auto; width: 56px; border-radius: 20px; padding: 0;"><i class="fas fa-arrow-up"></i></button>
            </div>
        </div>
    </div>

    <!-- New Payment Modal (Bank Details) -->
    <div class="modal fade" id="newPaymentModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="background: var(--bg-surface); border: 1px solid var(--border-light); border-radius: 24px; color: var(--text-primary); box-shadow: 0 20px 40px rgba(0,0,0,0.1);">
                <div class="modal-header" style="border-bottom: 1px solid var(--border-light); padding: 1.5rem;">
                    <h5 class="modal-title fw-bold" style="font-family: 'Outfit', sans-serif;">New Bank Transfer</h5>
                    <div data-bs-dismiss="modal" style="cursor: pointer; width: 36px; height: 36px; display: flex; align-items: center; justify-content: center; border-radius: 50%; background: var(--list-bg); transition: background 0.2s;"><i class="fas fa-times"></i></div>
                </div>
                <div class="modal-body" style="padding: 1.5rem;">
                    <div class="mb-3">
                        <label class="form-label text-secondary" style="font-size: 0.85rem; font-weight: 500;">Recipient Name</label>
                        <input type="text" class="form-control" placeholder="e.g. John Doe" style="background: var(--list-bg); border: 1px solid var(--border-light); border-radius: 12px; padding: 12px; color: var(--text-primary); font-family: 'Outfit', sans-serif;">
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-secondary" style="font-size: 0.85rem; font-weight: 500;">Sort Code</label>
                        <input type="text" class="form-control" placeholder="00-00-00" style="background: var(--list-bg); border: 1px solid var(--border-light); border-radius: 12px; padding: 12px; color: var(--text-primary); font-family: 'Outfit', sans-serif; letter-spacing: 2px;">
                    </div>
                    <div class="mb-4">
                        <label class="form-label text-secondary" style="font-size: 0.85rem; font-weight: 500;">Account Number</label>
                        <input type="text" class="form-control" placeholder="12345678" style="background: var(--list-bg); border: 1px solid var(--border-light); border-radius: 12px; padding: 12px; color: var(--text-primary); font-family: 'Outfit', sans-serif; letter-spacing: 2px;">
                    </div>
                    <button class="btn-pro btn-pro-primary w-100" data-bs-dismiss="modal">Continue</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap Bundle JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        const chatModal = document.getElementById('chatPaymentModal')
        chatModal.addEventListener('shown.bs.offcanvas', () => {
            const chatBody = document.getElementById('chatHistoryBox');
            if(chatBody) chatBody.scrollTop = chatBody.scrollHeight;
        });
    </script>
</body>
</html>
