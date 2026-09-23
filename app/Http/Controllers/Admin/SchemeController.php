<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Scheme;
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

        $schemes = Scheme::query()
            ->when($category, fn($q) => $q->where('category', $category))
            ->when($search, function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('title', 'like', "%{$search}%")
                        ->orWhere('title_kn', 'like', "%{$search}%")
                        ->orWhere('sponsoring_agency', 'like', "%{$search}%");
                });
            })
            ->orderBy('display_order')
            ->orderBy('title')
            ->paginate(15)
            ->withQueryString();

        return view('admin.schemes.index', compact('schemes', 'search', 'category'));
    }

    public function create(): View
    {
        return view('admin.schemes.form', [
            'scheme' => new Scheme(['is_active' => true, 'category' => 'subsidy', 'display_order' => 0]),
            'isEdit' => false,
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
            'benefits' => 'nullable|string',
            'benefits_kn' => 'nullable|string',
            'eligibility_criteria' => 'nullable|string',
            'eligibility_criteria_kn' => 'nullable|string',
            'eligibility' => 'nullable|string',
            'eligibility_kn' => 'nullable|string',
            'documents_required' => 'nullable|string',
            'official_url' => 'nullable|url|max:500',
            'apply_url' => 'nullable|url|max:500',
            'icon_emoji' => 'nullable|string|max:20',
            'display_order' => 'nullable|integer',
            'is_active' => 'nullable|boolean',
        ]);

        $validated['slug'] = !empty($validated['slug']) ? Str::slug($validated['slug']) : Str::slug($validated['title']);
        $validated['sponsoring_agency'] = $validated['sponsoring_agency'] ?? 'Karnataka Dept of Agriculture';
        $validated['benefit_amount'] = $validated['benefit_amount'] ?? $validated['benefits'] ?? null;
        $validated['benefit_amount_kn'] = $validated['benefit_amount_kn'] ?? $validated['benefits_kn'] ?? null;
        $validated['eligibility_criteria'] = $validated['eligibility_criteria'] ?? $validated['eligibility'] ?? null;
        $validated['eligibility_criteria_kn'] = $validated['eligibility_criteria_kn'] ?? $validated['eligibility_kn'] ?? null;
        $validated['is_active'] = $request->boolean('is_active');
        $validated['display_order'] = (int) ($validated['display_order'] ?? 0);

        Scheme::create($validated);

        return redirect()->route('admin.schemes.index')->with('status', 'ಸರ್ಕಾರಿ ಯೋಜನೆಯನ್ನು ಯಶಸ್ವಿಯಾಗಿ ಸೇರಿಸಲಾಗಿದೆ (Scheme created successfully).');
    }

    public function edit(Scheme $scheme): View
    {
        return view('admin.schemes.form', [
            'scheme' => $scheme,
            'isEdit' => true,
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
            'benefits' => 'nullable|string',
            'benefits_kn' => 'nullable|string',
            'eligibility_criteria' => 'nullable|string',
            'eligibility_criteria_kn' => 'nullable|string',
            'eligibility' => 'nullable|string',
            'eligibility_kn' => 'nullable|string',
            'documents_required' => 'nullable|string',
            'official_url' => 'nullable|url|max:500',
            'apply_url' => 'nullable|url|max:500',
            'icon_emoji' => 'nullable|string|max:20',
            'display_order' => 'nullable|integer',
            'is_active' => 'nullable|boolean',
        ]);

        $validated['slug'] = !empty($validated['slug']) ? Str::slug($validated['slug']) : $scheme->slug;
        $validated['sponsoring_agency'] = $validated['sponsoring_agency'] ?? $scheme->sponsoring_agency ?? 'Karnataka Dept of Agriculture';
        $validated['benefit_amount'] = $validated['benefit_amount'] ?? $validated['benefits'] ?? $scheme->benefit_amount;
        $validated['benefit_amount_kn'] = $validated['benefit_amount_kn'] ?? $validated['benefits_kn'] ?? $scheme->benefit_amount_kn;
        $validated['eligibility_criteria'] = $validated['eligibility_criteria'] ?? $validated['eligibility'] ?? $scheme->eligibility_criteria;
        $validated['eligibility_criteria_kn'] = $validated['eligibility_criteria_kn'] ?? $validated['eligibility_kn'] ?? $scheme->eligibility_criteria_kn;
        $validated['is_active'] = $request->boolean('is_active');
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

    public function destroy(Scheme $scheme): RedirectResponse
    {
        $scheme->delete();

        return redirect()->route('admin.schemes.index')->with('status', 'ಯೋಜನೆಯನ್ನು ಅಳಿಸಲಾಗಿದೆ (Scheme deleted).');
    }
}
