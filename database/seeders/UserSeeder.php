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
            'name' => 'Ria Oktaviana',
            'email' => 'ria@puterako.com',
            'password' => Hash::make('riaoktav123'),
            'role' => 'staff',
            'departemen' => 'Sales',
            'kantor' => 'Surabaya',
            'nohp' => '081234567894'
        ]);
        User::create([
            'name' => 'Diyah Cahyani Choirunnisa',
            'email' => 'diyah@puterako.com',
            'password' => Hash::make('diyahcah321'),
            'role' => 'staff',
            'departemen' => 'Sales',
            'kantor' => 'Surabaya',
            'nohp' => '081234567894'
        ]);
        User::create([
            'name' => 'Fitria Yuninda Miftachul Rizky',
            'email' => 'fitria@puterako.com',
            'password' => Hash::make('fitriayu456'),
            'role' => 'staff',
            'departemen' => 'Sales',
            'kantor' => 'Surabaya',
            'nohp' => '081234567894'
        ]);
        User::create([
            'name' => 'Jeffrey Stevanus Kurniawan',
            'email' => 'jeffrey@puterako.com',
            'password' => Hash::make('supervisor@puterako'),
            'role' => 'supervisor',
            'departemen' => 'Sales',
            'kantor' => 'Surabaya',
            'nohp' => '081234567893'
        ]);
        User::create([
            'name' => 'Junly Kodradjaya',
            'email' => 'junlyko@puterako.com',
            'password' => Hash::make('direktur@puterako'),
            'role' => 'direktur',
            'departemen' => 'Sales',
            'kantor' => 'Surabaya',
            'nohp' => '081234567891'
        ]);
        User::create([
            'name' => 'Santoso Direjo',
            'email' => 'santoso@puterako.com',
            'password' => Hash::make('manajer@puterako'),
            'role' => 'manager',
            'departemen' => 'Sales',
            'kantor' => 'Surabaya',
            'nohp' => '081234567892'
        ]);
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
            'name' => 'Andhika Rizky Aulia',
            'email' => 'andhika@puterako.com',
            'password' => Hash::make('andhika112'),
            'role' => 'administrator',
            'departemen' => 'IT',
            'kantor' => 'Surabaya',
            'nohp' => '081331016271'
        ]);
    }
}