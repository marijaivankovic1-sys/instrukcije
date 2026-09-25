<?php

namespace App\Controllers;

use App\Models\TerminModel;
use App\Libraries\DozvoleService;
use CodeIgniter\RESTful\ResourceController;

class Termini extends ResourceController
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
    // GET /api/termini
    // =====================================================
    // Prikazuju se samo:
    // - slobodni termini
    // - budući termini
    // - današnji termini koji još nisu počeli
    // =====================================================

    public function index()
    {
        $model = new TerminModel();

        $danas = date('Y-m-d');
        $sada = date('H:i:s');

        $termini = $model
            ->select('
                termini.id,
                termini.tutor_id,
                termini.predmet_id,
                termini.datum,
                termini.vrijeme_od,
                termini.vrijeme_do,
                termini.cijena,
                termini.status,
                predmeti.naziv AS predmet_naziv,
                korisnici.ime AS instruktor_ime,
                korisnici.prezime AS instruktor_prezime
            ')
            ->join(
                'predmeti',
                'predmeti.id = termini.predmet_id'
            )
            ->join(
                'korisnici',
                'korisnici.id = termini.tutor_id'
            )
            ->where(
                'termini.status',
                'slobodan'
            )
            ->groupStart()
                ->where(
                    'termini.datum >',
                    $danas
                )
                ->orGroupStart()
                    ->where(
                        'termini.datum',
                        $danas
                    )
                    ->where(
                        'termini.vrijeme_od >',
                        $sada
                    )
                ->groupEnd()
            ->groupEnd()
            ->orderBy(
                'termini.datum',
                'ASC'
            )
            ->orderBy(
                'termini.vrijeme_od',
                'ASC'
            )
            ->findAll();

        return $this->respond([
            'uspjeh' => true,
            'termini' => $termini
        ]);
    }


    // =====================================================
    // GET /api/termini/{id}
    // =====================================================

    public function show($id = null)
    {
        $model = new TerminModel();

        $termin = $model
            ->select('
                termini.id,
                termini.tutor_id,
                termini.predmet_id,
                termini.datum,
                termini.vrijeme_od,
                termini.vrijeme_do,
                termini.cijena,
                termini.status,
                predmeti.naziv AS predmet_naziv,
                korisnici.ime AS instruktor_ime,
                korisnici.prezime AS instruktor_prezime
            ')
            ->join(
                'predmeti',
                'predmeti.id = termini.predmet_id'
            )
            ->join(
                'korisnici',
                'korisnici.id = termini.tutor_id'
            )
            ->find($id);

        if (!$termin) {
            return $this->failNotFound(
                'Termin nije pronađen.'
            );
        }

        return $this->respond([
            'uspjeh' => true,
            'termin' => $termin
        ]);
    }


    // =====================================================
    // POST /api/termini
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
                'upravljanje_terminima'
            )
        ) {
            return $this->failForbidden(
                'Nemate dozvolu za dodavanje termina.'
            );
        }

        $data =
            $this->request->getJSON(true);

        if (
            empty($data['predmet_id']) ||
            empty($data['datum']) ||
            empty($data['vrijeme_od']) ||
            empty($data['vrijeme_do']) ||
            !isset($data['cijena'])
        ) {
            return $this->failValidationErrors(
                'Predmet, datum, vrijeme i cijena su obavezni.'
            );
        }

        if (
            $data['vrijeme_do'] <=
            $data['vrijeme_od']
        ) {
            return $this->failValidationErrors(
                'Vrijeme završetka mora biti nakon vremena početka.'
            );
        }

        $noviTermin = [
            'tutor_id' =>
                $korisnikId,

            'predmet_id' =>
                $data['predmet_id'],

            'datum' =>
                $data['datum'],

            'vrijeme_od' =>
                $data['vrijeme_od'],

            'vrijeme_do' =>
                $data['vrijeme_do'],

            'cijena' =>
                $data['cijena'],

            'status' =>
                $data['status'] ?? 'slobodan'
        ];

        $model =
            new TerminModel();

        $id = $model->insert(
            $noviTermin,
            true
        );

        if (!$id) {
            return $this->failServerError(
                'Termin nije moguće spremiti.'
            );
        }

        return $this->respondCreated([
            'uspjeh' => true,
            'poruka' =>
                'Termin je uspješno dodan.',
            'id' => $id
        ]);
    }


    // =====================================================
    // PUT /api/termini/{id}
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
                'upravljanje_terminima'
            )
        ) {
            return $this->failForbidden(
                'Nemate dozvolu za uređivanje termina.'
            );
        }

        $model =
            new TerminModel();

        $termin =
            $model->find($id);

        if (!$termin) {
            return $this->failNotFound(
                'Termin nije pronađen.'
            );
        }

        // Tutor može uređivati samo svoje termine.
        // Administrator ima dozvolu pregled_svih_rezervacija
        // i može uređivati sve termine.
        if (
            !$this->imaDozvolu(
                $korisnikId,
                'pregled_svih_rezervacija'
            ) &&
            (int) $termin['tutor_id'] !==
            $korisnikId
        ) {
            return $this->failForbidden(
                'Možete uređivati samo vlastite termine.'
            );
        }

        $data =
            $this->request->getJSON(true);

        if (
            isset($data['vrijeme_od']) &&
            isset($data['vrijeme_do']) &&
            $data['vrijeme_do'] <=
            $data['vrijeme_od']
        ) {
            return $this->failValidationErrors(
                'Vrijeme završetka mora biti nakon vremena početka.'
            );
        }

        $podaci = [];

        if (isset($data['predmet_id'])) {
            $podaci['predmet_id'] =
                $data['predmet_id'];
        }

        if (isset($data['datum'])) {
            $podaci['datum'] =
                $data['datum'];
        }

        if (isset($data['vrijeme_od'])) {
            $podaci['vrijeme_od'] =
                $data['vrijeme_od'];
        }

        if (isset($data['vrijeme_do'])) {
            $podaci['vrijeme_do'] =
                $data['vrijeme_do'];
        }

        if (isset($data['cijena'])) {
            $podaci['cijena'] =
                $data['cijena'];
        }

        if (isset($data['status'])) {
            $podaci['status'] =
                $data['status'];
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
                'Termin je uspješno uređen.'
        ]);
    }


    // =====================================================
    // DELETE /api/termini/{id}
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

        if (
            !$this->imaDozvolu(
                $korisnikId,
                'upravljanje_terminima'
            )
        ) {
            return $this->failForbidden(
                'Nemate dozvolu za brisanje termina.'
            );
        }

        $model =
            new TerminModel();

        $termin =
            $model->find($id);

        if (!$termin) {
            return $this->failNotFound(
                'Termin nije pronađen.'
            );
        }

        if (
            !$this->imaDozvolu(
                $korisnikId,
                'pregled_svih_rezervacija'
            ) &&
            (int) $termin['tutor_id'] !==
            $korisnikId
        ) {
            return $this->failForbidden(
                'Možete brisati samo vlastite termine.'
            );
        }

        $model->delete($id);

        return $this->respondDeleted([
            'uspjeh' => true,
            'poruka' =>
                'Termin je uspješno obrisan.'
        ]);
    }
}