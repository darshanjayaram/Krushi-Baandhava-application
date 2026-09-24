<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\MarketRequest;
use App\Models\AuditLog;
use App\Models\DataSource;
use App\Models\District;
use App\Models\Market;
use App\Models\MarketSourceMapping;
use App\Models\Taluk;
use App\Services\Ingestion\MarketPriceIngestionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class MarketController extends Controller
{
    /**
     * Standard Karnataka APMC and Board Market Presets for quick auto-fill.
     */
    protected function getKarnatakaPresets(): array
    {
        return [
            ['name' => 'Shivamogga APMC', 'name_kn' => 'ಶಿವಮೊಗ್ಗ ಎಪಿಎಂಸಿ', 'code' => 'KA_APMC_SHI', 'district' => 'Shivamogga', 'market_type' => 'APMC', 'lat' => 13.9310, 'lon' => 75.5700, 'address' => 'APMC Market Yard, B.H. Road, Shivamogga'],
            ['name' => 'Sagara APMC', 'name_kn' => 'ಸಾಗರ ಎಪಿಎಂಸಿ', 'code' => 'KA_APMC_SAG', 'district' => 'Shivamogga', 'market_type' => 'APMC', 'lat' => 14.1670, 'lon' => 75.0330, 'address' => 'APMC Yard, Sagara, Shivamogga District'],
            ['name' => 'Shikaripura APMC', 'name_kn' => 'ಶಿಕಾರಿಪುರ ಎಪಿಎಂಸಿ', 'code' => 'KA_APMC_SKP', 'district' => 'Shivamogga', 'market_type' => 'APMC', 'lat' => 14.2690, 'lon' => 75.3530, 'address' => 'APMC Yard, Shikaripura'],
            ['name' => 'Bhadravathi APMC', 'name_kn' => 'ಭದ್ರಾವತಿ ಎಪಿಎಂಸಿ', 'code' => 'KA_APMC_BDV', 'district' => 'Shivamogga', 'market_type' => 'APMC', 'lat' => 13.8400, 'lon' => 75.7000, 'address' => 'APMC Yard, Bhadravathi'],
            ['name' => 'Channagiri APMC', 'name_kn' => 'ಚನ್ನಗಿರಿ ಎಪಿಎಂಸಿ', 'code' => 'KA_APMC_CNG', 'district' => 'Davanagere', 'market_type' => 'APMC', 'lat' => 14.0270, 'lon' => 75.9260, 'address' => 'APMC Yard, Main Road, Channagiri'],
            ['name' => 'Davanagere APMC', 'name_kn' => 'ದಾವಣಗೆರೆ ಎಪಿಎಂಸಿ', 'code' => 'KA_APMC_DVG', 'district' => 'Davanagere', 'market_type' => 'APMC', 'lat' => 14.4644, 'lon' => 75.9218, 'address' => 'APMC Yard, Davanagere'],
            ['name' => 'Harihara APMC', 'name_kn' => 'ಹರಿಹರ ಎಪಿಎಂಸಿ', 'code' => 'KA_APMC_HRH', 'district' => 'Davanagere', 'market_type' => 'APMC', 'lat' => 14.5120, 'lon' => 75.8050, 'address' => 'APMC Yard, Harihara'],
            ['name' => 'Chikkamagaluru APMC', 'name_kn' => 'ಚಿಕ್ಕಮಗಳೂರು ಎಪಿಎಂಸಿ', 'code' => 'KA_APMC_CKM', 'district' => 'Chikkamagaluru', 'market_type' => 'APMC', 'lat' => 13.3161, 'lon' => 75.7720, 'address' => 'APMC Yard, Chikkamagaluru'],
            ['name' => 'Tarikere APMC', 'name_kn' => 'ತರೀಕೆರೆ ಎಪಿಎಂಸಿ', 'code' => 'KA_APMC_TRK', 'district' => 'Chikkamagaluru', 'market_type' => 'APMC', 'lat' => 13.7130, 'lon' => 75.8150, 'address' => 'APMC Yard, Tarikere'],
            ['name' => 'Kadur APMC', 'name_kn' => 'ಕಡೂರು ಎಪಿಎಂಸಿ', 'code' => 'KA_APMC_KDR', 'district' => 'Chikkamagaluru', 'market_type' => 'APMC', 'lat' => 13.5530, 'lon' => 76.0120, 'address' => 'APMC Yard, Kadur'],
            ['name' => 'Sirsi APMC', 'name_kn' => 'ಶಿರಸಿ ಎಪಿಎಂಸಿ', 'code' => 'KA_APMC_SRS', 'district' => 'Uttara Kannada', 'market_type' => 'APMC', 'lat' => 14.6195, 'lon' => 74.8354, 'address' => 'TSS APMC Yard, Sirsi, Uttara Kannada'],
            ['name' => 'Yellapur APMC', 'name_kn' => 'ಯಲ್ಲಾಪುರ ಎಪಿಎಂಸಿ', 'code' => 'KA_APMC_YLP', 'district' => 'Uttara Kannada', 'market_type' => 'APMC', 'lat' => 14.9640, 'lon' => 74.7120, 'address' => 'APMC Yard, Yellapur'],
            ['name' => 'Kumta APMC', 'name_kn' => 'ಕುಮಟಾ ಎಪಿಎಂಸಿ', 'code' => 'KA_APMC_KMT', 'district' => 'Uttara Kannada', 'market_type' => 'APMC', 'lat' => 14.4270, 'lon' => 74.4080, 'address' => 'APMC Yard, Kumta'],
            ['name' => 'Mangaluru APMC (Baikampady)', 'name_kn' => 'ಮಂಗಳೂರು ಎಪಿಎಂಸಿ (ಬೈಕಂಪಾಡಿ)', 'code' => 'KA_APMC_MNG', 'district' => 'Dakshina Kannada', 'market_type' => 'APMC', 'lat' => 12.9467, 'lon' => 74.8122, 'address' => 'APMC Yard, Baikampady, Mangaluru'],
            ['name' => 'Bantwal APMC', 'name_kn' => 'ಬಂಟ್ವಾಳ ಎಪಿಎಂಸಿ', 'code' => 'KA_APMC_BNT', 'district' => 'Dakshina Kannada', 'market_type' => 'APMC', 'lat' => 12.8940, 'lon' => 75.0330, 'address' => 'APMC Yard, B.C. Road, Bantwal'],
            ['name' => 'Puttur APMC', 'name_kn' => 'ಪುತ್ತೂರು ಎಪಿಎಂಸಿ', 'code' => 'KA_APMC_PTR', 'district' => 'Dakshina Kannada', 'market_type' => 'APMC', 'lat' => 12.7667, 'lon' => 75.2000, 'address' => 'APMC Yard, Puttur'],
            ['name' => 'Sullia APMC', 'name_kn' => 'ಸುಳ್ಯ ಎಪಿಎಂಸಿ', 'code' => 'KA_APMC_SLA', 'district' => 'Dakshina Kannada', 'market_type' => 'APMC', 'lat' => 12.5600, 'lon' => 75.3900, 'address' => 'APMC Yard, Sullia'],
            ['name' => 'Udupi APMC', 'name_kn' => 'ಉಡುಪಿ ಎಪಿಎಂಸಿ', 'code' => 'KA_APMC_UDP', 'district' => 'Udupi', 'market_type' => 'APMC', 'lat' => 13.3420, 'lon' => 74.7450, 'address' => 'APMC Market, Adi Udupi'],
            ['name' => 'Kundapura APMC', 'name_kn' => 'ಕುಂದಾಪುರ ಎಪಿಎಂಸಿ', 'code' => 'KA_APMC_KND', 'district' => 'Udupi', 'market_type' => 'APMC', 'lat' => 13.6280, 'lon' => 74.6950, 'address' => 'APMC Yard, Kundapura'],
            ['name' => 'Tumakuru APMC', 'name_kn' => 'ತುಮಕೂರು ಎಪಿಎಂಸಿ', 'code' => 'KA_APMC_TMK', 'district' => 'Tumakuru', 'market_type' => 'APMC', 'lat' => 13.3409, 'lon' => 77.1010, 'address' => 'APMC Yard, Batawadi, Tumakuru'],
            ['name' => 'Tiptur APMC', 'name_kn' => 'ತಿಪಟೂರು ಎಪಿಎಂಸಿ', 'code' => 'KA_APMC_TPT', 'district' => 'Tumakuru', 'market_type' => 'APMC', 'lat' => 13.2570, 'lon' => 76.4780, 'address' => 'APMC Yard, Tiptur (Copra Capital)'],
            ['name' => 'Hassan APMC', 'name_kn' => 'ಹಾಸನ ಎಪಿಎಂಸಿ', 'code' => 'KA_APMC_HSN', 'district' => 'Hassan', 'market_type' => 'APMC', 'lat' => 13.0033, 'lon' => 76.1004, 'address' => 'APMC Yard, B.M. Road, Hassan'],
            ['name' => 'Arsikere APMC', 'name_kn' => 'ಅರಸೀಕೆರೆ ಎಪಿಎಂಸಿ', 'code' => 'KA_APMC_ASK', 'district' => 'Hassan', 'market_type' => 'APMC', 'lat' => 13.3138, 'lon' => 76.2570, 'address' => 'APMC Yard, Arsikere, Hassan'],
            ['name' => 'Channarayapatna APMC', 'name_kn' => 'ಚನ್ನರಾಯಪಟ್ಟಣ ಎಪಿಎಂಸಿ', 'code' => 'KA_APMC_CRP', 'district' => 'Hassan', 'market_type' => 'APMC', 'lat' => 12.9050, 'lon' => 76.3880, 'address' => 'APMC Yard, Channarayapatna'],
            ['name' => 'Sakleshpur APMC', 'name_kn' => 'ಸಕಲೇಶಪುರ ಎಪಿಎಂಸಿ', 'code' => 'KA_APMC_SKL', 'district' => 'Hassan', 'market_type' => 'APMC', 'lat' => 12.9430, 'lon' => 75.7870, 'address' => 'APMC Yard, Sakleshpur'],
            ['name' => 'Mysuru APMC (Bandipalya)', 'name_kn' => 'ಮೈಸೂರು ಎಪಿಎಂಸಿ (ಬಂಡಿಪಾಳ್ಯ)', 'code' => 'KA_APMC_MYS', 'district' => 'Mysuru', 'market_type' => 'APMC', 'lat' => 12.2740, 'lon' => 76.6690, 'address' => 'APMC Bandipalya Yard, Mysuru'],
            ['name' => 'Nanjangud APMC', 'name_kn' => 'ನಂಜನಗೂಡು ಎಪಿಎಂಸಿ', 'code' => 'KA_APMC_NJG', 'district' => 'Mysuru', 'market_type' => 'APMC', 'lat' => 12.1190, 'lon' => 76.6830, 'address' => 'APMC Yard, Nanjangud'],
            ['name' => 'Mandya APMC', 'name_kn' => 'ಮಂಡ್ಯ ಎಪಿಎಂಸಿ', 'code' => 'KA_APMC_MDY', 'district' => 'Mandya', 'market_type' => 'APMC', 'lat' => 12.5218, 'lon' => 76.8951, 'address' => 'APMC Yard, Mandya'],
            ['name' => 'Kolar APMC (Tomato Market)', 'name_kn' => 'ಕೋಲಾರ ಎಪಿಎಂಸಿ (ಟೊಮೆಟೊ ಮಾರುಕಟ್ಟೆ)', 'code' => 'KA_APMC_KLR', 'district' => 'Kolar', 'market_type' => 'APMC', 'lat' => 13.1380, 'lon' => 78.1350, 'address' => 'APMC Yard, Kolar'],
            ['name' => 'Chikkaballapura APMC', 'name_kn' => 'ಚಿಕ್ಕಬಳ್ಳಾಪುರ ಎಪಿಎಂಸಿ', 'code' => 'KA_APMC_CKB', 'district' => 'Chikkaballapura', 'market_type' => 'APMC', 'lat' => 13.4355, 'lon' => 77.7315, 'address' => 'APMC Yard, Chikkaballapura'],
            ['name' => 'Yeshwanthpur APMC', 'name_kn' => 'ಯಶವಂತಪುರ ಎಪಿಎಂಸಿ', 'code' => 'KA_APMC_YPR', 'district' => 'Bengaluru Urban', 'market_type' => 'APMC', 'lat' => 13.0238, 'lon' => 77.5458, 'address' => 'APMC Yard, Yeshwanthpur, Bengaluru'],
            ['name' => 'Binny Mill (F&V)', 'name_kn' => 'ಬಿನ್ನಿ ಮಿಲ್ (ಹಣ್ಣು ಮತ್ತು ತರಕಾರಿ)', 'code' => 'KA_APMC_BNM', 'district' => 'Bengaluru Urban', 'market_type' => 'Specialized Market', 'lat' => 12.9667, 'lon' => 77.5667, 'address' => 'Binny Mill Market, Cottonpet, Bengaluru'],
            ['name' => 'Hubballi APMC (Amaragol)', 'name_kn' => 'ಹುಬ್ಬಳ್ಳಿ ಎಪಿಎಂಸಿ (ಅಮರಗೋಳ)', 'code' => 'KA_APMC_HBL', 'district' => 'Dharwad', 'market_type' => 'APMC', 'lat' => 15.3950, 'lon' => 75.0930, 'address' => 'Amaragol APMC Yard, Hubballi'],
            ['name' => 'Belagavi APMC', 'name_kn' => 'ಬೆಳಗಾವಿ ಎಪಿಎಂಸಿ', 'code' => 'KA_APMC_BLG', 'district' => 'Belagavi', 'market_type' => 'APMC', 'lat' => 15.8497, 'lon' => 74.4977, 'address' => 'APMC Yard, Belagavi'],
            ['name' => 'Ballari APMC', 'name_kn' => 'ಬಳ್ಳಾರಿ ಎಪಿಎಂಸಿ', 'code' => 'KA_APMC_BAL', 'district' => 'Ballari', 'market_type' => 'APMC', 'lat' => 15.1394, 'lon' => 76.9214, 'address' => 'APMC Yard, Ballari'],
            ['name' => 'Kalaburagi APMC (Nehru Gunj)', 'name_kn' => 'ಕಲಬುರಗಿ ಎಪಿಎಂಸಿ (ನೆಹರು ಗಂಜ್)', 'code' => 'KA_APMC_KLB', 'district' => 'Kalaburagi', 'market_type' => 'APMC', 'lat' => 17.3297, 'lon' => 76.8343, 'address' => 'Nehru Gunj APMC Yard, Kalaburagi'],
            ['name' => 'Vijayapura APMC', 'name_kn' => 'ವಿಜಯಪುರ ಎಪಿಎಂಸಿ', 'code' => 'KA_APMC_VJP', 'district' => 'Vijayapura', 'market_type' => 'APMC', 'lat' => 16.8302, 'lon' => 75.7100, 'address' => 'APMC Yard, Vijayapura'],
            ['name' => 'Raichur APMC (Cotton & Paddy)', 'name_kn' => 'ರಾಯಚೂರು ಎಪಿಎಂಸಿ (ಹತ್ತಿ ಮತ್ತು ಭತ್ತ)', 'code' => 'KA_APMC_RCH', 'district' => 'Raichur', 'market_type' => 'APMC', 'lat' => 16.2076, 'lon' => 77.3463, 'address' => 'APMC Yard, Raichur'],
            ['name' => 'Ramanagara APMC (Silk Cocoon)', 'name_kn' => 'ರಾಮನಗರ ಎಪಿಎಂಸಿ (ರೇಷ್ಮೆ ಗೂಡು)', 'code' => 'KA_APMC_RAM', 'district' => 'Ramanagara', 'market_type' => 'Specialized Market', 'lat' => 12.7214, 'lon' => 77.2807, 'address' => 'Government Silk Cocoon Market, Ramanagara'],
            ['name' => 'Madikeri (Coffee Board Centre)', 'name_kn' => 'ಮಡಿಕೇರಿ (ಕಾಫಿ ಮಂಡಳಿ ಕೇಂದ್ರ)', 'code' => 'CB_MDK', 'district' => 'Kodagu', 'market_type' => 'Private', 'lat' => 12.4244, 'lon' => 75.7382, 'address' => 'Coffee Board Regional Office, Madikeri'],
            ['name' => 'Chikkamagaluru (Coffee Board Centre)', 'name_kn' => 'ಚಿಕ್ಕಮಗಳೂರು (ಕಾಫಿ ಮಂಡಳಿ ಕೇಂದ್ರ)', 'code' => 'CB_CKM', 'district' => 'Chikkamagaluru', 'market_type' => 'Private', 'lat' => 13.3161, 'lon' => 75.7720, 'address' => 'Coffee Board Office, Chikkamagaluru'],
            ['name' => 'Hassan (Coffee Board Centre)', 'name_kn' => 'ಹಾಸನ (ಕಾಫಿ ಮಂಡಳಿ ಕೇಂದ್ರ)', 'code' => 'CB_HSN', 'district' => 'Hassan', 'market_type' => 'Private', 'lat' => 13.0033, 'lon' => 76.1004, 'address' => 'Coffee Board Office, B.M. Road, Hassan'],
            ['name' => 'Arsikere (CDB Centre)', 'name_kn' => 'ಅರಸೀಕೆರೆ (ತೆಂಗು ಮಂಡಳಿ ಕೇಂದ್ರ)', 'code' => 'CDB_ASK', 'district' => 'Hassan', 'market_type' => 'Private', 'lat' => 13.3138, 'lon' => 76.2570, 'address' => 'Coconut Development Board Field Office, Arsikere'],
            ['name' => 'Mandya (CDB Centre)', 'name_kn' => 'ಮಂಡ್ಯ (ತೆಂಗು ಮಂಡಳಿ ಕೇಂದ್ರ)', 'code' => 'CDB_MDY', 'district' => 'Mandya', 'market_type' => 'Private', 'lat' => 12.5218, 'lon' => 76.8951, 'address' => 'Coconut Development Board Office, Mandya'],
            ['name' => 'Tumakuru (CDB Centre)', 'name_kn' => 'ತುಮಕೂರು (ತೆಂಗು ಮಂಡಳಿ ಕೇಂದ್ರ)', 'code' => 'CDB_TMK', 'district' => 'Tumakuru', 'market_type' => 'Private', 'lat' => 13.3409, 'lon' => 77.1010, 'address' => 'Coconut Development Board Office, Tumakuru'],
        ];
    }

    /**
     * Auto-generate a clean, unique code for a mandi when none is provided.
     */
    protected function generateMarketCode(string $name, ?int $ignoreId = null): string
    {
        $clean = preg_replace('/\s*\(.*?\)/', '', $name);
        $clean = preg_replace('/\s+APMC/i', '', $clean);
        $base = 'KA_APMC_' . Str::upper(Str::slug(trim($clean), '_'));
        if (strlen($base) > 35) {
            $base = substr($base, 0, 35);
        }
        $code = $base;
        $counter = 1;
        while (Market::where('code', $code)->when($ignoreId, fn($q) => $q->where('id', '!=', $ignoreId))->exists()) {
            $code = "{$base}_{$counter}";
            $counter++;
        }
        return $code;
    }

    /**
     * Display a listing of APMC markets.
     */
    public function index(Request $request): View
    {
        $search = $request->query('search');
        $districtId = $request->query('district_id');

        $markets = Market::with(['district', 'taluk'])
            ->when($districtId, function ($query, $districtId) {
                $query->where('district_id', $districtId);
            })
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('name_kn', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        $districts = District::where('is_active', true)->orderBy('name')->get();

        return view('admin.master.markets.index', compact('markets', 'districts', 'search', 'districtId'));
    }

    /**
     * Show the form for creating a new APMC market.
     */
    public function create(Request $request): View
    {
        $selectedDistrictId = $request->query('district_id');
        $districts = District::where('is_active', true)->orderBy('name')->get();
        $taluks = Taluk::where('is_active', true)
            ->when($selectedDistrictId, fn ($q) => $q->where('district_id', $selectedDistrictId))
            ->orderBy('name')
            ->get();
        $presets = $this->getKarnatakaPresets();

        return view('admin.master.markets.form', [
            'market' => new Market(['district_id' => $selectedDistrictId, 'market_type' => 'APMC', 'is_active' => true]),
            'districts' => $districts,
            'taluks' => $taluks,
            'presets' => $presets,
            'isEdit' => false,
        ]);
    }

    /**
     * Store a newly created market.
     */
    public function store(MarketRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active');

        if (empty($data['code'])) {
            $data['code'] = $this->generateMarketCode($data['name']);
        }

        $market = Market::create($data);

        AuditLog::log('market.create', 'Market', $market->id, null, $market->toArray());

        return redirect()->route('admin.markets.index')
            ->with('success', "Mandi '{$market->name}' registered successfully with code [{$market->code}].");
    }

    /**
     * Show the form for editing the market.
     */
    public function edit(Market $market): View
    {
        $districts = District::where('is_active', true)->orderBy('name')->get();
        $taluks = Taluk::where('district_id', $market->district_id)->orderBy('name')->get();
        $dataSources = DataSource::where('is_active', true)->orderBy('name')->get();
        $presets = $this->getKarnatakaPresets();

        $market->load(['sourceMappings.dataSource']);

        return view('admin.master.markets.form', [
            'market' => $market,
            'districts' => $districts,
            'taluks' => $taluks,
            'dataSources' => $dataSources,
            'presets' => $presets,
            'isEdit' => true,
        ]);
    }

    /**
     * Update the specified market.
     */
    public function update(MarketRequest $request, Market $market): RedirectResponse
    {
        $oldValues = $market->toArray();
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active');

        if (empty($data['code'])) {
            $data['code'] = $this->generateMarketCode($data['name'], $market->id);
        }

        $market->update($data);

        AuditLog::log('market.update', 'Market', $market->id, $oldValues, $market->toArray());

        return redirect()->route('admin.markets.index')
            ->with('success', "Mandi '{$market->name}' updated successfully.");
    }

    /**
     * Toggle the active status of a market.
     */
    public function toggleStatus(Market $market): RedirectResponse
    {
        $market->is_active = !$market->is_active;
        $market->save();

        AuditLog::log('market.status_toggle', 'Market', $market->id, null, ['is_active' => $market->is_active]);

        $statusLabel = $market->is_active ? 'activated' : 'deactivated';
        return back()->with('success', "Mandi '{$market->name}' was {$statusLabel}.");
    }

    /**
     * Add a raw feed alias mapping directly to this market.
     */
    public function addAlias(Request $request, Market $market, MarketPriceIngestionService $service): RedirectResponse
    {
        $validated = $request->validate([
            'source_market_name' => ['required', 'string', 'max:255'],
            'data_source_id' => ['nullable', 'exists:data_sources,id'],
            'source_district_name' => ['nullable', 'string', 'max:150'],
        ]);

        $dataSourceId = !empty($validated['data_source_id'])
            ? (int) $validated['data_source_id']
            : (DataSource::where('code', 'data_gov_mandi')->value('id') ?? DataSource::first()->id);

        $mapping = MarketSourceMapping::updateOrCreate(
            [
                'data_source_id' => $dataSourceId,
                'source_market_name' => trim($validated['source_market_name']),
                'source_district_name' => !empty($validated['source_district_name']) ? trim($validated['source_district_name']) : null,
            ],
            [
                'market_id' => $market->id,
                'is_verified' => true,
            ]
        );

        AuditLog::log('market.alias_add', 'MarketSourceMapping', $mapping->id, null, $mapping->toArray());

        // Auto-reprocess affected rejected records
        $reprocessResults = $service->reprocessBatch($dataSourceId, $validated['source_market_name']);

        $msg = "Feed alias '{$validated['source_market_name']}' successfully mapped to {$market->name}.";
        if ($reprocessResults['processed'] > 0) {
            $msg .= " Auto-reprocessed {$reprocessResults['processed']} existing raw price records into active prices!";
        }

        return back()->with('success', $msg);
    }

    /**
     * Remove an alias mapping from this market.
     */
    public function removeAlias(Market $market, MarketSourceMapping $mapping): RedirectResponse
    {
        if ($mapping->market_id !== $market->id) {
            abort(403, 'Unauthorized alias modification.');
        }

        $aliasName = $mapping->source_market_name;
        $mapping->delete();

        AuditLog::log('market.alias_remove', 'MarketSourceMapping', $mapping->id, null, ['deleted_alias' => $aliasName]);

        return back()->with('success', "Alias '{$aliasName}' removed from {$market->name}.");
    }
}
