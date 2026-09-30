<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SystemSetting;
use Illuminate\Http\Request;

class NoteController extends Controller
{
    /**
     * Display the Admin Notes & System Guidelines.
     */
    public function index()
    {
        $customNotes = SystemSetting::get('admin_operational_notes', '');

        return view('admin.notes.index', [
            'customNotes' => $customNotes,
        ]);
    }

    /**
     * Update custom operational notes.
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'admin_notes' => 'nullable|string|max:20000',
        ]);

        SystemSetting::set('admin_operational_notes', $validated['admin_notes'] ?? '', 'string', 'operations', 'Internal operational notes and memos for admin users.');

        return redirect()->route('admin.notes.index')->with('success', 'Admin operational notes updated successfully.');
    }
}
