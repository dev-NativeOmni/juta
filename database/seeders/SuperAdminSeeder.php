<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        $superAdminRole = Role::where('name', 'super_admin')->firstOrFail();

        $username = env('SUPERADMIN_USERNAME', 'superadmin');
        $password = env('SUPERADMIN_PASSWORD');
        $generated = blank($password);

        if ($generated) {
            $password = Str::password(16, symbols: false);
        }

        User::updateOrCreate(
            ['username' => $username],
            [
                'role_id' => $superAdminRole->id,
                'name' => 'Super Admin',
                'password' => Hash::make($password),
                'status' => 'active',
            ]
        );

        if ($generated) {
            $this->command?->warn("Super admin '{$username}' dibuat dengan password: {$password}");
            $this->command?->warn('Simpan password ini dan segera ganti setelah login.');
        } else {
            $this->command?->info("Super admin '{$username}' dibuat/diperbarui.");
        }
    }
}
