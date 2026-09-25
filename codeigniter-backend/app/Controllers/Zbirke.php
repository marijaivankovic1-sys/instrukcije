<?php

namespace App\Controllers;

use App\Models\ZbirkaModel;
use App\Libraries\DozvoleService;
use CodeIgniter\RESTful\ResourceController;

class Zbirke extends ResourceController
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

    private function imaDozvolu(int $korisnikId, string $dozvola): bool
    {
        $service = new DozvoleService();

        return $service->korisnikImaDozvolu(
            $korisnikId,
            $dozvola
        );
    }

    private function provjeriUpravljanje()
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
                'upravljanje_predmetima'
            )
        ) {
            return $this->failForbidden(
                'Nemate dozvolu za upravljanje zbirkama.'
            );
        }

        return null;
    }

    private function mapaZaUpload(): string
    {
        $putanja = FCPATH . 'uploads' . DIRECTORY_SEPARATOR . 'zbirke';

        if (!is_dir($putanja)) {
            mkdir($putanja, 0775, true);
        }

        return $putanja;
    }

    private function obrisiDatoteku(?string $putanja): void
    {
        if (!$putanja) {
            return;
        }

        $relativna = str_replace(
            ['/', '\\'],
            DIRECTORY_SEPARATOR,
            $putanja
        );

        $punaPutanja = FCPATH . $relativna;

        if (is_file($punaPutanja)) {
            unlink($punaPutanja);
        }
    }

    private function spremiSliku($datoteka): array
    {
        if (
            !$datoteka ||
            !$datoteka->isValid() ||
            $datoteka->hasMoved()
        ) {
            return [
                'uspjeh' => false,
                'poruka' => 'Slika nije ispravna.'
            ];
        }

        $dozvoljeniTipovi = [
            'image/jpeg',
            'image/png',
            'image/webp'
        ];

        if (
            !in_array(
                $datoteka->getMimeType(),
                $dozvoljeniTipovi,
                true
            )
        ) {
            return [
                'uspjeh' => false,
                'poruka' =>
                    'Slika mora biti JPG, PNG ili WEBP.'
            ];
        }

        if ($datoteka->getSize() > 5 * 1024 * 1024) {
            return [
                'uspjeh' => false,
                'poruka' =>
                    'Slika ne smije biti veća od 5 MB.'
            ];
        }

        $novoIme = $datoteka->getRandomName();

        $datoteka->move(
            $this->mapaZaUpload(),
            $novoIme
        );

        return [
            'uspjeh' => true,
            'putanja' => 'uploads/zbirke/' . $novoIme
        ];
    }

    private function spremiPdf($datoteka): array
    {
        if (
            !$datoteka ||
            !$datoteka->isValid() ||
            $datoteka->hasMoved()
        ) {
            return [
                'uspjeh' => false,
                'poruka' => 'PDF datoteka nije ispravna.'
            ];
        }

        if ($datoteka->getMimeType() !== 'application/pdf') {
            return [
                'uspjeh' => false,
                'poruka' => 'Dozvoljene su samo PDF datoteke.'
            ];
        }

        if ($datoteka->getSize() > 10 * 1024 * 1024) {
            return [
                'uspjeh' => false,
                'poruka' =>
                    'PDF ne smije biti veći od 10 MB.'
            ];
        }

        $novoIme = $datoteka->getRandomName();

        $datoteka->move(
            $this->mapaZaUpload(),
            $novoIme
        );

        return [
            'uspjeh' => true,
            'putanja' => 'uploads/zbirke/' . $novoIme
        ];
    }

    public function index()
    {
        $korisnikId = $this->prijavljeniKorisnikId();

        if (!$korisnikId) {
            return $this->failUnauthorized(
                'Korisnik nije prijavljen.'
            );
        }

        $model = new ZbirkaModel();

        $zbirke = $model
            ->select('
                zbirke.id,
                zbirke.predmet_id,
                zbirke.naziv,
                zbirke.opis,
                zbirke.cijena,
                zbirke.slika_putanja,
                zbirke.pdf_putanja,
                predmeti.naziv AS predmet_naziv
            ')
            ->join(
                'predmeti',
                'predmeti.id = zbirke.predmet_id'
            )
            ->findAll();

        return $this->respond([
            'uspjeh' => true,
            'zbirke' => $zbirke
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

        $model = new ZbirkaModel();

        $zbirka = $model
            ->select('
                zbirke.id,
                zbirke.predmet_id,
                zbirke.naziv,
                zbirke.opis,
                zbirke.cijena,
                zbirke.slika_putanja,
                zbirke.pdf_putanja,
                predmeti.naziv AS predmet_naziv
            ')
            ->join(
                'predmeti',
                'predmeti.id = zbirke.predmet_id'
            )
            ->find($id);

        if (!$zbirka) {
            return $this->failNotFound(
                'Zbirka nije pronađena.'
            );
        }

        return $this->respond([
            'uspjeh' => true,
            'zbirka' => $zbirka
        ]);
    }

    public function create()
    {
        $zabrana = $this->provjeriUpravljanje();

        if ($zabrana) {
            return $zabrana;
        }

        /*
         * CREATE sada očekuje multipart/form-data.
         */
        $predmetId = $this->request->getPost('predmet_id');
        $naziv = $this->request->getPost('naziv');
        $opis = $this->request->getPost('opis') ?? '';
        $cijena = $this->request->getPost('cijena');

        if (
            empty($predmetId) ||
            empty($naziv) ||
            $cijena === null ||
            $cijena === ''
        ) {
            return $this->failValidationErrors(
                'Predmet, naziv i cijena su obavezni.'
            );
        }

        if (!is_numeric($cijena) || (float) $cijena < 0) {
            return $this->failValidationErrors(
                'Cijena nije ispravna.'
            );
        }

        $db = db_connect();

        $predmet = $db
            ->table('predmeti')
            ->where('id', $predmetId)
            ->get()
            ->getRowArray();

        if (!$predmet) {
            return $this->failNotFound(
                'Predmet nije pronađen.'
            );
        }

        $slikaPutanja = null;
        $pdfPutanja = null;

        $slika = $this->request->getFile('slika');

        if (
            $slika &&
            $slika->getError() !== UPLOAD_ERR_NO_FILE
        ) {
            $rezultatSlike = $this->spremiSliku($slika);

            if (!$rezultatSlike['uspjeh']) {
                return $this->failValidationErrors(
                    $rezultatSlike['poruka']
                );
            }

            $slikaPutanja = $rezultatSlike['putanja'];
        }

        $pdf = $this->request->getFile('pdf');

        if (
            $pdf &&
            $pdf->getError() !== UPLOAD_ERR_NO_FILE
        ) {
            $rezultatPdf = $this->spremiPdf($pdf);

            if (!$rezultatPdf['uspjeh']) {
                /*
                 * Ako je slika već spremljena, a PDF nije valjan,
                 * obrišemo sliku da ne ostane nepotrebna datoteka.
                 */
                $this->obrisiDatoteku($slikaPutanja);

                return $this->failValidationErrors(
                    $rezultatPdf['poruka']
                );
            }

            $pdfPutanja = $rezultatPdf['putanja'];
        }

        $model = new ZbirkaModel();

        $id = $model->insert([
            'predmet_id' => (int) $predmetId,
            'naziv' => trim($naziv),
            'opis' => trim($opis),
            'cijena' => (float) $cijena,
            'slika_putanja' => $slikaPutanja,
            'pdf_putanja' => $pdfPutanja
        ], true);

        if (!$id) {
            $this->obrisiDatoteku($slikaPutanja);
            $this->obrisiDatoteku($pdfPutanja);

            return $this->failServerError(
                'Zbirka nije mogla biti spremljena.'
            );
        }

        return $this->respondCreated([
            'uspjeh' => true,
            'poruka' => 'Zbirka je uspješno dodana.',
            'id' => $id,
            'slika_putanja' => $slikaPutanja,
            'pdf_putanja' => $pdfPutanja
        ]);
    }

    public function update($id = null)
    {
        $zabrana = $this->provjeriUpravljanje();

        if ($zabrana) {
            return $zabrana;
        }

        $model = new ZbirkaModel();
        $zbirka = $model->find($id);

        if (!$zbirka) {
            return $this->failNotFound(
                'Zbirka nije pronađena.'
            );
        }

        $data = $this->request->getJSON(true);
        $podaci = [];

        if (isset($data['predmet_id'])) {
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

            $podaci['predmet_id'] =
                (int) $data['predmet_id'];
        }

        if (isset($data['naziv'])) {
            $podaci['naziv'] =
                trim($data['naziv']);
        }

        if (isset($data['opis'])) {
            $podaci['opis'] =
                trim($data['opis']);
        }

        if (isset($data['cijena'])) {
            if (
                !is_numeric($data['cijena']) ||
                (float) $data['cijena'] < 0
            ) {
                return $this->failValidationErrors(
                    'Cijena nije ispravna.'
                );
            }

            $podaci['cijena'] =
                (float) $data['cijena'];
        }

        /*
         * Omogućujemo spremanje ručno unesene relativne
         * putanje slike i PDF-a iz administratorske forme.
         *
         * Primjer:
         * uploads/zbirke/matematika1.png
         */
        if (array_key_exists('slika_putanja', $data)) {
            $podaci['slika_putanja'] =
                $data['slika_putanja'] !== null &&
                trim((string) $data['slika_putanja']) !== ''
                    ? trim((string) $data['slika_putanja'])
                    : null;
        }

        if (array_key_exists('pdf_putanja', $data)) {
            $podaci['pdf_putanja'] =
                $data['pdf_putanja'] !== null &&
                trim((string) $data['pdf_putanja']) !== ''
                    ? trim((string) $data['pdf_putanja'])
                    : null;
        }

        if (empty($podaci)) {
            return $this->failValidationErrors(
                'Nisu poslani podaci za izmjenu.'
            );
        }

        $model->update($id, $podaci);

        return $this->respond([
            'uspjeh' => true,
            'poruka' =>
                'Zbirka je uspješno uređena.'
        ]);
    }

    /*
     * POST /api/zbirke/{id}/datoteke
     *
     * Zamjena slike i/ili PDF-a postojeće zbirke.
     */
    public function uploadDatoteka($id = null)
    {
        $zabrana = $this->provjeriUpravljanje();

        if ($zabrana) {
            return $zabrana;
        }

        $model = new ZbirkaModel();
        $zbirka = $model->find($id);

        if (!$zbirka) {
            return $this->failNotFound(
                'Zbirka nije pronađena.'
            );
        }

        $slika = $this->request->getFile('slika');
        $pdf = $this->request->getFile('pdf');

        $imaSliku =
            $slika &&
            $slika->getError() !== UPLOAD_ERR_NO_FILE;

        $imaPdf =
            $pdf &&
            $pdf->getError() !== UPLOAD_ERR_NO_FILE;

        if (!$imaSliku && !$imaPdf) {
            return $this->failValidationErrors(
                'Nije poslana nijedna datoteka.'
            );
        }

        $podaci = [];
        $novaSlika = null;
        $noviPdf = null;

        if ($imaSliku) {
            $rezultatSlike = $this->spremiSliku($slika);

            if (!$rezultatSlike['uspjeh']) {
                return $this->failValidationErrors(
                    $rezultatSlike['poruka']
                );
            }

            $novaSlika = $rezultatSlike['putanja'];
            $podaci['slika_putanja'] = $novaSlika;
        }

        if ($imaPdf) {
            $rezultatPdf = $this->spremiPdf($pdf);

            if (!$rezultatPdf['uspjeh']) {
                $this->obrisiDatoteku($novaSlika);

                return $this->failValidationErrors(
                    $rezultatPdf['poruka']
                );
            }

            $noviPdf = $rezultatPdf['putanja'];
            $podaci['pdf_putanja'] = $noviPdf;
        }

        $model->update($id, $podaci);

        /*
         * Tek nakon uspješnog updatea brišemo stare datoteke.
         */
        if ($novaSlika) {
            $this->obrisiDatoteku(
                $zbirka['slika_putanja'] ?? null
            );
        }

        if ($noviPdf) {
            $this->obrisiDatoteku(
                $zbirka['pdf_putanja'] ?? null
            );
        }

        return $this->respond([
            'uspjeh' => true,
            'poruka' =>
                'Datoteke su uspješno spremljene.',
            'slika_putanja' =>
                $podaci['slika_putanja']
                ?? $zbirka['slika_putanja'],
            'pdf_putanja' =>
                $podaci['pdf_putanja']
                ?? $zbirka['pdf_putanja']
        ]);
    }

    public function delete($id = null)
    {
        $zabrana = $this->provjeriUpravljanje();

        if ($zabrana) {
            return $zabrana;
        }

        $model = new ZbirkaModel();
        $zbirka = $model->find($id);

        if (!$zbirka) {
            return $this->failNotFound(
                'Zbirka nije pronađena.'
            );
        }

        $model->delete($id);

        $this->obrisiDatoteku(
            $zbirka['slika_putanja'] ?? null
        );

        $this->obrisiDatoteku(
            $zbirka['pdf_putanja'] ?? null
        );

        return $this->respondDeleted([
            'uspjeh' => true,
            'poruka' => 'Zbirka je uspješno obrisana.'
        ]);
    }
}