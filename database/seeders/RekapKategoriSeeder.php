<?php
// filepath: database/seeders/RekapKategoriSeeder.php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\RekapKategori;

class RekapKategoriSeeder extends Seeder
{
    public function run(): void
    {
        $kategoris = [
            'Main Unit',
            'Kabel',
            'Rack & Accessories',
            'Accessories FO',
            'Accessories UTP',
            'Pipa & Kelengkapan',
            'Consumable Material',
            'Lain-lain'
        ];

        foreach ($kategoris as $kategori) {
            RekapKategori::create(['nama' => $kategori]);
        }
    }
}