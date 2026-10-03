<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\District;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class UserController extends Controller
{
    /**
     * Display a listing of admin staff users and roles.
     */
    public function index(Request $request): View
    {
        $search = $request->query('search');
        $roleFilter = $request->query('role');
        $statusFilter = $request->query('status');

        $adminRoles = array_keys(User::getAdminRoles());

        $users = User::with('district')
            ->whereIn('role', $adminRoles)
            ->when($search, function ($q, $search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                });
            })
            ->when($roleFilter, fn ($q) => $q->where('role', $roleFilter))
            ->when($statusFilter !== null && $statusFilter !== '', function ($q) use ($statusFilter) {
                $q->where('is_active', $statusFilter === 'active');
            })
            ->orderBy('id', 'asc')
            ->paginate(15)
            ->withQueryString();

        $metrics = [
            'total_staff' => User::whereIn('role', $adminRoles)->count(),
            'active_staff' => User::whereIn('role', $adminRoles)->where('is_active', true)->count(),
            'super_admins' => User::where('role', User::ROLE_SUPER_ADMIN)->count(),
            'data_admins' => User::where('role', User::ROLE_DATA_ADMIN)->count(),
            'content_admins' => User::where('role', User::ROLE_CONTENT_ADMIN)->count(),
            'support_admins' => User::where('role', User::ROLE_SUPPORT_ADMIN)->count(),
        ];

        $availableRoles = User::getAdminRoles();

        return view('admin.users.index', compact('users', 'metrics', 'search', 'roleFilter', 'statusFilter', 'availableRoles'));
    }

    /**
     * Show form for creating a new admin staff member.
     */
    public function create(): View
    {
        $districts = District::where('is_active', true)->orderBy('name')->get();
        $roles = User::getAdminRoles();
        $user = new User(['role' => User::ROLE_DATA_ADMIN, 'is_active' => true, 'preferred_language' => 'kn']);

        return view('admin.users.form', compact('user', 'districts', 'roles'));
    }

    /**
     * Store a newly created admin staff user.
     */
    public function store(Request $request): RedirectResponse
    {
        $adminRoles = array_keys(User::getAdminRoles());

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:150', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:20', 'unique:users,phone'],
            'role' => ['required', 'in:' . implode(',', $adminRoles)],
            'district_id' => ['nullable', 'exists:districts,id'],
            'password' => ['required', 'string', Password::min(8)],
            'preferred_language' => ['required', 'in:kn,en'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'role' => $validated['role'],
            'district_id' => $validated['district_id'],
            'password' => Hash::make($validated['password']),
            'preferred_language' => $validated['preferred_language'],
            'is_active' => true,
        ]);

        AuditLog::log('admin.user.create', 'User', $user->id, null, [
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
        ]);

        return redirect()->route('admin.users.index')->with('success', "New staff member \"{$user->name}\" created successfully with role {$user->getRoleTitle()}.");
    }

    /**
     * Show form for editing an admin staff user.
     */
    public function edit(User $user): View
    {
        $districts = District::where('is_active', true)->orderBy('name')->get();
        $roles = User::getAdminRoles();

        return view('admin.users.form', compact('user', 'districts', 'roles'));
    }

    /**
     * Update an admin staff user.
     */
    public function update(Request $request, User $user): RedirectResponse
    {
        $adminRoles = array_keys(User::getAdminRoles());

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:150', 'unique:users,email,' . $user->id],
            'phone' => ['nullable', 'string', 'max:20', 'unique:users,phone,' . $user->id],
            'role' => ['required', 'in:' . implode(',', $adminRoles)],
            'district_id' => ['nullable', 'exists:districts,id'],
            'password' => ['nullable', 'string', Password::min(8)],
            'is_active' => ['nullable', 'boolean'],
        ]);

        // Safeguard: Cannot demote yourself if currently logged in
        if ($user->id === auth()->id() && $validated['role'] !== User::ROLE_SUPER_ADMIN) {
            return back()->withErrors(['role' => 'You cannot demote your own account from Super Administrator.'])->withInput();
        }

        // Safeguard: Cannot deactivate yourself
        $isActive = $request->boolean('is_active', true);
        if ($user->id === auth()->id() && !$isActive) {
            return back()->withErrors(['is_active' => 'You cannot deactivate your own logged-in account.'])->withInput();
        }

        $oldValues = [
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'role' => $user->role,
            'is_active' => $user->is_active,
            'district_id' => $user->district_id,
        ];

        $user->name = $validated['name'];
        $user->email = $validated['email'];
        $user->phone = $validated['phone'];
        $user->role = $validated['role'];
        $user->district_id = $validated['district_id'];
        $user->is_active = $isActive;

        if (!empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        $user->save();

        AuditLog::log('admin.user.update', 'User', $user->id, $oldValues, [
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'is_active' => $user->is_active,
        ]);

        return redirect()->route('admin.users.index')->with('success', "Staff member \"{$user->name}\" updated successfully.");
    }

    /**
     * One-click toggle staff active status.
     */
    public function toggle(User $user): RedirectResponse
    {
        // Safeguard: Cannot suspend own account
        if ($user->id === auth()->id()) {
            return back()->with('error', 'You cannot suspend your own account while logged in.');
        }

        // Safeguard: Cannot suspend the last active Super Admin
        if ($user->isSuperAdmin() && $user->is_active && User::where('role', User::ROLE_SUPER_ADMIN)->where('is_active', true)->count() <= 1) {
            return back()->with('error', 'Cannot suspend this user. The platform must maintain at least one active Super Administrator.');
        }

        $newStatus = !$user->is_active;
        $user->update(['is_active' => $newStatus]);

        AuditLog::log('admin.user.toggle_status', 'User', $user->id, null, ['is_active' => $newStatus]);

        $statusLabel = $newStatus ? 'activated' : 'suspended';
        return back()->with('success', "Staff account for \"{$user->name}\" has been {$statusLabel}.");
    }

    /**
     * Remove the specified admin staff member.
     */
    public function destroy(User $user): RedirectResponse
    {
        // Safeguard: Cannot delete self
        if ($user->id === auth()->id()) {
            return back()->with('error', 'You cannot delete your own account while logged in.');
        }

        // Safeguard: Cannot delete the last Super Admin
        if ($user->isSuperAdmin() && User::where('role', User::ROLE_SUPER_ADMIN)->count() <= 1) {
            return back()->with('error', 'Cannot delete this user. The platform must have at least one Super Administrator.');
        }

        $name = $user->name;
        AuditLog::log('admin.user.delete', 'User', $user->id, ['name' => $name, 'email' => $user->email, 'role' => $user->role]);

        $user->delete();

        return redirect()->route('admin.users.index')->with('success', "Staff member \"{$name}\" has been removed.");
    }
}
