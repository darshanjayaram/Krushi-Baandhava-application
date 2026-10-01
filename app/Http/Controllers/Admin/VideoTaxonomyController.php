<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CuratedVideo;
use App\Models\VideoTaxonomy;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class VideoTaxonomyController extends Controller
{
    public function index(Request $request): View
    {
        $currentType = $request->query('type', 'category');
        if (!in_array($currentType, ['category', 'growth_stage'])) {
            $currentType = 'category';
        }

        $items = VideoTaxonomy::where('type', $currentType)
            ->ordered()
            ->get();

        $categoryCount = VideoTaxonomy::category()->count();
        $growthStageCount = VideoTaxonomy::growthStage()->count();

        return view('admin.videos.taxonomies.index', compact(
            'items',
            'currentType',
            'categoryCount',
            'growthStageCount'
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        $type = $request->input('type', 'category');
        if (!in_array($type, ['category', 'growth_stage'])) {
            $type = 'category';
        }

        $slug = $request->input('slug') ? Str::slug($request->input('slug'), '_') : Str::slug($request->input('name'), '_');

        $validated = $request->validate([
            'type' => ['required', Rule::in(['category', 'growth_stage'])],
            'name' => ['required', 'string', 'max:120'],
            'name_kn' => ['nullable', 'string', 'max:191'],
            'icon' => ['nullable', 'string', 'max:32'],
            'display_order' => ['nullable', 'integer'],
            'description' => ['nullable', 'string'],
        ]);

        // Validate uniqueness of slug for this type
        $request->validate([
            'slug' => [
                'nullable',
                Rule::unique('video_taxonomies', 'slug')->where('type', $type),
            ],
        ]);

        VideoTaxonomy::create([
            'type' => $type,
            'slug' => $slug,
            'name' => $validated['name'],
            'name_kn' => $validated['name_kn'] ?? null,
            'icon' => $validated['icon'] ?: ($type === 'category' ? '🌾' : '🌱'),
            'description' => $validated['description'] ?? null,
            'display_order' => (int) ($validated['display_order'] ?? 0),
            'is_active' => $request->boolean('is_active', true),
        ]);

        $label = $type === 'category' ? 'Category' : 'Growth Stage';
        return redirect()->route('admin.videos.taxonomies.index', ['type' => $type])
            ->with('success', "{$label} '{$validated['name']}' created successfully.");
    }

    public function update(Request $request, VideoTaxonomy $taxonomy): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'name_kn' => ['nullable', 'string', 'max:191'],
            'slug' => [
                'required',
                'string',
                'max:64',
                Rule::unique('video_taxonomies', 'slug')
                    ->where('type', $taxonomy->type)
                    ->ignore($taxonomy->id),
            ],
            'icon' => ['nullable', 'string', 'max:32'],
            'display_order' => ['nullable', 'integer'],
            'description' => ['nullable', 'string'],
        ]);

        $oldSlug = $taxonomy->slug;
        $newSlug = Str::slug($validated['slug'], '_');

        $taxonomy->update([
            'name' => $validated['name'],
            'name_kn' => $validated['name_kn'] ?? null,
            'slug' => $newSlug,
            'icon' => $validated['icon'],
            'description' => $validated['description'] ?? null,
            'display_order' => (int) ($validated['display_order'] ?? 0),
            'is_active' => $request->boolean('is_active'),
        ]);

        // If the admin changed the slug, update existing curated videos that referenced the old slug
        if ($oldSlug !== $newSlug) {
            if ($taxonomy->type === 'category') {
                CuratedVideo::where('category', $oldSlug)->update(['category' => $newSlug]);
            } else {
                CuratedVideo::where('growth_stage', $oldSlug)->update(['growth_stage' => $newSlug]);
            }
        }

        $label = $taxonomy->type === 'category' ? 'Category' : 'Growth Stage';
        return redirect()->route('admin.videos.taxonomies.index', ['type' => $taxonomy->type])
            ->with('success', "{$label} updated successfully.");
    }

    public function destroy(VideoTaxonomy $taxonomy): RedirectResponse
    {
        $type = $taxonomy->type;
        $videosCount = $taxonomy->videos_count;

        if ($videosCount > 0) {
            $label = $type === 'category' ? 'category' : 'growth stage';
            return redirect()->route('admin.videos.taxonomies.index', ['type' => $type])
                ->with('error', "Cannot delete {$label} '{$taxonomy->name}' because {$videosCount} video(s) are linked to it. Please reassign or deactivate it instead.");
        }

        $name = $taxonomy->name;
        $taxonomy->delete();

        $label = $type === 'category' ? 'Category' : 'Growth Stage';
        return redirect()->route('admin.videos.taxonomies.index', ['type' => $type])
            ->with('success', "{$label} '{$name}' deleted successfully.");
    }

    public function toggle(VideoTaxonomy $taxonomy): JsonResponse
    {
        $taxonomy->is_active = !$taxonomy->is_active;
        $taxonomy->save();

        return response()->json([
            'success' => true,
            'is_active' => $taxonomy->is_active,
            'message' => 'Status updated successfully.',
        ]);
    }
}
