<?php

namespace App\Controllers;

use App\Models\KosaricaModel;
use CodeIgniter\RESTful\ResourceController;

class Kosarica extends ResourceController
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

    public function index()
    {
        $korisnikId = $this->prijavljeniKorisnikId();

        if (!$korisnikId) {
            return $this->failUnauthorized(
                'Korisnik nije prijavljen.'
            );
        }

        $model = new KosaricaModel();

        $stavke = $model
            ->select('
                kosarica_stavke.id,
                kosarica_stavke.korisnik_id,
                kosarica_stavke.zbirka_id,
                kosarica_stavke.kolicina,
                kosarica_stavke.datum_dodavanja,

                zbirke.naziv,
                zbirke.cijena,
                zbirke.slika_putanja,
                zbirke.pdf_putanja,

                predmeti.naziv AS predmet_naziv,

                (zbirke.cijena * kosarica_stavke.kolicina) AS ukupno
            ')
            ->join(
                'zbirke',
                'zbirke.id = kosarica_stavke.zbirka_id'
            )
            ->join(
                'predmeti',
                'predmeti.id = zbirke.predmet_id'
            )
            ->where(
                'kosarica_stavke.korisnik_id',
                $korisnikId
            )
            ->findAll();

        return $this->respond([
            'uspjeh' => true,
            'stavke' => $stavke
        ]);
    }

    public function show($id = null)
    {
        $korisnikId = $this->prijavljeniKorisnikId();

        if (!$korisnikId) {
            return $this->failUnauthorized(
                'Korisnik nije prijavljen.'
            );
        }

        $model = new KosaricaModel();

        $stavka = $model
            ->select('
                kosarica_stavke.id,
                kosarica_stavke.korisnik_id,
                kosarica_stavke.zbirka_id,
                kosarica_stavke.kolicina,
                kosarica_stavke.datum_dodavanja,

                zbirke.naziv,
                zbirke.cijena,
                zbirke.slika_putanja,
                zbirke.pdf_putanja,

                predmeti.naziv AS predmet_naziv,

                (zbirke.cijena * kosarica_stavke.kolicina) AS ukupno
            ')
            ->join(
                'zbirke',
                'zbirke.id = kosarica_stavke.zbirka_id'
            )
            ->join(
                'predmeti',
                'predmeti.id = zbirke.predmet_id'
            )
            ->where(
                'kosarica_stavke.korisnik_id',
                $korisnikId
            )
            ->find($id);

        if (!$stavka) {
            return $this->failNotFound(
                'Stavka košarice nije pronađena.'
            );
        }

        return $this->respond([
            'uspjeh' => true,
            'stavka' => $stavka
        ]);
    }

    public function create()
    {
        $korisnikId = $this->prijavljeniKorisnikId();

        if (!$korisnikId) {
            return $this->failUnauthorized(
                'Korisnik nije prijavljen.'
            );
        }

        $data = $this->request->getJSON(true);

        if (empty($data['zbirka_id'])) {
            return $this->failValidationErrors(
                'Zbirka je obavezna.'
            );
        }

        $kolicina = isset($data['kolicina'])
            ? (int) $data['kolicina']
            : 1;

        if ($kolicina < 1) {
            return $this->failValidationErrors(
                'Količina mora biti najmanje 1.'
            );
        }

        $db = db_connect();

        $zbirka = $db
            ->table('zbirke')
            ->where('id', $data['zbirka_id'])
            ->get()
            ->getRowArray();

        if (!$zbirka) {
            return $this->failNotFound(
                'Zbirka nije pronađena.'
            );
        }

        $model = new KosaricaModel();

        $postojeca = $model
            ->where('korisnik_id', $korisnikId)
            ->where('zbirka_id', $data['zbirka_id'])
            ->first();

        if ($postojeca) {
            $novaKolicina =
                (int) $postojeca['kolicina'] + $kolicina;

            $model->update(
                $postojeca['id'],
                [
                    'kolicina' => $novaKolicina
                ]
            );

            return $this->respond([
                'uspjeh' => true,
                'poruka' =>
                    'Količina postojeće stavke je povećana.',
                'id' => $postojeca['id']
            ]);
        }

        $id = $model->insert([
            'korisnik_id' => $korisnikId,
            'zbirka_id' => $data['zbirka_id'],
            'kolicina' => $kolicina
        ], true);

        return $this->respondCreated([
            'uspjeh' => true,
            'poruka' => 'Zbirka je dodana u košaricu.',
            'id' => $id
        ]);
    }

    public function update($id = null)
    {
        $korisnikId = $this->prijavljeniKorisnikId();

        if (!$korisnikId) {
            return $this->failUnauthorized(
                'Korisnik nije prijavljen.'
            );
        }

        $model = new KosaricaModel();

        $stavka = $model
            ->where('korisnik_id', $korisnikId)
            ->find($id);

        if (!$stavka) {
            return $this->failNotFound(
                'Stavka košarice nije pronađena.'
            );
        }

        $data = $this->request->getJSON(true);

        if (!isset($data['kolicina'])) {
            return $this->failValidationErrors(
                'Količina je obavezna.'
            );
        }

        $kolicina = (int) $data['kolicina'];

        if ($kolicina < 1) {
            return $this->failValidationErrors(
                'Količina mora biti najmanje 1.'
            );
        }

        $model->update($id, [
            'kolicina' => $kolicina
        ]);

        return $this->respond([
            'uspjeh' => true,
            'poruka' =>
                'Količina je uspješno promijenjena.'
        ]);
    }

    public function delete($id = null)
    {
        $korisnikId = $this->prijavljeniKorisnikId();

        if (!$korisnikId) {
            return $this->failUnauthorized(
                'Korisnik nije prijavljen.'
            );
        }

        $model = new KosaricaModel();

        $stavka = $model
            ->where('korisnik_id', $korisnikId)
            ->find($id);

        if (!$stavka) {
            return $this->failNotFound(
                'Stavka košarice nije pronađena.'
            );
        }

        $model->delete($id);

        return $this->respondDeleted([
            'uspjeh' => true,
            'poruka' =>
                'Stavka je uklonjena iz košarice.'
        ]);
    }

    public function obrisiSve()
    {
        $korisnikId = $this->prijavljeniKorisnikId();

        if (!$korisnikId) {
            return $this->failUnauthorized(
                'Korisnik nije prijavljen.'
            );
        }

        $model = new KosaricaModel();

        $model
            ->where('korisnik_id', $korisnikId)
            ->delete();

        return $this->respondDeleted([
            'uspjeh' => true,
            'poruka' => 'Košarica je ispražnjena.'
        ]);
    }
}