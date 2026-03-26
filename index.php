<?php
// FinPay End-User Presentation Homepage (Revolut Architecture)
// V6: Void Fillers, Orbital Crypto Bag, & Trust Badges
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FinPay - One app, all things money</title>
    <!-- Fonts: Inter for the hyper-clean brutalist aesthetic -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <!-- Bootstrap CSS for grid -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --r-black: #191c1f;
            --r-dark: #000000;
            --r-white: #ffffff;
            --r-gray-100: #fcfcfc;
            --r-gray-200: #f3f4f6;
            --r-gray-300: #e5e7eb;
            --r-gray-text: #6b7280;
            --r-blue: #2563eb;
            --r-blue-light: #eff6ff;
            --r-green: #10b981;
            --r-purple: #8b5cf6;
            --r-pink: #ec4899;
        }

        * { font-family: 'Inter', sans-serif; box-sizing: border-box; }
        
        body {
            background-color: var(--r-white);
            color: var(--r-black);
            -webkit-font-smoothing: antialiased;
            overflow-x: hidden;
        }

        /* Abstract Global Mesh Gradient for the Hero */
        .global-mesh {
            position: absolute; top: 0; left: 0; width: 100%; height: 1000px;
            background: 
                radial-gradient(ellipse at 15% 0%, rgba(37,99,235,0.06), transparent 50%),
                radial-gradient(ellipse at 85% 10%, rgba(139,92,246,0.04), transparent 50%);
            z-index: -1; pointer-events: none;
        }

        /* Essential Typography */
        h1, h2, h3, h4 { font-weight: 800; letter-spacing: -0.04em; color: var(--r-dark); }
        .headline-super { font-size: clamp(3rem, 10vw, 8.5rem); line-height: 0.95; padding: 0 1rem; }
        .headline-section { font-size: clamp(2.5rem, 5vw, 4.5rem); line-height: 1.05; margin-bottom: 1.5rem; }
        .headline-card { font-size: clamp(1.8rem, 3vw, 2.5rem); line-height: 1.1; margin-bottom: 1rem; }
        .text-body-large { font-size: 1.25rem; color: var(--r-gray-text); font-weight: 500; line-height: 1.5; }
        .text-body { font-size: 1rem; color: var(--r-gray-text); font-weight: 500; line-height: 1.6; }

        /* Minimalist Navbar */
        .navbar-rev {
            background: rgba(255,255,255,0.85);
            backdrop-filter: blur(24px); -webkit-backdrop-filter: blur(24px);
            padding: 1.2rem 2rem; position: fixed; width: 100%; top: 0; z-index: 1000;
            display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid rgba(0,0,0,0.03);
        }
        
        @media (max-width: 768px) {
            .navbar-rev { padding: 1rem; justify-content: space-between; }
        }

        .nav-logo { font-size: 1.5rem; font-weight: 900; color: var(--r-dark); text-decoration: none; letter-spacing: -1px; }
        .nav-links a { color: var(--r-black); font-weight: 600; font-size: 0.95rem; margin: 0 1.2rem; text-decoration: none; transition: opacity 0.2s; }
        .nav-links a:hover { opacity: 0.6; }
        .btn-rev {
            background: var(--r-dark); color: var(--r-white); padding: 0.8rem 1.8rem;
            border-radius: 100px; font-weight: 700; font-size: 0.95rem; text-decoration: none;
            transition: transform 0.2s, background 0.2s; border: none; display: inline-block;
        }
        .btn-rev:hover { transform: scale(1.02); background: #333; color: var(--r-white); }
        
        .btn-rev-light {
            background: rgba(255,255,255,0.1); color: var(--r-white); padding: 0.8rem 1.8rem;
            backdrop-filter: blur(10px); -webkit-backdrop-filter: blur(10px); border: 1px solid rgba(255,255,255,0.2);
            border-radius: 100px; font-weight: 700; font-size: 0.95rem; text-decoration: none;
            display: inline-block; transition: all 0.2s; 
        }
        .btn-rev-light:hover { background: var(--r-white); color: var(--r-dark); transform: scale(1.02); }

        /* Blocks & Grids */
        .presentation-wrap { max-width: 1400px; margin: 0 auto; padding: 0 1.5rem; }
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 1.5rem; }
        @media (max-width: 991px) { .grid-2 { grid-template-columns: 1fr; } }

        /* Premium Subtle Gradients injected into Structural Blocks */
        .p-block {
            background: linear-gradient(145deg, var(--r-white) 0%, var(--r-gray-200) 100%);
            border-radius: 40px; padding: 4rem; position: relative; overflow: hidden; display: flex; flex-direction: column;
            min-height: 650px; text-decoration: none; color: inherit; perspective: 1200px;
        }
        .p-block-dark { 
            background: radial-gradient(circle at top right, rgba(37,99,235,0.15) 0%, transparent 60%), 
                        radial-gradient(circle at bottom left, rgba(16,185,129,0.08) 0%, transparent 50%), 
                        var(--r-dark); 
            color: var(--r-white); 
        }
        .p-block-dark h2, .p-block-dark h3 { color: var(--r-white); }
        .p-block-dark .text-body-large { color: #a1a1aa; }
        
        .p-block-blue { background: linear-gradient(135deg, #ffffff 0%, #eff6ff 100%); }

        @media (max-width: 768px) {
            .p-block { padding: 2.5rem 1.5rem; border-radius: 28px; }
            .grid-2 .p-block { min-height: auto !important; display: flex; flex-direction: column; gap: 1.5rem; justify-content: flex-start; }
            .grid-2 .text-max-500 { padding-bottom: 0px; position: relative; margin-bottom: 0; }
            .grid-2 .ui-interactive { position: relative !important; right: auto !important; left: auto !important; bottom: auto !important; top: auto !important; transform: none !important; margin: 0 auto !important; width: 100% !important; max-width: 320px !important; }
            .hide-mobile, .mock-cc-light { display: none !important; }
            .headline-card { font-size: 1.5rem; line-height: 1.2; }
        }

        /* 3D JS Interactive Mockups */
        .ui-interactive {
            position: absolute; box-shadow: 0 24px 60px rgba(0,0,0,0.08);
            transition: transform 0.8s cubic-bezier(0.2, 0.8, 0.2, 1);
            transform-style: preserve-3d; will-change: transform;
        }

        /* Orbital Widget Container */
        .mock-orbital-container {
            position: relative; width: 100%; max-width: 600px; height: 350px; 
            margin: 3rem auto 0 auto; transform-style: preserve-3d;
        }
        
        /* Inner Cards */
        .mock-bal-main {
            width: 380px; background: rgba(255,255,255,0.95); backdrop-filter: blur(16px);
            border-radius: 32px; padding: 2.5rem; border: 1px solid rgba(255,255,255,0.2);
            box-shadow: 0 30px 60px rgba(0,0,0,0.25); 
            left: 50%; top: 50%; transform: translate(-50%, -50%); z-index: 3;
        }
        .mock-bal-stat { font-size: 3.2rem; font-weight: 900; color: var(--r-dark); letter-spacing: -2px; line-height: 1; margin: 15px 0 25px 0; }
        
        .mock-tx-orb-1 { width: 250px; background: rgba(255,255,255,0.95); backdrop-filter: blur(10px); border-radius: 20px; padding: 1.2rem; box-shadow: 0 20px 40px rgba(0,0,0,0.2); left: -5%; top: 10%; --base-rot: -6deg; transform: rotate(var(--base-rot)); z-index: 2; }
        .mock-tx-orb-2 { width: 260px; background: rgba(255,255,255,0.95); backdrop-filter: blur(10px); border-radius: 20px; padding: 1.2rem; box-shadow: 0 20px 40px rgba(0,0,0,0.2); right: -8%; bottom: 10%; --base-rot: 8deg; transform: rotate(var(--base-rot)); z-index: 4; }

        @media (max-width: 768px) {
            .mock-orbital-container { transform: scale(0.85); margin-top: 1rem; }
            .mock-tx-orb-1 { left: 0%; top: 0%; }
            .mock-tx-orb-2 { right: 0%; bottom: 0%; }
        }

        .p-block:hover .mock-tx-orb-1 { transform: rotate(-10deg) translateX(-10px) scale(1.05); }
        .p-block:hover .mock-tx-orb-2 { transform: rotate(12deg) translateX(10px) translateY(10px) scale(1.05); }

        /* Other Utilities */
        .mock-btn { flex: 1; height: 50px; background: var(--r-blue); border-radius: 100px; color: white; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 0.95rem; }
        .mock-btn.secondary { background: var(--r-gray-200); color: var(--r-dark); }
        .mock-av { width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; color: white; }
        
        /* Transfer Card */
        .mock-transfer-card { background: var(--r-white); border-radius: 24px; padding: 1.5rem; width: 320px; right: 40px; bottom: 50px; z-index: 3;}
        @media (max-width: 768px) { .mock-transfer-card { width: calc(100% - 40px); right: 20px; bottom: 20px; } }
        .mock-amt { font-size: 3rem; font-weight: 900; letter-spacing: -2px; color: var(--r-dark); margin: 10px 0; border-bottom: 1px solid var(--r-gray-300); padding-bottom: 10px; }
        
        /* Credit Cards */
        .mock-cc { width: 320px; height: 200px; background: linear-gradient(135deg, #111, #333); border: 1px solid rgba(255,255,255,0.1); border-radius: 20px; color: white; padding: 1.5rem; display: flex; flex-direction: column; justify-content: space-between; right: -20px; bottom: 80px; --base-rot: -10deg; transform: rotate(var(--base-rot)); transition: transform 0.6s cubic-bezier(0.2, 0.8, 0.2, 1); }
        .mock-cc-light { background: linear-gradient(135deg, #fff, #f8f9fa); color: var(--r-dark); border: 1px solid rgba(0,0,0,0.05); --base-rot: 5deg; bottom: 40px; right: 20px; z-index: -1; }
        .p-block:hover .mock-cc { transform: rotate(0deg) translateY(-20px) scale(1.05); }
        .p-block:hover .mock-cc-light { transform: rotate(15deg) translateX(30px) translateY(-10px) scale(0.95); }
        @media (max-width: 768px) { .mock-cc { width: 280px; height: 175px; right: 5%; bottom: 60px; } .mock-cc-light { right: 10%; bottom: 20px;} }

        /* The Bag of Crypto Orbital Cloud */
        .mock-crypto { width: 70px; height: 70px; border-radius: 20px; color: white; display: flex; align-items: center; justify-content: center; font-size: 2rem; position: absolute; box-shadow: 0 20px 40px rgba(0,0,0,0.3); transition: transform 0.6s cubic-bezier(0.2, 0.8, 0.2, 1); border: 1px solid rgba(255,255,255,0.15); font-weight: 800; font-family: monospace; z-index: 5; }
        
        .c-btc { background: linear-gradient(135deg, #fbc531, #f7931a); right: 20%; top: 35%; --base-rot: 12deg; transform: scale(1.3) rotate(var(--base-rot)); z-index: 10; }
        .c-eth { background: linear-gradient(135deg, #819bfa, #627eea); left: 75%; bottom: 25%; --base-rot: -8deg; transform: scale(1.1) rotate(var(--base-rot)); z-index: 8; }
        .c-usdt { background: linear-gradient(135deg, #10b981, #059669); right: 10%; bottom: 35%; --base-rot: -15deg; transform: scale(0.9) rotate(var(--base-rot)); z-index: 6; }
        .c-sol { background: linear-gradient(135deg, #14F195, #9945FF); right: 40%; top: 20%; --base-rot: 20deg; transform: scale(0.85) rotate(var(--base-rot)); z-index: 4; }
        .c-ada { background: linear-gradient(135deg, #0ea5e9, #0284c7); left: 80%; top: 15%; --base-rot: 5deg; transform: scale(0.8) rotate(var(--base-rot)); z-index: 3; }
        .c-doge { background: linear-gradient(135deg, #fbbf24, #d97706); right: 25%; top: 8%; --base-rot: -25deg; transform: scale(0.65) rotate(var(--base-rot)); z-index: 2; opacity: 0.8; filter: blur(2px); }
        .c-shib { background: linear-gradient(135deg, #ef4444, #b91c1c); right: 30%; bottom: 8%; --base-rot: 15deg; transform: scale(0.6) rotate(var(--base-rot)); z-index: 1; opacity: 0.7; filter: blur(3px); }

        .p-block:hover .c-btc { transform: rotate(0deg) scale(1.4) translateY(-20px); }
        .p-block:hover .c-eth { transform: rotate(0deg) scale(1.2) translateY(10px); }
        .p-block:hover .c-sol { transform: rotate(5deg) scale(0.9) translateX(-15px); }

        @media (max-width: 768px) {
            .mock-crypto { transform: scale(0.6) !important; filter: none !important; opacity: 1 !important; }
            .c-sol, .c-ada, .c-doge, .c-shib { display: none; /* Hide noise on mobile */ }
        }

        /* Infinite Marquee */
        .marquee-container { width: 100%; overflow: hidden; white-space: nowrap; position: relative; padding: 4rem 0 2rem; mask-image: linear-gradient(to right, transparent, black 10%, black 90%, transparent); -webkit-mask-image: linear-gradient(to right, transparent, black 10%, black 90%, transparent); }
        .marquee-content { display: inline-flex; gap: 4rem; animation: scroll-left 30s linear infinite; align-items: center; }
        .marquee-content:hover { animation-play-state: paused; }
        .marquee-item { font-size: 1.5rem; font-weight: 800; color: var(--r-gray-text); display: flex; align-items: center; gap: 0.75rem; opacity: 0.4; transition: opacity 0.3s, color 0.3s, transform 0.3s; cursor: default; }
        .marquee-item:hover { opacity: 1; color: var(--r-dark); transform: scale(1.05); }
        @keyframes scroll-left { from { transform: translateX(0); } to { transform: translateX(calc(-50% - 2rem)); } }

        /* Security Block Responsive */
        @media (max-width: 991px) { .security-grid { grid-template-columns: 1fr !important; gap: 2rem !important; padding: 2rem 1.5rem !important; } }

        /* Chart Animations */
        .chart-bar { transform-origin: bottom; transform: scaleY(0.1); transition: transform 0.8s cubic-bezier(0.2, 0.8, 0.2, 1); }
        .reveal.active .chart-bar { transform: scaleY(1); }

        /* Component Transitions */
        .reveal { opacity: 0; transform: translateY(40px); transition: 1s cubic-bezier(0.2,0.8,0.2,1); }
        .reveal.active { opacity: 1; transform: translateY(0); }
        
        /* Layout formatting */
        .text-max-800 { max-width: 800px; margin: 0 auto; position: relative; z-index: 10; }
        .text-max-500 { max-width: 500px; position: relative; z-index: 10; padding-bottom: 20px; }
        .padding-section { padding: 140px 0; }
        .pt-hero { padding-top: 220px; padding-bottom: 100px; }
    </style>
</head>
<body>

    <!-- Catchy Global Mesh -->
    <div class="global-mesh"></div>

    <nav class="navbar-rev">
        <a href="index.php" class="nav-logo">finpay</a>
        
        <div class="nav-links d-none d-md-flex">
            <a href="#features" class="opacity-75">Features</a>
            <a href="#cards" class="opacity-75">Cards</a>
            <a href="#crypto" class="opacity-75">Crypto</a>
            <a href="#wealth" class="opacity-75">Wealth</a>
        </div>

        <div class="d-flex align-items-center gap-3">
            <a href="user/login.php" class="nav-links d-none d-sm-block text-decoration-none fw-bold" style="color: var(--r-dark);">Log in</a>
            <a href="user/index.php" class="btn-rev">Sign up</a>
        </div>
    </nav>

    <main class="presentation-wrap">
        
        <!-- Brutalist Hero -->
        <section class="text-center pt-hero reveal">
            <h1 class="headline-super">One app,<br>all things money.</h1>
            <p class="hero-sub mt-4">From easy money management to crypto trading. Open your FinPay account in a flash and take control of your financial life.</p>
            <div class="d-flex justify-content-center gap-3 mt-5">
                <a href="user/index.php" class="btn-rev" style="padding: 1rem 3rem; font-size: 1.1rem;">Get a free account</a>
            </div>

            <!-- Trust Badges directly beneath CTA to fill the empty Hero void -->
            <div class="d-flex flex-wrap justify-content-center align-items-center gap-4 mt-5 pt-4" style="opacity: 0.5;">
                <div class="d-flex align-items-center gap-2">
                    <div style="color: #000; font-size: 1rem;"><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star-half-alt"></i></div>
                    <div style="font-weight: 700; font-size: 0.85rem;">4.9 App Store</div>
                </div>
                <div class="d-none d-sm-block" style="width: 1px; height: 16px; background: var(--r-dark);"></div>
                <div style="font-weight: 700; font-size: 0.85rem;"><i class="fas fa-shield-alt me-2"></i>FCA Regulated</div>
                <div class="d-none d-sm-block" style="width: 1px; height: 16px; background: var(--r-dark);"></div>
                <div style="font-weight: 700; font-size: 0.85rem;"><i class="fas fa-users me-2"></i>1M+ Active Users</div>
            </div>
        </section>

        <!-- Infinite Integrated Partners Marquee -->
        <div class="marquee-container reveal">
            <div class="marquee-content">
                <!-- Set 1 -->
                <div class="marquee-item"><i class="fab fa-apple"></i> Apple Pay</div>
                <div class="marquee-item"><i class="fab fa-google"></i> Google Pay</div>
                <div class="marquee-item"><i class="fab fa-cc-visa"></i> Visa</div>
                <div class="marquee-item"><i class="fab fa-cc-mastercard"></i> Mastercard</div>
                <div class="marquee-item"><i class="fab fa-stripe"></i> Stripe</div>
                <div class="marquee-item"><i class="fab fa-paypal"></i> PayPal</div>
                <div class="marquee-item"><i class="fab fa-bitcoin"></i> Crypto</div>
                <!-- Set 2 (Duplicated) -->
                <div class="marquee-item"><i class="fab fa-apple"></i> Apple Pay</div>
                <div class="marquee-item"><i class="fab fa-google"></i> Google Pay</div>
                <div class="marquee-item"><i class="fab fa-cc-visa"></i> Visa</div>
                <div class="marquee-item"><i class="fab fa-cc-mastercard"></i> Mastercard</div>
                <div class="marquee-item"><i class="fab fa-stripe"></i> Stripe</div>
                <div class="marquee-item"><i class="fab fa-paypal"></i> PayPal</div>
                <div class="marquee-item"><i class="fab fa-bitcoin"></i> Crypto</div>
            </div>
        </div>

        <!-- Block 1: Catchy Orbital Balance Mockup -->
        <section class="p-block p-block-dark mb-4 reveal text-center interactive-block" style="min-height: 750px;">
            <div class="text-max-800 mx-auto" style="pointer-events: none;">
                <h2 class="headline-section text-white">Manage your entire financial life in one place.</h2>
                <p class="text-body-large text-white opacity-75">Track balances, send money instantly, and review your analytics without leaving the app.</p>
            </div>
            
            <div class="mock-orbital-container target-3d">
                
                <!-- Center Core Component -->
                <div class="ui-interactive mock-bal-main" style="--base-rot: 0deg;">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div style="font-weight: 700; color: var(--r-gray-text); font-size: 0.85rem; letter-spacing: 1px;">TOTAL BALANCE</div>
                        <div style="background: rgba(16,185,129,0.1); color: var(--r-green); padding: 6px 14px; border-radius: 100px; font-weight: 800; font-size: 0.8rem;"><i class="fas fa-chart-line me-1"></i> +4.2%</div>
                    </div>
                    <div class="mock-bal-stat">£14,250<span style="font-size: 1.5rem; color: var(--r-gray-text);">.00</span></div>
                    <div class="d-flex gap-2">
                        <div class="mock-btn"><i class="fas fa-arrow-up me-2"></i> Send</div>
                        <div class="mock-btn secondary"><i class="fas fa-plus me-2"></i> Add</div>
                    </div>
                </div>

                <!-- Floating Satellite 1 -->
                <div class="ui-interactive mock-tx-orb-1">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center">
                            <div class="mock-av" style="background: linear-gradient(135deg, #34d399, #10b981);"><i class="fas fa-briefcase"></i></div>
                            <div class="ms-3 text-start">
                                <div style="font-weight: 800; font-size: 0.95rem; color: var(--r-dark);">Salary</div>
                                <div style="font-size: 0.75rem; color: var(--r-gray-text);">Yesterday</div>
                            </div>
                        </div>
                        <div style="font-weight: 900; color: var(--r-green); font-size: 1.1rem;">+£4.2k</div>
                    </div>
                </div>

                <!-- Floating Satellite 2 -->
                <div class="ui-interactive mock-tx-orb-2">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center">
                            <div class="mock-av" style="background: #111;"><i class="fab fa-apple"></i></div>
                            <div class="ms-3 text-start">
                                <div style="font-weight: 800; font-size: 0.95rem; color: var(--r-dark);">Apple Store</div>
                                <div style="font-size: 0.75rem; color: var(--r-gray-text);">Today</div>
                            </div>
                        </div>
                        <div style="font-weight: 900; color: var(--r-dark); font-size: 1.1rem;">-£99<span style="font-size: 0.8rem; color: var(--r-gray-text);">.00</span></div>
                    </div>
                </div>

            </div>
        </section>

        <!-- Feature Grid (2x2) -->
        <div class="grid-2" id="features">
            
            <!-- Send & Receive -->
            <div class="p-block reveal interactive-block">
                <div class="text-max-500">
                    <h3 class="headline-card">Sending money is as easy as sending a text.</h3>
                    <p class="text-body">Send and request money effortlessly from friends, family, or global businesses in seconds. No hidden fees.</p>
                </div>
                
                <!-- Main Transfer UI -->
                <div class="ui-interactive mock-transfer-card shadow-sm border border-light target-3d" style="--base-rot: 0deg;">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div style="font-weight: 700; color: var(--r-gray-text); font-size: 0.85rem;">SEND TO</div>
                        <div style="background: rgba(37,99,235,0.1); color: var(--r-blue); padding: 4px 10px; border-radius: 100px; font-weight: 700; font-size: 0.75rem;">UK (GBP)</div>
                    </div>
                    <div class="d-flex align-items-center mb-3">
                        <div class="mock-av" style="background: linear-gradient(135deg, #fbcfe8, #ec4899);"><i class="fas fa-user text-white opacity-75"></i></div>
                        <div class="ms-3">
                            <div style="font-weight: 700; color: var(--r-dark);">Sarah Jenkins</div>
                            <div style="font-size: 0.8rem; color: var(--r-gray-text);">@sarahj</div>
                        </div>
                    </div>
                    <div class="mock-amt">£150.00</div>
                    <div class="mock-btn mt-3 w-100" style="height: 44px; border-radius: 12px; transition: transform 0.2s;">Send Instantly <i class="fas fa-bolt ms-2"></i></div>
                </div>

                <!-- Secondary Orbital Card filling the void -->
                <div class="ui-interactive hide-mobile shadow-sm border target-3d" style="background: rgba(255,255,255,0.95); backdrop-filter: blur(10px); padding: 1.2rem; width: 260px; border-radius: 20px; right: 80px; bottom: 220px; --base-rot: -8deg; transform: rotate(-8deg); z-index: 1;">
                    <div style="font-weight: 700; color: var(--r-gray-text); font-size: 0.75rem; margin-bottom: 5px; letter-spacing: 1px;">REQUEST FROM</div>
                    <div class="d-flex align-items-center mb-2">
                        <div class="mock-av" style="background: var(--r-purple); width: 30px; height: 30px; font-size: 0.8rem;">MJ</div>
                        <div class="ms-2" style="font-weight: 700; font-size: 0.85rem;">Mike J.</div>
                    </div>
                    <div style="font-size: 1.5rem; font-weight: 900; color: var(--r-dark);">£50.00</div>
                </div>
            </div>

            <!-- Virtual Cards -->
            <div class="p-block p-block-blue reveal interactive-block" id="cards">
                <div class="text-max-500">
                    <h3 class="headline-card">A card for every occasion.</h3>
                    <p class="text-body">Generate virtual cards instantly for online shopping, or order a sleek physical card. Freeze directly in the app.</p>
                </div>
                
                <!-- 3D Stacking Cards -->
                <div class="ui-interactive mock-cc mock-cc-light target-3d-slow">
                    <div class="d-flex justify-content-between">
                        <div style="font-weight: 900; font-size: 1.2rem; letter-spacing: -1px;">finpay</div>
                        <i class="fas fa-wifi" style="transform: rotate(90deg);"></i>
                    </div>
                    <div>
                        <div style="font-family: monospace; font-size: 1.1rem; letter-spacing: 2px;">**** **** **** 4921</div>
                        <div class="d-flex justify-content-between align-items-end mt-2">
                            <div style="font-size: 0.8rem; font-weight: 600;">J. DOE</div>
                            <i class="fab fa-cc-visa fs-2"></i>
                        </div>
                    </div>
                </div>
                <div class="ui-interactive mock-cc shadow-lg target-3d">
                    <div class="d-flex justify-content-between">
                        <div style="font-weight: 900; font-size: 1.2rem; letter-spacing: -1px; color: white;">finpay</div>
                        <i class="fas fa-wifi" style="transform: rotate(90deg); color: white;"></i>
                    </div>
                    <div>
                        <div style="font-family: monospace; font-size: 1.1rem; letter-spacing: 2px; color: white;">**** **** **** 8832</div>
                        <div class="d-flex justify-content-between align-items-end mt-2">
                            <div style="font-size: 0.8rem; font-weight: 600; color: white;">JAMES DOE</div>
                            <i class="fab fa-cc-mastercard fs-2 text-white"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Analytics -->
            <div class="p-block reveal interactive-block" id="wealth">
                <div class="text-max-500">
                    <h3 class="headline-card">Track, budget, and conquer.</h3>
                    <p class="text-body">Visualize your spending explicitly. FinPay categorizes your transactions perfectly so you know exactly where your capital is going.</p>
                </div>
                
                <div class="ui-interactive mock-transfer-card border-0 shadow-lg target-3d" style="width: 300px; background: white; --base-rot: 0deg;">
                    <div style="font-weight: 700; font-size: 1.2rem; margin-bottom: 20px; color: var(--r-dark);">Spent this month</div>
                    <div style="font-size: 2.5rem; font-weight: 900; color: var(--r-dark); margin-bottom: 30px; letter-spacing: -2px;">£1,420</div>
                    
                    <div style="height: 120px; display: flex; align-items: flex-end; gap: 15px; border-bottom: 2px solid var(--r-gray-200); padding-bottom: 10px;">
                        <div class="chart-bar" style="width: 40px; height: 40%; background: var(--r-gray-300); border-radius: 8px 8px 0 0; transition-delay: 0.1s;"></div>
                        <div class="chart-bar" style="width: 40px; height: 70%; background: var(--r-gray-300); border-radius: 8px 8px 0 0; transition-delay: 0.2s;"></div>
                        <div class="chart-bar" style="width: 40px; height: 30%; background: var(--r-gray-300); border-radius: 8px 8px 0 0; transition-delay: 0.3s;"></div>
                        <div class="chart-bar" style="width: 40px; height: 90%; background: linear-gradient(to top, var(--r-blue), #60a5fa); border-radius: 8px 8px 0 0; box-shadow: 0 5px 20px rgba(37,99,235,0.3); transition-delay: 0.4s;"></div>
                        <div class="chart-bar" style="width: 40px; height: 50%; background: var(--r-gray-300); border-radius: 8px 8px 0 0; transition-delay: 0.5s;"></div>
                    </div>
                </div>

                <!-- Floating Tag filling the top-right void -->
                <div class="ui-interactive hide-mobile shadow-lg target-3d" style="background: rgba(255,255,255,0.95); backdrop-filter: blur(10px); padding: 12px 20px; border-radius: 100px; color: var(--r-dark); font-weight: 800; right: 30px; top: 120px; --base-rot: 8deg; transform: rotate(8deg); display: flex; align-items: center; gap: 10px;">
                    <div style="width: 12px; height: 12px; background: var(--r-pink); border-radius: 50%;"></div> Groceries
                </div>
            </div>

            <!-- Currency/FX -->
            <div class="p-block reveal interactive-block" style="background: radial-gradient(circle at top left, #fef3c7, #fde68a);">
                <div class="text-max-500">
                    <h3 class="headline-card">Exchange currency instantly.</h3>
                    <p class="text-body" style="color: #92400e;">Hold over 30 currencies and exchange them in real-time at excellent rates. Perfect for your next trip globally.</p>
                </div>
                
                <div class="ui-interactive mock-transfer-card shadow-lg border-0 target-3d" style="--base-rot: 0deg;">
                    <div style="background: var(--r-gray-100); border-radius: 16px; padding: 15px; margin-bottom: 10px;">
                        <div style="font-size: 0.8rem; font-weight: 700; color: var(--r-gray-text);">YOU SELL</div>
                        <div class="d-flex justify-content-between align-items-center mt-2">
                            <div style="font-size: 2rem; font-weight: 900; color: var(--r-dark); letter-spacing: -2px;">£1,000</div>
                            <div style="font-weight: 700; background: white; padding: 8px 12px; border-radius: 100px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); color: var(--r-dark);">GBP <i class="fas fa-chevron-down ms-1 fs-6"></i></div>
                        </div>
                    </div>
                    <div style="text-align: center; margin: -15px 0; position: relative; z-index: 2;">
                        <div style="width: 36px; height: 36px; background: var(--r-blue); color: white; border-radius: 50%; display: inline-flex; justify-content: center; align-items: center; border: 4px solid white;"><i class="fas fa-arrow-down"></i></div>
                    </div>
                    <div style="background: var(--r-gray-100); border-radius: 16px; padding: 15px;">
                        <div style="font-size: 0.8rem; font-weight: 700; color: var(--r-gray-text);">YOU GET</div>
                        <div class="d-flex justify-content-between align-items-center mt-2">
                            <div style="font-size: 2rem; font-weight: 900; color: var(--r-blue); letter-spacing: -2px;">€1,168</div>
                            <div style="font-weight: 700; background: white; padding: 8px 12px; border-radius: 100px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); color: var(--r-dark);">EUR <i class="fas fa-chevron-down ms-1 fs-6"></i></div>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <!-- Full Width Crypto Presentation: The Cloud Bag -->
        <section class="p-block p-block-dark mb-5 reveal interactive-block" style="min-height: 600px; background: radial-gradient(circle at right center, rgba(37,99,235,0.1), transparent 50%), radial-gradient(circle at left bottom, rgba(236,72,153,0.05), transparent 40%), var(--r-dark);" id="crypto">
            <div class="text-max-500" style="pointer-events: none;">
                <h2 class="headline-section text-white">Crypto, without the cryptic.</h2>
                <p class="text-body-large text-white opacity-75">Buy, sell, and hold Bitcoin, Ethereum, Solana, and 100+ other tokens. Start building your portfolio with just £1.</p>
                <div class="mt-4 pointer-events-auto">
                    <a href="user/index.php" class="btn-rev-light">Explore 100+ Tokens</a>
                </div>
            </div>
            
            <!-- Professional Orbital Bag of Cryptos -->
            <div class="ui-interactive mock-crypto c-btc target-3d"><i class="fab fa-bitcoin"></i></div>
            <div class="ui-interactive mock-crypto c-eth target-3d"><i class="fab fa-ethereum"></i></div>
            <div class="ui-interactive mock-crypto c-usdt target-3d-slow" style="font-size: 1.1rem;">USDT</div>
            <div class="ui-interactive mock-crypto c-sol target-3d" style="font-size: 1.4rem;">SOL</div>
            <div class="ui-interactive mock-crypto c-ada target-3d-slow" style="font-size: 1.4rem;">ADA</div>
            <div class="ui-interactive mock-crypto c-doge target-3d" style="font-size: 1.6rem;">Ð</div>
            <div class="ui-interactive mock-crypto c-shib target-3d-slow" style="font-size: 0.8rem;">SHIB</div>
        </section>

    </main>



    <!-- Revolut-style Footer CTA -->
    <section class="padding-section text-center reveal" style="background: var(--r-white); border-top: 1px solid var(--r-gray-200);">
        <h2 class="headline-super mb-4">Join 1M+ users.</h2>
        <div class="d-flex justify-content-center gap-3">
            <a href="user/index.php" class="btn-rev" style="padding: 1.2rem 3rem; font-size: 1.2rem;">Sign up in minutes</a>
        </div>
    </section>

    <!-- Very clean Footer -->
    <footer style="background: var(--r-gray-100); padding: 5rem 0 3rem; border-top: 1px solid var(--r-gray-300);">
        <div class="presentation-wrap">
            <div class="row mb-5">
                <div class="col-lg-3">
                    <a href="index.php" class="nav-logo">finpay</a>
                </div>
                <div class="col-lg-2 col-6">
                    <div style="font-weight: 800; margin-bottom: 1.5rem;">Services</div>
                    <ul class="list-unstyled">
                        <li class="mb-2"><a href="#" style="color: var(--r-gray-text); text-decoration: none; font-weight: 600;">Transfers</a></li>
                        <li class="mb-2"><a href="#" style="color: var(--r-gray-text); text-decoration: none; font-weight: 600;">Cards</a></li>
                        <li class="mb-2"><a href="#" style="color: var(--r-gray-text); text-decoration: none; font-weight: 600;">Crypto</a></li>
                    </ul>
                </div>
                <div class="col-lg-2 col-6">
                    <div style="font-weight: 800; margin-bottom: 1.5rem;">Company</div>
                    <ul class="list-unstyled">
                        <li class="mb-2"><a href="#" style="color: var(--r-gray-text); text-decoration: none; font-weight: 600;">About</a></li>
                        <li class="mb-2"><a href="#" style="color: var(--r-gray-text); text-decoration: none; font-weight: 600;">Careers</a></li>
                        <li class="mb-2"><a href="#" style="color: var(--r-gray-text); text-decoration: none; font-weight: 600;">Legal</a></li>
                    </ul>
                </div>
            </div>
            <div style="font-size: 0.8rem; color: #a1a1aa; border-top: 1px solid var(--r-gray-300); padding-top: 2rem;">
                FinPay is a financial technology company, not a bank. The FinPay Card is issued by Partner Banks. Cryptocurrency services are provided by FinPay Digital Assets LLC. Investing involves risk. <br><br>
                &copy; <?php echo date('Y'); ?> FinPay Inc. All rights reserved.
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // 1. Initial CSS Object Intersection Animations (Fades only, no numbers)
            const observer = new IntersectionObserver((entries, obs) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('active');
                        obs.unobserve(entry.target);
                    }
                });
            }, { threshold: 0.2 });

            document.querySelectorAll('.reveal').forEach(el => observer.observe(el));

            // 2. High-Fidelity 3D Pointer Events (Hover Parallax)
            // Desktop only checking
            if(window.innerWidth > 768) {
                const interactiveBlocks = document.querySelectorAll('.interactive-block');
                
                interactiveBlocks.forEach(block => {
                    let targets = Array.from(block.querySelectorAll('.target-3d, .target-3d-slow'));
                    
                    block.addEventListener('mousemove', (e) => {
                        const rect = block.getBoundingClientRect();
                        
                        // Calculate mouse position relative to center [-1 to 1]
                        const x = ((e.clientX - rect.left) / rect.width - 0.5) * 2;
                        const y = ((e.clientY - rect.top) / rect.height - 0.5) * 2;
                        
                        // Request animation frame for buttery smooth hardware acceleration
                        window.requestAnimationFrame(() => {
                            targets.forEach(target => {
                                // Strip existing transforms and apply dynamic 3d perspective
                                const intensity = target.classList.contains('target-3d-slow') ? 4 : 10;
                                
                                // Recover base rotation if it exists
                                const computedStyle = window.getComputedStyle(target);
                                const baseRot = computedStyle.getPropertyValue('--base-rot') || '0deg';
                                
                                target.style.transform = `perspective(1000px) rotateX(${-y * intensity}deg) rotateY(${x * intensity}deg) translateZ(${intensity * 2}px) rotate(${baseRot})`;
                                target.style.transition = 'transform 0.1s linear';
                            });
                        });
                    });

                    block.addEventListener('mouseleave', () => {
                        window.requestAnimationFrame(() => {
                            targets.forEach(target => {
                                const computedStyle = window.getComputedStyle(target);
                                const baseRot = computedStyle.getPropertyValue('--base-rot') || '0deg';
                                target.style.transition = 'transform 0.8s cubic-bezier(0.2, 0.8, 0.2, 1)';
                                target.style.transform = `rotate(${baseRot})`;
                            });
                        });
                    });
                });
            }
        });
    </script>
</body>
</html>
