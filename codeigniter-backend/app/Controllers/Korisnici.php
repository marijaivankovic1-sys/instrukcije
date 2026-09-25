<?php

namespace App\Controllers;

use App\Models\KorisnikModel;
use App\Libraries\DozvoleService;
use CodeIgniter\RESTful\ResourceController;

class Korisnici extends ResourceController
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
    // DOZVOLE
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


    private function provjeriPristup()
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
                'upravljanje_korisnicima'
            )
        ) {
            return $this->failForbidden(
                'Nemate dozvolu za upravljanje korisnicima.'
            );
        }

        return null;
    }


    // =====================================================
    // PRETVARANJE ULOGE U ID IZ TABLICE uloge
    // =====================================================

    private function dohvatiUlogaId(
        string $uloga
    ): ?int
    {
        $db = db_connect();

        /*
         * U tablici uloge imamo:
         * Administrator
         * Tutor
         * Student
         *
         * Dok legacy stupac korisnici.uloga
         * koristi "Admin", "Tutor", "Student".
         */

        $nazivUloge = match ($uloga) {
            'Admin' => 'Administrator',
            'Tutor' => 'Tutor',
            'Student' => 'Student',
            default => null
        };

        if (!$nazivUloge) {
            return null;
        }

        $red = $db
            ->table('uloge')
            ->select('id')
            ->where('naziv', $nazivUloge)
            ->get()
            ->getRowArray();

        return $red
            ? (int) $red['id']
            : null;
    }


    // =====================================================
    // SINKRONIZACIJA TABLICE korisnik_uloga
    // =====================================================

    private function postaviUloguKorisniku(
        int $korisnikId,
        string $uloga
    ): bool
    {
        $ulogaId =
            $this->dohvatiUlogaId($uloga);

        if (!$ulogaId) {
            return false;
        }

        $db = db_connect();

        /*
         * U našem projektu korisnik ima jednu aktivnu ulogu.
         * Zato prvo brišemo postojeću vezu,
         * pa dodajemo novu.
         */

        $db
            ->table('korisnik_uloga')
            ->where(
                'korisnik_id',
                $korisnikId
            )
            ->delete();


        $db
            ->table('korisnik_uloga')
            ->insert([
                'korisnik_id' => $korisnikId,
                'uloga_id' => $ulogaId
            ]);


        return true;
    }


    // =====================================================
    // LISTA KORISNIKA
    // =====================================================

    public function index()
    {
        $zabrana =
            $this->provjeriPristup();

        if ($zabrana) {
            return $zabrana;
        }


        $db = db_connect();

        /*
         * Ulogu dohvaćamo iz normaliziranih tablica.
         * Legacy korisnici.uloga ostavljamo sinkroniziran
         * zbog postojećih dijelova aplikacije.
         */

        $korisnici = $db
            ->table('korisnici')
            ->select('
                korisnici.id,
                korisnici.ime,
                korisnici.prezime,
                korisnici.email,
                korisnici.status,
                korisnici.datum_registracije,
                korisnici.uloga AS legacy_uloga,
                uloge.naziv AS normalizirana_uloga
            ')
            ->join(
                'korisnik_uloga',
                'korisnik_uloga.korisnik_id = korisnici.id',
                'left'
            )
            ->join(
                'uloge',
                'uloge.id = korisnik_uloga.uloga_id',
                'left'
            )
            ->orderBy(
                'korisnici.id',
                'ASC'
            )
            ->get()
            ->getResultArray();


        foreach ($korisnici as &$korisnik) {

            $normalizirana =
                $korisnik['normalizirana_uloga']
                ?? null;


            if (
                $normalizirana ===
                'Administrator'
            ) {
                $korisnik['uloga'] =
                    'Admin';

            } elseif (
                $normalizirana ===
                'Tutor'
            ) {
                $korisnik['uloga'] =
                    'Tutor';

            } elseif (
                $normalizirana ===
                'Student'
            ) {
                $korisnik['uloga'] =
                    'Student';

            } else {
                /*
                 * Fallback za stare zapise
                 * koji eventualno još nemaju
                 * korisnik_uloga vezu.
                 */
                $korisnik['uloga'] =
                    $korisnik['legacy_uloga']
                    ?? 'Student';
            }


            unset(
                $korisnik['legacy_uloga'],
                $korisnik['normalizirana_uloga']
            );
        }


        return $this->respond([
            'uspjeh' => true,
            'korisnici' => $korisnici
        ]);
    }


    // =====================================================
    // JEDAN KORISNIK
    // =====================================================

    public function show($id = null)
    {
        $zabrana =
            $this->provjeriPristup();

        if ($zabrana) {
            return $zabrana;
        }


        $db = db_connect();


        $korisnik = $db
            ->table('korisnici')
            ->select('
                korisnici.id,
                korisnici.ime,
                korisnici.prezime,
                korisnici.email,
                korisnici.status,
                korisnici.datum_registracije,
                korisnici.uloga AS legacy_uloga,
                uloge.naziv AS normalizirana_uloga
            ')
            ->join(
                'korisnik_uloga',
                'korisnik_uloga.korisnik_id = korisnici.id',
                'left'
            )
            ->join(
                'uloge',
                'uloge.id = korisnik_uloga.uloga_id',
                'left'
            )
            ->where(
                'korisnici.id',
                $id
            )
            ->get()
            ->getRowArray();


        if (!$korisnik) {
            return $this->failNotFound(
                'Korisnik nije pronađen.'
            );
        }


        $normalizirana =
            $korisnik['normalizirana_uloga']
            ?? null;


        if (
            $normalizirana ===
            'Administrator'
        ) {
            $korisnik['uloga'] =
                'Admin';

        } elseif (
            $normalizirana ===
            'Tutor'
        ) {
            $korisnik['uloga'] =
                'Tutor';

        } elseif (
            $normalizirana ===
            'Student'
        ) {
            $korisnik['uloga'] =
                'Student';

        } else {
            $korisnik['uloga'] =
                $korisnik['legacy_uloga']
                ?? 'Student';
        }


        unset(
            $korisnik['legacy_uloga'],
            $korisnik['normalizirana_uloga']
        );


        return $this->respond([
            'uspjeh' => true,
            'korisnik' => $korisnik
        ]);
    }


    // =====================================================
    // DODAVANJE KORISNIKA
    // =====================================================

    public function create()
    {
        $zabrana =
            $this->provjeriPristup();

        if ($zabrana) {
            return $zabrana;
        }


        $model =
            new KorisnikModel();


        $data =
            $this->request->getJSON(true);


        if (
            empty($data['ime']) ||
            empty($data['prezime']) ||
            empty($data['email']) ||
            empty($data['lozinka'])
        ) {
            return $this->failValidationErrors(
                'Ime, prezime, email i lozinka su obavezni.'
            );
        }


        $email =
            trim($data['email']);


        if (
            !filter_var(
                $email,
                FILTER_VALIDATE_EMAIL
            )
        ) {
            return $this->failValidationErrors(
                'Email adresa nije ispravna.'
            );
        }


        $postojeci =
            $model
                ->where(
                    'email',
                    $email
                )
                ->first();


        if ($postojeci) {
            return $this->failValidationErrors(
                'Korisnik s ovom email adresom već postoji.'
            );
        }


        $uloga =
            $data['uloga']
            ?? 'Student';


        $status =
            $data['status']
            ?? 'aktivan';


        $dozvoljeneUloge = [
            'Student',
            'Tutor',
            'Admin'
        ];


        if (
            !in_array(
                $uloga,
                $dozvoljeneUloge,
                true
            )
        ) {
            return $this->failValidationErrors(
                'Uloga nije ispravna.'
            );
        }


        $db = db_connect();

        $db->transStart();


        $id = $model->insert(
            [
                'ime' =>
                    trim($data['ime']),

                'prezime' =>
                    trim($data['prezime']),

                'email' =>
                    $email,

                'lozinka' =>
                    password_hash(
                        $data['lozinka'],
                        PASSWORD_DEFAULT
                    ),

                'uloga' =>
                    $uloga,

                'status' =>
                    $status
            ],
            true
        );


        if (!$id) {

            $db->transRollback();

            return $this->failServerError(
                'Korisnika nije moguće dodati.'
            );
        }


        if (
            !$this->postaviUloguKorisniku(
                (int) $id,
                $uloga
            )
        ) {

            $db->transRollback();

            return $this->failServerError(
                'Korisnik je kreiran, ali uloga nije mogla biti povezana.'
            );
        }


        $db->transComplete();


        if (!$db->transStatus()) {

            return $this->failServerError(
                'Greška pri spremanju korisnika.'
            );
        }


        return $this->respondCreated([
            'uspjeh' => true,
            'poruka' =>
                'Korisnik je uspješno dodan.',
            'id' => $id
        ]);
    }


    // =====================================================
    // UREĐIVANJE KORISNIKA
    // =====================================================

    public function update($id = null)
    {
        $zabrana =
            $this->provjeriPristup();

        if ($zabrana) {
            return $zabrana;
        }


        $model =
            new KorisnikModel();


        $korisnik =
            $model->find($id);


        if (!$korisnik) {
            return $this->failNotFound(
                'Korisnik nije pronađen.'
            );
        }


        $data =
            $this->request->getJSON(true);


        $podaci = [];


        if (isset($data['ime'])) {

            $ime =
                trim($data['ime']);

            if ($ime === '') {
                return $this->failValidationErrors(
                    'Ime ne može biti prazno.'
                );
            }

            $podaci['ime'] =
                $ime;
        }


        if (isset($data['prezime'])) {

            $prezime =
                trim($data['prezime']);

            if ($prezime === '') {
                return $this->failValidationErrors(
                    'Prezime ne može biti prazno.'
                );
            }

            $podaci['prezime'] =
                $prezime;
        }


        if (isset($data['email'])) {

            $email =
                trim($data['email']);


            if (
                !filter_var(
                    $email,
                    FILTER_VALIDATE_EMAIL
                )
            ) {
                return $this->failValidationErrors(
                    'Email adresa nije ispravna.'
                );
            }


            $postojeci =
                $model
                    ->where(
                        'email',
                        $email
                    )
                    ->where(
                        'id !=',
                        $id
                    )
                    ->first();


            if ($postojeci) {
                return $this->failValidationErrors(
                    'Korisnik s ovom email adresom već postoji.'
                );
            }


            $podaci['email'] =
                $email;
        }


        if (!empty($data['lozinka'])) {

            $podaci['lozinka'] =
                password_hash(
                    $data['lozinka'],
                    PASSWORD_DEFAULT
                );
        }


        $novaUloga = null;


        if (isset($data['uloga'])) {

            $dozvoljeneUloge = [
                'Student',
                'Tutor',
                'Admin'
            ];


            if (
                !in_array(
                    $data['uloga'],
                    $dozvoljeneUloge,
                    true
                )
            ) {
                return $this->failValidationErrors(
                    'Uloga nije ispravna.'
                );
            }


            $novaUloga =
                $data['uloga'];


            /*
             * Legacy stupac ostavljamo sinkroniziran
             * zbog postojećih dijelova aplikacije.
             */
            $podaci['uloga'] =
                $novaUloga;
        }


        if (isset($data['status'])) {

            $podaci['status'] =
                $data['status'];
        }


        if (
            empty($podaci) &&
            !$novaUloga
        ) {
            return $this->failValidationErrors(
                'Nisu poslani podaci za izmjenu.'
            );
        }


        $db = db_connect();

        $db->transStart();


        if (!empty($podaci)) {

            $model->update(
                $id,
                $podaci
            );
        }


        if ($novaUloga) {

            if (
                !$this->postaviUloguKorisniku(
                    (int) $id,
                    $novaUloga
                )
            ) {

                $db->transRollback();

                return $this->failServerError(
                    'Uloga korisnika nije mogla biti ažurirana.'
                );
            }
        }


        $db->transComplete();


        if (!$db->transStatus()) {

            return $this->failServerError(
                'Greška pri spremanju izmjena korisnika.'
            );
        }


        return $this->respond([
            'uspjeh' => true,
            'poruka' =>
                'Korisnik je uspješno uređen.'
        ]);
    }


    // =====================================================
    // BRISANJE KORISNIKA
    // =====================================================

    public function delete($id = null)
    {
        $zabrana =
            $this->provjeriPristup();

        if ($zabrana) {
            return $zabrana;
        }


        $model =
            new KorisnikModel();


        $korisnik =
            $model->find($id);


        if (!$korisnik) {
            return $this->failNotFound(
                'Korisnik nije pronađen.'
            );
        }


        /*
         * Radi zaštite administrator ne može
         * obrisati samoga sebe.
         */

        $prijavljeniId =
            $this->prijavljeniKorisnikId();


        if (
            $prijavljeniId ===
            (int) $id
        ) {
            return $this->failForbidden(
                'Ne možete obrisati vlastiti korisnički račun.'
            );
        }


        $db = db_connect();

        $db->transStart();


        /*
         * Prvo brišemo vezu u korisnik_uloga.
         * Ako FK ima ON DELETE CASCADE, ovo nije nužno,
         * ali je ovako jasno i sigurno.
         */

        $db
            ->table('korisnik_uloga')
            ->where(
                'korisnik_id',
                $id
            )
            ->delete();


        $model->delete($id);


        $db->transComplete();


        if (!$db->transStatus()) {

            return $this->failServerError(
                'Korisnika nije moguće obrisati.'
            );
        }


        return $this->respondDeleted([
            'uspjeh' => true,
            'poruka' =>
                'Korisnik je uspješno obrisan.'
        ]);
    }
}