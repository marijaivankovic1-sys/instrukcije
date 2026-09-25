<?php

namespace App\Controllers;

use App\Models\RezervacijaModel;
use App\Libraries\DozvoleService;
use CodeIgniter\RESTful\ResourceController;

class Rezervacije extends ResourceController
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
    ): bool
    {
        $service = new DozvoleService();

        return $service->korisnikImaDozvolu(
            $korisnikId,
            $dozvola
        );
    }


    // =====================================================
    // GET /api/rezervacije
    // =====================================================

    public function index()
    {
        $korisnikId = $this->prijavljeniKorisnikId();

        if (!$korisnikId) {
            return $this->failUnauthorized(
                'Korisnik nije prijavljen.'
            );
        }

        $model = new RezervacijaModel();

        $query = $model
            ->select('
                rezervacije.id,
                rezervacije.termin_id,
                rezervacije.student_id,
                rezervacije.napomena,
                rezervacije.privitak_putanja,
                rezervacije.status,
                rezervacije.datum_rezervacije,

                termini.datum,
                termini.vrijeme_od,
                termini.vrijeme_do,
                termini.cijena,
                termini.tutor_id,

                predmeti.naziv AS predmet_naziv,

                studenti.ime AS student_ime,
                studenti.prezime AS student_prezime,

                tutori.ime AS tutor_ime,
                tutori.prezime AS tutor_prezime
            ')
            ->join(
                'termini',
                'termini.id = rezervacije.termin_id'
            )
            ->join(
                'predmeti',
                'predmeti.id = termini.predmet_id'
            )
            ->join(
                'korisnici AS studenti',
                'studenti.id = rezervacije.student_id'
            )
            ->join(
                'korisnici AS tutori',
                'tutori.id = termini.tutor_id'
            );

        // Administrator vidi sve.
        if (
            $this->imaDozvolu(
                $korisnikId,
                'pregled_svih_rezervacija'
            )
        ) {
            return $this->respond([
                'uspjeh' => true,
                'rezervacije' => $query->findAll()
            ]);
        }

        // Tutor vidi rezervacije svojih termina.
        if (
            $this->imaDozvolu(
                $korisnikId,
                'obrada_rezervacija'
            )
        ) {
            $rezervacije = $query
                ->where(
                    'termini.tutor_id',
                    $korisnikId
                )
                ->findAll();

            return $this->respond([
                'uspjeh' => true,
                'rezervacije' => $rezervacije
            ]);
        }

        // Student vidi samo svoje rezervacije.
        if (
            $this->imaDozvolu(
                $korisnikId,
                'pregled_vlastitih_rezervacija'
            )
        ) {
            $rezervacije = $query
                ->where(
                    'rezervacije.student_id',
                    $korisnikId
                )
                ->findAll();

            return $this->respond([
                'uspjeh' => true,
                'rezervacije' => $rezervacije
            ]);
        }

        return $this->failForbidden(
            'Nemate dozvolu za pregled rezervacija.'
        );
    }


    // =====================================================
    // GET /api/rezervacije/{id}
    // =====================================================

    public function show($id = null)
    {
        $korisnikId = $this->prijavljeniKorisnikId();

        if (!$korisnikId) {
            return $this->failUnauthorized(
                'Korisnik nije prijavljen.'
            );
        }

        $model = new RezervacijaModel();

        $rezervacija = $model
            ->select('
                rezervacije.id,
                rezervacije.termin_id,
                rezervacije.student_id,
                rezervacije.napomena,
                rezervacije.privitak_putanja,
                rezervacije.status,
                rezervacije.datum_rezervacije,

                termini.datum,
                termini.vrijeme_od,
                termini.vrijeme_do,
                termini.cijena,
                termini.tutor_id,

                predmeti.naziv AS predmet_naziv,

                studenti.ime AS student_ime,
                studenti.prezime AS student_prezime,

                tutori.ime AS tutor_ime,
                tutori.prezime AS tutor_prezime
            ')
            ->join(
                'termini',
                'termini.id = rezervacije.termin_id'
            )
            ->join(
                'predmeti',
                'predmeti.id = termini.predmet_id'
            )
            ->join(
                'korisnici AS studenti',
                'studenti.id = rezervacije.student_id'
            )
            ->join(
                'korisnici AS tutori',
                'tutori.id = termini.tutor_id'
            )
            ->find($id);

        if (!$rezervacija) {
            return $this->failNotFound(
                'Rezervacija nije pronađena.'
            );
        }

        // Administrator vidi sve.
        if (
            $this->imaDozvolu(
                $korisnikId,
                'pregled_svih_rezervacija'
            )
        ) {
            return $this->respond([
                'uspjeh' => true,
                'rezervacija' => $rezervacija
            ]);
        }

        // Student vidi svoju rezervaciju.
        if (
            (int) $rezervacija['student_id'] === $korisnikId &&
            $this->imaDozvolu(
                $korisnikId,
                'pregled_vlastitih_rezervacija'
            )
        ) {
            return $this->respond([
                'uspjeh' => true,
                'rezervacija' => $rezervacija
            ]);
        }

        // Tutor vidi rezervaciju svojeg termina.
        if (
            (int) $rezervacija['tutor_id'] === $korisnikId &&
            $this->imaDozvolu(
                $korisnikId,
                'obrada_rezervacija'
            )
        ) {
            return $this->respond([
                'uspjeh' => true,
                'rezervacija' => $rezervacija
            ]);
        }

        return $this->failForbidden(
            'Nemate dozvolu za pregled ove rezervacije.'
        );
    }


    // =====================================================
    // POST /api/rezervacije
    // =====================================================

    public function create()
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
                'rezerviranje_termina'
            )
        ) {
            return $this->failForbidden(
                'Nemate dozvolu za rezerviranje termina.'
            );
        }

        $data = $this->request->getPost();

        if (empty($data)) {
            $data = $this->request->getJSON(true) ?? [];
        }

        if (empty($data['termin_id'])) {
            return $this->failValidationErrors(
                'Termin je obavezan.'
            );
        }

        $db = db_connect();

        $termin = $db
            ->table('termini')
            ->where(
                'id',
                $data['termin_id']
            )
            ->get()
            ->getRowArray();

        if (!$termin) {
            return $this->failNotFound(
                'Termin nije pronađen.'
            );
        }

        // Može se rezervirati samo slobodan termin.
        if ($termin['status'] !== 'slobodan') {
            return $this->failValidationErrors(
                'Ovaj termin više nije dostupan.'
            );
        }

        $model = new RezervacijaModel();

        // Isti student ne može dvaput rezervirati isti termin.
        $postojeca = $model
            ->where(
                'termin_id',
                $data['termin_id']
            )
            ->where(
                'student_id',
                $korisnikId
            )
            ->first();

        if ($postojeca) {
            return $this->failValidationErrors(
                'Ovaj termin ste već rezervirali.'
            );
        }


        // =====================================================
        // PDF PRIVITAK
        // =====================================================

        $privitakPutanja = null;

        $privitak =
            $this->request->getFile('privitak');

        if (
            $privitak &&
            $privitak->isValid() &&
            !$privitak->hasMoved()
        ) {
            if (
                $privitak->getSize() >
                10 * 1024 * 1024
            ) {
                return $this->failValidationErrors(
                    'PDF dokument smije imati najviše 10 MB.'
                );
            }

            $mime =
                $privitak->getMimeType();

            $ekstenzija =
                strtolower(
                    $privitak->getClientExtension()
                );

            if (
                $mime !== 'application/pdf' ||
                $ekstenzija !== 'pdf'
            ) {
                return $this->failValidationErrors(
                    'Dopušten je samo PDF dokument.'
                );
            }

            $odredisnaMapa =
                FCPATH .
                'uploads' .
                DIRECTORY_SEPARATOR .
                'rezervacije';

            if (!is_dir($odredisnaMapa)) {
                mkdir(
                    $odredisnaMapa,
                    0775,
                    true
                );
            }

            $novoIme =
                $privitak->getRandomName();

            $privitak->move(
                $odredisnaMapa,
                $novoIme
            );

            $privitakPutanja =
                'uploads/rezervacije/' .
                $novoIme;

        } elseif (
            $privitak &&
            $privitak->getError() !==
            UPLOAD_ERR_NO_FILE
        ) {
            return $this->failValidationErrors(
                'PDF dokument nije moguće učitati.'
            );
        }


        $novaRezervacija = [
            'termin_id' =>
                $data['termin_id'],

            // ID studenta uzimamo iz sesije.
            'student_id' =>
                $korisnikId,

            'napomena' =>
                $data['napomena'] ?? '',

            'privitak_putanja' =>
                $privitakPutanja,

            'status' =>
                'na čekanju'
        ];

        $id = $model->insert(
            $novaRezervacija,
            true
        );

        if (!$id) {
            return $this->failServerError(
                'Rezervaciju nije moguće spremiti.'
            );
        }

        return $this->respondCreated([
            'uspjeh' => true,
            'poruka' =>
                'Rezervacija je uspješno poslana.',
            'id' => $id
        ]);
    }


    // =====================================================
    // PUT /api/rezervacije/{id}
    // =====================================================
    // Tutor/Admin obrađuje rezervaciju.
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
                'obrada_rezervacija'
            )
        ) {
            return $this->failForbidden(
                'Nemate dozvolu za obradu rezervacija.'
            );
        }

        $model =
            new RezervacijaModel();

        $rezervacija = $model
            ->select('
                rezervacije.*,
                termini.tutor_id
            ')
            ->join(
                'termini',
                'termini.id = rezervacije.termin_id'
            )
            ->find($id);

        if (!$rezervacija) {
            return $this->failNotFound(
                'Rezervacija nije pronađena.'
            );
        }

        // Tutor može obrađivati samo svoje termine.
        // Administrator može obrađivati sve.
        if (
            !$this->imaDozvolu(
                $korisnikId,
                'pregled_svih_rezervacija'
            ) &&
            (int) $rezervacija['tutor_id'] !==
            $korisnikId
        ) {
            return $this->failForbidden(
                'Ne možete obrađivati rezervaciju tuđeg termina.'
            );
        }

        $data =
            $this->request->getJSON(true);

        if (empty($data['status'])) {
            return $this->failValidationErrors(
                'Status rezervacije je obavezan.'
            );
        }

        $dozvoljeniStatusi = [
            'na čekanju',
            'prihvaćeno',
            'odbijeno'
        ];

        if (
            !in_array(
                $data['status'],
                $dozvoljeniStatusi,
                true
            )
        ) {
            return $this->failValidationErrors(
                'Status rezervacije nije ispravan.'
            );
        }

        $db = db_connect();

        $db->transStart();

        // Mijenjamo status rezervacije.
        $model->update(
            $id,
            [
                'status' =>
                    $data['status']
            ]
        );


        // =====================================================
        // PRIHVAĆENA REZERVACIJA
        // =====================================================

        if (
            $data['status'] ===
            'prihvaćeno'
        ) {
            // Termin više nije slobodan.
            $db
                ->table('termini')
                ->where(
                    'id',
                    $rezervacija['termin_id']
                )
                ->update([
                    'status' =>
                        'rezerviran'
                ]);

            // Ostale rezervacije za isti termin odbijamo.
            $db
                ->table('rezervacije')
                ->where(
                    'termin_id',
                    $rezervacija['termin_id']
                )
                ->where(
                    'id !=',
                    $id
                )
                ->where(
                    'status',
                    'na čekanju'
                )
                ->update([
                    'status' =>
                        'odbijeno'
                ]);
        }


        // =====================================================
        // ODBIJENA REZERVACIJA
        // =====================================================

        if (
            $data['status'] ===
            'odbijeno'
        ) {
            /*
             * Ako za termin nema druge prihvaćene
             * rezervacije, termin je ponovno slobodan.
             */
            $postojiPrihvacena = $db
                ->table('rezervacije')
                ->where(
                    'termin_id',
                    $rezervacija['termin_id']
                )
                ->where(
                    'status',
                    'prihvaćeno'
                )
                ->countAllResults();

            if ($postojiPrihvacena === 0) {
                $db
                    ->table('termini')
                    ->where(
                        'id',
                        $rezervacija['termin_id']
                    )
                    ->update([
                        'status' =>
                            'slobodan'
                    ]);
            }
        }


        $db->transComplete();

        if (!$db->transStatus()) {
            return $this->failServerError(
                'Greška pri promjeni statusa rezervacije.'
            );
        }

        return $this->respond([
            'uspjeh' => true,
            'poruka' =>
                'Status rezervacije je uspješno promijenjen.'
        ]);
    }


    // =====================================================
    // DELETE /api/rezervacije/{id}
    // =====================================================
    // Student otkazuje svoju rezervaciju.
    // Ako je rezervacija bila prihvaćena,
    // pripadajući termin ponovno postaje slobodan.
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
            new RezervacijaModel();

        $rezervacija =
            $model->find($id);

        if (!$rezervacija) {
            return $this->failNotFound(
                'Rezervacija nije pronađena.'
            );
        }


        // =====================================================
        // ADMINISTRATOR
        // =====================================================

        if (
            $this->imaDozvolu(
                $korisnikId,
                'pregled_svih_rezervacija'
            )
        ) {
            $db = db_connect();

            $db->transStart();

            /*
             * Ako administrator briše prihvaćenu
             * rezervaciju, termin ponovno oslobađamo.
             */
            if (
                $rezervacija['status'] ===
                'prihvaćeno'
            ) {
                $db
                    ->table('termini')
                    ->where(
                        'id',
                        $rezervacija['termin_id']
                    )
                    ->update([
                        'status' =>
                            'slobodan'
                    ]);
            }

            $model->delete($id);

            $db->transComplete();

            if (!$db->transStatus()) {
                return $this->failServerError(
                    'Rezervaciju nije moguće obrisati.'
                );
            }

            return $this->respondDeleted([
                'uspjeh' => true,
                'poruka' =>
                    'Rezervacija je uspješno obrisana.'
            ]);
        }


        // =====================================================
        // STUDENT
        // =====================================================

        if (
            (int) $rezervacija['student_id'] !==
            $korisnikId
        ) {
            return $this->failForbidden(
                'Možete otkazati samo vlastitu rezervaciju.'
            );
        }

        if (
            !$this->imaDozvolu(
                $korisnikId,
                'otkazivanje_rezervacije'
            )
        ) {
            return $this->failForbidden(
                'Nemate dozvolu za otkazivanje rezervacije.'
            );
        }


        $db = db_connect();

        $db->transStart();

        /*
         * NOVO:
         *
         * Ako student otkazuje prihvaćenu rezervaciju,
         * termin više nije zauzet i vraćamo ga među
         * slobodne termine.
         */
        if (
            $rezervacija['status'] ===
            'prihvaćeno'
        ) {
            $db
                ->table('termini')
                ->where(
                    'id',
                    $rezervacija['termin_id']
                )
                ->update([
                    'status' =>
                        'slobodan'
                ]);
        }

        $model->delete($id);

        $db->transComplete();

        if (!$db->transStatus()) {
            return $this->failServerError(
                'Rezervaciju nije moguće otkazati.'
            );
        }

        return $this->respondDeleted([
            'uspjeh' => true,
            'poruka' =>
                'Rezervacija je uspješno otkazana.'
        ]);
    }
}