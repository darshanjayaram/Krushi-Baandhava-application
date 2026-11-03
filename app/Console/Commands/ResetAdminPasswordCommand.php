<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;

class ResetAdminPasswordCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'admin:reset-password {email? : The admin email address} {password? : The new password (min 8 characters)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reset the password for an admin or super_admin account';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->argument('email');

        if (!$email) {
            $admins = User::whereIn('role', ['admin', 'super_admin'])->get();

            if ($admins->isEmpty()) {
                $this->error('No admin accounts found in the database.');
                return 1;
            }

            $choices = $admins->map(fn ($u) => "{$u->email} ({$u->name} - {$u->role})")->toArray();
            $selected = $this->choice('Select an admin user to reset password:', $choices, 0);

            // Extract email
            preg_match('/^([^\s]+)/', $selected, $matches);
            $email = $matches[1] ?? $admins->first()->email;
        }

        $user = User::where('email', $email)->first();

        if (!$user) {
            $this->error("User with email '{$email}' not found.");
            return 1;
        }

        if (!in_array($user->role, ['admin', 'super_admin'])) {
            $this->warn("Note: User '{$email}' currently has role '{$user->role}'. Promoting to super_admin.");
            $user->role = 'super_admin';
        }

        $password = $this->argument('password');

        if (!$password) {
            $password = $this->secret('Enter new password (min 8 characters):');
            if (!$password) {
                $password = 'Admin@1234';
                $this->info("No password entered. Defaulting to: {$password}");
            }
        }

        if (strlen($password) < 8) {
            $this->error('Password must be at least 8 characters long.');
            return 1;
        }

        $user->password = Hash::make($password);
        $user->is_active = true;
        $user->save();

        // Clear rate limiter locks if user was locked out
        RateLimiter::clear('admin-login');
        RateLimiter::clear(strtolower($user->email) . '|127.0.0.1');

        $this->newLine();
        $this->info("✅ Password successfully updated for: {$user->email}");
        $this->table(
            ['Field', 'Value'],
            [
                ['Name', $user->name],
                ['Email', $user->email],
                ['Role', $user->role],
                ['New Password', $password],
                ['Status', 'Active'],
            ]
        );

        return 0;
    }
}
