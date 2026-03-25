<!doctype html>
<html lang="en" class="h-full">
 <head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Fake Review Detector</title>
  <script src="https://cdn.tailwindcss.com/3.4.17"></script>
  <script src="https://cdn.jsdelivr.net/npm/lucide@0.263.0/dist/umd/lucide.min.js"></script>
  <script src="/_sdk/element_sdk.js"></script>
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&amp;family=JetBrains+Mono:wght@400;500&amp;display=swap" rel="stylesheet">
  <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        html, body { height: 100%; font-family: 'Outfit', sans-serif; }

        .app-bg {
            background: #0a0a1a;
            min-height: 100%;
            width: 100%;
            overflow: auto;
            position: relative;
        }

        .app-bg::before {
            content: '';
            position: fixed;
            top: -40%; left: -20%;
            width: 70%; height: 70%;
            background: radial-gradient(circle, rgba(99, 102, 241, 0.15) 0%, transparent 70%);
            pointer-events: none;
        }
        .app-bg::after {
            content: '';
            position: fixed;
            bottom: -30%; right: -20%;
            width: 60%; height: 60%;
            background: radial-gradient(circle, rgba(236, 72, 153, 0.12) 0%, transparent 70%);
            pointer-events: none;
        }

        .glass-card {
            background: rgba(255, 255, 255, 0.04);
            backdrop-filter: blur(24px);
            -webkit-backdrop-filter: blur(24px);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 24px;
        }

        .glass-input {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 16px;
            color: #e2e8f0;
            font-family: 'Outfit', sans-serif;
            transition: all 0.3s ease;
        }
        .glass-input:focus {
            outline: none;
            border-color: rgba(99, 102, 241, 0.5);
            box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1), 0 0 30px rgba(99, 102, 241, 0.1);
            background: rgba(255, 255, 255, 0.07);
        }
        .glass-input::placeholder { color: rgba(255, 255, 255, 0.25); }

        .analyze-btn {
            background: linear-gradient(135deg, #6366f1, #8b5cf6, #a855f7);
            border: none;
            border-radius: 14px;
            color: white;
            font-family: 'Outfit', sans-serif;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        .analyze-btn::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, transparent, rgba(255,255,255,0.15), transparent);
            transform: translateX(-100%);
            transition: transform 0.5s;
        }
        .analyze-btn:hover::before { transform: translateX(100%); }
        .analyze-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 40px rgba(99, 102, 241, 0.35);
        }
        .analyze-btn:active { transform: translateY(0); }
        .analyze-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none !important;
            box-shadow: none !important;
        }

        .result-card {
            background: rgba(255, 255, 255, 0.03);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.06);
            border-radius: 20px;
            animation: slideUp 0.5s ease;
        }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes pulse-dot {
            0%, 80%, 100% { opacity: 0.3; transform: scale(0.8); }
            40% { opacity: 1; transform: scale(1); }
        }

        .score-ring {
            width: 120px; height: 120px;
            border-radius: 50%;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .score-ring svg { position: absolute; transform: rotate(-90deg); }
        .score-ring circle {
            fill: none;
            stroke-width: 6;
            stroke-linecap: round;
        }

        .metric-bar {
            height: 6px;
            border-radius: 3px;
            background: rgba(255, 255, 255, 0.08);
            overflow: hidden;
        }
        .metric-fill {
            height: 100%;
            border-radius: 3px;
            transition: width 1s ease;
        }

        .floating-orb {
            position: fixed;
            border-radius: 50%;
            pointer-events: none;
            animation: float 8s ease-in-out infinite;
        }
        @keyframes float {
            0%, 100% { transform: translateY(0) scale(1); }
            50% { transform: translateY(-30px) scale(1.05); }
        }

        .tag { 
            display: inline-block; 
            padding: 4px 12px; 
            border-radius: 20px; 
            font-size: 12px; 
            font-weight: 500;
        }
    </style>
  <style>body { box-sizing: border-box; }</style>
  <script src="/_sdk/data_sdk.js" type="text/javascript"></script>
 </head>
 <body class="h-full">
  <div class="app-bg" id="app-wrapper">
   <!-- Floating orbs -->
   <div class="floating-orb" style="width:300px;height:300px;top:10%;left:5%;background:radial-gradient(circle,rgba(99,102,241,0.08),transparent);animation-delay:-2s"></div>
   <div class="floating-orb" style="width:200px;height:200px;bottom:20%;right:10%;background:radial-gradient(circle,rgba(236,72,153,0.06),transparent);animation-delay:-5s"></div>
   <div class="flex flex-col items-center justify-start px-4 py-8 md:py-12 relative z-10 w-full" style="min-height:100%">
    <!-- Header -->
    <div class="text-center mb-8 md:mb-10">
     <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full mb-5" style="background:rgba(99,102,241,0.12);border:1px solid rgba(99,102,241,0.2)">
      <i data-lucide="shield-check" style="width:14px;height:14px;color:#818cf8"></i> <span class="text-xs font-medium tracking-wider uppercase" style="color:#a5b4fc" id="badge-text">AI-Powered Analysis</span>
     </div>
     <h1 class="text-3xl md:text-5xl font-bold mb-3 tracking-tight" id="main-title" style="background:linear-gradient(135deg,#e2e8f0,#ffffff,#c7d2fe);-webkit-background-clip:text;-webkit-text-fill-color:transparent">Fake Review Detector</h1>
     <p class="text-sm md:text-base max-w-md mx-auto" style="color:rgba(255,255,255,0.4)" id="subtitle-text">Paste any product review and our AI will analyze its authenticity using advanced pattern detection.</p>
    </div><!-- Main Card -->
    <div class="glass-card w-full max-w-2xl p-6 md:p-8 mb-6">
     <form id="review-form" onsubmit="return false;">
      <label for="review-input" class="block text-sm font-medium mb-3" style="color:rgba(255,255,255,0.6)"> <i data-lucide="message-square" style="width:14px;height:14px;display:inline-block;vertical-align:middle;margin-right:6px;color:#818cf8"></i> Review Text </label> <textarea id="review-input" class="glass-input w-full p-4 text-sm md:text-base resize-none" rows="5" placeholder="Paste a product review here to analyze its authenticity..." style="line-height:1.7"></textarea>
      <div class="flex items-center justify-between mt-5 flex-wrap gap-3">
       <span class="text-xs" style="color:rgba(255,255,255,0.25)" id="char-count">0 characters</span> <button type="button" id="analyze-btn" class="analyze-btn px-8 py-3 text-sm md:text-base flex items-center gap-2" onclick="analyzeReview()"> <i data-lucide="scan" style="width:18px;height:18px"></i> <span id="btn-label">Analyze Review</span> </button>
      </div>
     </form>
    </div><!-- Loading State -->
    <div id="loading-state" class="hidden w-full max-w-2xl">
     <div class="glass-card p-8 flex flex-col items-center gap-4">
      <div class="flex gap-2">
       <div class="w-3 h-3 rounded-full" style="background:#6366f1;animation:pulse-dot 1.4s infinite;animation-delay:0s"></div>
       <div class="w-3 h-3 rounded-full" style="background:#8b5cf6;animation:pulse-dot 1.4s infinite;animation-delay:0.2s"></div>
       <div class="w-3 h-3 rounded-full" style="background:#a855f7;animation:pulse-dot 1.4s infinite;animation-delay:0.4s"></div>
      </div>
      <p class="text-sm" style="color:rgba(255,255,255,0.5)">Analyzing review patterns...</p>
     </div>
    </div><!-- Result -->
    <div id="result-area" class="hidden w-full max-w-2xl"></div><!-- Info Section -->
    <div class="w-full max-w-4xl mt-12 grid grid-cols-1 md:grid-cols-2 gap-6 mb-8"><!-- How It Works -->
     <div class="glass-card p-6 md:p-7">
      <div class="flex items-center gap-2 mb-4"><i data-lucide="brain" style="width:20px;height:20px;color:#6366f1"></i>
       <h3 class="text-lg font-semibold" style="color:#e2e8f0">How It Works</h3>
      </div>
      <p class="text-sm mb-4" style="color:rgba(255,255,255,0.6)">Our AI detector analyzes multiple linguistic patterns:</p>
      <ul class="text-xs space-y-2" style="color:rgba(255,255,255,0.5)">
       <li class="flex gap-2"><span style="color:#6366f1">•</span> <span>Sentiment extremes and superlatives</span></li>
       <li class="flex gap-2"><span style="color:#8b5cf6">•</span> <span>Excessive punctuation &amp; capitalization</span></li>
       <li class="flex gap-2"><span style="color:#a855f7">•</span> <span>Vocabulary diversity &amp; complexity</span></li>
       <li class="flex gap-2"><span style="color:#ec4899">•</span> <span>Review length &amp; structure patterns</span></li>
       <li class="flex gap-2"><span style="color:#06b6d4">•</span> <span>Generic praise vs. specific details</span></li>
      </ul>
     </div><!-- About This Tool -->
     <div class="glass-card p-6 md:p-7">
      <div class="flex items-center gap-2 mb-4"><i data-lucide="info" style="width:20px;height:20px;color:#ec4899"></i>
       <h3 class="text-lg font-semibold" style="color:#e2e8f0">About This Tool</h3>
      </div>
      <p class="text-sm" style="color:rgba(255,255,255,0.6)">This fake review detector uses advanced pattern recognition to identify potentially inauthentic product reviews. It's designed to help consumers make informed purchasing decisions by flagging suspicious reviews that may be artificially generated, paid endorsements, or competitor sabotage.</p>
     </div>
    </div><!-- Characteristics Grid -->
    <div class="w-full max-w-4xl mb-8">
     <h2 class="text-2xl font-bold mb-6 text-center" style="color:#e2e8f0">Fake vs. Real Reviews</h2>
     <div class="grid grid-cols-1 md:grid-cols-2 gap-6"><!-- Fake Reviews -->
      <div class="glass-card p-6 md:p-7 border-l-4" style="border-left-color:#ef4444">
       <div class="flex items-center gap-2 mb-5"><i data-lucide="alert-triangle" style="width:24px;height:24px;color:#ef4444"></i>
        <h3 class="text-xl font-semibold" style="color:#fca5a5">Signs of Fake Reviews</h3>
       </div>
       <ul class="space-y-3 text-sm" style="color:rgba(255,255,255,0.6)">
        <li class="flex gap-3"><span style="color:#ef4444;font-weight:600">✓</span> <span><span style="color:#e2e8f0">Extreme superlatives:</span> "BEST PRODUCT EVER!!!"</span></li>
        <li class="flex gap-3"><span style="color:#ef4444;font-weight:600">✓</span> <span><span style="color:#e2e8f0">Excessive punctuation:</span> Multiple exclamation marks or question marks</span></li>
        <li class="flex gap-3"><span style="color:#ef4444;font-weight:600">✓</span> <span><span style="color:#e2e8f0">ALL CAPS sections:</span> Random capitalization throughout text</span></li>
        <li class="flex gap-3"><span style="color:#ef4444;font-weight:600">✓</span> <span><span style="color:#e2e8f0">Generic praise:</span> No specific product details or personal experience</span></li>
        <li class="flex gap-3"><span style="color:#ef4444;font-weight:600">✓</span> <span><span style="color:#e2e8f0">Very short:</span> One or two sentences, no substance</span></li>
        <li class="flex gap-3"><span style="color:#ef4444;font-weight:600">✓</span> <span><span style="color:#e2e8f0">Suspicious timing:</span> Multiple similar reviews posted within hours</span></li>
        <li class="flex gap-3"><span style="color:#ef4444;font-weight:600">✓</span> <span><span style="color:#e2e8f0">External links:</span> URLs directing to other websites</span></li>
        <li class="flex gap-3"><span style="color:#ef4444;font-weight:600">✓</span> <span><span style="color:#e2e8f0">Poor vocabulary:</span> Repetitive words, lack of complexity</span></li>
       </ul>
      </div><!-- Real Reviews -->
      <div class="glass-card p-6 md:p-7 border-l-4" style="border-left-color:#10b981">
       <div class="flex items-center gap-2 mb-5"><i data-lucide="check-circle" style="width:24px;height:24px;color:#10b981"></i>
        <h3 class="text-xl font-semibold" style="color:#86efac">Signs of Genuine Reviews</h3>
       </div>
       <ul class="space-y-3 text-sm" style="color:rgba(255,255,255,0.6)">
        <li class="flex gap-3"><span style="color:#10b981;font-weight:600">✓</span> <span><span style="color:#e2e8f0">Specific details:</span> Mentions actual product features or personal experience</span></li>
        <li class="flex gap-3"><span style="color:#10b981;font-weight:600">✓</span> <span><span style="color:#e2e8f0">Balanced tone:</span> Mix of pros and cons, not entirely one-sided</span></li>
        <li class="flex gap-3"><span style="color:#10b981;font-weight:600">✓</span> <span><span style="color:#e2e8f0">Normal punctuation:</span> Standard use of periods, commas, and occasional emphasis</span></li>
        <li class="flex gap-3"><span style="color:#10b981;font-weight:600">✓</span> <span><span style="color:#e2e8f0">Natural language:</span> Conversational tone with varied vocabulary</span></li>
        <li class="flex gap-3"><span style="color:#10b981;font-weight:600">✓</span> <span><span style="color:#e2e8f0">Adequate length:</span> Detailed explanation of use case and outcomes</span></li>
        <li class="flex gap-3"><span style="color:#10b981;font-weight:600">✓</span> <span><span style="color:#e2e8f0">Personal context:</span> References to when or how the product was used</span></li>
        <li class="flex gap-3"><span style="color:#10b981;font-weight:600">✓</span> <span><span style="color:#e2e8f0">Honest assessment:</span> Realistic star ratings that match the review text</span></li>
        <li class="flex gap-3"><span style="color:#10b981;font-weight:600">✓</span> <span><span style="color:#e2e8f0">No promotional content:</span> Focus on product experience, not marketing</span></li>
       </ul>
      </div>
     </div>
    </div><!-- Footer -->
    <p class="text-xs mt-12 mb-4" style="color:rgba(255,255,255,0.15)">For demonstration purposes only. Results are based on pattern analysis and should not be considered definitive.</p>
   </div>
  </div>
  <script>
const defaultConfig = {
    main_title: 'Fake Review Detector',
    subtitle_text: 'Paste any product review and our AI will analyze its authenticity using advanced pattern detection.',
    button_text: 'Analyze Review',
    background_color: '#0a0a1a',
    accent_color: '#6366f1',
    surface_color: 'rgba(255,255,255,0.04)',
    text_color: '#e2e8f0',
    highlight_color: '#ec4899',
    font_family: 'Outfit',
    font_size: 16
};

window.elementSdk.init({
    defaultConfig,
    onConfigChange: async (config) => {
        const t = (k) => config[k] || defaultConfig[k];
        document.getElementById('main-title').textContent = t('main_title');
        document.getElementById('subtitle-text').textContent = t('subtitle_text');
        document.getElementById('btn-label').textContent = t('button_text');

        const bg = t('background_color');
        document.querySelector('.app-bg').style.background = bg;

        const font = t('font_family');
        const baseSize = t('font_size');
        const stack = `${font}, sans-serif`;
        document.getElementById('main-title').style.fontFamily = stack;
        document.getElementById('main-title').style.fontSize = `${baseSize * 2.5}px`;
        document.getElementById('subtitle-text').style.fontFamily = stack;
        document.getElementById('subtitle-text').style.fontSize = `${baseSize * 0.9}px`;
        document.getElementById('btn-label').style.fontFamily = stack;
        document.getElementById('btn-label').style.fontSize = `${baseSize}px`;

        const accent = t('accent_color');
        document.getElementById('analyze-btn').style.background = `linear-gradient(135deg, ${accent}, ${t('highlight_color')})`;

        document.querySelectorAll('.glass-card, .result-card').forEach(el => {
            el.style.background = t('surface_color');
        });

        const textCol = t('text_color');
        document.getElementById('main-title').style.webkitTextFillColor = '';
        document.getElementById('main-title').style.color = textCol;
    },
    mapToCapabilities: (config) => {
        const c = (key) => ({
            get: () => config[key] || defaultConfig[key],
            set: (v) => { config[key] = v; window.elementSdk.setConfig({ [key]: v }); }
        });
        return {
            recolorables: [c('background_color'), c('surface_color'), c('text_color'), c('accent_color'), c('highlight_color')],
            borderables: [],
            fontEditable: c('font_family'),
            fontSizeable: { 
                get: () => config.font_size || defaultConfig.font_size, 
                set: (v) => { config.font_size = v; window.elementSdk.setConfig({ font_size: v }); } 
            }
        };
    },
    mapToEditPanelValues: (config) => new Map([
        ['main_title', config.main_title || defaultConfig.main_title],
        ['subtitle_text', config.subtitle_text || defaultConfig.subtitle_text],
        ['button_text', config.button_text || defaultConfig.button_text]
    ])
});

// Character counter
const textarea = document.getElementById('review-input');
const charCount = document.getElementById('char-count');
textarea.addEventListener('input', () => {
    charCount.textContent = `${textarea.value.length} characters`;
});

// Analysis logic
function analyzeReview() {
    const text = textarea.value.trim();
    if (!text) {
        textarea.style.borderColor = 'rgba(239,68,68,0.5)';
        setTimeout(() => textarea.style.borderColor = '', 2000);
        return;
    }

    const btn = document.getElementById('analyze-btn');
    btn.disabled = true;
    document.getElementById('loading-state').classList.remove('hidden');
    document.getElementById('result-area').classList.add('hidden');

    setTimeout(() => {
        const result = computeAnalysis(text);
        renderResult(result);
        btn.disabled = false;
        document.getElementById('loading-state').classList.add('hidden');
        document.getElementById('result-area').classList.remove('hidden');
    }, 1800);
}

function computeAnalysis(text) {
    const words = text.split(/\s+/).filter(Boolean);
    const wordCount = words.length;
    const sentences = text.split(/[.!?]+/).filter(Boolean);
    const avgWordLen = words.reduce((s, w) => s + w.length, 0) / (wordCount || 1);
    const exclamations = (text.match(/!/g) || []).length;
    const caps = (text.match(/[A-Z]/g) || []).length;
    const capsRatio = caps / (text.length || 1);
    const uniqueWords = new Set(words.map(w => w.toLowerCase())).size;
    const lexicalDiv = uniqueWords / (wordCount || 1);

    const fakeSignals = [
        'amazing', 'perfect', 'best ever', 'life changing', 'must buy',
        'highly recommend', 'five stars', '5 stars', 'love it', 'fantastic',
        'incredible', 'awesome', 'wonderful', 'excellent', 'superb',
        'game changer', 'blown away', 'exceeded expectations'
    ];
    const lower = text.toLowerCase();
    const signalHits = fakeSignals.filter(s => lower.includes(s)).length;

    let fakeScore = 30;
    if (wordCount < 15) fakeScore += 15;
    if (wordCount > 200) fakeScore -= 10;
    if (exclamations > 3) fakeScore += 12;
    if (capsRatio > 0.3) fakeScore += 15;
    if (lexicalDiv < 0.5) fakeScore += 10;
    if (signalHits >= 3) fakeScore += 20;
    else if (signalHits >= 1) fakeScore += 8;
    if (avgWordLen < 4) fakeScore += 5;
    if (sentences.length < 2) fakeScore += 10;
    if (text.includes('http') || text.includes('www')) fakeScore += 15;

    // Add randomness
    fakeScore += (Math.random() * 10 - 5);
    fakeScore = Math.max(5, Math.min(95, Math.round(fakeScore)));

    const genuine = 100 - fakeScore;
    const verdict = fakeScore >= 65 ? 'likely_fake' : fakeScore >= 40 ? 'suspicious' : 'likely_genuine';

    const flags = [];
    if (exclamations > 3) flags.push('Excessive punctuation');
    if (capsRatio > 0.3) flags.push('High capitalization');
    if (signalHits >= 2) flags.push('Generic praise patterns');
    if (wordCount < 15) flags.push('Very short review');
    if (lexicalDiv < 0.5) flags.push('Low vocabulary diversity');
    if (text.includes('http')) flags.push('Contains external links');
    if (sentences.length < 2) flags.push('Single sentence review');
    if (flags.length === 0) flags.push('No major red flags');

    return {
        fakeScore, genuine, verdict, flags,
        metrics: {
            sentiment: Math.min(100, signalHits * 18 + 20),
            complexity: Math.min(100, Math.round(lexicalDiv * 100)),
            consistency: Math.max(10, 80 - exclamations * 8 - (capsRatio > 0.2 ? 20 : 0)),
            detail: Math.min(100, Math.round(wordCount / 2))
        }
    };
}

function renderResult(r) {
    const area = document.getElementById('result-area');
    const vColors = {
        likely_fake: { ring: '#ef4444', bg: 'rgba(239,68,68,0.1)', border: 'rgba(239,68,68,0.2)', label: 'Likely Fake', icon: 'alert-triangle' },
        suspicious: { ring: '#f59e0b', bg: 'rgba(245,158,11,0.1)', border: 'rgba(245,158,11,0.2)', label: 'Suspicious', icon: 'alert-circle' },
        likely_genuine: { ring: '#10b981', bg: 'rgba(16,185,129,0.1)', border: 'rgba(16,185,129,0.2)', label: 'Likely Genuine', icon: 'check-circle' }
    };
    const v = vColors[r.verdict];

    area.innerHTML = `
        <div class="result-card p-6 md:p-8">
            <!-- Verdict Header -->
            <div class="flex flex-col sm:flex-row items-center gap-6 mb-8">
                <div class="score-ring flex-shrink-0">
                    <svg width="120" height="120" viewBox="0 0 120 120">
                        <circle cx="60" cy="60" r="52" stroke="rgba(255,255,255,0.06)" />
                        <circle cx="60" cy="60" r="52" stroke="${v.ring}" stroke-dasharray="${r.fakeScore * 3.27} 327" style="transition:stroke-dasharray 1.5s ease" />
                    </svg>
                    <div class="text-center z-10">
                        <div class="text-2xl font-bold" style="color:${v.ring}">${r.fakeScore}%</div>
                        <div class="text-xs" style="color:rgba(255,255,255,0.35)">Fake Score</div>
                    </div>
                </div>
                <div class="text-center sm:text-left">
                    <div class="tag mb-2" style="background:${v.bg};border:1px solid ${v.border};color:${v.ring}">
                        <i data-lucide="${v.icon}" style="width:12px;height:12px;display:inline-block;vertical-align:middle;margin-right:4px"></i>
                        ${v.label}
                    </div>
                    <h2 class="text-xl font-semibold mb-1" style="color:#e2e8f0">Analysis Complete</h2>
                    <p class="text-sm" style="color:rgba(255,255,255,0.4)">Based on linguistic patterns, sentiment, and structural analysis.</p>
                </div>
            </div>

            <!-- Metrics -->
            <div class="grid grid-cols-2 gap-4 mb-6">
                ${renderMetric('Sentiment Bias', r.metrics.sentiment, '#6366f1')}
                ${renderMetric('Vocabulary Range', r.metrics.complexity, '#8b5cf6')}
                ${renderMetric('Consistency', r.metrics.consistency, '#06b6d4')}
                ${renderMetric('Detail Level', r.metrics.detail, '#ec4899')}
            </div>

            <!-- Flags -->
            <div class="p-4 rounded-2xl" style="background:rgba(255,255,255,0.03);border:1px solid rgba(255,255,255,0.05)">
                <div class="text-xs font-medium mb-3 uppercase tracking-wider" style="color:rgba(255,255,255,0.35)">
                    <i data-lucide="flag" style="width:12px;height:12px;display:inline-block;vertical-align:middle;margin-right:4px"></i>
                    Detected Patterns
                </div>
                <div class="flex flex-wrap gap-2">
                    ${r.flags.map(f => `<span class="tag" style="background:rgba(255,255,255,0.05);color:rgba(255,255,255,0.6);border:1px solid rgba(255,255,255,0.08)">${f}</span>`).join('')}
                </div>
            </div>
        </div>
    `;
    lucide.createIcons();
}

function renderMetric(label, value, color) {
    return `
        <div class="p-3 rounded-xl" style="background:rgba(255,255,255,0.02)">
            <div class="flex justify-between items-center mb-2">
                <span class="text-xs" style="color:rgba(255,255,255,0.45)">${label}</span>
                <span class="text-xs font-mono font-medium" style="color:${color}">${value}%</span>
            </div>
            <div class="metric-bar">
                <div class="metric-fill" style="width:${value}%;background:linear-gradient(90deg,${color},${color}88)"></div>
            </div>
        </div>
    `;
}

lucide.createIcons();
</script>
 <script>(function(){function c(){var b=a.contentDocument||a.contentWindow.document;if(b){var d=b.createElement('script');d.innerHTML="window.__CF$cv$params={r:'9e1c2b7661f091a4',t:'MTc3NDQyMzQwMi4wMDAwMDA='};var a=document.createElement('script');a.nonce='';a.src='/cdn-cgi/challenge-platform/scripts/jsd/main.js';document.getElementsByTagName('head')[0].appendChild(a);";b.getElementsByTagName('head')[0].appendChild(d)}}if(document.body){var a=document.createElement('iframe');a.height=1;a.width=1;a.style.position='absolute';a.style.top=0;a.style.left=0;a.style.border='none';a.style.visibility='hidden';document.body.appendChild(a);if('loading'!==document.readyState)c();else if(window.addEventListener)document.addEventListener('DOMContentLoaded',c);else{var e=document.onreadystatechange||function(){};document.onreadystatechange=function(b){e(b);'loading'!==document.readyState&&(document.onreadystatechange=e,c())}}}})();</script></body>
</html>