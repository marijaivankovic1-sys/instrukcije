<?php

namespace App\Controllers;

use App\Models\RecenzijaModel;
use App\Libraries\DozvoleService;
use CodeIgniter\RESTful\ResourceController;

class Recenzije extends ResourceController
{
    protected $format = 'json';


    private function prijavljeniKorisnikId(): ?int
    {
        $session = session();

        if (!$session->get('prijavljen')) {
            return null;
        }

        return (int) $session->get('korisnik_id');
    }


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
    // POPIS RECENZIJA
    // =====================================================

    public function index()
    {
        $korisnikId =
            $this->prijavljeniKorisnikId();

        if (
            $korisnikId &&
            !$this->imaDozvolu(
                $korisnikId,
                'pregled_recenzija'
            )
        ) {
            return $this->failForbidden(
                'Nemate dozvolu za pregled recenzija.'
            );
        }


        $model =
            new RecenzijaModel();


        $recenzije = $model
            ->select('
                recenzije.id,
                recenzije.tutor_id,
                recenzije.student_id,
                recenzije.ocjena,
                recenzije.komentar,
                recenzije.datum_recenzije,

                studenti.ime AS student_ime,
                studenti.prezime AS student_prezime,

                tutori.ime AS tutor_ime,
                tutori.prezime AS tutor_prezime
            ')
            ->join(
                'korisnici AS studenti',
                'studenti.id = recenzije.student_id'
            )
            ->join(
                'korisnici AS tutori',
                'tutori.id = recenzije.tutor_id'
            )
            ->orderBy(
                'recenzije.datum_recenzije',
                'DESC'
            )
            ->findAll();


        return $this->respond([
            'uspjeh' => true,
            'recenzije' => $recenzije
        ]);
    }


    // =====================================================
    // POPIS INSTRUKTORA
    // =====================================================

    public function instruktori()
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
            'pisanje_recenzije'
        )
    ) {
        return $this->failForbidden(
            'Nemate dozvolu za pisanje recenzije.'
        );
    }

    $db = db_connect();

    $instruktori = $db
        ->table('rezervacije')
        ->distinct()
        ->select('
            tutori.id,
            tutori.ime,
            tutori.prezime
        ')
        ->join(
            'termini',
            'termini.id = rezervacije.termin_id'
        )
        ->join(
            'korisnici AS tutori',
            'tutori.id = termini.tutor_id'
        )
        ->join(
            'korisnik_uloga',
            'korisnik_uloga.korisnik_id = tutori.id'
        )
        ->join(
            'uloge',
            'uloge.id = korisnik_uloga.uloga_id'
        )
        ->where(
            'rezervacije.student_id',
            $korisnikId
        )
        ->where(
            'rezervacije.status',
            'prihvaćeno'
        )
        ->where(
            'uloge.naziv',
            'Tutor'
        )
        ->where(
            'tutori.status',
            'aktivan'
        )
        ->orderBy(
            'tutori.prezime',
            'ASC'
        )
        ->orderBy(
            'tutori.ime',
            'ASC'
        )
        ->get()
        ->getResultArray();

    return $this->respond([
        'uspjeh' => true,
        'instruktori' => $instruktori
    ]);
}


    // =====================================================
    // JEDNA RECENZIJA
    // =====================================================

    public function show($id = null)
    {
        $korisnikId =
            $this->prijavljeniKorisnikId();

        if (
            $korisnikId &&
            !$this->imaDozvolu(
                $korisnikId,
                'pregled_recenzija'
            )
        ) {
            return $this->failForbidden(
                'Nemate dozvolu za pregled recenzija.'
            );
        }


        $model =
            new RecenzijaModel();


        $recenzija = $model
            ->select('
                recenzije.id,
                recenzije.tutor_id,
                recenzije.student_id,
                recenzije.ocjena,
                recenzije.komentar,
                recenzije.datum_recenzije,

                studenti.ime AS student_ime,
                studenti.prezime AS student_prezime,

                tutori.ime AS tutor_ime,
                tutori.prezime AS tutor_prezime
            ')
            ->join(
                'korisnici AS studenti',
                'studenti.id = recenzije.student_id'
            )
            ->join(
                'korisnici AS tutori',
                'tutori.id = recenzije.tutor_id'
            )
            ->find($id);


        if (!$recenzija) {
            return $this->failNotFound(
                'Recenzija nije pronađena.'
            );
        }


        return $this->respond([
            'uspjeh' => true,
            'recenzija' => $recenzija
        ]);
    }


    // =====================================================
    // DODAJ RECENZIJU
    // =====================================================

    public function create()
    {
        $korisnikId =
            $this->prijavljeniKorisnikId();


        if (!$korisnikId) {
            return $this->failUnauthorized(
                'Korisnik nije prijavljen.'
            );
        }


        if (
            !$this->imaDozvolu(
                $korisnikId,
                'pisanje_recenzije'
            )
        ) {
            return $this->failForbidden(
                'Nemate dozvolu za pisanje recenzije.'
            );
        }


        $data =
            $this->request->getJSON(true);


        if (
            empty($data['tutor_id']) ||
            !isset($data['ocjena']) ||
            empty($data['komentar'])
        ) {
            return $this->failValidationErrors(
                'Tutor, ocjena i komentar su obavezni.'
            );
        }


        $ocjena =
            (int) $data['ocjena'];


        if (
            $ocjena < 1 ||
            $ocjena > 5
        ) {
            return $this->failValidationErrors(
                'Ocjena mora biti između 1 i 5.'
            );
        }


        $db =
            db_connect();


        /*
         * Provjeravamo da odabrani korisnik
         * zaista ima ulogu Tutor.
         */

        $tutor = $db
            ->table('korisnici')
            ->select('korisnici.id')
            ->join(
                'korisnik_uloga',
                'korisnik_uloga.korisnik_id = korisnici.id'
            )
            ->join(
                'uloge',
                'uloge.id = korisnik_uloga.uloga_id'
            )
            ->where(
                'korisnici.id',
                (int) $data['tutor_id']
            )
            ->where(
                'uloge.naziv',
                'Tutor'
            )
            ->get()
            ->getRowArray();


        if (!$tutor) {
            return $this->failNotFound(
                'Tutor nije pronađen.'
            );
        }


        if (
            (int) $data['tutor_id'] ===
            $korisnikId
        ) {
            return $this->failValidationErrors(
                'Ne možete napisati recenziju sami sebi.'
            );
        }


        $model =
            new RecenzijaModel();


        $id = $model->insert([
            'tutor_id' =>
                (int) $data['tutor_id'],

            'student_id' =>
                $korisnikId,

            'ocjena' =>
                $ocjena,

            'komentar' =>
                trim($data['komentar'])
        ], true);


        return $this->respondCreated([
            'uspjeh' => true,
            'poruka' =>
                'Recenzija je uspješno dodana.',
            'id' => $id
        ]);
    }


    // =====================================================
    // UREDI RECENZIJU
    // =====================================================

    public function update($id = null)
    {
        $korisnikId =
            $this->prijavljeniKorisnikId();


        if (!$korisnikId) {
            return $this->failUnauthorized(
                'Korisnik nije prijavljen.'
            );
        }


        if (
            !$this->imaDozvolu(
                $korisnikId,
                'pisanje_recenzije'
            )
        ) {
            return $this->failForbidden(
                'Nemate dozvolu za uređivanje recenzije.'
            );
        }


        $model =
            new RecenzijaModel();


        $recenzija =
            $model->find($id);


        if (!$recenzija) {
            return $this->failNotFound(
                'Recenzija nije pronađena.'
            );
        }


        if (
            (int) $recenzija['student_id'] !==
            $korisnikId
        ) {
            return $this->failForbidden(
                'Možete uređivati samo vlastitu recenziju.'
            );
        }


        $data =
            $this->request->getJSON(true);


        $podaci = [];


        if (isset($data['ocjena'])) {

            $ocjena =
                (int) $data['ocjena'];


            if (
                $ocjena < 1 ||
                $ocjena > 5
            ) {
                return $this->failValidationErrors(
                    'Ocjena mora biti između 1 i 5.'
                );
            }


            $podaci['ocjena'] =
                $ocjena;
        }


        if (isset($data['komentar'])) {

            $komentar =
                trim($data['komentar']);


            if ($komentar === '') {
                return $this->failValidationErrors(
                    'Komentar ne može biti prazan.'
                );
            }


            $podaci['komentar'] =
                $komentar;
        }


        if (empty($podaci)) {
            return $this->failValidationErrors(
                'Nisu poslani podaci za izmjenu.'
            );
        }


        $model->update(
            $id,
            $podaci
        );


        return $this->respond([
            'uspjeh' => true,
            'poruka' =>
                'Recenzija je uspješno uređena.'
        ]);
    }


    // =====================================================
    // OBRIŠI RECENZIJU
    // =====================================================

    public function delete($id = null)
    {
        $korisnikId =
            $this->prijavljeniKorisnikId();


        if (!$korisnikId) {
            return $this->failUnauthorized(
                'Korisnik nije prijavljen.'
            );
        }


        $model =
            new RecenzijaModel();


        $recenzija =
            $model->find($id);


        if (!$recenzija) {
            return $this->failNotFound(
                'Recenzija nije pronađena.'
            );
        }


        $jeAdmin =
            $this->imaDozvolu(
                $korisnikId,
                'upravljanje_korisnicima'
            );


        $jeVlasnik =
            (int) $recenzija['student_id'] ===
            $korisnikId;


        if (
            !$jeAdmin &&
            !$jeVlasnik
        ) {
            return $this->failForbidden(
                'Ne možete obrisati tuđu recenziju.'
            );
        }


        $model->delete($id);


        return $this->respondDeleted([
            'uspjeh' => true,
            'poruka' =>
                'Recenzija je uspješno obrisana.'
        ]);
    }
}