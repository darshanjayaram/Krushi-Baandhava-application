<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CropRequest;
use App\Models\AuditLog;
use App\Models\Crop;
use App\Models\CropCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CropController extends Controller
{
    /**
     * Display a listing of crops.
     */
    public function index(Request $request): View
    {
        $search = $request->query('search');
        $categoryId = $request->query('category_id');

        $crops = Crop::with(['category'])
            ->withCount('varieties')
            ->when($categoryId, function ($query, $categoryId) {
                $query->where('category_id', $categoryId);
            })
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('name_kn', 'like', "%{$search}%")
                        ->orWhere('scientific_name', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        $categories = CropCategory::where('is_active', true)->orderBy('display_order')->get();

        return view('admin.master.crops.index', compact('crops', 'categories', 'search', 'categoryId'));
    }

    /**
     * Show the form for creating a new crop.
     */
    public function create(Request $request): View
    {
        $categories = CropCategory::where('is_active', true)->orderBy('display_order')->get();

        return view('admin.master.crops.form', [
            'crop' => new Crop([
                'category_id' => $request->query('category_id') ?? $categories->first()?->id,
                'standard_unit' => 'Quintal',
                'is_major' => false,
                'is_active' => true,
            ]),
            'categories' => $categories,
            'isEdit' => false,
        ]);
    }

    /**
     * Store a newly created crop.
     */
    public function store(CropRequest $request): RedirectResponse
    {
        $data = $request->validated();
        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($data['name']);
        }
        $data['is_major'] = $request->boolean('is_major');
        $data['is_active'] = $request->boolean('is_active');

        $crop = Crop::create($data);

        AuditLog::log('crop.create', 'Crop', $crop->id, null, $crop->toArray());

        return redirect()->route('admin.crops.index')
            ->with('success', "Crop '{$crop->name}' registered successfully.");
    }

    /**
     * Show the form for editing the crop and managing varieties.
     */
    public function edit(Crop $crop): View
    {
        $crop->load(['varieties' => fn ($q) => $q->orderBy('name')]);
        $categories = CropCategory::where('is_active', true)->orderBy('display_order')->get();

        return view('admin.master.crops.form', [
            'crop' => $crop,
            'categories' => $categories,
            'isEdit' => true,
        ]);
    }

    /**
     * Update the specified crop.
     */
    public function update(CropRequest $request, Crop $crop): RedirectResponse
    {
        $oldValues = $crop->toArray();
        $data = $request->validated();
        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($data['name']);
        }
        $data['is_major'] = $request->boolean('is_major');
        $data['is_active'] = $request->boolean('is_active');

        $crop->update($data);

        AuditLog::log('crop.update', 'Crop', $crop->id, $oldValues, $crop->toArray());

        return redirect()->route('admin.crops.index')
            ->with('success', "Crop '{$crop->name}' updated successfully.");
    }

    /**
     * Toggle the active status of a crop.
     */
    public function toggleStatus(Crop $crop): RedirectResponse
    {
        $crop->is_active = !$crop->is_active;
        $crop->save();

        AuditLog::log('crop.status_toggle', 'Crop', $crop->id, null, ['is_active' => $crop->is_active]);

        $statusLabel = $crop->is_active ? 'activated' : 'deactivated';
        return back()->with('success', "Crop '{$crop->name}' was {$statusLabel}.");
    }
}
