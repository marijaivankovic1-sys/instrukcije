<?php

namespace App\Controllers;

use App\Libraries\DozvoleService;
use CodeIgniter\RESTful\ResourceController;

class UlogeDozvole extends ResourceController
{
    protected $format = 'json';


    // =====================================================
    // PRIJAVLJENI KORISNIK
    // =====================================================

    private function prijavljeniKorisnikId(): ?int
    {
        $session = session();

        if (!$session->get('prijavljen')) {
            return null;
        }

        return (int) $session->get('korisnik_id');
    }


    // =====================================================
    // PROVJERA DOZVOLE
    // =====================================================

    private function imaDozvolu(
        int $korisnikId,
        string $dozvola
    ): bool {
        $service = new DozvoleService();

        return $service->korisnikImaDozvolu(
            $korisnikId,
            $dozvola
        );
    }


    // =====================================================
    // PROVJERA PRISTUPA
    // =====================================================

    private function provjeriPristup()
    {
        $korisnikId = $this->prijavljeniKorisnikId();

        if (!$korisnikId) {
            return $this->failUnauthorized(
                'Korisnik nije prijavljen.'
            );
        }

        if (
            !$this->imaDozvolu(
                $korisnikId,
                'upravljanje_uloga_dozvola'
            )
        ) {
            return $this->failForbidden(
                'Nemate dozvolu za upravljanje ulogama i dozvolama.'
            );
        }

        return null;
    }


    // =====================================================
    // POPIS ULOGA
    // =====================================================

    public function uloge()
    {
        if ($odgovor = $this->provjeriPristup()) {
            return $odgovor;
        }

        $db = db_connect();

        $uloge = $db
            ->table('uloge')
            ->orderBy('id', 'ASC')
            ->get()
            ->getResultArray();

        return $this->respond([
            'uspjeh' => true,
            'uloge' => $uloge
        ]);
    }


    // =====================================================
    // DODAJ ULOGU
    // =====================================================

    public function createUloga()
    {
        if ($odgovor = $this->provjeriPristup()) {
            return $odgovor;
        }

        $data = $this->request->getJSON(true);

        $naziv = trim($data['naziv'] ?? '');

        if ($naziv === '') {
            return $this->failValidationErrors(
                'Naziv uloge je obavezan.'
            );
        }

        $db = db_connect();

        $postoji = $db
            ->table('uloge')
            ->where('naziv', $naziv)
            ->get()
            ->getRowArray();

        if ($postoji) {
            return $this->failValidationErrors(
                'Uloga s tim nazivom već postoji.'
            );
        }

        $db
            ->table('uloge')
            ->insert([
                'naziv' => $naziv
            ]);

        return $this->respondCreated([
            'uspjeh' => true,
            'poruka' => 'Uloga je uspješno dodana.',
            'id' => $db->insertID()
        ]);
    }


    // =====================================================
    // UREDI ULOGU
    // =====================================================

    public function updateUloga($id = null)
    {
        if ($odgovor = $this->provjeriPristup()) {
            return $odgovor;
        }

        $db = db_connect();

        $uloga = $db
            ->table('uloge')
            ->where('id', $id)
            ->get()
            ->getRowArray();

        if (!$uloga) {
            return $this->failNotFound(
                'Uloga nije pronađena.'
            );
        }

        $data = $this->request->getJSON(true);

        $naziv = trim($data['naziv'] ?? '');

        if ($naziv === '') {
            return $this->failValidationErrors(
                'Naziv uloge je obavezan.'
            );
        }

        $postoji = $db
            ->table('uloge')
            ->where('naziv', $naziv)
            ->where('id !=', $id)
            ->get()
            ->getRowArray();

        if ($postoji) {
            return $this->failValidationErrors(
                'Uloga s tim nazivom već postoji.'
            );
        }

        $db
            ->table('uloge')
            ->where('id', $id)
            ->update([
                'naziv' => $naziv
            ]);

        return $this->respond([
            'uspjeh' => true,
            'poruka' => 'Uloga je uspješno uređena.'
        ]);
    }


    // =====================================================
    // OBRIŠI ULOGU
    // =====================================================

    public function deleteUloga($id = null)
    {
        if ($odgovor = $this->provjeriPristup()) {
            return $odgovor;
        }

        $db = db_connect();

        $uloga = $db
            ->table('uloge')
            ->where('id', $id)
            ->get()
            ->getRowArray();

        if (!$uloga) {
            return $this->failNotFound(
                'Uloga nije pronađena.'
            );
        }

        // Ne dopuštamo brisanje uloge ako je dodijeljena korisniku.
        $koristiSe = $db
            ->table('korisnik_uloga')
            ->where('uloga_id', $id)
            ->countAllResults();

        if ($koristiSe > 0) {
            return $this->failValidationErrors(
                'Uloga se ne može obrisati jer je dodijeljena jednom ili više korisnika.'
            );
        }

        $db->transStart();

        $db
            ->table('uloga_dozvola')
            ->where('uloga_id', $id)
            ->delete();

        $db
            ->table('uloge')
            ->where('id', $id)
            ->delete();

        $db->transComplete();

        if ($db->transStatus() === false) {
            return $this->failServerError(
                'Došlo je do greške pri brisanju uloge.'
            );
        }

        return $this->respondDeleted([
            'uspjeh' => true,
            'poruka' => 'Uloga je uspješno obrisana.'
        ]);
    }


    // =====================================================
    // POPIS SVIH DOZVOLA
    // =====================================================

    public function dozvole()
    {
        if ($odgovor = $this->provjeriPristup()) {
            return $odgovor;
        }

        $db = db_connect();

        $dozvole = $db
            ->table('dozvole')
            ->orderBy('id', 'ASC')
            ->get()
            ->getResultArray();

        return $this->respond([
            'uspjeh' => true,
            'dozvole' => $dozvole
        ]);
    }


    // =====================================================
    // DODAJ DOZVOLU
    // =====================================================

    public function createDozvola()
    {
        if ($odgovor = $this->provjeriPristup()) {
            return $odgovor;
        }

        $data = $this->request->getJSON(true);

        $naziv = trim($data['naziv'] ?? '');

        if ($naziv === '') {
            return $this->failValidationErrors(
                'Naziv dozvole je obavezan.'
            );
        }

        $db = db_connect();

        $postoji = $db
            ->table('dozvole')
            ->where('naziv', $naziv)
            ->get()
            ->getRowArray();

        if ($postoji) {
            return $this->failValidationErrors(
                'Dozvola s tim nazivom već postoji.'
            );
        }

        $db
            ->table('dozvole')
            ->insert([
                'naziv' => $naziv
            ]);

        return $this->respondCreated([
            'uspjeh' => true,
            'poruka' => 'Dozvola je uspješno dodana.',
            'id' => $db->insertID()
        ]);
    }


    // =====================================================
    // UREDI DOZVOLU
    // =====================================================

    public function updateDozvola($id = null)
    {
        if ($odgovor = $this->provjeriPristup()) {
            return $odgovor;
        }

        $db = db_connect();

        $dozvola = $db
            ->table('dozvole')
            ->where('id', $id)
            ->get()
            ->getRowArray();

        if (!$dozvola) {
            return $this->failNotFound(
                'Dozvola nije pronađena.'
            );
        }

        $data = $this->request->getJSON(true);

        $naziv = trim($data['naziv'] ?? '');

        if ($naziv === '') {
            return $this->failValidationErrors(
                'Naziv dozvole je obavezan.'
            );
        }

        $postoji = $db
            ->table('dozvole')
            ->where('naziv', $naziv)
            ->where('id !=', $id)
            ->get()
            ->getRowArray();

        if ($postoji) {
            return $this->failValidationErrors(
                'Dozvola s tim nazivom već postoji.'
            );
        }

        $db
            ->table('dozvole')
            ->where('id', $id)
            ->update([
                'naziv' => $naziv
            ]);

        return $this->respond([
            'uspjeh' => true,
            'poruka' => 'Dozvola je uspješno uređena.'
        ]);
    }


    // =====================================================
    // OBRIŠI DOZVOLU
    // =====================================================

    public function deleteDozvola($id = null)
    {
        if ($odgovor = $this->provjeriPristup()) {
            return $odgovor;
        }

        $db = db_connect();

        $dozvola = $db
            ->table('dozvole')
            ->where('id', $id)
            ->get()
            ->getRowArray();

        if (!$dozvola) {
            return $this->failNotFound(
                'Dozvola nije pronađena.'
            );
        }

        $db->transStart();

        $db
            ->table('uloga_dozvola')
            ->where('dozvola_id', $id)
            ->delete();

        $db
            ->table('dozvole')
            ->where('id', $id)
            ->delete();

        $db->transComplete();

        if ($db->transStatus() === false) {
            return $this->failServerError(
                'Došlo je do greške pri brisanju dozvole.'
            );
        }

        return $this->respondDeleted([
            'uspjeh' => true,
            'poruka' => 'Dozvola je uspješno obrisana.'
        ]);
    }


    // =====================================================
    // DOZVOLE ODABRANE ULOGE
    // =====================================================

    public function dozvoleUloge($ulogaId = null)
    {
        if ($odgovor = $this->provjeriPristup()) {
            return $odgovor;
        }

        $db = db_connect();

        $uloga = $db
            ->table('uloge')
            ->where('id', $ulogaId)
            ->get()
            ->getRowArray();

        if (!$uloga) {
            return $this->failNotFound(
                'Uloga nije pronađena.'
            );
        }

        $dozvole = $db
            ->table('uloga_dozvola')
            ->select('dozvola_id')
            ->where('uloga_id', $ulogaId)
            ->get()
            ->getResultArray();

        $dozvolaIds = array_map(
            static fn ($red) => (int) $red['dozvola_id'],
            $dozvole
        );

        return $this->respond([
            'uspjeh' => true,
            'uloga' => $uloga,
            'dozvole' => $dozvolaIds
        ]);
    }


    // =====================================================
    // SPREMI DOZVOLE ODABRANE ULOGE
    // =====================================================

    public function spremiDozvoleUloge($ulogaId = null)
    {
        if ($odgovor = $this->provjeriPristup()) {
            return $odgovor;
        }

        $db = db_connect();

        $uloga = $db
            ->table('uloge')
            ->where('id', $ulogaId)
            ->get()
            ->getRowArray();

        if (!$uloga) {
            return $this->failNotFound(
                'Uloga nije pronađena.'
            );
        }

        $data = $this->request->getJSON(true);

        $dozvole = $data['dozvole'] ?? [];

        if (!is_array($dozvole)) {
            return $this->failValidationErrors(
                'Dozvole moraju biti poslane kao polje.'
            );
        }

        $dozvole = array_values(
            array_unique(
                array_map('intval', $dozvole)
            )
        );

        // Provjera postoje li sve poslane dozvole.
        if (!empty($dozvole)) {
            $brojPostojecih = $db
                ->table('dozvole')
                ->whereIn('id', $dozvole)
                ->countAllResults();

            if ($brojPostojecih !== count($dozvole)) {
                return $this->failValidationErrors(
                    'Jedna ili više odabranih dozvola ne postoje.'
                );
            }
        }

        $db->transStart();

        // Brišemo stare veze.
        $db
            ->table('uloga_dozvola')
            ->where('uloga_id', $ulogaId)
            ->delete();

        // Upisujemo novi skup dozvola.
        if (!empty($dozvole)) {

            $redovi = [];

            foreach ($dozvole as $dozvolaId) {
                $redovi[] = [
                    'uloga_id' => (int) $ulogaId,
                    'dozvola_id' => $dozvolaId
                ];
            }

            $db
                ->table('uloga_dozvola')
                ->insertBatch($redovi);
        }

        $db->transComplete();

        if ($db->transStatus() === false) {
            return $this->failServerError(
                'Došlo je do greške pri spremanju dozvola.'
            );
        }

        return $this->respond([
            'uspjeh' => true,
            'poruka' => 'Dozvole uloge su uspješno spremljene.'
        ]);
    }
}