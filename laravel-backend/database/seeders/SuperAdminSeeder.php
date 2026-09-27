<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        // Provjeri postoji li već Super Administrator
        $postojeci = DB::table('korisnici')
            ->where('email', 'superadmin@test.com')
            ->first();

        if ($postojeci) {
            $korisnikId = $postojeci->id;
        } else {
            // Kreiranje korisnika
            $korisnikId = DB::table('korisnici')->insertGetId([
                'ime' => 'Super',
                'prezime' => 'Administrator',
                'email' => 'superadmin@test.com',
                'lozinka' => Hash::make('33333'),
            ]);
        }

        // Dodjela uloge Super Administrator (uloga_id = 1)
        $vezaPostoji = DB::table('korisnik_uloga')
            ->where('korisnik_id', $korisnikId)
            ->where('uloga_id', 1)
            ->exists();

        if (!$vezaPostoji) {
            DB::table('korisnik_uloga')->insert([
                'korisnik_id' => $korisnikId,
                'uloga_id' => 1,
            ]);
        }
    }
}