<?php

namespace App\Controllers;

use App\Models\KorisnikModel;
use App\Libraries\DozvoleService;
use CodeIgniter\RESTful\ResourceController;

class Auth extends ResourceController
{
    protected $format = 'json';

    public function registracija()
    {
        $model = new KorisnikModel();
        $data = $this->request->getJSON(true);

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

        $email = trim($data['email']);

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->failValidationErrors(
                'Email adresa nije ispravna.'
            );
        }

        $postojeci = $model
            ->where('email', $email)
            ->first();

        if ($postojeci) {
            return $this->failValidationErrors(
                'Korisnik s ovom email adresom već postoji.'
            );
        }

        $id = $model->insert([
            'ime' => trim($data['ime']),
            'prezime' => trim($data['prezime']),
            'email' => $email,
            'lozinka' => password_hash(
                $data['lozinka'],
                PASSWORD_DEFAULT
            ),
            'uloga' => 'Student',
            'status' => 'aktivan'
        ], true);

        return $this->respondCreated([
            'uspjeh' => true,
            'poruka' => 'Registracija je uspješna.',
            'id' => $id
        ]);
    }

    public function prijava()
    {
        $model = new KorisnikModel();
        $data = $this->request->getJSON(true);

        if (
            empty($data['email']) ||
            empty($data['lozinka'])
        ) {
            return $this->failValidationErrors(
                'Email i lozinka su obavezni.'
            );
        }

        $korisnik = $model
            ->where('email', trim($data['email']))
            ->first();

        if (!$korisnik) {
            return $this->failUnauthorized(
                'Email ili lozinka nisu ispravni.'
            );
        }

        if (!password_verify(
            $data['lozinka'],
            $korisnik['lozinka']
        )) {
            return $this->failUnauthorized(
                'Email ili lozinka nisu ispravni.'
            );
        }

        if ($korisnik['status'] !== 'aktivan') {
            return $this->failForbidden(
                'Korisnički račun nije aktivan.'
            );
        }

        $session = session();

        $session->set([
            'korisnik_id' => $korisnik['id'],
            'ime' => $korisnik['ime'],
            'prezime' => $korisnik['prezime'],
            'email' => $korisnik['email'],
            'uloga' => $korisnik['uloga'],
            'prijavljen' => true
        ]);

        return $this->respond([
            'uspjeh' => true,
            'poruka' => 'Prijava je uspješna.',
            'korisnik' => [
                'id' => $korisnik['id'],
                'ime' => $korisnik['ime'],
                'prezime' => $korisnik['prezime'],
                'email' => $korisnik['email'],
                'uloga' => $korisnik['uloga']
            ]
        ]);
    }

    public function odjava()
    {
        session()->destroy();

        return $this->respond([
            'uspjeh' => true,
            'poruka' => 'Odjava je uspješna.'
        ]);
    }

    public function ja()
    {
        $session = session();

        if (!$session->get('prijavljen')) {
            return $this->respond([
                'uspjeh' => true,
                'prijavljen' => false,
                'korisnik' => null
            ]);
        }

        return $this->respond([
            'uspjeh' => true,
            'prijavljen' => true,
            'korisnik' => [
                'id' => $session->get('korisnik_id'),
                'ime' => $session->get('ime'),
                'prezime' => $session->get('prezime'),
                'email' => $session->get('email'),
                'uloga' => $session->get('uloga')
            ]
        ]);
    }

    public function dozvole()
    {
        $session = session();

        if (!$session->get('prijavljen')) {
            return $this->failUnauthorized(
                'Korisnik nije prijavljen.'
            );
        }

        $korisnikId = (int) $session->get('korisnik_id');

        $dozvoleService = new DozvoleService();

        $dozvole = $dozvoleService->dohvatiDozvoleKorisnika(
            $korisnikId
        );

        return $this->respond([
            'uspjeh' => true,
            'korisnik_id' => $korisnikId,
            'dozvole' => $dozvole
        ]);
    }
}