<!-- Swap Modal (Offcanvas) -->
<div class="offcanvas offcanvas-end chat-modal" tabindex="-1" id="swapModal" style="z-index: 10500;">
    <div class="chat-header pb-3 border-bottom border-secondary border-opacity-10 align-items-center">
        <div data-bs-dismiss="offcanvas" class="shadow-sm" style="cursor: pointer; width: 44px; height: 44px; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; border-radius: 14px; border: 1px solid var(--border-light); background: var(--bg-surface); transition: background 0.2s;"><i class="fas fa-arrow-right"></i></div>
        <div class="text-end">
            <div style="font-weight: 700; font-size: 1.1rem;">Swap Assets</div>
            <div style="font-size: 0.75rem; color: var(--text-secondary); font-weight: 500; text-transform: uppercase; letter-spacing: 0.5px;"><i class="fas fa-bolt text-accent me-1"></i> 0% Fees</div>
        </div>
    </div>
    
    <div class="chat-body d-flex flex-column" style="padding: 1.5rem 1rem 6rem 1rem; overflow-y: auto;">
        
        <!-- You Pay -->
        <div class="swap-input-box mb-2" style="background: var(--bg-surface-light); border: 2px solid transparent; border-radius: 24px; padding: 1.5rem; transition: border-color 0.2s;">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div style="font-size: 0.85rem; color: var(--text-secondary); font-weight: 600;">You Pay</div>
                <div style="font-size: 0.85rem; color: var(--text-secondary); font-weight: 600;">Bal: £4,209.50</div>
            </div>
            
            <div class="d-flex align-items-center justify-content-between">
                <input type="text" placeholder="0.00" value="100.00" style="background: transparent; border: none; color: var(--text-primary); font-size: 2.5rem; font-weight: 700; font-family: 'Outfit'; outline: none; width: 55%;">
                
                <div class="asset-selector d-flex align-items-center shadow-sm" data-bs-toggle="modal" data-bs-target="#swapAssetSelectModal" style="gap: 8px; background: var(--bg-surface); padding: 8px 14px 8px 8px; border-radius: 100px; cursor: pointer; border: 1px solid var(--border-light);">
                    <div style="width: 28px; height: 28px; border-radius: 50%; display: flex; align-items: center; justify-content: center; background: rgba(59, 130, 246, 0.1); color: #3b82f6;"><i class="fas fa-pound-sign" style="font-size: 0.95rem;"></i></div>
                    <span style="font-weight: 700; color: var(--text-primary); font-size: 1rem;">GBP</span>
                    <i class="fas fa-chevron-down text-secondary ms-1" style="font-size: 0.75rem;"></i>
                </div>
            </div>
        </div>

        <!-- Swap Button -->
        <div class="swap-separator" style="display: flex; justify-content: center; margin: -20px 0; position: relative; z-index: 2;">
            <div class="swap-separator-btn shadow-sm" style="width: 44px; height: 44px; border-radius: 50%; background: var(--list-bg); border: 4px solid var(--bg-surface); display: flex; align-items: center; justify-content: center; color: var(--text-primary); cursor: pointer; transition: transform 0.2s; font-size: 1.1rem;">
                <i class="fas fa-arrow-down"></i>
            </div>
        </div>

        <!-- You Receive -->
        <div class="swap-input-box mt-2 mb-4" style="background: var(--bg-surface-light); border: 2px solid transparent; border-radius: 24px; padding: 1.5rem; transition: border-color 0.2s;">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div style="font-size: 0.85rem; color: var(--text-secondary); font-weight: 600;">You Receive</div>
                <div style="font-size: 0.85rem; color: var(--text-secondary); font-weight: 600;">Bal: 0.1250 BTC</div>
            </div>
            
            <div class="d-flex align-items-center justify-content-between">
                <input type="text" placeholder="0.00" value="0.0024" readonly style="background: transparent; border: none; color: var(--accent); font-size: 2.5rem; font-weight: 700; font-family: 'Outfit'; outline: none; width: 55%;">
                
                <div class="asset-selector d-flex align-items-center shadow-sm" data-bs-toggle="modal" data-bs-target="#swapAssetSelectModal" style="gap: 8px; background: var(--bg-surface); padding: 8px 14px 8px 8px; border-radius: 100px; cursor: pointer; border: 1px solid var(--border-light);">
                    <div style="width: 28px; height: 28px; border-radius: 50%; display: flex; align-items: center; justify-content: center; background: rgba(245, 158, 11, 0.1); color: #f59e0b;"><i class="fab fa-bitcoin" style="font-size: 0.95rem;"></i></div>
                    <span style="font-weight: 700; color: var(--text-primary); font-size: 1rem;">BTC</span>
                    <i class="fas fa-chevron-down text-secondary ms-1" style="font-size: 0.75rem;"></i>
                </div>
            </div>
        </div>

        <div class="exchange-info p-3 mb-4 rounded-4" style="background: var(--bg-surface-light); border: 1px solid var(--border-light);">
            <div class="d-flex justify-content-between align-items-center mb-2" style="font-size: 0.85rem; color: var(--text-secondary); font-weight: 500;">
                <div><i class="fas fa-info-circle me-2 text-accent"></i>Exchange Rate</div>
                <div>1 GBP = 0.000024 BTC</div>
            </div>
            <div class="d-flex justify-content-between align-items-center" style="font-size: 0.85rem; color: var(--text-secondary); font-weight: 500;">
                <div>Network Fee</div>
                <div class="text-success text-end">Free</div>
            </div>
        </div>

        <div class="mt-4 mt-auto w-100" style="padding-bottom: 2rem;">
            <button class="btn-pro btn-pro-primary w-100" style="padding: 16px; border-radius: 100px; font-weight: 700; font-size: 1.1rem; box-shadow: 0 8px 25px rgba(239, 184, 12, 0.25);">Review Order</button>
            <div class="text-center mt-4">
                <p style="font-size: 0.8rem; color: var(--text-secondary); margin-bottom: 0; opacity: 0.8;"><i class="fas fa-shield-alt text-success me-1"></i> Swaps restricted to GBP ↔ Crypto securely.</p>
            </div>
        </div>
        
    </div>
</div>

<!-- Asset Selection Modal (Internal) -->
<div class="modal fade" id="swapAssetSelectModal" tabindex="-1" aria-hidden="true" style="z-index: 99999;">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content" style="background: var(--bg-surface); border: 1px solid var(--border-light); border-radius: 24px; color: var(--text-primary);">
            <div class="modal-header border-0 pb-0 pt-4 px-4">
                <h5 class="modal-title fw-bold">Select Asset</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                
                <div style="font-size: 0.85rem; color: var(--text-secondary); font-weight: 600; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 15px;">Fiat</div>
                <div class="asset-row px-3 mb-4 rounded" style="background: var(--list-bg); border: 1px solid var(--border-light); cursor: pointer;" data-bs-dismiss="modal">
                    <div class="asset-icon" style="background: rgba(59, 130, 246, 0.1); color: #3b82f6; border-radius: 12px; width: 40px; height: 40px;"><i class="fas fa-pound-sign"></i></div>
                    <div class="asset-info ml-3">
                        <div class="asset-name" style="font-size: 1.05rem;">British Pound</div>
                        <div class="asset-sub">GBP</div>
                    </div>
                </div>

                <div style="font-size: 0.85rem; color: var(--text-secondary); font-weight: 600; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 15px;">Cryptocurrency</div>
                <div class="asset-row px-3 mb-2 rounded" style="background: var(--list-bg); border: 1px solid var(--border-light); cursor: pointer;" data-bs-dismiss="modal">
                    <div class="asset-icon" style="background: rgba(245, 158, 11, 0.1); color: #f59e0b; border-radius: 12px; width: 40px; height: 40px;"><i class="fab fa-bitcoin"></i></div>
                    <div class="asset-info ml-3">
                        <div class="asset-name" style="font-size: 1.05rem;">Bitcoin</div>
                        <div class="asset-sub">BTC</div>
                    </div>
                </div>
                
                <div class="asset-row px-3 mb-2 rounded" style="background: var(--list-bg); border: 1px solid var(--border-light); cursor: pointer;" data-bs-dismiss="modal">
                    <div class="asset-icon" style="background: rgba(99, 102, 241, 0.1); color: #6366f1; border-radius: 12px; width: 40px; height: 40px;"><i class="fab fa-ethereum"></i></div>
                    <div class="asset-info ml-3">
                        <div class="asset-name" style="font-size: 1.05rem;">Ethereum</div>
                        <div class="asset-sub">ETH</div>
                    </div>
                </div>

                <div class="asset-row px-3 rounded" style="background: var(--list-bg); border: 1px solid var(--border-light); cursor: pointer;" data-bs-dismiss="modal">
                    <div class="asset-icon" style="background: rgba(16, 185, 129, 0.1); color: #10b981; border-radius: 12px; width: 40px; height: 40px;"><i class="fas fa-bolt"></i></div>
                    <div class="asset-info ml-3">
                        <div class="asset-name" style="font-size: 1.05rem;">Solana</div>
                        <div class="asset-sub">SOL</div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>
