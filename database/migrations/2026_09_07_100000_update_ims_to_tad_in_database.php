<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Update users table names and optional emails
        if (Schema::hasTable('users')) {
            DB::table('users')->where('name', 'like', '%IMS%')->get()->each(function ($user) {
                $newName = str_replace(
                    ['IMS (Integrated Management System)', 'Integrated Management System', 'IMS'],
                    ['TAD (Tahfizh, Adab, Disiplin)', 'TAD (Tahfizh, Adab, Disiplin)', 'TAD'],
                    $user->name
                );
                DB::table('users')->where('id', $user->id)->update(['name' => $newName]);
            });

            if (Schema::hasColumn('users', 'email')) {
                DB::table('users')->where('email', 'like', '%@ims.test%')->get()->each(function ($user) {
                    $newEmail = str_replace('@ims.test', '@tad.test', $user->email);
                    DB::table('users')->where('id', $user->id)->update(['email' => $newEmail]);
                });
            }
        }

        // 2. Update students table names
        if (Schema::hasTable('students')) {
            DB::table('students')->where('name', 'like', '%IMS%')->get()->each(function ($student) {
                $newName = str_replace(
                    ['IMS (Integrated Management System)', 'Integrated Management System', 'IMS'],
                    ['TAD (Tahfizh, Adab, Disiplin)', 'TAD (Tahfizh, Adab, Disiplin)', 'TAD'],
                    $student->name
                );
                DB::table('students')->where('id', $student->id)->update(['name' => $newName]);
            });
        }

        // 3. Update parent_profiles table
        if (Schema::hasTable('parent_profiles')) {
            DB::table('parent_profiles')->where('address', 'like', '%IMS%')->get()->each(function ($parent) {
                $newAddress = str_replace('IMS', 'TAD', $parent->address);
                DB::table('parent_profiles')->where('id', $parent->id)->update(['address' => $newAddress]);
            });
        }

        // 4. Update settings table
        if (Schema::hasTable('settings')) {
            DB::table('settings')->where('value', 'like', '%IMS%')->get()->each(function ($setting) {
                $newValue = str_replace(
                    ['IMS (Integrated Management System)', 'Integrated Management System', 'IMS'],
                    ['TAD (Tahfizh, Adab, Disiplin)', 'TAD (Tahfizh, Adab, Disiplin)', 'TAD'],
                    $setting->value
                );
                DB::table('settings')->where('id', $setting->id)->update(['value' => $newValue]);
            });
        }
    }

    public function down(): void
    {
        // No reversal needed
    }
};
