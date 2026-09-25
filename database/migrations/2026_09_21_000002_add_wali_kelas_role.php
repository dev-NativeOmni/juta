<?php

use App\Models\Role;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Role::updateOrCreate(
            ['name' => 'wali_kelas'],
            ['display_name' => 'Wali Kelas']
        );
    }

    public function down(): void
    {
        Role::where('name', 'wali_kelas')->delete();
    }
};
