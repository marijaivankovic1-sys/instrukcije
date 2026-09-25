<?php

namespace App\Controllers;

use App\Models\TutorPredmetModel;
use App\Libraries\DozvoleService;
use CodeIgniter\RESTful\ResourceController;

class TutorPredmeti extends ResourceController
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

    private function jeAdministrator(int $korisnikId): bool
    {
        return $this->imaDozvolu(
            $korisnikId,
            'upravljanje_korisnicima'
        );
    }

    private function korisnikJeTutor(int $korisnikId): bool
    {
        $db = db_connect();

        $uloga = $db
            ->table('korisnik_uloga')
            ->select('korisnik_uloga.korisnik_id')
            ->join(
                'uloge',
                'uloge.id = korisnik_uloga.uloga_id'
            )
            ->where(
                'korisnik_uloga.korisnik_id',
                $korisnikId
            )
            ->where('uloge.naziv', 'Tutor')
            ->get()
            ->getRowArray();

        return $uloga !== null;
    }

    /*
     * GET /api/tutor-predmeti
     *
     * Dohvat svih veza tutor-predmet.
     */
    public function index()
    {
        $korisnikId = $this->prijavljeniKorisnikId();

        if (!$korisnikId) {
            return $this->failUnauthorized(
                'Korisnik nije prijavljen.'
            );
        }

        $model = new TutorPredmetModel();

        $veze = $model
            ->select('
                tutor_predmet.id,
                tutor_predmet.tutor_id,
                tutor_predmet.predmet_id,

                korisnici.ime AS tutor_ime,
                korisnici.prezime AS tutor_prezime,

                predmeti.naziv AS predmet_naziv
            ')
            ->join(
                'korisnici',
                'korisnici.id = tutor_predmet.tutor_id'
            )
            ->join(
                'predmeti',
                'predmeti.id = tutor_predmet.predmet_id'
            )
            ->orderBy('korisnici.prezime', 'ASC')
            ->orderBy('predmeti.naziv', 'ASC')
            ->findAll();

        return $this->respond([
            'uspjeh' => true,
            'tutor_predmeti' => $veze
        ]);
    }

    /*
     * GET /api/tutor-predmeti/{id}
     */
    public function show($id = null)
    {
        $korisnikId = $this->prijavljeniKorisnikId();

        if (!$korisnikId) {
            return $this->failUnauthorized(
                'Korisnik nije prijavljen.'
            );
        }

        $model = new TutorPredmetModel();

        $veza = $model
            ->select('
                tutor_predmet.id,
                tutor_predmet.tutor_id,
                tutor_predmet.predmet_id,

                korisnici.ime AS tutor_ime,
                korisnici.prezime AS tutor_prezime,

                predmeti.naziv AS predmet_naziv
            ')
            ->join(
                'korisnici',
                'korisnici.id = tutor_predmet.tutor_id'
            )
            ->join(
                'predmeti',
                'predmeti.id = tutor_predmet.predmet_id'
            )
            ->find($id);

        if (!$veza) {
            return $this->failNotFound(
                'Veza tutor-predmet nije pronađena.'
            );
        }

        return $this->respond([
            'uspjeh' => true,
            'tutor_predmet' => $veza
        ]);
    }

    /*
     * POST /api/tutor-predmeti
     *
     * Tutor može dodavati predmete samo sebi.
     * Administrator može odabrati tutor_id.
     */
    public function create()
    {
        $korisnikId = $this->prijavljeniKorisnikId();

        if (!$korisnikId) {
            return $this->failUnauthorized(
                'Korisnik nije prijavljen.'
            );
        }

        $jeAdmin = $this->jeAdministrator($korisnikId);

        if (
            !$jeAdmin &&
            !$this->imaDozvolu(
                $korisnikId,
                'upravljanje_terminima'
            )
        ) {
            return $this->failForbidden(
                'Nemate dozvolu za upravljanje predmetima tutora.'
            );
        }

        $data = $this->request->getJSON(true);

        if (empty($data['predmet_id'])) {
            return $this->failValidationErrors(
                'Predmet je obavezan.'
            );
        }

        /*
         * Administrator može poslati tutor_id.
         * Tutor uvijek dobiva vlastiti ID iz sessiona.
         */
        if ($jeAdmin) {
            if (empty($data['tutor_id'])) {
                return $this->failValidationErrors(
                    'Tutor je obavezan.'
                );
            }

            $tutorId = (int) $data['tutor_id'];
        } else {
            $tutorId = $korisnikId;
        }

        if (!$this->korisnikJeTutor($tutorId)) {
            return $this->failValidationErrors(
                'Odabrani korisnik nije Tutor.'
            );
        }

        $db = db_connect();

        $predmet = $db
            ->table('predmeti')
            ->where('id', $data['predmet_id'])
            ->get()
            ->getRowArray();

        if (!$predmet) {
            return $this->failNotFound(
                'Predmet nije pronađen.'
            );
        }

        $model = new TutorPredmetModel();

        $postojeca = $model
            ->where('tutor_id', $tutorId)
            ->where(
                'predmet_id',
                (int) $data['predmet_id']
            )
            ->first();

        if ($postojeca) {
            return $this->failValidationErrors(
                'Tutor već ima dodijeljen ovaj predmet.'
            );
        }

        $id = $model->insert([
            'tutor_id' => $tutorId,
            'predmet_id' => (int) $data['predmet_id']
        ], true);

        return $this->respondCreated([
            'uspjeh' => true,
            'poruka' =>
                'Predmet je uspješno dodijeljen tutoru.',
            'id' => $id
        ]);
    }

    /*
     * PUT /api/tutor-predmeti/{id}
     *
     * Tutor može mijenjati samo svoju vezu.
     * Administrator može mijenjati bilo koju.
     */
    public function update($id = null)
    {
        $korisnikId = $this->prijavljeniKorisnikId();

        if (!$korisnikId) {
            return $this->failUnauthorized(
                'Korisnik nije prijavljen.'
            );
        }

        $model = new TutorPredmetModel();

        $veza = $model->find($id);

        if (!$veza) {
            return $this->failNotFound(
                'Veza tutor-predmet nije pronađena.'
            );
        }

        $jeAdmin = $this->jeAdministrator($korisnikId);

        if (
            !$jeAdmin &&
            (
                !$this->imaDozvolu(
                    $korisnikId,
                    'upravljanje_terminima'
                ) ||
                (int) $veza['tutor_id'] !== $korisnikId
            )
        ) {
            return $this->failForbidden(
                'Možete uređivati samo vlastite predmete.'
            );
        }

        $data = $this->request->getJSON(true);

        if (empty($data['predmet_id'])) {
            return $this->failValidationErrors(
                'Predmet je obavezan.'
            );
        }

        $db = db_connect();

        $predmet = $db
            ->table('predmeti')
            ->where('id', $data['predmet_id'])
            ->get()
            ->getRowArray();

        if (!$predmet) {
            return $this->failNotFound(
                'Predmet nije pronađen.'
            );
        }

        /*
         * Tutor ostaje vlasnik vlastite veze.
         * Admin može eventualno promijeniti i tutora.
         */
        $tutorId = (int) $veza['tutor_id'];

        if (
            $jeAdmin &&
            isset($data['tutor_id'])
        ) {
            $tutorId = (int) $data['tutor_id'];

            if (!$this->korisnikJeTutor($tutorId)) {
                return $this->failValidationErrors(
                    'Odabrani korisnik nije Tutor.'
                );
            }
        }

        $postojeca = $model
            ->where('tutor_id', $tutorId)
            ->where(
                'predmet_id',
                (int) $data['predmet_id']
            )
            ->where('id !=', $id)
            ->first();

        if ($postojeca) {
            return $this->failValidationErrors(
                'Tutor već ima dodijeljen ovaj predmet.'
            );
        }

        $model->update($id, [
            'tutor_id' => $tutorId,
            'predmet_id' => (int) $data['predmet_id']
        ]);

        return $this->respond([
            'uspjeh' => true,
            'poruka' =>
                'Veza tutor-predmet je uspješno uređena.'
        ]);
    }

    /*
     * DELETE /api/tutor-predmeti/{id}
     *
     * Tutor može obrisati samo svoju vezu.
     * Administrator može obrisati bilo koju.
     */
    public function delete($id = null)
    {
        $korisnikId = $this->prijavljeniKorisnikId();

        if (!$korisnikId) {
            return $this->failUnauthorized(
                'Korisnik nije prijavljen.'
            );
        }

        $model = new TutorPredmetModel();

        $veza = $model->find($id);

        if (!$veza) {
            return $this->failNotFound(
                'Veza tutor-predmet nije pronađena.'
            );
        }

        $jeAdmin = $this->jeAdministrator($korisnikId);

        if (
            !$jeAdmin &&
            (
                !$this->imaDozvolu(
                    $korisnikId,
                    'upravljanje_terminima'
                ) ||
                (int) $veza['tutor_id'] !== $korisnikId
            )
        ) {
            return $this->failForbidden(
                'Možete obrisati samo vlastite predmete.'
            );
        }

        $model->delete($id);

        return $this->respondDeleted([
            'uspjeh' => true,
            'poruka' =>
                'Veza tutor-predmet je uspješno obrisana.'
        ]);
    }
}