<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. DATA SEKRETARIS JURUSAN
        User::updateOrCreate(
            ['username' => 'sekjur'],
            [
                'nama' => 'Sekretaris Jurusan',
                'email' => 'sekjur@gmail.com',
                'password' => Hash::make('sekjur123'),
                'role' => 'sekretaris',
                'prodi_id' => null,
            ]
        );

        // 2. DATA KETUA JURUSAN (KAJUR)
        User::updateOrCreate(
            ['username' => 'kajur'],
            [
                'nama' => 'Ketua Jurusan Teknik',
                'email' => 'kajur@gmail.com',
                'password' => Hash::make('kajur123'),
                'role' => 'kajur',
                'prodi_id' => null,
            ]
        );

        // 3. DATA KETUA PROGRAM STUDI (KAPRODI)
        $kaprodiData = [
            [
                'nama' => 'Kaprodi Teknik Informatika',
                'username' => 'kaprodi_ti',
                'email' => 'kaprodi.ti@gmail.com',
                'password' => 'ti123',
                'kode_prodi' => 'TI' // Dipakai untuk tracking ID prodi di database
            ],
            [
                'nama' => 'Kaprodi Rekayasa Keamanan Siber',
                'username' => 'kaprodi_rks',
                'email' => 'kaprodi.rks@gmail.com',
                'password' => 'rks123',
                'kode_prodi' => 'RKS'
            ],
            [
                'nama' => 'Kaprodi Teknologi Rekayasa Multimedia',
                'username' => 'kaprodi_trm',
                'email' => 'kaprodi.trm@gmail.com',
                'password' => 'trm123',
                'kode_prodi' => 'TRM'
            ],
            [
                'nama' => 'Kaprodi Teknologi Rekayasa Perangkat Lunak',
                'username' => 'kaprodi_trpl',
                'email' => 'kaprodi.trpl@gmail.com',
                'password' => 'trpl123',
                'kode_prodi' => 'TRPL'
            ],
        ];

        foreach ($kaprodiData as $data) {
            $prodiId = DB::table('program_studis')
                ->where('kode', $data['kode_prodi'])
                ->orWhere('nama', $data['kode_prodi'])
                ->value('id');

            User::updateOrCreate(
                ['username' => $data['username']],
                [
                    'nama' => $data['nama'],
                    'email' => $data['email'],
                    'password' => Hash::make($data['password']),
                    'role' => 'kaprodi',
                    'prodi_id' => $prodiId, 
                ]
            );
        }
    }
}
