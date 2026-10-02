<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('institutions', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique()->index();
            $table->string('name');
            $table->string('slug')->unique()->index();
            $table->string('logo_path')->nullable();
            $table->string('login_bg')->nullable();
            $table->string('landing_bg')->nullable();
            $table->text('address')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->json('settings')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // Seed default institution from existing settings if available
        $namaInstansi = 'TAD Management System';
        $logo = null;
        $loginBg = null;
        $landingBg = null;

        if (Schema::hasTable('settings')) {
            $namaSetting = DB::table('settings')->where('key', 'nama_instansi')->value('value');
            if ($namaSetting) {
                $namaInstansi = $namaSetting;
            }
            $logo = DB::table('settings')->where('key', 'logo')->value('value');
            $loginBg = DB::table('settings')->where('key', 'login_bg')->value('value');
            $landingBg = DB::table('settings')->where('key', 'landing_bg')->value('value');
        }

        DB::table('institutions')->insert([
            'code' => 'DEFAULT',
            'name' => $namaInstansi,
            'slug' => Str::slug($namaInstansi) ?: 'default',
            'logo_path' => $logo,
            'login_bg' => $loginBg,
            'landing_bg' => $landingBg,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('institutions');
    }
};
