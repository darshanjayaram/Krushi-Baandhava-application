<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Scheme;
use App\Models\SystemSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class SchemeController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->query('search');
        $category = $request->query('category');
        $status = $request->query('status');
        $featured = $request->query('featured');

        $query = Scheme::query();

        if ($category) {
            $query->where('category', $category);
        }

        if ($status !== null && $status !== '') {
            $query->where('is_active', $status === 'active' || $status === '1');
        }

        if ($featured !== null && $featured !== '') {
            $query->where('is_featured', $featured === 'yes' || $featured === '1');
        }

        if ($search) {
            $query->where(function ($sub) use ($search) {
                $sub->where('title', 'like', "%{$search}%")
                    ->orWhere('title_kn', 'like', "%{$search}%")
                    ->orWhere('sponsoring_agency', 'like', "%{$search}%")
                    ->orWhere('benefit_amount', 'like', "%{$search}%")
                    ->orWhere('benefit_amount_kn', 'like', "%{$search}%");
            });
        }

        $schemes = $query->orderByDesc('is_featured')
            ->orderBy('display_order')
            ->orderBy('id')
            ->paginate(15)
            ->withQueryString();

        // System Settings for Farmer UI
        $perPageSetting = (int) SystemSetting::get('schemes_per_page', 9);
        $sliderAutoplay = (int) SystemSetting::get('schemes_slider_autoplay', 5);
        $showBanner = (bool) SystemSetting::get('schemes_show_banner', true);

        // Stats
        $totalCount = Scheme::count();
        $activeCount = Scheme::where('is_active', true)->count();
        $featuredCount = Scheme::where('is_featured', true)->count();

        $categories = [
            'subsidy' => ['name_en' => 'Subsidies & Grants', 'name_kn' => 'ಸಬ್ಸಿಡಿ & ಅನುದಾನ', 'icon' => '💰'],
            'machinery' => ['name_en' => 'Farm Machinery', 'name_kn' => 'ಕೃಷಿ ಯಂತ್ರೋಪಕರಣ', 'icon' => '🚜'],
            'irrigation' => ['name_en' => 'Micro Irrigation', 'name_kn' => 'ಸೂಕ್ಷ್ಮ ನೀರಾವರಿ', 'icon' => '💧'],
            'insurance' => ['name_en' => 'Crop Insurance', 'name_kn' => 'ಬೆಳೆ ವಿಮೆ', 'icon' => '🛡️'],
            'organic' => ['name_en' => 'Organic & Soil', 'name_kn' => 'ಸಾವಯವ & ಮಣ್ಣು', 'icon' => '🌱'],
        ];

        return view('admin.schemes.index', compact(
            'schemes',
            'search',
            'category',
            'status',
            'featured',
            'perPageSetting',
            'sliderAutoplay',
            'showBanner',
            'totalCount',
            'activeCount',
            'featuredCount',
            'categories'
        ));
    }

    public function create(): View
    {
        return view('admin.schemes.form', [
            'scheme' => new Scheme([
                'is_active' => true,
                'is_featured' => false,
                'category' => 'subsidy',
                'display_order' => 0,
                'icon_emoji' => '💰',
            ]),
            'isEdit' => false,
            'categories' => $this->getCategoriesList(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'title_kn' => 'nullable|string|max:255',
            'slug' => 'nullable|string|max:255|unique:schemes,slug',
            'category' => 'required|string|max:50',
            'sponsoring_agency' => 'nullable|string|max:255',
            'benefit_amount' => 'nullable|string|max:255',
            'benefit_amount_kn' => 'nullable|string|max:255',
            'eligibility_criteria' => 'nullable|string',
            'eligibility_criteria_kn' => 'nullable|string',
            'documents_required' => 'nullable|string',
            'official_url' => 'nullable|url|max:500',
            'apply_url' => 'nullable|url|max:500',
            'icon_emoji' => 'nullable|string|max:20',
            'display_order' => 'nullable|integer',
            'is_active' => 'nullable|boolean',
            'is_featured' => 'nullable|boolean',
            'banner_tag' => 'nullable|string|max:100',
        ]);

        $validated['slug'] = !empty($validated['slug']) ? Str::slug($validated['slug']) : Str::slug($validated['title']);
        $validated['sponsoring_agency'] = $validated['sponsoring_agency'] ?? 'ಕರ್ನಾಟಕ ಕೃಷಿ ಇಲಾಖೆ';
        $validated['is_active'] = $request->boolean('is_active');
        $validated['is_featured'] = $request->boolean('is_featured');
        $validated['display_order'] = (int) ($validated['display_order'] ?? 0);

        Scheme::create($validated);

        return redirect()->route('admin.schemes.index')->with('status', 'ಸರ್ಕಾರಿ ಯೋಜನೆಯನ್ನು ಯಶಸ್ವಿಯಾಗಿ ಸೇರಿಸಲಾಗಿದೆ (Scheme created successfully).');
    }

    public function edit(Scheme $scheme): View
    {
        return view('admin.schemes.form', [
            'scheme' => $scheme,
            'isEdit' => true,
            'categories' => $this->getCategoriesList(),
        ]);
    }

    public function update(Request $request, Scheme $scheme): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'title_kn' => 'nullable|string|max:255',
            'slug' => 'nullable|string|max:255|unique:schemes,slug,' . $scheme->id,
            'category' => 'required|string|max:50',
            'sponsoring_agency' => 'nullable|string|max:255',
            'benefit_amount' => 'nullable|string|max:255',
            'benefit_amount_kn' => 'nullable|string|max:255',
            'eligibility_criteria' => 'nullable|string',
            'eligibility_criteria_kn' => 'nullable|string',
            'documents_required' => 'nullable|string',
            'official_url' => 'nullable|url|max:500',
            'apply_url' => 'nullable|url|max:500',
            'icon_emoji' => 'nullable|string|max:20',
            'display_order' => 'nullable|integer',
            'is_active' => 'nullable|boolean',
            'is_featured' => 'nullable|boolean',
            'banner_tag' => 'nullable|string|max:100',
        ]);

        $validated['slug'] = !empty($validated['slug']) ? Str::slug($validated['slug']) : $scheme->slug;
        $validated['sponsoring_agency'] = $validated['sponsoring_agency'] ?? $scheme->sponsoring_agency ?? 'ಕರ್ನಾಟಕ ಕೃಷಿ ಇಲಾಖೆ';
        $validated['is_active'] = $request->boolean('is_active');
        $validated['is_featured'] = $request->boolean('is_featured');
        $validated['display_order'] = (int) ($validated['display_order'] ?? 0);

        $scheme->update($validated);

        return redirect()->route('admin.schemes.index')->with('status', 'ಯೋಜನೆಯ ವಿವರಗಳನ್ನು ನವೀಕರಿಸಲಾಗಿದೆ (Scheme updated successfully).');
    }

    public function toggle(Scheme $scheme): RedirectResponse
    {
        $scheme->update(['is_active' => !$scheme->is_active]);

        $msg = $scheme->is_active ? 'ಯೋಜನೆಯನ್ನು ಸಕ್ರಿಯಗೊಳಿಸಲಾಗಿದೆ (Activated)' : 'ಯೋಜನೆಯನ್ನು ನಿಷ್ಕ್ರಿಯಗೊಳಿಸಲಾಗಿದೆ (Deactivated)';
        return back()->with('status', $msg);
    }

    public function toggleFeatured(Scheme $scheme): RedirectResponse
    {
        $scheme->update(['is_featured' => !$scheme->is_featured]);

        $msg = $scheme->is_featured 
            ? 'ಯೋಜನೆಯನ್ನು ಮುಖಪುಟ ಬ್ಯಾನರ್ ಸ್ಲೈಡರ್‌ಗೆ ಸೇರಿಸಲಾಗಿದೆ (Added to Banner Slider)' 
            : 'ಯೋಜನೆಯನ್ನು ಬ್ಯಾನರ್ ಸ್ಲೈಡರ್‌ನಿಂದ ತೆಗೆಯಲಾಗಿದೆ (Removed from Banner Slider)';
        return back()->with('status', $msg);
    }

    public function updateSettings(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'per_page' => 'required|integer|in:4,6,8,9,12,15,18,24,30',
            'slider_autoplay' => 'nullable|integer|in:0,3,4,5,7,10',
            'show_banner' => 'nullable|boolean',
        ]);

        SystemSetting::set(
            'schemes_per_page',
            (int) $validated['per_page'],
            'integer',
            'schemes',
            'Max schemes displayed per page on farmer schemes hub'
        );

        if ($request->has('slider_autoplay')) {
            SystemSetting::set(
                'schemes_slider_autoplay',
                (int) $validated['slider_autoplay'],
                'integer',
                'schemes',
                'Auto-play rotation duration in seconds for scheme banner slider (0 = manual)'
            );
        }

        if ($request->has('show_banner')) {
            SystemSetting::set(
                'schemes_show_banner',
                $request->boolean('show_banner'),
                'boolean',
                'schemes',
                'Enable or disable top spotlight banner slider on farmer schemes hub'
            );
        }

        return redirect()->route('admin.schemes.index')->with('status', "ಯೋಜನೆಗಳ ಪುಟದ ಸೆಟ್ಟಿಂಗ್ಸ್ ನವೀಕರಿಸಲಾಗಿದೆ (Settings updated successfully: {$validated['per_page']} schemes per page).");
    }

    public function bulkAction(Request $request): RedirectResponse
    {
        $ids = $request->input('selected_ids', []);
        $action = $request->input('action');

        if (empty($ids) || !is_array($ids)) {
            return back()->with('error', 'ಯಾವುದೇ ಯೋಜನೆಯನ್ನು ಆಯ್ಕೆ ಮಾಡಿಲ್ಲ (No schemes selected).');
        }

        switch ($action) {
            case 'activate':
                Scheme::whereIn('id', $ids)->update(['is_active' => true]);
                $msg = count($ids) . ' ಯೋಜನೆಗಳನ್ನು ಸಕ್ರಿಯಗೊಳಿಸಲಾಗಿದೆ (Activated).';
                break;
            case 'deactivate':
                Scheme::whereIn('id', $ids)->update(['is_active' => false]);
                $msg = count($ids) . ' ಯೋಜನೆಗಳನ್ನು ನಿಷ್ಕ್ರಿಯಗೊಳಿಸಲಾಗಿದೆ (Deactivated).';
                break;
            case 'feature':
                Scheme::whereIn('id', $ids)->update(['is_featured' => true]);
                $msg = count($ids) . ' ಯೋಜನೆಗಳನ್ನು ಬ್ಯಾನರ್ ಸ್ಲೈಡರ್‌ಗೆ ಸೇರಿಸಲಾಗಿದೆ (Featured on Banner Slider).';
                break;
            case 'unfeature':
                Scheme::whereIn('id', $ids)->update(['is_featured' => false]);
                $msg = count($ids) . ' ಯೋಜನೆಗಳನ್ನು ಬ್ಯಾನರ್ ಸ್ಲೈಡರ್‌ನಿಂದ ತೆಗೆಯಲಾಗಿದೆ (Unfeatured).';
                break;
            case 'delete':
                Scheme::whereIn('id', $ids)->delete();
                $msg = count($ids) . ' ಯೋಜನೆಗಳನ್ನು ಅಳಿಸಲಾಗಿದೆ (Deleted).';
                break;
            default:
                return back()->with('error', 'ಅಮಾನ್ಯ ಕ್ರಿಯೆ (Invalid action).');
        }

        return redirect()->route('admin.schemes.index')->with('status', $msg);
    }

    public function destroy(Scheme $scheme): RedirectResponse
    {
        $scheme->delete();

        return redirect()->route('admin.schemes.index')->with('status', 'ಯೋಜನೆಯನ್ನು ಅಳಿಸಲಾಗಿದೆ (Scheme deleted).');
    }

    private function getCategoriesList(): array
    {
        return [
            'subsidy' => ['name_en' => 'Subsidies & Grants', 'name_kn' => 'ಸಬ್ಸಿಡಿ & ಅನುದಾನ', 'icon' => '💰'],
            'machinery' => ['name_en' => 'Farm Machinery', 'name_kn' => 'ಕೃಷಿ ಯಂತ್ರೋಪಕರಣ', 'icon' => '🚜'],
            'irrigation' => ['name_en' => 'Micro Irrigation', 'name_kn' => 'ಸೂಕ್ಷ್ಮ ನೀರಾವರಿ', 'icon' => '💧'],
            'insurance' => ['name_en' => 'Crop Insurance', 'name_kn' => 'ಬೆಳೆ ವಿಮೆ', 'icon' => '🛡️'],
            'organic' => ['name_en' => 'Organic & Soil', 'name_kn' => 'ಸಾವಯವ & ಮಣ್ಣು', 'icon' => '🌱'],
        ];
    }
}
