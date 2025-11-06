<?php
// database/seeders/UserSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run()
    {
        User::create([
            'name' => 'Rangga Raditya Hariyanto',
            'email' => 'rangga@puterako.com',
            'password' => Hash::make('rangga7671234'),
            'role' => 'administrator',
            'departemen' => 'IT',
            'kantor' => 'Surabaya',
            'nohp' => '081331016271'
        ]);

        User::create([
            'name' => 'Junly Kodradjaya',
            'email' => 'junlyk@puterako.com',
            'password' => Hash::make('admin123'),
            'role' => 'direktur',
            'departemen' => 'Sales',
            'kantor' => 'Surabaya',
            'nohp' => '081234567891'
        ]);

        User::create([
            'name' => 'Santoso Direjo',
            'email' => 'manager@puterako.com',
            'password' => Hash::make('admin123'),
            'role' => 'manager',
            'departemen' => 'Sales',
            'kantor' => 'Surabaya',
            'nohp' => '081234567892'
        ]);

        User::create([
            'name' => 'Jeffry',
            'email' => 'supervisor@puterako.com',
            'password' => Hash::make('admin123'),
            'role' => 'supervisor',
            'departemen' => 'Sales',
            'kantor' => 'Pusat',
            'nohp' => '081234567893'
        ]);

        User::create([
            'name' => 'Staff Sales',
            'email' => 'staff@puterako.com',
            'password' => Hash::make('admin123'),
            'role' => 'staff',
            'departemen' => 'Sales',
            'kantor' => 'Pusat',
            'nohp' => '081234567894'
        ]);
    }
}