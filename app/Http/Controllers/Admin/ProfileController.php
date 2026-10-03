<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\District;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the admin's profile and security settings.
     */
    public function edit(): View
    {
        $user = auth()->user();
        $districts = District::where('is_active', true)->orderBy('name')->get();

        $recentLogs = AuditLog::where('user_id', $user->id)
            ->latest('id')
            ->limit(8)
            ->get();

        return view('admin.profile.edit', compact('user', 'districts', 'recentLogs'));
    }

    /**
     * Update the admin's personal information.
     */
    public function update(Request $request): RedirectResponse
    {
        $user = auth()->user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:20', 'unique:users,phone,' . $user->id],
            'preferred_language' => ['required', 'in:kn,en'],
            'district_id' => ['nullable', 'exists:districts,id'],
        ]);

        $oldValues = [
            'name' => $user->name,
            'phone' => $user->phone,
            'preferred_language' => $user->preferred_language,
            'district_id' => $user->district_id,
        ];

        $user->update($validated);

        if ($validated['preferred_language'] !== session('locale')) {
            session(['locale' => $validated['preferred_language']]);
        }

        AuditLog::log('admin.profile.update', 'User', $user->id, $oldValues, $validated);

        return redirect()->route('admin.profile.edit')->with('success', 'Your profile details have been updated successfully.');
    }

    /**
     * Update the admin's password.
     */
    public function updatePassword(Request $request): RedirectResponse
    {
        $user = auth()->user();

        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'string', Password::min(8)->letters()->numbers(), 'confirmed', 'different:current_password'],
        ]);

        $user->update([
            'password' => Hash::make($validated['password']),
        ]);

        AuditLog::log('admin.profile.password_change', 'User', $user->id);

        return redirect()->route('admin.profile.edit')->with('success', 'Your password has been changed successfully. Use your new password on your next login.');
    }
}
