<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class RolesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $doctorRole = Role::firstOrCreate(['name' => 'doctor']);
        $patientRole = Role::firstOrCreate(['name' => 'patient']);
        $labRole    = Role::firstOrCreate(['name' => 'laborantin']);

        $admin = User::firstOrCreate(
            ['email' => 'admin@medinfo.bf'],
            ['name' => 'Super Admin', 'password' => Hash::make('Admin123!')]
        );
        $admin->assignRole($adminRole);
    }
}
