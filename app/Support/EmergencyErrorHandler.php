<?php

namespace App\Support;

class EmergencyErrorHandler
{
    /**
     * Render a self-contained, zero-disk-I/O HTML page for fatal storage / disk quota errors.
     * Does NOT use Blade, does NOT write to disk, does NOT call database.
     */
    public static function renderDiskQuotaPage(?string $detail = null): string
    {
        $safeDetail = $detail ? htmlspecialchars(substr($detail, 0, 300), ENT_QUOTES, 'UTF-8') : '';
        $detailHtml = $safeDetail ? '<div style="margin-top: 6px; color: #B91C1C; font-size: 10px;">Error: ' . $safeDetail . '</div>' : '';

        return <<<HTML
<!DOCTYPE html>
<html lang="kn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ಸರ್ವರ್ ಶೇಖರಣಾ ಮಿತಿ ಮೀರಿದೆ | Disk Quota Exceeded — Krushi Baandhava</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            background-color: #FAF8F5;
            color: #292524;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }
        .card {
            background: #ffffff;
            border: 2px solid #E2DAC8;
            border-radius: 24px;
            max-width: 580px;
            width: 100%;
            padding: 2rem;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.05), 0 8px 10px -6px rgba(0, 0, 0, 0.05);
            text-align: center;
        }
        .header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid #EAE3D2;
            padding-bottom: 1rem;
            margin-bottom: 1.5rem;
        }
        .brand {
            display: flex;
            align-items: center;
            gap: 12px;
            text-align: left;
        }
        .logo-box {
            width: 44px;
            height: 44px;
            background: #FAF8F5;
            border: 1px solid #E2DAC8;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }
        .brand-title {
            font-size: 15px;
            font-weight: 800;
            color: #1c1917;
        }
        .brand-sub {
            font-size: 11px;
            font-weight: 700;
            color: #1C5A2C;
        }
        .badge {
            background: #FEF2F2;
            border: 1px solid #FECACA;
            color: #B91C1C;
            font-size: 11px;
            font-weight: 800;
            padding: 4px 10px;
            border-radius: 9999px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .icon-circle {
            width: 64px;
            height: 64px;
            background: #FEF3C7;
            border: 2px solid #FDE68A;
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            margin: 0 auto 1rem;
        }
        h1 {
            font-size: 20px;
            font-weight: 900;
            color: #1c1917;
            margin-bottom: 0.5rem;
            line-height: 1.3;
        }
        .subheading {
            font-size: 13px;
            font-weight: 700;
            color: #B45309;
            margin-bottom: 1rem;
        }
        p {
            font-size: 13px;
            line-height: 1.6;
            color: #57534E;
            margin-bottom: 1.25rem;
        }
        .info-box {
            background: #FAF8F5;
            border: 1px solid #E2DAC8;
            border-radius: 16px;
            padding: 1rem;
            text-align: left;
            font-size: 12px;
            color: #44403C;
            margin-bottom: 1.5rem;
            line-height: 1.5;
        }
        .admin-box {
            background: #F5F5F4;
            border: 1px dashed #D6D3D1;
            border-radius: 12px;
            padding: 0.75rem 1rem;
            text-align: left;
            font-size: 11px;
            color: #44403C;
            margin-bottom: 1.5rem;
            font-family: monospace;
            word-break: break-all;
        }
        .admin-title {
            font-weight: 800;
            color: #1C5A2C;
            margin-bottom: 4px;
            font-family: sans-serif;
            font-size: 11px;
        }
        .btn-group {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 12px 18px;
            border-radius: 14px;
            font-size: 12px;
            font-weight: 800;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.15s ease;
            border: none;
        }
        .btn-primary {
            background: #1C5A2C;
            color: #ffffff;
        }
        .btn-primary:hover {
            background: #154622;
        }
        .btn-secondary {
            background: #ffffff;
            color: #44403C;
            border: 1.5px solid #E2DAC8;
        }
        .btn-secondary:hover {
            background: #F5F5F4;
        }
        @media (max-width: 480px) {
            .btn-group { grid-template-columns: 1fr; }
            .card { padding: 1.25rem; }
        }
    </style>
</head>
<body>
    <div class="card">
        <div class="header">
            <div class="brand">
                <div class="logo-box">🌾</div>
                <div>
                    <div class="brand-title">Krushi Baandhava</div>
                    <div class="brand-sub">ಕೃಷಿ ಬಾಂಧವ</div>
                </div>
            </div>
            <div class="badge">
                <span>⚠️</span> Error 500 (Disk Full)
            </div>
        </div>

        <div class="icon-circle">
            💾
        </div>

        <h1>ಸರ್ವರ್ ಶೇಖರಣಾ ಮಿತಿ ತಲುಪಿದೆ</h1>
        <div class="subheading">Server Storage Quota Exceeded (errno 122)</div>

        <p>
            ಸರ್ವರ್‌ನಲ್ಲಿ ತಾತ್ಕಾಲಿಕ ಫೈಲ್‌ಗಳನ್ನು ಬರೆಯಲು ಶೇಖರಣಾ ಸ್ಥಳಾವಕಾಶ (Disk Quota) ಕೊರತೆಯಾಗಿದೆ. ನಮ್ಮ ತಾಂತ್ರಿಕ ತಂಡಕ್ಕೆ ಈಗಾಗಲೇ ಈ ಬಗ್ಗೆ ಮಾಹಿತಿ ತಲುಪಿದೆ ಮತ್ತು ಶೀಘ್ರವೇ ಪರಿಹರಿಸಲಾಗುವುದು.
            <br><br>
            <span style="font-size: 11px; color: #78716C;">
                The host server ran out of disk quota or storage space, preventing temporary cache and view compilation. Normal service will resume once log/cache files are purged.
            </span>
        </p>

        <div class="info-box">
            <strong>🌱 ರೈತ ಬಾಂಧವರ ಗಮನಕ್ಕೆ (For Farmers):</strong><br>
            • ದಯವಿಟ್ಟು 5-10 ನಿಮಿಷಗಳ ನಂತರ ಪುಟವನ್ನು ರಿಫ್ರೆಶ್ ಮಾಡಿ.<br>
            • ತುರ್ತು ಮಾರುಕಟ್ಟೆ ಮಾಹಿತಿಗಾಗಿ ಸ್ಥಳೀಯ ಎಪಿಎಂಸಿ ಅಥವಾ ಸಹಾಯವಾಣಿಯನ್ನು ಸಂಪರ್ಕಿಸಿ.
        </div>

        <div class="admin-box">
            <div class="admin-title">🛠️ Administrator / cPanel Resolution Steps:</div>
            1. Login to cPanel &rarr; <strong>File Manager</strong><br>
            2. Truncate huge logs in: <code>storage/logs/laravel.log</code><br>
            3. Delete view cache in: <code>storage/framework/views/*.php</code><br>
            4. In <code>.env</code>, set: <code>APP_DEBUG=false</code> & <code>LOG_CHANNEL=daily</code>
            {$detailHtml}
        </div>

        <div class="btn-group">
            <button onclick="window.location.reload()" class="btn btn-primary">
                🔄 ರಿಫ್ರೆಶ್ ಮಾಡಿ (Try Again)
            </button>
            <a href="/" class="btn btn-secondary">
                🏠 ಮುಖಪುಟ (Home)
            </a>
        </div>
    </div>
</body>
</html>
HTML;
    }
}

