// asset_details.js
// Dynamic asset details data hydrator using CryptoCompare

(() => {
    const symbol = new URLSearchParams(window.location.search).get('asset') || 'BTC';

    // Metadata that is not reliably available from ticker APIs.
    const coinMeta = {
        'BTC': { name: 'Bitcoin', color: '#F7931A', desc: 'The first decentralized cryptocurrency.' },
        'ETH': { name: 'Ethereum', color: '#627EEA', desc: 'A decentralized platform for smart contracts.' },
        'USDT': { name: 'Tether', color: '#26A17B', desc: 'A stablecoin pegged to the US Dollar.' },
        'BNB': { name: 'BNB', color: '#F3BA2F', desc: 'The native coin of the Binance ecosystem.' },
        'SOL': { name: 'Solana', color: '#14F195', desc: 'High-performance blockchain for decentralized apps.' },
        'XRP': { name: 'XRP', color: '#23292F', desc: 'Digital asset built for payments.' },
        'ADA': { name: 'Cardano', color: '#0033AD', desc: 'A proof-of-stake blockchain platform.' },
        'DOGE': { name: 'Dogecoin', color: '#C2A633', desc: 'Open source peer-to-peer digital currency.' },
        'TRX': { name: 'TRON', color: '#FF0013', desc: 'Decentralized blockchain-based operating system.' }
    };

    const meta = coinMeta[symbol] || { name: symbol, color: '#888888', desc: 'Digital Asset' };

    // Formatters
    const fmtUSD = (v) => '$' + parseFloat(v).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    const fmtVol = (v) => {
        if (v >= 1e9) return '$' + (v / 1e9).toFixed(2) + 'B';
        if (v >= 1e6) return '$' + (v / 1e6).toFixed(2) + 'M';
        return '$' + (v / 1e3).toFixed(2) + 'K';
    };

    const setText = (id, value) => {
        const el = document.getElementById(id);
        if (el) el.textContent = value;
    };

    async function fetchData() {
        try {
            // Fetch ticker data
            let price = 1.00;
            let percentChange = 0.00;
            let vol = 0;
            let high = 0;
            let low = 0;
            let marketCap = 0;

            if (symbol === 'GBP') {
                price = 1.27;
                percentChange = 0.08;
                vol = 1800000000;
                high = 1.28;
                low = 1.26;
                marketCap = 0;
            } else if (symbol !== 'USDT' && symbol !== 'USDC') {
                const res = await fetch(`https://min-api.cryptocompare.com/data/pricemultifull?fsyms=${symbol}&tsyms=USD`);
                if (res.ok) {
                    const data = await res.json();
                    
                    if (data.RAW && data.RAW[symbol] && data.RAW[symbol].USD) {
                        const ticker = data.RAW[symbol].USD;
                        price = parseFloat(ticker.PRICE);
                        percentChange = parseFloat(ticker.CHANGEPCT24HOUR);
                        vol = parseFloat(ticker.VOLUME24HOURTO); // Volume in USD
                        high = parseFloat(ticker.HIGH24HOUR);
                        low = parseFloat(ticker.LOW24HOUR);
                        marketCap = parseFloat(ticker.MKTCAP || 0);
                    }
                }
            } else {
                // Stablecoin assumptions
                price = 1.00;
                percentChange = 0.00;
                vol = 50000000000;
                high = 1.001;
                low = 0.999;
                marketCap = 0;
            }

            updateUI(price, percentChange, vol, high, low, marketCap);

        } catch (e) {
            console.error("Fetch failed", e);
        }
    }

    function updateUI(price, change, vol, high, low, marketCap) {
        // Balances
        const balEl = document.getElementById('crypto-balance');
        const amount = parseFloat((balEl && balEl.getAttribute('data-amount')) || 0);
        const fiatVal = amount * price;

        setText('fiat-balance', fmtUSD(fiatVal));
        setText('market-price', fmtUSD(price));

        // Market Stats
        setText('trading-volume', fmtVol(vol));
        setText('day-range', `${fmtUSD(low)} - ${fmtUSD(high)}`);

        const dailyChangeEl = document.getElementById('daily-change');
        if (dailyChangeEl) {
            const positive = change >= 0;
            dailyChangeEl.textContent = `${positive ? '+' : ''}${change.toFixed(2)}% (24h)`;
            dailyChangeEl.style.color = positive ? '#10b981' : '#ef4444';
        }

        const marketCapEl = document.getElementById('market-cap');
        if (marketCapEl) {
            marketCapEl.textContent = marketCap > 0 ? fmtVol(marketCap) : 'N/A';
        }

        // Update metadata if generic
        setText('coin-name', meta.name);
        setText('coin-symbol', symbol);
        const descEl = document.getElementById('about-desc');
        if (descEl) descEl.textContent = meta.desc;
    }

    // Init
    fetchData();
    setInterval(fetchData, 10000); // Live update every 10s

})();
