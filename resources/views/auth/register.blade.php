@extends('layouts.app')

@section('content')

<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@700;800&family=JetBrains+Mono:wght@500&display=swap" rel="stylesheet">

<style>
    .bm-wrap {
        --bg: #070b14;
        --panel: #0d1220;
        --card: #111827;
        --border: #1f2937;
        --text: #f3f4f6;
        --muted: #8b95a7;
        --blue: #3b82f6;
        --green: #34d399;
        --orange: #fb923c;
        min-height: 100vh;
        background: var(--bg);
        color: var(--text);
        font-family: 'Inter', sans-serif;
        display: flex;
        justify-content: center;
        position: fixed;
        inset: 0;
        overflow-y: auto;
        z-index: 1050;
    }
    .bm-inner {
        display: grid;
        grid-template-columns: 1fr 1fr;
        width: 100%;
        max-width: 1180px;
        background: radial-gradient(600px 300px at 15% 0%, rgba(59,130,246,.18), transparent 70%), #080d18;
    }
    .bm-left {
        padding: 56px 56px 40px;
        border-right: 1px solid var(--border);
        background-image: radial-gradient(rgba(255,255,255,.05) 1px, transparent 1px);
        background-size: 22px 22px;
        display: flex; flex-direction: column;
    }
    .bm-logo { display:flex; align-items:center; gap:12px; font-family:'Plus Jakarta Sans',sans-serif; font-weight:800; font-size:24px; }
    .bm-logo-icon {
        width:32px; height:32px; border-radius:9px;
        background: linear-gradient(135deg,#4f8cff,#2f5be0);
        display:flex; align-items:center; justify-content:center; font-size:15px;
        box-shadow: 0 0 18px rgba(59,130,246,.5);
    }
    .bm-hero { text-align:center; margin-top:56px; }
    .bm-pill {
        display:inline-flex; align-items:center; gap:8px;
        border:1px solid #1e3a8a; background:rgba(30,58,138,.25);
        color:#93c5fd; border-radius:999px; padding:10px 18px;
        font-family:'JetBrains Mono',monospace; font-size:11px; letter-spacing:1.2px;
    }
    .bm-pill i { width:6px; height:6px; border-radius:50%; background:var(--blue); display:inline-block; }
    .bm-hero h1 {
        font-family:'Plus Jakarta Sans',sans-serif; font-weight:800;
        font-size:44px; line-height:1.1; margin:28px auto 18px; max-width:420px;
    }
    .bm-hero p { color:var(--muted); font-size:16px; line-height:1.6; max-width:360px; margin:0 auto; }

    .bm-card { margin-top:40px; background:var(--card); border:1px solid var(--border); border-radius:18px; padding:18px; }
    .bm-co { display:flex; align-items:center; gap:14px; margin-bottom:16px; }
    .bm-avatar { width:32px; height:32px; border-radius:50%; background:linear-gradient(135deg,#60a5fa,#3b5bdb); }
    .bm-co b { display:block; font-size:14px; }
    .bm-co small { color:var(--muted); font-size:11px; }
    .bm-stats { display:grid; grid-template-columns:repeat(3,1fr); gap:12px; }
    .bm-stat { border:1px solid var(--border); background:#0c1220; border-radius:12px; padding:16px 12px; text-align:center; }
    .bm-stat span { display:block; font-size:10px; letter-spacing:.8px; color:var(--muted); text-transform:uppercase; }
    .bm-stat strong { display:block; font-size:20px; margin:10px 0 12px; }
    .bm-stat em { font-style:normal; font-size:11px; font-weight:600; color:var(--green); display:block; text-align:left; }
    .bm-stat em.auto { color:var(--orange); }
    .bm-chart { margin-top:12px; border:1px solid var(--border); background:#0c1220; border-radius:12px; padding:14px 16px; }
    .bm-chart-head { display:flex; justify-content:space-between; font-size:12px; font-weight:600; }
    .bm-chart-head span { color:var(--green); font-family:'JetBrains Mono',monospace; font-size:11px; }
    .bm-chart svg { width:100%; height:56px; margin-top:8px; }
    .bm-trust { display:flex; gap:24px; margin-top:14px; font-size:12px; color:var(--muted); }
    .bm-trust .ok { color:var(--green); }

    .bm-right { padding:48px 80px; display:flex; flex-direction:column; justify-content:center; }
    .bm-form { width:100%; max-width:380px; margin:0 auto; }
    .bm-tabs { display:grid; grid-template-columns:1fr 1fr; background:var(--panel); border:1px solid var(--border); border-radius:14px; padding:6px; }
    .bm-tabs a { text-align:center; padding:12px; border-radius:10px; font-weight:600; font-size:15px; color:var(--muted); text-decoration:none; }
    .bm-tabs a.active { background:linear-gradient(180deg,#3b82f6,#2f6fe0); color:#fff; box-shadow:0 0 18px rgba(59,130,246,.45); }
    .bm-form h2 { text-align:center; font-family:'Plus Jakarta Sans',sans-serif; font-weight:800; font-size:26px; margin:32px 0 8px; color:#fff; }
    .bm-sub { text-align:center; color:var(--muted); font-size:14px; line-height:1.5; margin-bottom:22px; }
    .bm-social { display:grid; grid-template-columns:1fr 1fr; gap:12px; }
    .bm-social button {
        display:flex; align-items:center; justify-content:center; gap:10px;
        background:var(--panel); border:1px solid var(--border); color:#fff;
        border-radius:10px; padding:12px; font-weight:600; font-size:14px; cursor:pointer;
    }
    .bm-social button:hover { border-color:#374151; }
    .bm-divider { display:flex; align-items:center; gap:12px; margin:22px 0; color:#6b7280; font-size:11px; letter-spacing:1.2px; }
    .bm-divider::before, .bm-divider::after { content:''; flex:1; height:1px; background:var(--border); }
    .bm-label { display:block; text-align:center; font-weight:600; font-size:13px; color:#d1d5db; margin-bottom:8px; }
    .bm-grid2 { display:grid; grid-template-columns:1fr 1fr; gap:12px; }
    .bm-input-wrap { position:relative; margin-bottom:18px; }
    .bm-input {
        width:100%; background:var(--panel); border:1px solid var(--border); border-radius:10px;
        padding:13px 14px; color:#fff; font-size:14px; outline:none;
    }
    .bm-input::placeholder { color:#6b7280; }
    .bm-input:focus { border-color:var(--blue); box-shadow:0 0 0 3px rgba(59,130,246,.2); }
    .bm-show { position:absolute; right:14px; top:50%; transform:translateY(-50%); background:none; border:0; color:#cbd5e1; font-size:12px; font-weight:600; cursor:pointer; }
    .bm-err { color:#f87171; font-size:12px; display:block; margin-top:6px; }
    .bm-check { display:flex; align-items:center; gap:10px; color:#cbd5e1; font-size:13px; margin:4px 0 20px; cursor:pointer; }
    .bm-btn {
        width:100%; border:0; border-radius:10px; padding:14px; color:#fff; font-weight:600; font-size:15px;
        background:linear-gradient(180deg,#3b82f6,#2f6fe0); box-shadow:0 8px 24px rgba(59,130,246,.35); cursor:pointer;
    }
    .bm-btn:hover { filter:brightness(1.08); }
    .bm-foot { text-align:center; margin-top:20px; font-size:13px; color:#9ca3af; }
    .bm-foot a { color:#60a5fa; font-weight:600; text-decoration:none; }
    .bm-secure { text-align:center; margin-top:32px; font-family:'JetBrains Mono',monospace; font-size:10px; letter-spacing:1.2px; color:#6b7280; }

    @media (max-width: 900px) {
        .bm-inner { grid-template-columns:1fr; }
        .bm-left { display:none; }
        .bm-right { padding:40px 24px; }
    }
    @media (max-width: 480px) {
        .bm-grid2 { grid-template-columns:1fr; gap:0; }
    }
</style>

<div class="bm-wrap">
    <div class="bm-inner">

        {{-- LEFT: brand panel --}}
        <div class="bm-left">
            <div class="bm-logo">
                <div class="bm-logo-icon"><i class="bi bi-journal-text"></i></div>
                Bloommonie
            </div>

            <div class="bm-hero">
                <div class="bm-pill"><i></i> AI-POWERED BOOKKEEPING</div>
                <h1>Your books, always up to date.</h1>
                <p>Sign in to review real-time cash flow, reconciled transactions, and tax-ready reports — all handled automatically.</p>
            </div>

            <div class="bm-card">
                <div class="bm-co">
                    <div class="bm-avatar"></div>
                    <div>
                        <b>Acme Corp</b>
                        <small>Bloommonie Verified</small>
                    </div>
                </div>
                <div class="bm-stats">
                    <div class="bm-stat"><span>Cash Flow</span><strong>$48,259</strong><em>✓ Synced</em></div>
                    <div class="bm-stat"><span>Uncategorized</span><strong>0</strong><em class="auto">⚡ Auto</em></div>
                    <div class="bm-stat"><span>Tax Write-offs</span><strong>$4,812</strong><em>✓ Synced</em></div>
                </div>
                <div class="bm-chart">
                    <div class="bm-chart-head">Real-time Growth Curve <span>+24% vs Prev.</span></div>
                    <svg viewBox="0 0 400 56" preserveAspectRatio="none">
                        <polyline fill="none" stroke="#3b82f6" stroke-width="2"
                            points="0,48 50,42 100,46 150,30 200,34 250,20 300,24 350,18 400,12"/>
                    </svg>
                </div>
            </div>

            <div class="bm-trust">
                <span><i class="bi bi-lock"></i> Bank-level 256-bit encryption</span>
                <span><span class="ok">✓</span> SOC 2 Type II</span>
            </div>
        </div>

        {{-- RIGHT: form --}}
        <div class="bm-right">
            <div class="bm-form">

                <div class="bm-tabs">
                    <a href="{{ route('login') }}">Log in</a>
                    <a href="{{ route('register') }}" class="active">Sign up</a>
                </div>

                <h2>Create your account</h2>
                <p class="bm-sub">Start automating your bookkeeping in minutes — no card required.</p>

                <!-- <div class="bm-social">
                    <button type="button">
                        <svg width="16" height="16" viewBox="0 0 48 48"><path fill="#EA4335" d="M24 9.5c3.5 0 6.6 1.2 9 3.6l6.7-6.7C35.7 2.4 30.2 0 24 0 14.6 0 6.5 5.4 2.6 13.2l7.8 6.1C12.3 13.6 17.7 9.5 24 9.5z"/><path fill="#4285F4" d="M46.5 24.5c0-1.6-.1-3.1-.4-4.5H24v9h12.7c-.6 3-2.3 5.5-4.8 7.2l7.5 5.8c4.4-4.1 7.1-10.1 7.1-17.5z"/><path fill="#FBBC05" d="M10.4 28.7A14.5 14.5 0 0 1 9.5 24c0-1.6.3-3.2.8-4.7l-7.8-6.1A24 24 0 0 0 0 24c0 3.9.9 7.5 2.6 10.8l7.8-6.1z"/><path fill="#34A853" d="M24 48c6.2 0 11.4-2 15.2-5.5l-7.5-5.8c-2 1.4-4.7 2.3-7.7 2.3-6.3 0-11.7-4.1-13.6-9.8l-7.8 6.1C6.5 42.6 14.6 48 24 48z"/></svg>
                        Google
                    </button>
                    <button type="button">
                        <svg width="16" height="16" viewBox="0 0 23 23"><path fill="#f35325" d="M1 1h10v10H1z"/><path fill="#81bc06" d="M12 1h10v10H12z"/><path fill="#05a6f0" d="M1 12h10v10H1z"/><path fill="#ffba08" d="M12 12h10v10H12z"/></svg>
                        Microsoft
                    </button>
                </div> -->

                <div class="bm-divider">SIGN UP WITH EMAIL</div>

                <form method="POST" action="{{ route('register') }}">
                    @csrf

                    <div class="bm-grid2">
                        <!-- Full Name -->
                        <div>
                            <label class="bm-label" for="name">Full Name</label>
                            <div class="bm-input-wrap">
                                <input id="name" type="text" name="name" class="bm-input"
                                    placeholder="Full Name" value="{{ old('name') }}" required>
                                @error('name')
                                    <small class="bm-err">{{ $message }}</small>
                                @enderror
                            </div>
                        </div>

                        <!-- Phone Number -->
                        <div>
                            <label class="bm-label" for="phone">Phone Number</label>
                            <div class="bm-input-wrap">
                                <input id="phone" type="text" name="phone" class="bm-input"
                                    placeholder="Phone Number" value="{{ old('phone') }}">
                                @error('phone')
                                    <small class="bm-err">{{ $message }}</small>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <!-- Email -->
                    <label class="bm-label" for="email">Email</label>
                    <div class="bm-input-wrap">
                        <input id="email" type="email" name="email" class="bm-input"
                            placeholder="Email" value="{{ old('email') }}" required>
                        @error('email')
                            <small class="bm-err">{{ $message }}</small>
                        @enderror
                    </div>

                    <!-- Password -->
                    <label class="bm-label" for="password">Password</label>
                    <div class="bm-input-wrap">
                        <input id="password" type="password" name="password" class="bm-input"
                            placeholder="Password" required>
                        <button type="button" class="bm-show" data-target="password">Show</button>
                        @error('password')
                            <small class="bm-err">{{ $message }}</small>
                        @enderror
                    </div>

                    <!-- Confirm Password -->
                    <label class="bm-label" for="password_confirmation">Confirm Password</label>
                    <div class="bm-input-wrap">
                        <input id="password_confirmation" type="password" name="password_confirmation" class="bm-input"
                            placeholder="Confirm Password" required>
                        <button type="button" class="bm-show" data-target="password_confirmation">Show</button>
                    </div>

                    <!-- Checkbox -->
                    <label class="bm-check">
                        <input type="checkbox" required>
                        Agree to Policy and Privacy
                    </label>

                    <!-- Button -->
                    <button type="submit" class="bm-btn">Sign Up →</button>

                    <!-- Login link -->
                    <div class="bm-foot">
                        Already have an account? <a href="{{ route('login') }}">Sign In</a>
                    </div>
                </form>

                <div class="bm-secure"><i class="bi bi-lock"></i> ENCRYPTED · SOC 2 TYPE II CERTIFIED</div>
            </div>
        </div>

    </div>
</div>

<script>
    document.querySelectorAll('.bm-show').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const input = document.getElementById(this.dataset.target);
            const show = input.type === 'password';
            input.type = show ? 'text' : 'password';
            this.textContent = show ? 'Hide' : 'Show';
        });
    });
</script>

@endsection