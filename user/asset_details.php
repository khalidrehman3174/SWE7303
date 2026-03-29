<?php
$pageTitle = 'FinPay Pro - Asset Details';
$activePage = 'assets';
require_once 'templates/head.php';
require_once __DIR__ . '/../includes/db_connect.php';

$asset = isset($_GET['asset']) ? strtoupper(trim((string)$_GET['asset'])) : 'BTC';
$asset = preg_replace('/[^A-Z0-9]/', '', $asset);
if ($asset === '' || strlen($asset) > 10) {
    $asset = 'BTC';
}

$assetMeta = [
    'GBP' => ['name' => 'British Pound', 'type' => 'Fiat Currency', 'icon' => 'fas fa-pound-sign', 'color' => '#3b82f6', 'description' => 'The base fiat currency in your FinPay account for deposits, withdrawals, and settlements.'],
    'BTC' => ['name' => 'Bitcoin', 'type' => 'Digital Asset', 'icon' => 'fab fa-bitcoin', 'color' => '#f59e0b', 'description' => 'Bitcoin is the original decentralized digital currency and a core store-of-value asset.'],
    'ETH' => ['name' => 'Ethereum', 'type' => 'Smart Contract Asset', 'icon' => 'fab fa-ethereum', 'color' => '#6366f1', 'description' => 'Ethereum powers decentralized applications and programmable on-chain transactions.'],
    'USDT' => ['name' => 'Tether', 'type' => 'Stablecoin', 'icon' => 'fas fa-coins', 'color' => '#26A17B', 'description' => 'USDT is a USD-pegged stablecoin used for trading liquidity and capital preservation.'],
    'BNB' => ['name' => 'BNB', 'type' => 'Exchange Utility Asset', 'icon' => 'fas fa-cube', 'color' => '#eab308', 'description' => 'BNB is the utility asset of the Binance ecosystem and related chain networks.'],
    'SOL' => ['name' => 'Solana', 'type' => 'Layer 1 Asset', 'icon' => 'fas fa-bolt', 'color' => '#10b981', 'description' => 'Solana is a high-throughput blockchain focused on low-latency transactions.'],
    'XRP' => ['name' => 'XRP', 'type' => 'Payment Asset', 'icon' => 'fas fa-water', 'color' => '#475569', 'description' => 'XRP is used in fast cross-border value transfer and payment infrastructure use cases.'],
];

$meta = $assetMeta[$asset] ?? [
    'name' => $asset,
    'type' => 'Digital Asset',
    'icon' => 'fas fa-coins',
    'color' => '#64748b',
    'description' => 'A tracked asset in your FinPay portfolio.',
];

$userId = (int)$_SESSION['user_id'];
$amount = 0.0;
$stmt = mysqli_prepare($dbc, 'SELECT balance FROM wallets WHERE user_id = ? AND symbol = ? LIMIT 1');
if ($stmt) {
    mysqli_stmt_bind_param($stmt, 'is', $userId, $asset);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = $result ? mysqli_fetch_assoc($result) : null;
    mysqli_stmt_close($stmt);
    if ($row && isset($row['balance'])) {
        $amount = (float)$row['balance'];
    }
}

$amountDisplay = number_format($amount, $asset === 'GBP' ? 2 : 6);

$assetNetworks = [
    'BTC' => [
        ['id' => 'bitcoin', 'name' => 'Bitcoin', 'tag' => 'Native', 'eta' => '10-30 mins'],
        ['id' => 'lightning', 'name' => 'Lightning', 'tag' => 'Layer 2', 'eta' => '< 2 mins'],
    ],
    'ETH' => [
        ['id' => 'ethereum', 'name' => 'Ethereum', 'tag' => 'ERC-20', 'eta' => '2-8 mins'],
        ['id' => 'arbitrum', 'name' => 'Arbitrum', 'tag' => 'L2', 'eta' => '< 3 mins'],
    ],
    'USDT' => [
        ['id' => 'ethereum', 'name' => 'Ethereum', 'tag' => 'ERC-20', 'eta' => '2-8 mins'],
        ['id' => 'tron', 'name' => 'TRON', 'tag' => 'TRC-20', 'eta' => '< 2 mins'],
        ['id' => 'bsc', 'name' => 'BNB Chain', 'tag' => 'BEP-20', 'eta' => '< 3 mins'],
    ],
    'BNB' => [
        ['id' => 'bsc', 'name' => 'BNB Chain', 'tag' => 'BEP-20', 'eta' => '< 3 mins'],
    ],
    'SOL' => [
        ['id' => 'solana', 'name' => 'Solana', 'tag' => 'Native', 'eta' => '< 2 mins'],
    ],
    'XRP' => [
        ['id' => 'xrp', 'name' => 'XRP Ledger', 'tag' => 'Native', 'eta' => '< 2 mins'],
    ],
];

$availableNetworks = $assetNetworks[$asset] ?? [
    ['id' => 'mainnet', 'name' => 'Mainnet', 'tag' => 'Default', 'eta' => '2-10 mins'],
];
?>
<body>
    <?php require_once 'templates/sidebar.php'; ?>

    <main class="main-content">
        <header class="mobile-header">
            <a class="profile-btn" href="assets.php"><i class="fas fa-chevron-left"></i></a>
            <div style="font-weight: 700; letter-spacing: 1px;">ASSET DETAILS</div>
            <div class="profile-btn"><i class="fas fa-ellipsis-h"></i></div>
        </header>

        <div class="px-3 px-lg-5 pt-lg-5 pb-4">
            <div class="d-none d-lg-flex justify-content-between align-items-center mb-4">
                <a href="assets.php" class="asset-back-btn"><i class="fas fa-chevron-left"></i><span>Assets</span></a>
                <div class="d-flex gap-2">
                    <button class="btn-pro btn-pro-secondary" style="max-width: 170px;"><i class="fas fa-bell"></i> Alerts</button>
                    <button class="btn-pro btn-pro-primary" style="max-width: 170px;"><i class="fas fa-plus"></i> Add Funds</button>
                </div>
            </div>

            <section class="glass-panel p-4 p-lg-5 mb-4" style="border-radius: 28px; position: relative; overflow: hidden;">
                <div style="position:absolute; inset:auto -70px -80px auto; width:240px; height:240px; border-radius:50%; background: radial-gradient(circle, <?php echo htmlspecialchars($meta['color'], ENT_QUOTES, 'UTF-8'); ?> 0%, transparent 68%); opacity:0.2; filter: blur(6px);"></div>
                <div class="d-flex flex-column flex-lg-row justify-content-between gap-4 align-items-start align-items-lg-center" style="position: relative; z-index: 1;">
                    <div class="d-flex align-items-center gap-3">
                        <div style="width:64px; height:64px; border-radius:20px; display:flex; align-items:center; justify-content:center; font-size:1.8rem; background: rgba(255,255,255,0.75); color: <?php echo htmlspecialchars($meta['color'], ENT_QUOTES, 'UTF-8'); ?>; border:1px solid var(--border-light);">
                            <i class="<?php echo htmlspecialchars($meta['icon'], ENT_QUOTES, 'UTF-8'); ?>"></i>
                        </div>
                        <div>
                            <div id="coin-name" style="font-size:1.7rem; font-weight:800; letter-spacing:-0.3px;"><?php echo htmlspecialchars($meta['name'], ENT_QUOTES, 'UTF-8'); ?></div>
                            <div class="asset-sub" style="font-size:0.95rem; margin-top:2px;"><span id="coin-symbol"><?php echo htmlspecialchars($asset, ENT_QUOTES, 'UTF-8'); ?></span> • <?php echo htmlspecialchars($meta['type'], ENT_QUOTES, 'UTF-8'); ?></div>
                            <div class="asset-inline-actions asset-inline-actions-left mt-3">
                                <button class="asset-inline-btn is-primary" data-bs-toggle="offcanvas" data-bs-target="#assetDepositModal" aria-controls="assetDepositModal" aria-label="Deposit" title="Deposit"><i class="fas fa-arrow-down"></i><span class="asset-inline-label">Deposit</span></button>
                                <button class="asset-inline-btn" data-bs-toggle="offcanvas" data-bs-target="#assetWithdrawModal" aria-controls="assetWithdrawModal" aria-label="Withdraw" title="Withdraw"><i class="fas fa-arrow-up"></i><span class="asset-inline-label">Withdraw</span></button>
                                <button class="asset-inline-btn" data-bs-toggle="offcanvas" data-bs-target="#swapModal" aria-controls="swapModal" aria-label="Swap" title="Swap"><i class="fas fa-exchange-alt"></i><span class="asset-inline-label">Swap</span></button>
                            </div>
                        </div>
                    </div>

                    <div class="text-lg-end asset-hero-right">
                        <div id="market-price" class="d-none d-lg-block" style="font-size:2rem; font-weight:800; line-height:1;">$0.00</div>
                        <div id="daily-change" class="asset-sub d-none d-lg-block" style="font-weight:700; margin-top:8px; color: var(--text-secondary);">24h change loading...</div>

                        <div class="asset-mobile-holding d-lg-none">
                            <div class="asset-mobile-holding-label">You Hold</div>
                            <div class="asset-mobile-holding-value"><?php echo htmlspecialchars($amountDisplay, ENT_QUOTES, 'UTF-8'); ?> <?php echo htmlspecialchars($asset, ENT_QUOTES, 'UTF-8'); ?></div>
                        </div>

                        <div class="asset-inline-actions asset-inline-actions-mobile d-lg-none mt-3">
                            <button class="asset-inline-btn is-primary" data-bs-toggle="offcanvas" data-bs-target="#assetDepositModal" aria-controls="assetDepositModal" aria-label="Deposit" title="Deposit"><i class="fas fa-arrow-down"></i><span class="asset-inline-label">Deposit</span></button>
                            <button class="asset-inline-btn" data-bs-toggle="offcanvas" data-bs-target="#assetWithdrawModal" aria-controls="assetWithdrawModal" aria-label="Withdraw" title="Withdraw"><i class="fas fa-arrow-up"></i><span class="asset-inline-label">Withdraw</span></button>
                            <button class="asset-inline-btn" data-bs-toggle="offcanvas" data-bs-target="#swapModal" aria-controls="swapModal" aria-label="Swap" title="Swap"><i class="fas fa-exchange-alt"></i><span class="asset-inline-label">Swap</span></button>
                        </div>
                    </div>
                </div>
            </section>

            <section class="row g-3 g-lg-4 mb-4">
                <div class="col-12 col-md-6 col-xl-3">
                    <div class="glass-panel p-4" style="border-radius:20px; height:100%;">
                        <div class="asset-sub text-uppercase" style="letter-spacing:1px;">You Hold</div>
                        <div id="crypto-balance" data-amount="<?php echo htmlspecialchars((string)$amount, ENT_QUOTES, 'UTF-8'); ?>" style="font-size:1.5rem; font-weight:800; margin-top:8px;"><?php echo htmlspecialchars($amountDisplay, ENT_QUOTES, 'UTF-8'); ?> <?php echo htmlspecialchars($asset, ENT_QUOTES, 'UTF-8'); ?></div>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-xl-3">
                    <div class="glass-panel p-4" style="border-radius:20px; height:100%;">
                        <div class="asset-sub text-uppercase" style="letter-spacing:1px;">Current Value</div>
                        <div id="fiat-balance" style="font-size:1.5rem; font-weight:800; margin-top:8px;">$0.00</div>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-xl-3">
                    <div class="glass-panel p-4" style="border-radius:20px; height:100%;">
                        <div class="asset-sub text-uppercase" style="letter-spacing:1px;">24H Volume</div>
                        <div id="trading-volume" style="font-size:1.5rem; font-weight:800; margin-top:8px;">$0.00</div>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-xl-3">
                    <div class="glass-panel p-4" style="border-radius:20px; height:100%;">
                        <div class="asset-sub text-uppercase" style="letter-spacing:1px;">24H Range</div>
                        <div id="day-range" style="font-size:1.15rem; font-weight:800; margin-top:10px;">$0.00 - $0.00</div>
                    </div>
                </div>
            </section>

            <section class="glass-panel p-4 p-lg-5 mb-4" style="border-radius:24px;">
                <div class="d-flex flex-column flex-md-row gap-3 align-items-md-center justify-content-between mb-3">
                    <h3 class="section-heading mb-0">About This Asset</h3>
                    <span style="font-size:0.8rem; font-weight:700; text-transform:uppercase; letter-spacing:1px; color: var(--text-secondary);">Live market estimates</span>
                </div>
                <p id="about-desc" class="mb-0" style="font-size:1rem; line-height:1.8; color: var(--text-secondary); max-width: 920px;"><?php echo htmlspecialchars($meta['description'], ENT_QUOTES, 'UTF-8'); ?></p>
            </section>
        </div>
    </main>

    <?php require_once 'templates/bottom_nav.php'; ?>
    <style>
        .asset-inline-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 0.7rem;
        }

        .asset-inline-actions-mobile {
            display: none;
        }

        .asset-mobile-holding {
            display: none;
        }

        .asset-inline-btn {
            border: 1px solid var(--border-light);
            background: linear-gradient(180deg, var(--bg-surface) 0%, var(--bg-surface-light) 100%);
            color: var(--text-primary);
            border-radius: 14px;
            min-height: 44px;
            padding: 0.55rem 0.9rem;
            font-size: 0.8rem;
            font-weight: 700;
            letter-spacing: 0.2px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.4rem;
            transition: all 0.22s ease;
            box-shadow: 0 8px 18px rgba(15, 23, 42, 0.06);
        }

        .asset-inline-btn i {
            font-size: 0.78rem;
        }

        .asset-inline-label {
            line-height: 1;
        }

        .asset-inline-btn:hover {
            background: var(--hover-bg);
            transform: translateY(-1px);
            box-shadow: 0 10px 20px rgba(15, 23, 42, 0.1);
        }

        .asset-inline-btn.is-primary {
            border-color: transparent;
            background: var(--btn-primary-bg);
            color: var(--btn-primary-color);
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.2);
        }

        .asset-inline-btn.is-primary:hover {
            filter: brightness(0.98);
            box-shadow: 0 12px 26px rgba(15, 23, 42, 0.24);
        }

        @media (max-width: 991.98px) {
            .asset-inline-actions-left {
                display: none;
            }

            .asset-hero-right {
                width: 100%;
                text-align: left;
            }

            .asset-mobile-holding {
                display: block;
            }

            .asset-mobile-holding-label {
                font-size: 0.75rem;
                text-transform: uppercase;
                letter-spacing: 0.8px;
                color: var(--text-secondary);
                font-weight: 700;
                margin-bottom: 0.35rem;
            }

            .asset-mobile-holding-value {
                font-size: 1.35rem;
                font-weight: 800;
                line-height: 1.1;
                color: var(--text-primary);
            }

            .asset-inline-actions-mobile {
                display: flex;
                width: 100%;
                flex-direction: row;
                flex-wrap: nowrap;
                gap: 0.5rem;
            }

            .asset-inline-btn {
                flex: 1 1 0;
                width: auto;
                min-height: 64px;
                padding: 0.55rem 0.4rem;
                justify-content: center;
                align-items: center;
                flex-direction: column;
                gap: 0.22rem;
                border-radius: 12px;
            }

            .asset-inline-label {
                display: block;
                font-size: 0.72rem;
                letter-spacing: 0.1px;
                text-align: center;
                line-height: 1.15;
            }

            .asset-inline-btn i {
                font-size: 0.82rem;
            }
        }

        @media (max-width: 575.98px) {
            .asset-inline-actions {
                gap: 0.5rem;
            }

            .asset-mobile-holding-value {
                font-size: 1.25rem;
            }
        }

        .transfer-flow {
            position: relative;
            padding: 1.65rem 1.05rem 8.5rem 1.05rem !important;
            gap: 1.2rem;
        }

        .transfer-panel {
            border: 1px solid var(--border-light);
            background: linear-gradient(180deg, rgba(255,255,255,0.58) 0%, rgba(255,255,255,0.35) 100%);
            border-radius: 18px;
            padding: 1.25rem;
            box-shadow: 0 10px 26px rgba(15, 23, 42, 0.06);
        }

        @media (prefers-color-scheme: dark) {
            .transfer-panel {
                background: linear-gradient(180deg, rgba(255,255,255,0.045) 0%, rgba(255,255,255,0.02) 100%);
                box-shadow: 0 12px 30px rgba(0, 0, 0, 0.35);
            }
        }

        .transfer-section-title {
            font-size: 1.08rem;
            font-weight: 700;
            margin-bottom: 0.7rem;
        }

        .transfer-subline {
            font-size: 0.86rem;
            color: var(--text-secondary);
            margin-bottom: 0.75rem;
        }

        .transfer-network-panel {
            display: flex;
            flex-direction: column;
            min-height: 0;
        }

        .network-list {
            display: flex;
            flex-direction: column;
            gap: 0.65rem;
            margin-bottom: 0;
            max-height: min(52vh, 430px);
            overflow-y: auto;
            padding-right: 0.3rem;
            scrollbar-width: thin;
            scrollbar-color: rgba(148, 163, 184, 0.55) transparent;
        }

        .network-list::-webkit-scrollbar {
            width: 8px;
        }

        .network-list::-webkit-scrollbar-thumb {
            background: rgba(148, 163, 184, 0.45);
            border-radius: 999px;
        }

        .network-list::-webkit-scrollbar-track {
            background: transparent;
        }

        .network-row {
            width: 100%;
            text-align: left;
            border: 1px solid transparent;
            background: var(--bg-surface-light);
            color: var(--text-primary);
            padding: 1.05rem 1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: all 0.2s ease;
            border-radius: 16px;
        }

        .network-row:hover {
            background: rgba(0, 210, 106, 0.03);
        }

        .network-row.is-selected {
            background: rgba(0, 210, 106, 0.05);
            border-color: var(--accent);
        }

        .network-main {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .network-meta {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .network-eta {
            font-size: 0.77rem;
            color: var(--text-secondary);
        }

        .network-check {
            font-size: 1.15rem;
            color: var(--text-secondary);
        }

        .network-row.is-selected .network-check {
            color: var(--accent);
        }

        .network-warning-inline {
            margin-top: 0.9rem;
            border: 1px solid rgba(245, 158, 11, 0.4);
            background: linear-gradient(180deg, rgba(255,255,255,0.96) 0%, rgba(250,250,250,0.94) 100%);
            border-radius: 16px;
            padding: 0.95rem 0.95rem 1rem;
            opacity: 0;
            transform: translateY(10px);
            transition: opacity 0.18s ease, transform 0.18s ease;
            box-shadow: 0 -6px 24px rgba(15, 23, 42, 0.12);
        }

        .network-warning-inline.is-visible {
            opacity: 1;
            transform: translateY(0);
        }

        .network-warning-dock {
            position: absolute;
            left: 1.05rem;
            right: 1.05rem;
            bottom: 1rem;
            z-index: 12;
            margin-top: 0;
        }

        .network-warning-text {
            font-size: 0.83rem;
            color: var(--text-secondary);
            line-height: 1.5;
            margin-bottom: 0.72rem;
        }

        .network-warning-actions {
            display: flex;
            flex-direction: column;
            align-items: stretch;
            gap: 0.72rem;
        }

        .network-warning-toggle {
            display: inline-flex;
            align-items: center;
            gap: 0.42rem;
            font-size: 0.8rem;
            color: var(--text-secondary);
            user-select: none;
            cursor: pointer;
            margin-right: 0;
            width: fit-content;
        }

        .network-warning-toggle input {
            width: 15px;
            height: 15px;
            accent-color: #10b981;
        }

        @media (prefers-color-scheme: dark) {
            .network-warning-inline {
                background: linear-gradient(180deg, rgba(15, 23, 42, 0.92) 0%, rgba(15, 23, 42, 0.88) 100%);
                box-shadow: 0 -8px 26px rgba(0, 0, 0, 0.45);
            }
        }

        .network-arrow {
            font-size: 0.75rem;
            color: var(--text-secondary);
            opacity: 0.75;
        }

        .qr-panel {
            border: 1px solid var(--border-light);
            background: var(--bg-surface-light);
            border-radius: 16px;
            padding: 14px;
            margin-bottom: 0.65rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .qr-image-wrap {
            width: 128px;
            height: 128px;
            border-radius: 14px;
            overflow: hidden;
            background: #fff;
            border: 1px solid var(--border-light);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .qr-image-wrap img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .address-panel {
            border-radius: 14px;
            border: 1px dashed var(--border-light);
            background: var(--bg-surface-light);
            padding: 12px;
            margin-bottom: 0.2rem;
        }

        .transfer-cta {
            margin-top: 0.45rem;
        }

        .transfer-address-note {
            font-size: 0.79rem;
            color: var(--text-secondary);
            margin-top: 0.6rem;
        }

        .transfer-field {
            margin-bottom: 0.85rem;
        }

        .transfer-label {
            font-size: 0.78rem;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: 700;
            margin-bottom: 0.4rem;
        }

        .transfer-input {
            width: 100%;
            padding: 12px 14px;
            border-radius: 12px;
            border: 1px solid var(--border-light);
            background: var(--bg-surface-light);
            color: var(--text-primary);
            outline: none;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }

        .transfer-input:focus {
            border-color: rgba(16, 185, 129, 0.45);
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.12);
        }

        .transfer-summary {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.84rem;
            color: var(--text-secondary);
            border-top: 1px dashed var(--border-light);
            padding-top: 0.65rem;
            margin-top: 0.2rem;
            margin-bottom: 0.8rem;
        }

        .asset-back-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.55rem;
            text-decoration: none;
            color: var(--text-primary);
            background: var(--bg-surface);
            border: 1px solid var(--border-light);
            border-radius: 999px;
            padding: 10px 14px;
            font-weight: 600;
            font-size: 0.9rem;
            transition: all 0.2s ease;
        }

        .asset-back-btn i {
            width: 26px;
            height: 26px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: var(--hover-bg);
            font-size: 0.75rem;
        }

        .asset-back-btn:hover {
            color: var(--text-primary);
            transform: translateY(-1px);
            box-shadow: 0 8px 18px rgba(15, 23, 42, 0.08);
        }

        .transfer-back-btn {
            cursor: pointer;
            width: 42px;
            height: 42px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.95rem;
            border-radius: 12px;
            border: 1px solid var(--border-light);
            background: var(--bg-surface);
            color: var(--text-primary);
            transition: all 0.2s ease;
        }

        .transfer-back-btn:hover {
            background: var(--hover-bg);
            transform: translateY(-1px);
        }

        .transfer-mini-back {
            border: 0;
            background: transparent;
            color: var(--text-secondary);
            font-size: 0.82rem;
            font-weight: 600;
            padding: 6px 4px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: color 0.2s ease;
        }

        .transfer-mini-back:hover {
            color: var(--text-primary);
        }

        @media (max-width: 768px) {
            .transfer-flow {
                padding-bottom: 8rem !important;
            }

            .network-list {
                max-height: min(48vh, 340px);
            }

            .network-warning-dock {
                left: 0.85rem;
                right: 0.85rem;
                bottom: 0.85rem;
            }
        }
    </style>

    <!-- Asset Deposit Modal -->
    <div class="offcanvas offcanvas-end chat-modal" tabindex="-1" id="assetDepositModal" style="z-index: 10530;">
        <div class="chat-header pb-3 border-bottom border-secondary border-opacity-10 align-items-center justify-content-between">
            <div class="d-flex align-items-center gap-3">
                <button type="button" data-bs-dismiss="offcanvas" class="transfer-back-btn" aria-label="Close deposit modal"><i class="fas fa-chevron-left"></i></button>
                <div>
                    <div style="font-weight: 700; font-size: 1.1rem;">Deposit <?php echo htmlspecialchars($asset, ENT_QUOTES, 'UTF-8'); ?></div>
                    <div style="font-size: 0.78rem; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.8px;">Network and address</div>
                </div>
            </div>
            <span id="depositStepBadge" style="font-size: 0.72rem; font-weight: 700; padding: 6px 10px; border-radius: 999px; border: 1px solid var(--border-light); color: var(--text-secondary);">Step 1/2</span>
        </div>

        <div class="chat-body transfer-flow">
            <section id="depositStep1" class="transfer-panel transfer-network-panel">
                <div class="transfer-section-title">Select Network</div>

                <div id="depositNetworkList" class="network-list"></div>
            </section>

            <section id="depositStep2" class="transfer-panel d-none">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div>
                        <div style="font-weight: 700; font-size: 1.08rem;">Deposit Address</div>
                        <div id="depositNetworkMeta" style="font-size: 0.85rem; color: var(--text-secondary);"></div>
                    </div>
                    <button id="depositBackBtn" class="transfer-mini-back"><i class="fas fa-chevron-left"></i> Back</button>
                </div>

                <div class="qr-panel">
                    <div class="qr-image-wrap">
                        <img id="depositQrImage" src="" alt="Deposit QR Code">
                    </div>
                </div>

                <div class="address-panel">
                    <div id="depositAddress" style="font-size: 0.88rem; line-height: 1.6; word-break: break-all; color: var(--text-primary);"></div>
                </div>

                <div class="d-flex gap-2 transfer-cta">
                    <button id="copyDepositAddressBtn" class="btn-pro btn-pro-primary" style="width: 100%;"><i class="fas fa-copy"></i> Copy Address</button>
                </div>
                <p class="transfer-address-note mb-0">Use only the selected network.</p>
            </section>

            <div id="depositNetworkWarning" class="network-warning-inline network-warning-dock d-none">
                <p id="depositNetworkWarningText" class="network-warning-text mb-0">Make sure the selected network matches your sender wallet network.</p>
                <div class="network-warning-actions">
                    <label class="network-warning-toggle">
                        <input id="depositDontShowAgain" type="checkbox">
                        <span>Don't show again</span>
                    </label>
                    <button id="depositNetworkConfirmBtn" type="button" class="btn-pro btn-pro-primary" style="width: 100%;"><i class="fas fa-check"></i> Got it</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Asset Withdraw Modal -->
    <div class="offcanvas offcanvas-end chat-modal" tabindex="-1" id="assetWithdrawModal" style="z-index: 10525;">
        <div class="chat-header pb-3 border-bottom border-secondary border-opacity-10 align-items-center justify-content-between">
            <div class="d-flex align-items-center gap-3">
                <button type="button" data-bs-dismiss="offcanvas" class="transfer-back-btn" aria-label="Close withdraw modal"><i class="fas fa-chevron-left"></i></button>
                <div>
                    <div style="font-weight: 700; font-size: 1.1rem;">Withdraw <?php echo htmlspecialchars($asset, ENT_QUOTES, 'UTF-8'); ?></div>
                    <div style="font-size: 0.78rem; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.8px;">Network and transfer</div>
                </div>
            </div>
            <span id="withdrawStepBadge" style="font-size: 0.72rem; font-weight: 700; padding: 6px 10px; border-radius: 999px; border: 1px solid var(--border-light); color: var(--text-secondary);">Step 1/2</span>
        </div>

        <div class="chat-body transfer-flow">
            <section id="withdrawStep1" class="transfer-panel transfer-network-panel">
                <div class="transfer-section-title">Select Network</div>
                <div class="transfer-subline">Balance: <?php echo htmlspecialchars($amountDisplay, ENT_QUOTES, 'UTF-8'); ?> <?php echo htmlspecialchars($asset, ENT_QUOTES, 'UTF-8'); ?></div>
                <div id="withdrawNetworkList" class="network-list"></div>
            </section>

            <section id="withdrawStep2" class="transfer-panel d-none">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <div style="font-weight: 700; font-size: 1.08rem;">Withdrawal</div>
                        <div id="withdrawNetworkMeta" style="font-size: 0.84rem; color: var(--text-secondary);"></div>
                    </div>
                    <button id="withdrawBackBtn" class="transfer-mini-back"><i class="fas fa-chevron-left"></i> Back</button>
                </div>

                <div class="transfer-field">
                    <label class="transfer-label">Address</label>
                    <input id="withdrawAddressInput" class="transfer-input" type="text" placeholder="Enter destination wallet address">
                </div>

                <div class="transfer-field">
                    <label class="transfer-label">Amount (<?php echo htmlspecialchars($asset, ENT_QUOTES, 'UTF-8'); ?>)</label>
                    <input id="withdrawAmountInput" class="transfer-input" type="number" min="0" step="0.000001" placeholder="0.00">
                </div>

                <div class="transfer-summary">
                    <span>Estimated network fee</span>
                    <span id="withdrawFeeText">--</span>
                </div>

                <button id="withdrawReviewBtn" class="btn-pro btn-pro-primary" style="width:100%;"><i class="fas fa-paper-plane"></i> Review Withdrawal</button>
            </section>

            <div id="withdrawNetworkWarning" class="network-warning-inline network-warning-dock d-none">
                <p id="withdrawNetworkWarningText" class="network-warning-text mb-0">Make sure the selected network matches your destination wallet network.</p>
                <div class="network-warning-actions">
                    <label class="network-warning-toggle">
                        <input id="withdrawDontShowAgain" type="checkbox">
                        <span>Don't show again</span>
                    </label>
                    <button id="withdrawNetworkConfirmBtn" type="button" class="btn-pro btn-pro-primary" style="width: 100%;"><i class="fas fa-check"></i> Got it</button>
                </div>
            </div>
        </div>
    </div>

    <?php require_once 'templates/swap_widget.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        window.assetTransferConfig = <?php echo json_encode([
            'asset' => $asset,
            'networks' => $availableNetworks,
            'balance' => $amount,
            'balanceDisplay' => $amountDisplay,
        ], JSON_UNESCAPED_SLASHES); ?>;

        window.assetSwapDefaults = <?php echo json_encode([
            'symbol' => $asset,
            'name' => $meta['name'],
            'icon' => $meta['icon'],
            'color' => $meta['color'],
            'amount' => $amount,
            'amountDisplay' => $amountDisplay,
        ], JSON_UNESCAPED_SLASHES); ?>;

        (function () {
            function formatNetworkFee(networkId) {
                var fees = {
                    bitcoin: '0.00012 ' + window.assetTransferConfig.asset,
                    lightning: '0.00001 ' + window.assetTransferConfig.asset,
                    ethereum: '0.0025 ' + window.assetTransferConfig.asset,
                    arbitrum: '0.0006 ' + window.assetTransferConfig.asset,
                    tron: '1.00 ' + window.assetTransferConfig.asset,
                    bsc: '0.0008 ' + window.assetTransferConfig.asset,
                    solana: '0.00002 ' + window.assetTransferConfig.asset,
                    xrp: '0.25 ' + window.assetTransferConfig.asset,
                    mainnet: 'Network fee varies',
                };

                return fees[networkId] || 'Network fee varies';
            }

            function makeDepositAddress(symbol, networkId) {
                var seed = (symbol + '_' + networkId + '_finpay').toUpperCase().replace(/[^A-Z0-9]/g, '');
                var body = '';
                while (body.length < 34) {
                    body += seed;
                }
                return (networkId.substring(0, 3).toUpperCase() + '1' + body.substring(0, 33));
            }

            function initTransferModals() {
                var cfg = window.assetTransferConfig || null;
                if (!cfg || !Array.isArray(cfg.networks) || cfg.networks.length === 0) {
                    return;
                }

                var selectedDepositNetwork = null;
                var selectedWithdrawNetwork = null;

                var depositList = document.getElementById('depositNetworkList');
                var withdrawList = document.getElementById('withdrawNetworkList');
                var depositStep1 = document.getElementById('depositStep1');
                var depositStep2 = document.getElementById('depositStep2');
                var withdrawStep1 = document.getElementById('withdrawStep1');
                var withdrawStep2 = document.getElementById('withdrawStep2');
                var depositStepBadge = document.getElementById('depositStepBadge');
                var withdrawStepBadge = document.getElementById('withdrawStepBadge');
                var depositAddressEl = document.getElementById('depositAddress');
                var depositQrImage = document.getElementById('depositQrImage');
                var depositNetworkMeta = document.getElementById('depositNetworkMeta');
                var withdrawNetworkMeta = document.getElementById('withdrawNetworkMeta');
                var withdrawFeeText = document.getElementById('withdrawFeeText');
                var depositWarningBox = document.getElementById('depositNetworkWarning');
                var depositWarningText = document.getElementById('depositNetworkWarningText');
                var depositConfirmBtn = document.getElementById('depositNetworkConfirmBtn');
                var depositDontShowAgain = document.getElementById('depositDontShowAgain');
                var withdrawWarningBox = document.getElementById('withdrawNetworkWarning');
                var withdrawWarningText = document.getElementById('withdrawNetworkWarningText');
                var withdrawConfirmBtn = document.getElementById('withdrawNetworkConfirmBtn');
                var withdrawDontShowAgain = document.getElementById('withdrawDontShowAgain');
                var depositWarningAfterConfirm = null;
                var withdrawWarningAfterConfirm = null;

                function shouldSkipWarning() {
                    try {
                        return localStorage.getItem('finpay_skip_network_warning') === '1';
                    } catch (e) {
                        return false;
                    }
                }

                function setSkipWarning(enabled) {
                    try {
                        if (enabled) {
                            localStorage.setItem('finpay_skip_network_warning', '1');
                        } else {
                            localStorage.removeItem('finpay_skip_network_warning');
                        }
                    } catch (e) {
                        // no-op
                    }
                }

                function withInlineNetworkWarning(networkName, actionLabel, warningBox, warningText, dontShowAgainInput, setAfterConfirm, done) {
                    if (shouldSkipWarning()) {
                        done();
                        return;
                    }

                    if (!warningBox) {
                        done();
                        return;
                    }

                    setAfterConfirm(done);
                    if (warningText) {
                        warningText.textContent = 'You selected ' + networkName + ' for ' + actionLabel + '. Make sure your sender/receiver wallet uses the same network.';
                    }
                    if (dontShowAgainInput) {
                        dontShowAgainInput.checked = false;
                    }
                    warningBox.classList.remove('d-none');
                    warningBox.classList.remove('is-visible');
                    requestAnimationFrame(function () {
                        warningBox.classList.add('is-visible');
                    });
                }

                function hideInlineNetworkWarning(warningBox) {
                    if (!warningBox) {
                        return;
                    }
                    warningBox.classList.remove('is-visible');
                    setTimeout(function () {
                        warningBox.classList.add('d-none');
                    }, 180);
                }

                function networkRowHtml(nw, selected) {
                    return '<button type="button" class="network-row ' + (selected ? 'is-selected' : '') + '" data-network-id="' + nw.id + '">' +
                    '<span class="network-main">' +
                    '<span><strong style="font-size:0.95rem; font-weight:700;">' + nw.name + '</strong><span style="display:block; font-size:0.74rem; color:var(--text-secondary); margin-top:2px;">' + nw.tag + '</span></span>' +
                    '</span>' +
                    '<span class="network-meta"><span class="network-eta">' + nw.eta + '</span><i class="' + (selected ? 'fas fa-check-circle' : 'far fa-circle') + ' network-check"></i></span></button>';
                }

                function renderDepositNetworks() {
                    if (!depositList) return;
                    depositList.innerHTML = cfg.networks.map(function (nw) {
                        return networkRowHtml(nw, selectedDepositNetwork && selectedDepositNetwork.id === nw.id);
                    }).join('');
                }

                function renderWithdrawNetworks() {
                    if (!withdrawList) return;
                    withdrawList.innerHTML = cfg.networks.map(function (nw) {
                        return networkRowHtml(nw, selectedWithdrawNetwork && selectedWithdrawNetwork.id === nw.id);
                    }).join('');
                }

                function resetDepositFlow() {
                    selectedDepositNetwork = null;
                    renderDepositNetworks();
                    if (depositStep2) depositStep2.classList.add('d-none');
                    if (depositStep1) depositStep1.classList.remove('d-none');
                    if (depositStepBadge) depositStepBadge.textContent = 'Step 1/2';
                    if (depositNetworkMeta) depositNetworkMeta.textContent = '';
                    if (depositAddressEl) depositAddressEl.textContent = '';
                    if (depositQrImage) depositQrImage.removeAttribute('src');
                    hideInlineNetworkWarning(depositWarningBox);
                    depositWarningAfterConfirm = null;
                }

                function resetWithdrawFlow() {
                    selectedWithdrawNetwork = null;
                    renderWithdrawNetworks();
                    if (withdrawStep2) withdrawStep2.classList.add('d-none');
                    if (withdrawStep1) withdrawStep1.classList.remove('d-none');
                    if (withdrawStepBadge) withdrawStepBadge.textContent = 'Step 1/2';
                    if (withdrawNetworkMeta) withdrawNetworkMeta.textContent = '';
                    if (withdrawFeeText) withdrawFeeText.textContent = '--';
                    if (withdrawAddressInput) withdrawAddressInput.value = '';
                    if (withdrawAmountInput) withdrawAmountInput.value = '';
                    hideInlineNetworkWarning(withdrawWarningBox);
                    withdrawWarningAfterConfirm = null;
                    if (withdrawReviewBtn) {
                        withdrawReviewBtn.innerHTML = '<i class="fas fa-paper-plane"></i> Review Withdrawal';
                    }
                }

                renderDepositNetworks();
                renderWithdrawNetworks();

                if (depositList) {
                    depositList.addEventListener('click', function (event) {
                        var btn = event.target.closest('button[data-network-id]');
                        if (!btn) return;
                        var id = btn.getAttribute('data-network-id');
                        selectedDepositNetwork = cfg.networks.find(function (nw) { return nw.id === id; }) || null;
                        renderDepositNetworks();

                        if (!selectedDepositNetwork) return;
                        withInlineNetworkWarning(
                            selectedDepositNetwork.name,
                            'deposit',
                            depositWarningBox,
                            depositWarningText,
                            depositDontShowAgain,
                            function (nextStep) { depositWarningAfterConfirm = nextStep; },
                            function () {
                            var address = makeDepositAddress(cfg.asset, selectedDepositNetwork.id);
                            if (depositStep1) depositStep1.classList.add('d-none');
                            if (depositStep2) depositStep2.classList.remove('d-none');
                            if (depositStepBadge) depositStepBadge.textContent = 'Step 2/2';
                            if (depositNetworkMeta) depositNetworkMeta.textContent = selectedDepositNetwork.name + ' • ' + selectedDepositNetwork.tag;
                            if (depositAddressEl) depositAddressEl.textContent = address;
                            if (depositQrImage) {
                                depositQrImage.src = 'https://api.qrserver.com/v1/create-qr-code/?size=256x256&data=' + encodeURIComponent(address);
                            }
                            }
                        );
                    });
                }

                if (withdrawList) {
                    withdrawList.addEventListener('click', function (event) {
                        var btn = event.target.closest('button[data-network-id]');
                        if (!btn) return;
                        var id = btn.getAttribute('data-network-id');
                        selectedWithdrawNetwork = cfg.networks.find(function (nw) { return nw.id === id; }) || null;
                        renderWithdrawNetworks();

                        if (!selectedWithdrawNetwork) return;
                        withInlineNetworkWarning(
                            selectedWithdrawNetwork.name,
                            'withdrawal',
                            withdrawWarningBox,
                            withdrawWarningText,
                            withdrawDontShowAgain,
                            function (nextStep) { withdrawWarningAfterConfirm = nextStep; },
                            function () {
                            if (withdrawStep1) withdrawStep1.classList.add('d-none');
                            if (withdrawStep2) withdrawStep2.classList.remove('d-none');
                            if (withdrawStepBadge) withdrawStepBadge.textContent = 'Step 2/2';
                            if (withdrawNetworkMeta) withdrawNetworkMeta.textContent = selectedWithdrawNetwork.name + ' • ' + selectedWithdrawNetwork.tag;
                            if (withdrawFeeText) withdrawFeeText.textContent = formatNetworkFee(selectedWithdrawNetwork.id);
                            }
                        );
                    });
                }

                if (depositConfirmBtn) {
                    depositConfirmBtn.addEventListener('click', function () {
                        if (depositDontShowAgain && depositDontShowAgain.checked) {
                            setSkipWarning(true);
                        }
                        hideInlineNetworkWarning(depositWarningBox);
                        if (typeof depositWarningAfterConfirm === 'function') {
                            var next = depositWarningAfterConfirm;
                            depositWarningAfterConfirm = null;
                            next();
                        }
                    });
                }

                if (withdrawConfirmBtn) {
                    withdrawConfirmBtn.addEventListener('click', function () {
                        if (withdrawDontShowAgain && withdrawDontShowAgain.checked) {
                            setSkipWarning(true);
                        }
                        hideInlineNetworkWarning(withdrawWarningBox);
                        if (typeof withdrawWarningAfterConfirm === 'function') {
                            var next = withdrawWarningAfterConfirm;
                            withdrawWarningAfterConfirm = null;
                            next();
                        }
                    });
                }

                var depositBackBtn = document.getElementById('depositBackBtn');
                if (depositBackBtn) {
                    depositBackBtn.addEventListener('click', function () {
                        if (depositStep2) depositStep2.classList.add('d-none');
                        if (depositStep1) depositStep1.classList.remove('d-none');
                        if (depositStepBadge) depositStepBadge.textContent = 'Step 1/2';
                        hideInlineNetworkWarning(depositWarningBox);
                        depositWarningAfterConfirm = null;
                    });
                }

                var copyBtn = document.getElementById('copyDepositAddressBtn');
                if (copyBtn) {
                    copyBtn.addEventListener('click', function () {
                        var address = depositAddressEl ? depositAddressEl.textContent : '';
                        if (!address) return;
                        navigator.clipboard.writeText(address).then(function () {
                            copyBtn.innerHTML = '<i class="fas fa-check"></i> Copied';
                            setTimeout(function () {
                                copyBtn.innerHTML = '<i class="fas fa-copy"></i> Copy Address';
                            }, 1500);
                        });
                    });
                }

                var withdrawBackBtn = document.getElementById('withdrawBackBtn');
                if (withdrawBackBtn) {
                    withdrawBackBtn.addEventListener('click', function () {
                        if (withdrawStep2) withdrawStep2.classList.add('d-none');
                        if (withdrawStep1) withdrawStep1.classList.remove('d-none');
                        if (withdrawStepBadge) withdrawStepBadge.textContent = 'Step 1/2';
                        hideInlineNetworkWarning(withdrawWarningBox);
                        withdrawWarningAfterConfirm = null;
                    });
                }

                var withdrawReviewBtn = document.getElementById('withdrawReviewBtn');
                var withdrawAddressInput = document.getElementById('withdrawAddressInput');
                var withdrawAmountInput = document.getElementById('withdrawAmountInput');
                if (withdrawReviewBtn) {
                    withdrawReviewBtn.addEventListener('click', function () {
                        var address = withdrawAddressInput ? withdrawAddressInput.value.trim() : '';
                        var amount = withdrawAmountInput ? parseFloat(withdrawAmountInput.value) : 0;

                        if (!address || amount <= 0) {
                            withdrawReviewBtn.innerHTML = '<i class="fas fa-exclamation-circle"></i> Enter address and amount';
                            setTimeout(function () {
                                withdrawReviewBtn.innerHTML = '<i class="fas fa-paper-plane"></i> Review Withdrawal';
                            }, 1600);
                            return;
                        }

                        withdrawReviewBtn.innerHTML = '<i class="fas fa-check"></i> Ready for Confirmation';
                    });
                }

                var depositModalEl = document.getElementById('assetDepositModal');
                if (depositModalEl) {
                    depositModalEl.addEventListener('hidden.bs.offcanvas', function () {
                        resetDepositFlow();
                    });
                }

                var withdrawModalEl = document.getElementById('assetWithdrawModal');
                if (withdrawModalEl) {
                    withdrawModalEl.addEventListener('hidden.bs.offcanvas', function () {
                        resetWithdrawFlow();
                    });
                }

                resetDepositFlow();
                resetWithdrawFlow();
            }

            function applySwapDefaults() {
                var modal = document.getElementById('swapModal');
                var cfg = window.assetSwapDefaults || null;
                if (!modal || !cfg) {
                    return;
                }

                var payInput = modal.querySelector('.swap-input-box:first-child input');
                var receiveInput = modal.querySelector('.swap-input-box:nth-child(3) input');
                var reviewOrderBtn = modal.querySelector('.mt-4.mt-auto.w-100 button.btn-pro.btn-pro-primary');

                var defaultState = {
                    payInput: payInput ? payInput.value : '',
                    receiveInput: receiveInput ? receiveInput.value : '',
                    reviewBtnHtml: reviewOrderBtn ? reviewOrderBtn.innerHTML : '',
                };

                var boxes = modal.querySelectorAll('.swap-input-box');
                if (!boxes || boxes.length < 2) {
                    return;
                }

                var receiveBox = boxes[1];
                var receiveSelector = receiveBox.querySelector('.asset-selector');
                if (receiveSelector) {
                    var symbolEl = receiveSelector.querySelector('span');
                    if (symbolEl) {
                        symbolEl.textContent = cfg.symbol;
                    }

                    var iconWrap = receiveSelector.querySelector('div');
                    if (iconWrap) {
                        iconWrap.style.background = 'rgba(0,0,0,0.05)';
                        iconWrap.style.color = cfg.color || '#10b981';
                        var iconEl = iconWrap.querySelector('i');
                        if (iconEl) {
                            iconEl.className = cfg.icon || 'fas fa-coins';
                        }
                    }
                }

                var receiveBalance = receiveBox.querySelector('.d-flex.justify-content-between.align-items-center.mb-3 div:last-child');
                if (receiveBalance) {
                    receiveBalance.textContent = 'Bal: ' + cfg.amountDisplay + ' ' + cfg.symbol;
                }

                var rateValue = modal.querySelector('.exchange-info .d-flex.justify-content-between.align-items-center.mb-2 div:last-child');
                if (rateValue) {
                    rateValue.textContent = '1 GBP = -- ' + cfg.symbol;
                }

                var helper = modal.querySelector('.mt-4.mt-auto.w-100 .text-center p');
                if (helper) {
                    helper.innerHTML = '<i class="fas fa-shield-alt text-success me-1"></i> Swaps restricted to GBP \u2194 ' + cfg.symbol + ' securely.';
                }

                modal.addEventListener('hidden.bs.offcanvas', function () {
                    if (payInput) {
                        payInput.value = defaultState.payInput;
                    }
                    if (receiveInput) {
                        receiveInput.value = defaultState.receiveInput;
                    }
                    if (reviewOrderBtn && defaultState.reviewBtnHtml) {
                        reviewOrderBtn.innerHTML = defaultState.reviewBtnHtml;
                    }

                    var scrollBody = modal.querySelector('.chat-body');
                    if (scrollBody) {
                        scrollBody.scrollTop = 0;
                    }
                });
            }

            document.addEventListener('DOMContentLoaded', function () {
                initTransferModals();
                applySwapDefaults();
            });
        })();
    </script>
    <script src="../assets/js/asset_details.js"></script>
</body>
</html>
