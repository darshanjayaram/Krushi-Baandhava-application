<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Krushi Baandhava — Fresh Deployment & Setup Wizard</title>
    <style>
        /* =========================================================
           RESET & CORE STYLES (Zero external dependency - 100% reliable)
           ========================================================= */
        *, *::before, *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        :root {
            --bg-dark: #090d16;
            --surface-card: #0f172a;
            --surface-hover: #1e293b;
            --border-subtle: #334155;
            --border-glow: #059669;
            --primary: #10b981;
            --primary-hover: #059669;
            --primary-glow: rgba(16, 185, 129, 0.25);
            --accent-amber: #f59e0b;
            --accent-cyan: #06b6d4;
            --accent-rose: #f43f5e;
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            --text-dim: #64748b;
            --font-stack: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
        }

        body {
            font-family: var(--font-stack);
            background-color: var(--bg-dark);
            background-image: 
                radial-gradient(at 0% 0%, rgba(5, 150, 105, 0.18) 0px, transparent 50%),
                radial-gradient(at 100% 100%, rgba(6, 78, 59, 0.25) 0px, transparent 50%),
                radial-gradient(at 50% 50%, rgba(15, 23, 42, 0.6) 0px, transparent 100%);
            min-height: 100vh;
            color: var(--text-main);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px 16px;
            line-height: 1.5;
        }

        /* Ambient Glow & Container */
        .wizard-wrapper {
            width: 100%;
            max-width: 760px;
            background: rgba(15, 23, 42, 0.92);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(52, 211, 153, 0.25);
            border-radius: 24px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.7), 0 0 35px rgba(16, 185, 129, 0.12);
            overflow: hidden;
            position: relative;
        }

        /* Top Header */
        .wizard-header {
            padding: 28px 32px 20px;
            border-bottom: 1px solid var(--border-subtle);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            flex-wrap: wrap;
        }

        .brand-box {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .brand-icon {
            width: 48px;
            height: 48px;
            border-radius: 14px;
            background: linear-gradient(135deg, #10b981 0%, #047857 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            box-shadow: 0 8px 16px rgba(16, 185, 129, 0.3);
            flex-shrink: 0;
        }

        .brand-title {
            font-size: 22px;
            font-weight: 800;
            color: #ffffff;
            letter-spacing: -0.5px;
            line-height: 1.2;
        }

        .brand-subtitle {
            font-size: 12px;
            color: var(--primary);
            font-weight: 600;
            margin-top: 2px;
        }

        /* Stepper Progress Bar */
        .stepper-nav {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .step-pill {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: #1e293b;
            color: var(--text-dim);
            font-weight: 700;
            font-size: 13px;
            border: 2px solid transparent;
            transition: all 0.25s ease;
        }

        .step-pill.active {
            background: var(--primary);
            color: #ffffff;
            border-color: #6ee7b7;
            box-shadow: 0 0 12px var(--primary-glow);
        }

        .step-pill.done {
            background: rgba(16, 185, 129, 0.2);
            color: var(--primary);
            border-color: rgba(16, 185, 129, 0.4);
        }

        .step-divider {
            width: 20px;
            height: 2px;
            background: #1e293b;
        }

        /* Form Body */
        .wizard-body {
            padding: 32px;
        }

        .step-content {
            display: none;
            animation: fadeIn 0.3s ease;
        }

        .step-content.active {
            display: block;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(6px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .section-header {
            margin-bottom: 24px;
        }

        .section-title {
            font-size: 18px;
            font-weight: 700;
            color: #ffffff;
            letter-spacing: -0.3px;
        }

        .section-desc {
            font-size: 13px;
            color: var(--text-muted);
            margin-top: 4px;
        }

        /* Diagnostic Info Box */
        .info-card {
            background: rgba(30, 41, 59, 0.6);
            border: 1px solid var(--border-subtle);
            border-radius: 14px;
            padding: 16px 20px;
            margin-bottom: 22px;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 13px;
            padding: 6px 0;
            border-bottom: 1px solid rgba(51, 65, 85, 0.4);
        }

        .info-row:last-child {
            border-bottom: none;
            padding-bottom: 0;
        }

        .status-ok {
            color: #34d399;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .status-bad {
            color: var(--accent-rose);
            font-weight: 600;
        }

        /* Form Controls */
        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 16px;
            margin-bottom: 20px;
        }

        @media (max-width: 640px) {
            .form-grid {
                grid-template-columns: 1fr;
            }
            .wizard-header {
                padding: 20px;
            }
            .wizard-body {
                padding: 20px;
            }
        }

        .col-span-2 {
            grid-column: span 2 / span 2;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .form-label {
            font-size: 12px;
            font-weight: 600;
            color: #cbd5e1;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .form-hint {
            font-size: 11px;
            color: var(--text-dim);
            margin-top: 3px;
        }

        .form-input {
            width: 100%;
            background: #090d16;
            border: 1px solid #334155;
            border-radius: 10px;
            padding: 11px 14px;
            color: #ffffff;
            font-size: 13px;
            font-family: inherit;
            outline: none;
            transition: all 0.2s ease;
        }

        .form-input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.2);
            background: #0b1120;
        }

        .form-input::placeholder {
            color: #475569;
        }

        /* Interactive Select Cards (Step 3) */
        .option-card {
            background: rgba(30, 41, 59, 0.5);
            border: 1px solid var(--border-subtle);
            border-radius: 14px;
            padding: 18px;
            margin-bottom: 14px;
            transition: all 0.2s ease;
        }

        .option-card:hover {
            border-color: rgba(16, 185, 129, 0.4);
            background: rgba(30, 41, 59, 0.8);
        }

        .option-label {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            cursor: pointer;
        }

        .option-checkbox {
            width: 18px;
            height: 18px;
            accent-color: var(--primary);
            margin-top: 2px;
            cursor: pointer;
        }

        .option-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            width: 100%;
        }

        .option-title {
            font-size: 14px;
            font-weight: 700;
            color: #ffffff;
        }

        .badge-pill {
            font-size: 10px;
            font-weight: 700;
            padding: 2px 8px;
            border-radius: 9999px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .badge-emerald {
            background: rgba(16, 185, 129, 0.15);
            color: #6ee7b7;
            border: 1px solid rgba(16, 185, 129, 0.3);
        }

        .badge-amber {
            background: rgba(245, 158, 11, 0.15);
            color: #fcd34d;
            border: 1px solid rgba(245, 158, 11, 0.3);
        }

        .option-desc {
            font-size: 12px;
            color: var(--text-muted);
            margin-top: 5px;
            line-height: 1.45;
        }

        /* Collapsible Preview Box */
        .preview-toggle-btn {
            background: transparent;
            border: none;
            color: var(--primary);
            font-size: 11px;
            font-weight: 600;
            cursor: pointer;
            margin-top: 10px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 0;
            outline: none;
        }

        .preview-toggle-btn:hover {
            text-decoration: underline;
        }

        .preview-box {
            display: none;
            background: #090d16;
            border: 1px solid #1e293b;
            border-radius: 10px;
            padding: 12px;
            margin-top: 8px;
            max-height: 180px;
            overflow-y: auto;
            font-size: 11px;
            color: #cbd5e1;
            line-height: 1.6;
        }

        .preview-box.open {
            display: block;
        }

        /* Buttons & Actions */
        .btn-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 28px;
            padding-top: 20px;
            border-top: 1px solid var(--border-subtle);
        }

        .btn-primary {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: #ffffff;
            border: none;
            border-radius: 12px;
            padding: 12px 24px;
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
            box-shadow: 0 4px 14px rgba(16, 185, 129, 0.35);
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s ease;
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, #059669 0%, #047857 100%);
            box-shadow: 0 6px 20px rgba(16, 185, 129, 0.45);
            transform: translateY(-1px);
        }

        .btn-secondary {
            background: #1e293b;
            color: #cbd5e1;
            border: 1px solid #334155;
            border-radius: 12px;
            padding: 11px 20px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .btn-secondary:hover {
            background: #334155;
            color: #ffffff;
        }

        .btn-test {
            background: #1e293b;
            border: 1px solid #475569;
            color: #f1f5f9;
            padding: 8px 14px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s ease;
        }

        .btn-test:hover {
            background: #334155;
            border-color: #64748b;
        }

        .test-feedback {
            font-size: 12px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        /* Alert / Messages */
        .alert-error {
            background: rgba(244, 63, 94, 0.12);
            border: 1px solid rgba(244, 63, 94, 0.35);
            color: #fca5a5;
            padding: 14px 18px;
            border-radius: 12px;
            font-size: 13px;
            margin-bottom: 20px;
        }

        .alert-success {
            background: rgba(16, 185, 129, 0.12);
            border: 1px solid rgba(16, 185, 129, 0.35);
            color: #6ee7b7;
            padding: 14px 18px;
            border-radius: 12px;
            font-size: 13px;
            margin-bottom: 20px;
        }

        /* Review Summary (Step 4) */
        .summary-card {
            background: rgba(15, 23, 42, 0.8);
            border: 1px solid var(--border-subtle);
            border-radius: 14px;
            padding: 20px;
            margin-bottom: 20px;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid rgba(51, 65, 85, 0.5);
            font-size: 13px;
        }

        .summary-row:last-child {
            border-bottom: none;
            padding-bottom: 0;
        }

        .summary-label {
            color: var(--text-muted);
        }

        .summary-val {
            font-weight: 700;
            color: #ffffff;
        }

        .launch-btn {
            width: 100%;
            padding: 16px;
            font-size: 15px;
            border-radius: 14px;
            justify-content: center;
        }
    </style>
</head>
<body>

    <div class="wizard-wrapper">
        <!-- Wizard Header -->
        <div class="wizard-header">
            <div class="brand-box">
                <div class="brand-icon">🌾</div>
                <div>
                    <div class="brand-title">Krushi Baandhava</div>
                    <div class="brand-subtitle">Fresh cPanel Deployment & Master Setup</div>
                </div>
            </div>

            <!-- Stepper Progress Indicators -->
            <div class="stepper-nav">
                <div id="badge-step-1" class="step-pill active">1</div>
                <div class="step-divider"></div>
                <div id="badge-step-2" class="step-pill">2</div>
                <div class="step-divider"></div>
                <div id="badge-step-3" class="step-pill">3</div>
                <div class="step-divider"></div>
                <div id="badge-step-4" class="step-pill">4</div>
            </div>
        </div>

        <!-- Wizard Body -->
        <div class="wizard-body">

            @if(session('error'))
                <div class="alert-error">
                    <strong>❌ Error:</strong> {{ session('error') }}
                </div>
            @endif

            @if($errors->any())
                <div class="alert-error">
                    <strong style="display: block; margin-bottom: 6px;">Please review the errors below:</strong>
                    <ul style="padding-left: 20px;">
                        @foreach($errors->all() as $err)
                            <li>{{ $err }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form id="setup-form" action="{{ route('setup.run') }}" method="POST">
                @csrf

                <!-- ========================================== -->
                <!-- STEP 1: Database & System Environment      -->
                <!-- ========================================== -->
                <div id="step-1" class="step-content active">
                    <div class="section-header">
                        <h2 class="section-title">Step 1: Database & Server Environment</h2>
                        <p class="section-desc">Verify your MySQL database credentials and server readiness before starting.</p>
                    </div>

                    <!-- Server Environment Diagnostics -->
                    <div class="info-card">
                        <div class="info-row">
                            <span style="color: var(--text-muted);">PHP Version (&ge; 8.2 required):</span>
                            <span class="{{ $phpOk ? 'status-ok' : 'status-bad' }}">
                                {{ $phpOk ? '✓ PHP ' . $phpVersion : '✗ PHP ' . $phpVersion . ' (Upgrade required)' }}
                            </span>
                        </div>
                        <div class="info-row">
                            <span style="color: var(--text-muted);">Extensions (PDO, MySQL, Mbstring, cURL):</span>
                            <span class="{{ $allExtensionsOk ? 'status-ok' : 'status-bad' }}">
                                {{ $allExtensionsOk ? '✓ All Enabled' : '✗ Missing Extensions' }}
                            </span>
                        </div>
                        <div class="info-row">
                            <span style="color: var(--text-muted);">Directory Write Permissions (storage & cache):</span>
                            <span class="{{ $writablePaths['storage'] && $writablePaths['bootstrap_cache'] ? 'status-ok' : 'status-bad' }}">
                                {{ $writablePaths['storage'] && $writablePaths['bootstrap_cache'] ? '✓ Writable' : '✗ Permission Denied' }}
                            </span>
                        </div>
                    </div>

                    <!-- Database Inputs -->
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label" for="db_host">Database Host</label>
                            <input type="text" name="db_host" id="db_host" value="{{ old('db_host', $dbHost) }}" class="form-input" required>
                            <span class="form-hint">Usually <code>127.0.0.1</code> or <code>localhost</code> in cPanel</span>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="db_port">Database Port</label>
                            <input type="number" name="db_port" id="db_port" value="{{ old('db_port', $dbPort) }}" class="form-input" required>
                            <span class="form-hint">Standard MySQL port is 3306</span>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="db_database">Database Name</label>
                            <input type="text" name="db_database" id="db_database" value="{{ old('db_database', $dbDatabase) }}" placeholder="e.g. user_krushidb" class="form-input" required>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="db_username">Database Username</label>
                            <input type="text" name="db_username" id="db_username" value="{{ old('db_username', $dbUsername) }}" placeholder="e.g. user_dbadmin" class="form-input" required>
                        </div>

                        <div class="form-group col-span-2">
                            <label class="form-label" for="db_password">Database Password</label>
                            <input type="password" name="db_password" id="db_password" placeholder="Database user password" class="form-input">
                        </div>

                        <div class="form-group col-span-2">
                            <label class="form-label" for="app_url">Application Domain URL</label>
                            <input type="url" name="app_url" id="app_url" value="{{ old('app_url', $appUrl) }}" placeholder="https://yourdomain.com" class="form-input">
                        </div>
                    </div>

                    <!-- Live DB Test Button & Output -->
                    <div style="display: flex; align-items: center; gap: 12px; margin-top: 10px;">
                        <button type="button" onclick="testDatabaseConnection()" class="btn-test">
                            <span>🔌</span>
                            <span>Test MySQL Database Connection</span>
                        </button>
                        <div id="db-test-result" class="test-feedback"></div>
                    </div>

                    <!-- Next Step Action -->
                    <div class="btn-row">
                        <div></div>
                        <button type="button" onclick="goToStep(2)" class="btn-primary">
                            <span>Continue to Admin Account</span>
                            <span>→</span>
                        </button>
                    </div>
                </div>

                <!-- ========================================== -->
                <!-- STEP 2: Super Admin Account Creation       -->
                <!-- ========================================== -->
                <div id="step-2" class="step-content">
                    <div class="section-header">
                        <h2 class="section-title">Step 2: Create Super Administrator Account</h2>
                        <p class="section-desc">Set your credentials to access the Krushi Baandhava Admin Management Portal.</p>
                    </div>

                    <div style="display: flex; flex-direction: column; gap: 16px;">
                        <div class="form-group">
                            <label class="form-label" for="admin_name">Admin Full Name</label>
                            <input type="text" name="admin_name" id="admin_name" value="{{ old('admin_name', 'Krushi Administrator') }}" class="form-input" required>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="admin_email">Admin Email Address</label>
                            <input type="email" name="admin_email" id="admin_email" value="{{ old('admin_email', 'admin@krushibaandhava.org') }}" class="form-input" required>
                            <span class="form-hint">You will use this email address to log in to <code>/admin/login</code></span>
                        </div>

                        <div class="form-grid" style="margin-bottom: 0;">
                            <div class="form-group">
                                <label class="form-label" for="admin_password">Choose Password</label>
                                <input type="password" name="admin_password" id="admin_password" minlength="8" placeholder="Minimum 8 characters" class="form-input" required>
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="admin_password_confirmation">Confirm Password</label>
                                <input type="password" name="admin_password_confirmation" id="admin_password_confirmation" minlength="8" placeholder="Repeat your password" class="form-input" required>
                            </div>
                        </div>
                    </div>

                    <div class="info-card" style="margin-top: 20px; border-color: rgba(245, 158, 11, 0.35); background: rgba(245, 158, 11, 0.08);">
                        <div style="display: flex; gap: 12px; align-items: flex-start; color: #fcd34d; font-size: 13px;">
                            <span style="font-size: 18px;">🛡️</span>
                            <div>
                                <strong style="display: block; margin-bottom: 2px;">Permanent Security Lockdown</strong>
                                No default passwords will be created. Once installation is complete, this setup wizard permanently disables itself to eliminate hijacking risks.
                            </div>
                        </div>
                    </div>

                    <div class="btn-row">
                        <button type="button" onclick="goToStep(1)" class="btn-secondary">← Back</button>
                        <button type="button" onclick="goToStep(3)" class="btn-primary">
                            <span>Continue to Master Data</span>
                            <span>→</span>
                        </button>
                    </div>
                </div>

                <!-- ========================================== -->
                <!-- STEP 3: Master Data & APMC Selection       -->
                <!-- ========================================== -->
                <div id="step-3" class="step-content">
                    <div class="section-header">
                        <h2 class="section-title">Step 3: Master Data & APMC Mandi Selection</h2>
                        <p class="section-desc">Zero manual entry: Select Karnataka master directories to auto-populate upon setup.</p>
                    </div>

                    <!-- Karnataka APMC Mandis Option -->
                    <div class="option-card">
                        <label class="option-label">
                            <input type="checkbox" name="install_mandis" id="install_mandis" value="1" checked class="option-checkbox">
                            <div style="flex: 1;">
                                <div class="option-header">
                                    <span class="option-title">Karnataka State APMC Directory</span>
                                    <span class="badge-pill badge-emerald">31 Districts • 68 Mandis</span>
                                </div>
                                <div class="option-desc">
                                    Auto-creates all 31 Karnataka districts with official coordinates and provisions 68 verified APMC mandis (Shivamogga, Sagar, Sirsi, Tumakuru, Tiptur, Davanagere, Channagiri, Bangalore, Hubli, etc.).
                                </div>
                            </div>
                        </label>

                        <button type="button" onclick="toggleMandiPreview()" class="preview-toggle-btn">
                            <span id="mandi-arrow">▶</span>
                            <span>Preview Mandis across 31 Districts</span>
                        </button>

                        <div id="mandi-preview-box" class="preview-box">
                            <div><strong style="color: #ffffff;">Shivamogga:</strong> Shimoga APMC, Sagar APMC, Shikaripura APMC, Bhadravathi APMC</div>
                            <div><strong style="color: #ffffff;">Uttara Kannada:</strong> Sirsi APMC, Siddapur APMC, Yellapur APMC, Kumta APMC, Karwar APMC</div>
                            <div><strong style="color: #ffffff;">Tumakuru:</strong> Tumakuru APMC, Tiptur APMC, Madhugiri APMC, Sira APMC, Kunigal APMC</div>
                            <div><strong style="color: #ffffff;">Davanagere:</strong> Channagiri APMC, Davanagere APMC, Harihar APMC, Honnali APMC</div>
                            <div><strong style="color: #ffffff;">Hassan:</strong> Hassan APMC, Arsikere APMC, Channarayapatna APMC, Sakleshpur APMC</div>
                            <div><strong style="color: #ffffff;">Bengaluru Urban & Rural:</strong> Yeshwantpur APMC, Binny Mill, Doddaballapur APMC, Ramanagara APMC</div>
                            <div><strong style="color: #ffffff;">Belagavi:</strong> Belgaum APMC, Gokak APMC, Bailhongal APMC, Athani APMC, Nipani APMC</div>
                            <div><strong style="color: #ffffff;">Mysuru & Mandya:</strong> Mysuru APMC (Bandipalya), Mandya APMC, Maddur APMC, Nanjangud APMC</div>
                            <div><strong style="color: #ffffff;">Kolar & Chikkaballapura:</strong> Kolar APMC (Tomato Market), Chintamani APMC, Srinivaspur APMC</div>
                            <div><strong style="color: #ffffff;">Raichur & Koppal:</strong> Raichur APMC (Cotton Hub), Gangavathi APMC (Paddy Hub), Koppal APMC</div>
                            <div style="color: var(--text-dim); font-style: italic; margin-top: 4px;">+ All remaining Karnataka district APMC sub-markets and regional aggregation centres.</div>
                        </div>
                    </div>

                    <!-- Karnataka Crops Option -->
                    <div class="option-card">
                        <label class="option-label">
                            <input type="checkbox" name="install_crops" id="install_crops" value="1" checked class="option-checkbox">
                            <div style="flex: 1;">
                                <div class="option-header">
                                    <span class="option-title">Karnataka Core Agricultural Commodities</span>
                                    <span class="badge-pill badge-amber">19 Crops & Varietals</span>
                                </div>
                                <div class="option-desc">
                                    Includes Arecanut, Ragi, Paddy, Maize, Cotton, Onion, Tomato, Coconut, Tur, Bengal Gram, Ginger, Pepper, and sets automated alias mappings for data.gov.in.
                                </div>
                            </div>
                        </label>

                        <button type="button" onclick="toggleCropPreview()" class="preview-toggle-btn">
                            <span id="crop-arrow">▶</span>
                            <span>Preview 19 Commodities & Verified Grades</span>
                        </button>

                        <div id="crop-preview-box" class="preview-box">
                            <div><strong style="color: #34d399;">Cash Crops:</strong> Arecanut / ಅಡಿಕೆ (Rashi, Bette, Gorabal), Coconut / ತೆಂಗಿನಕಾಯಿ, Cotton / ಹತ್ತಿ, Coffee (Arabica, Robusta)</div>
                            <div><strong style="color: #fcd34d;">Cereals & Millets:</strong> Ragi / ರಾಗಿ (Local, Hybrid, Finger Millet), Paddy / ಭತ್ತ (Sona Masuri, Basmati), Maize / ಮೆಕ್ಕೆಜೋಳ, Jowar / ಜೋಳ, Wheat</div>
                            <div><strong style="color: #2dd4bf;">Pulses:</strong> Tur / ತೊಗರಿ (Red Gram), Bengal Gram / ಕಡಲೆ (Chana), Green Gram / ಹೆಸರು ಕಾಳು (Moong)</div>
                            <div><strong style="color: #fb7185;">Vegetables:</strong> Tomato / ಟೊಮೇಟೊ (Hybrid, Local), Onion / ಈರುಳ್ಳಿ (Nashik, Local Bellary), Potato, Green Chilli</div>
                            <div><strong style="color: #38bdf8;">Spices & Fruits:</strong> Ginger / ಶುಂಠಿ, Turmeric / ಅರಿಶಿನ, Black Pepper / ಕರಿಮೆಣಸು, Banana / ಬಾಳೆಹಣ್ಣು</div>
                        </div>
                    </div>

                    <!-- API Key -->
                    <div class="option-card" style="background: rgba(15, 23, 42, 0.4);">
                        <div class="form-group">
                            <label class="form-label" for="api_key">data.gov.in API Key (OGD India)</label>
                            <input type="text" name="api_key" id="api_key" value="{{ old('api_key', $apiKey) }}" placeholder="Optional: Enter your data.gov.in API key" class="form-input">
                        </div>
                        <div style="display: flex; align-items: center; gap: 10px; margin-top: 10px;">
                            <button type="button" onclick="testApiKeyConnection()" class="btn-test">Test API Key</button>
                            <span id="api-test-result" class="test-feedback"></span>
                        </div>
                    </div>

                    <!-- Live Sync Checkbox -->
                    <div style="margin-top: 10px; padding: 10px 14px; background: rgba(30, 41, 59, 0.4); border-radius: 10px;">
                        <label style="display: flex; align-items: center; gap: 10px; cursor: pointer; font-size: 13px; color: #cbd5e1;">
                            <input type="checkbox" name="sync_prices" value="1" checked class="option-checkbox">
                            <span><strong>Fetch today's live APMC mandi prices</strong> immediately upon initialization</span>
                        </label>
                    </div>

                    <div class="btn-row">
                        <button type="button" onclick="goToStep(2)" class="btn-secondary">← Back</button>
                        <button type="button" onclick="goToStep(4)" class="btn-primary">
                            <span>Review & Launch</span>
                            <span>→</span>
                        </button>
                    </div>
                </div>

                <!-- ========================================== -->
                <!-- STEP 4: Review & Execute Installation      -->
                <!-- ========================================== -->
                <div id="step-4" class="step-content">
                    <div class="section-header">
                        <h2 class="section-title">Step 4: Review & Initialize Krushi Baandhava</h2>
                        <p class="section-desc">Review your parameters. Click the button below to start the zero-terminal automated setup.</p>
                    </div>

                    <div class="summary-card">
                        <div class="summary-row">
                            <span class="summary-label">Database Target:</span>
                            <span id="summary-db" class="summary-val" style="color: var(--primary);">MySQL</span>
                        </div>
                        <div class="summary-row">
                            <span class="summary-label">Super Administrator:</span>
                            <span id="summary-admin" class="summary-val">admin@krushibaandhava.org</span>
                        </div>
                        <div class="summary-row">
                            <span class="summary-label">Karnataka APMC Mandis:</span>
                            <span class="summary-val" style="color: var(--primary);">✓ Auto-provision 31 Districts & 68 APMCs</span>
                        </div>
                        <div class="summary-row">
                            <span class="summary-label">Crop Commodities:</span>
                            <span class="summary-val" style="color: var(--primary);">✓ Auto-provision 19 Crops & Varieties</span>
                        </div>
                        <div class="summary-row">
                            <span class="summary-label">Security Lockdown:</span>
                            <span class="summary-val" style="color: var(--accent-amber);">✓ Permanent Lockout Enabled</span>
                        </div>
                    </div>

                    <button type="submit" id="submit-btn" class="btn-primary launch-btn">
                        <span>🌾</span>
                        <span>Complete Installation & Lock Setup Wizard</span>
                    </button>

                    <div class="btn-row" style="margin-top: 16px; padding-top: 14px;">
                        <button type="button" onclick="goToStep(3)" class="btn-secondary">← Back</button>
                    </div>
                </div>

            </form>
        </div>
    </div>

    <!-- Interactive Navigation & AJAX Handlers -->
    <script>
        function goToStep(stepNumber) {
            if (stepNumber === 2) {
                const dbHost = document.getElementById('db_host').value.trim();
                const dbName = document.getElementById('db_database').value.trim();
                const dbUser = document.getElementById('db_username').value.trim();
                if (!dbHost || !dbName || !dbUser) {
                    alert('Please enter Database Host, Database Name, and Username.');
                    return;
                }
            } else if (stepNumber === 3) {
                const adminName = document.getElementById('admin_name').value.trim();
                const adminEmail = document.getElementById('admin_email').value.trim();
                const adminPass = document.getElementById('admin_password').value;
                const adminPassConf = document.getElementById('admin_password_confirmation').value;
                if (!adminName || !adminEmail || !adminPass) {
                    alert('Please enter Admin Name, Email, and Password.');
                    return;
                }
                if (adminPass.length < 8) {
                    alert('Password must be at least 8 characters long.');
                    return;
                }
                if (adminPass !== adminPassConf) {
                    alert('Passwords do not match. Please re-enter.');
                    return;
                }
            } else if (stepNumber === 4) {
                document.getElementById('summary-db').innerText = document.getElementById('db_database').value + ' (' + document.getElementById('db_host').value + ')';
                document.getElementById('summary-admin').innerText = document.getElementById('admin_email').value;
            }

            document.querySelectorAll('.step-content').forEach(el => el.classList.remove('active'));
            document.getElementById('step-' + stepNumber).classList.add('active');

            for (let i = 1; i <= 4; i++) {
                const badge = document.getElementById('badge-step-' + i);
                badge.classList.remove('active', 'done');
                if (i === stepNumber) {
                    badge.classList.add('active');
                    badge.innerText = i;
                } else if (i < stepNumber) {
                    badge.classList.add('done');
                    badge.innerText = '✓';
                } else {
                    badge.innerText = i;
                }
            }

            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        function toggleMandiPreview() {
            const box = document.getElementById('mandi-preview-box');
            const arrow = document.getElementById('mandi-arrow');
            if (box.classList.contains('open')) {
                box.classList.remove('open');
                arrow.innerText = '▶';
            } else {
                box.classList.add('open');
                arrow.innerText = '▼';
            }
        }

        function toggleCropPreview() {
            const box = document.getElementById('crop-preview-box');
            const arrow = document.getElementById('crop-arrow');
            if (box.classList.contains('open')) {
                box.classList.remove('open');
                arrow.innerText = '▶';
            } else {
                box.classList.add('open');
                arrow.innerText = '▼';
            }
        }

        async function testDatabaseConnection() {
            const resultEl = document.getElementById('db-test-result');
            resultEl.innerHTML = '<span style="color: #fcd34d;">⏳ Connecting to MySQL...</span>';

            const payload = {
                host: document.getElementById('db_host').value,
                port: document.getElementById('db_port').value,
                database: document.getElementById('db_database').value,
                username: document.getElementById('db_username').value,
                password: document.getElementById('db_password').value,
                _token: '{{ csrf_token() }}'
            };

            try {
                const response = await fetch('{{ route("setup.test-db") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify(payload)
                });

                const data = await response.json();
                if (response.ok && data.success) {
                    resultEl.innerHTML = '<span style="color: #34d399;">✓ ' + data.message + '</span>';
                } else {
                    resultEl.innerHTML = '<span style="color: #f43f5e;">✗ ' + (data.message || 'Connection failed') + '</span>';
                }
            } catch (err) {
                resultEl.innerHTML = '<span style="color: #f43f5e;">✗ Network error</span>';
            }
        }

        async function testApiKeyConnection() {
            const key = document.getElementById('api_key').value.trim();
            const resultEl = document.getElementById('api-test-result');
            if (!key) {
                resultEl.innerHTML = '<span style="color: #fcd34d;">Please enter a key</span>';
                return;
            }

            resultEl.innerHTML = '<span style="color: #fcd34d;">⏳ Testing...</span>';

            try {
                const response = await fetch('{{ route("setup.test-api") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ api_key: key, _token: '{{ csrf_token() }}' })
                });

                const data = await response.json();
                if (response.ok && data.success) {
                    resultEl.innerHTML = '<span style="color: #34d399;">✓ Valid Key</span>';
                } else {
                    resultEl.innerHTML = '<span style="color: #f43f5e;">✗ ' + (data.message || 'Invalid') + '</span>';
                }
            } catch (err) {
                resultEl.innerHTML = '<span style="color: #f43f5e;">✗ Timeout</span>';
            }
        }

        document.getElementById('setup-form').addEventListener('submit', function() {
            const btn = document.getElementById('submit-btn');
            btn.disabled = true;
            btn.innerHTML = '<span>⏳</span><span>Initializing Karnataka Master Data & Securing App...</span>';
            btn.style.opacity = '0.75';
            btn.style.cursor = 'not-allowed';
        });
    </script>
</body>
</html>
