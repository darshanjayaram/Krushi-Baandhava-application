<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Krushi Baandhava — Setup Wizard Locked</title>
    <style>
        *, *::before, *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        :root {
            --bg-dark: #090d16;
            --surface-card: #0f172a;
            --border-subtle: #334155;
            --primary: #10b981;
            --font-stack: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
        }

        body {
            font-family: var(--font-stack);
            background-color: var(--bg-dark);
            background-image: 
                radial-gradient(at 0% 0%, rgba(5, 150, 105, 0.15) 0px, transparent 50%),
                radial-gradient(at 100% 100%, rgba(244, 63, 94, 0.12) 0px, transparent 50%);
            min-height: 100vh;
            color: #f8fafc;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px 16px;
            line-height: 1.5;
        }

        .locked-card {
            width: 100%;
            max-width: 520px;
            background: rgba(15, 23, 42, 0.92);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(52, 211, 153, 0.25);
            border-radius: 24px;
            padding: 40px 32px;
            text-align: center;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.7);
        }

        .lock-icon {
            width: 72px;
            height: 72px;
            border-radius: 20px;
            background: #1e293b;
            border: 1px solid rgba(16, 185, 129, 0.3);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            margin-bottom: 20px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.4);
        }

        .locked-title {
            font-size: 22px;
            font-weight: 800;
            color: #ffffff;
            margin-bottom: 8px;
            letter-spacing: -0.5px;
        }

        .locked-desc {
            font-size: 13px;
            color: #94a3b8;
            line-height: 1.5;
            margin-bottom: 24px;
        }

        .stats-box {
            background: rgba(30, 41, 59, 0.6);
            border: 1px solid #334155;
            border-radius: 14px;
            padding: 16px 20px;
            margin-bottom: 24px;
            text-align: left;
            font-size: 13px;
        }

        .stats-title {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--primary);
            margin-bottom: 10px;
        }

        .stats-row {
            display: flex;
            justify-content: space-between;
            padding: 6px 0;
            border-bottom: 1px solid rgba(51, 65, 85, 0.4);
            color: #cbd5e1;
        }

        .stats-row:last-child {
            border-bottom: none;
            padding-bottom: 0;
        }

        .stats-val {
            font-weight: 700;
            color: #ffffff;
        }

        .action-row {
            display: flex;
            gap: 12px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .btn-primary {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: #ffffff;
            text-decoration: none;
            border-radius: 12px;
            padding: 12px 24px;
            font-size: 13px;
            font-weight: 700;
            box-shadow: 0 4px 14px rgba(16, 185, 129, 0.35);
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s ease;
        }

        .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(16, 185, 129, 0.45);
        }

        .btn-secondary {
            background: #1e293b;
            color: #cbd5e1;
            text-decoration: none;
            border: 1px solid #334155;
            border-radius: 12px;
            padding: 11px 20px;
            font-size: 13px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s ease;
        }

        .btn-secondary:hover {
            background: #334155;
            color: #ffffff;
        }

        .footer-note {
            font-size: 11px;
            color: #64748b;
            margin-top: 24px;
            line-height: 1.4;
        }
    </style>
</head>
<body>

    <div class="locked-card">
        <div class="lock-icon">🔒</div>

        <h1 class="locked-title">Setup Wizard is Locked</h1>
        <p class="locked-desc">
            Krushi Baandhava has already been installed and secured. To protect your application and database from unauthorized modification, the initial setup wizard is permanently disabled.
        </p>

        <div class="stats-box">
            <div class="stats-title">Active Karnataka Master Coverage</div>
            <div class="stats-row">
                <span>Districts:</span>
                <span class="stats-val">{{ $districtsCount }} Districts</span>
            </div>
            <div class="stats-row">
                <span>APMC Mandis:</span>
                <span class="stats-val">{{ $marketsCount }} Mandis</span>
            </div>
            <div class="stats-row">
                <span>Crops & Commodities:</span>
                <span class="stats-val">{{ $cropsCount }} Crops</span>
            </div>
            @if(isset($installedAt))
            <div class="stats-row" style="color: #94a3b8; font-size: 11px;">
                <span>Installed At:</span>
                <span class="stats-val" style="font-family: monospace;">{{ $installedAt }}</span>
            </div>
            @endif
        </div>

        <div class="action-row">
            <a href="{{ route('admin.login') }}" class="btn-primary">
                <span>🔑</span>
                <span>Go to Admin Login</span>
            </a>
            <a href="{{ route('home') }}" class="btn-secondary">
                <span>🌾</span>
                <span>Open Farmer App</span>
            </a>
        </div>

        <p class="footer-note">
            To discover new APMCs or manage commodities, please log in and navigate to the <strong>Deployment & APMC Discovery Hub</strong>.
        </p>
    </div>

</body>
</html>
