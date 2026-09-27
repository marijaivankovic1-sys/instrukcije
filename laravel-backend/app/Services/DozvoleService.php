<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class DozvoleService
{
    public function korisnikImaDozvolu(
        int $korisnikId,
        string $nazivDozvole
    ): bool {
        return DB::table('korisnik_uloga as ku')
            ->join('uloge as u', 'u.id', '=', 'ku.uloga_id')
            ->join('uloga_dozvola as ud', 'ud.uloga_id', '=', 'u.id')
            ->join('dozvole as d', 'd.id', '=', 'ud.dozvola_id')
            ->where('ku.korisnik_id', $korisnikId)
            ->where('d.naziv', $nazivDozvole)
            ->exists();
    }

    public function dohvatiDozvoleKorisnika(int $korisnikId)
    {
        return DB::table('korisnik_uloga as ku')
            ->select(
                'd.id',
                'd.naziv',
                'd.opis'
            )
            ->join('uloge as u', 'u.id', '=', 'ku.uloga_id')
            ->join('uloga_dozvola as ud', 'ud.uloga_id', '=', 'u.id')
            ->join('dozvole as d', 'd.id', '=', 'ud.dozvola_id')
            ->where('ku.korisnik_id', $korisnikId)
            ->orderBy('d.id', 'asc')
            ->get();
    }
}