<?php

namespace App\Libraries;

class DozvoleService
{
    public function korisnikImaDozvolu(int $korisnikId, string $nazivDozvole): bool
    {
        $db = db_connect();

        $rezultat = $db->table('korisnik_uloga ku')
            ->select('dozvole.id')
            ->join('uloge u', 'u.id = ku.uloga_id')
            ->join('uloga_dozvola ud', 'ud.uloga_id = u.id')
            ->join('dozvole', 'dozvole.id = ud.dozvola_id')
            ->where('ku.korisnik_id', $korisnikId)
            ->where('dozvole.naziv', $nazivDozvole)
            ->get()
            ->getRowArray();

        return $rezultat !== null;
    }

    public function dohvatiDozvoleKorisnika(int $korisnikId): array
    {
        $db = db_connect();

        return $db->table('korisnik_uloga ku')
            ->select('dozvole.id, dozvole.naziv, dozvole.opis')
            ->join('uloge u', 'u.id = ku.uloga_id')
            ->join('uloga_dozvola ud', 'ud.uloga_id = u.id')
            ->join('dozvole', 'dozvole.id = ud.dozvola_id')
            ->where('ku.korisnik_id', $korisnikId)
            ->orderBy('dozvole.id', 'ASC')
            ->get()
            ->getResultArray();
    }
}