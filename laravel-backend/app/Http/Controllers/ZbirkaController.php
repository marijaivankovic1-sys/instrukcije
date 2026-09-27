<?php

namespace App\Http\Controllers;

use App\Models\Zbirka;
use App\Services\DozvoleService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class ZbirkaController extends Controller
{
    private function provjeriUpravljanje(
        Request $request,
        DozvoleService $dozvoleService
    ) {
        if (!$request->session()->get('prijavljen')) {
            return response()->json([
                'message' => 'Korisnik nije prijavljen.'
            ], 401);
        }

        $korisnikId = (int) $request->session()->get('korisnik_id');

        if (!$dozvoleService->korisnikImaDozvolu(
            $korisnikId,
            'upravljanje_predmetima'
        )) {
            return response()->json([
                'message' => 'Nemate dozvolu za upravljanje zbirkama.'
            ], 403);
        }

        return null;
    }

    private function mapaZaUpload(): string
    {
        $putanja = public_path('uploads/zbirke');

        if (!File::exists($putanja)) {
            File::makeDirectory($putanja, 0775, true);
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

        $punaPutanja = public_path($relativna);

        if (File::isFile($punaPutanja)) {
            File::delete($punaPutanja);
        }
    }

    private function spremiSliku($datoteka): array
    {
        if (!$datoteka || !$datoteka->isValid()) {
            return [
                'uspjeh' => false,
                'poruka' => 'Slika nije ispravna.'
            ];
        }

        $dozvoljeniTipovi = [
            'image/jpeg',
            'image/png',
            'image/webp',
        ];

        if (!in_array(
            $datoteka->getMimeType(),
            $dozvoljeniTipovi,
            true
        )) {
            return [
                'uspjeh' => false,
                'poruka' => 'Slika mora biti JPG, PNG ili WEBP.'
            ];
        }

        if ($datoteka->getSize() > 5 * 1024 * 1024) {
            return [
                'uspjeh' => false,
                'poruka' => 'Slika ne smije biti veća od 5 MB.'
            ];
        }

        $novoIme = uniqid('zbirka_', true)
            . '.'
            . $datoteka->getClientOriginalExtension();

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
        if (!$datoteka || !$datoteka->isValid()) {
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
                'poruka' => 'PDF ne smije biti veći od 10 MB.'
            ];
        }

        $novoIme = uniqid('zbirka_', true) . '.pdf';

        $datoteka->move(
            $this->mapaZaUpload(),
            $novoIme
        );

        return [
            'uspjeh' => true,
            'putanja' => 'uploads/zbirke/' . $novoIme
        ];
    }

    public function index(Request $request)
    {
        if (!$request->session()->get('prijavljen')) {
            return response()->json([
                'message' => 'Korisnik nije prijavljen.'
            ], 401);
        }

        $zbirke = Zbirka::query()
            ->select([
                'zbirke.id',
                'zbirke.predmet_id',
                'zbirke.naziv',
                'zbirke.opis',
                'zbirke.cijena',
                'zbirke.slika_putanja',
                'zbirke.pdf_putanja',
                'predmeti.naziv as predmet_naziv',
            ])
            ->join(
                'predmeti',
                'predmeti.id',
                '=',
                'zbirke.predmet_id'
            )
            ->get();

        return response()->json([
            'uspjeh' => true,
            'zbirke' => $zbirke
        ]);
    }

    public function show(Request $request, $id)
    {
        if (!$request->session()->get('prijavljen')) {
            return response()->json([
                'message' => 'Korisnik nije prijavljen.'
            ], 401);
        }

        $zbirka = Zbirka::query()
            ->select([
                'zbirke.id',
                'zbirke.predmet_id',
                'zbirke.naziv',
                'zbirke.opis',
                'zbirke.cijena',
                'zbirke.slika_putanja',
                'zbirke.pdf_putanja',
                'predmeti.naziv as predmet_naziv',
            ])
            ->join(
                'predmeti',
                'predmeti.id',
                '=',
                'zbirke.predmet_id'
            )
            ->where('zbirke.id', $id)
            ->first();

        if (!$zbirka) {
            return response()->json([
                'message' => 'Zbirka nije pronađena.'
            ], 404);
        }

        return response()->json([
            'uspjeh' => true,
            'zbirka' => $zbirka
        ]);
    }

    public function store(
        Request $request,
        DozvoleService $dozvoleService
    ) {
        if ($odgovor = $this->provjeriUpravljanje(
            $request,
            $dozvoleService
        )) {
            return $odgovor;
        }

        $predmetId = $request->input('predmet_id');
        $naziv = $request->input('naziv');
        $opis = $request->input('opis', '');
        $cijena = $request->input('cijena');

        if (
            empty($predmetId) ||
            empty($naziv) ||
            $cijena === null ||
            $cijena === ''
        ) {
            return response()->json([
                'message' => 'Predmet, naziv i cijena su obavezni.'
            ], 422);
        }

        if (!is_numeric($cijena) || (float) $cijena < 0) {
            return response()->json([
                'message' => 'Cijena nije ispravna.'
            ], 422);
        }

        if (!DB::table('predmeti')
            ->where('id', $predmetId)
            ->exists()) {
            return response()->json([
                'message' => 'Predmet nije pronađen.'
            ], 404);
        }

        $slikaPutanja = null;
        $pdfPutanja = null;

        if ($request->hasFile('slika')) {
            $rezultat = $this->spremiSliku(
                $request->file('slika')
            );

            if (!$rezultat['uspjeh']) {
                return response()->json([
                    'message' => $rezultat['poruka']
                ], 422);
            }

            $slikaPutanja = $rezultat['putanja'];
        }

        if ($request->hasFile('pdf')) {
            $rezultat = $this->spremiPdf(
                $request->file('pdf')
            );

            if (!$rezultat['uspjeh']) {
                $this->obrisiDatoteku($slikaPutanja);

                return response()->json([
                    'message' => $rezultat['poruka']
                ], 422);
            }

            $pdfPutanja = $rezultat['putanja'];
        }

        try {
            $zbirka = Zbirka::create([
                'predmet_id' => (int) $predmetId,
                'naziv' => trim($naziv),
                'opis' => trim($opis),
                'cijena' => (float) $cijena,
                'slika_putanja' => $slikaPutanja,
                'pdf_putanja' => $pdfPutanja,
            ]);
        } catch (\Throwable $e) {
            $this->obrisiDatoteku($slikaPutanja);
            $this->obrisiDatoteku($pdfPutanja);

            return response()->json([
                'message' => 'Zbirka nije mogla biti spremljena.'
            ], 500);
        }

        return response()->json([
            'uspjeh' => true,
            'poruka' => 'Zbirka je uspješno dodana.',
            'id' => $zbirka->id,
            'slika_putanja' => $slikaPutanja,
            'pdf_putanja' => $pdfPutanja
        ], 201);
    }

    public function update(
        Request $request,
        $id,
        DozvoleService $dozvoleService
    ) {
        if ($odgovor = $this->provjeriUpravljanje(
            $request,
            $dozvoleService
        )) {
            return $odgovor;
        }

        $zbirka = Zbirka::find($id);

        if (!$zbirka) {
            return response()->json([
                'message' => 'Zbirka nije pronađena.'
            ], 404);
        }

        $podaci = [];

        if ($request->has('predmet_id')) {
            if (!DB::table('predmeti')
                ->where('id', $request->input('predmet_id'))
                ->exists()) {
                return response()->json([
                    'message' => 'Predmet nije pronađen.'
                ], 404);
            }

            $podaci['predmet_id'] =
                (int) $request->input('predmet_id');
        }

        if ($request->has('naziv')) {
            $podaci['naziv'] =
                trim((string) $request->input('naziv'));
        }

        if ($request->has('opis')) {
            $podaci['opis'] =
                trim((string) $request->input('opis'));
        }

        if ($request->has('cijena')) {
            $cijena = $request->input('cijena');

            if (!is_numeric($cijena) || (float) $cijena < 0) {
                return response()->json([
                    'message' => 'Cijena nije ispravna.'
                ], 422);
            }

            $podaci['cijena'] = (float) $cijena;
        }

        if ($request->exists('slika_putanja')) {
            $vrijednost = trim(
                (string) $request->input('slika_putanja', '')
            );

            $podaci['slika_putanja'] =
                $vrijednost !== '' ? $vrijednost : null;
        }

        if ($request->exists('pdf_putanja')) {
            $vrijednost = trim(
                (string) $request->input('pdf_putanja', '')
            );

            $podaci['pdf_putanja'] =
                $vrijednost !== '' ? $vrijednost : null;
        }

        if (empty($podaci)) {
            return response()->json([
                'message' => 'Nisu poslani podaci za izmjenu.'
            ], 422);
        }

        $zbirka->update($podaci);

        return response()->json([
            'uspjeh' => true,
            'poruka' => 'Zbirka je uspješno uređena.'
        ]);
    }

    public function uploadDatoteka(
        Request $request,
        $id,
        DozvoleService $dozvoleService
    ) {
        if ($odgovor = $this->provjeriUpravljanje(
            $request,
            $dozvoleService
        )) {
            return $odgovor;
        }

        $zbirka = Zbirka::find($id);

        if (!$zbirka) {
            return response()->json([
                'message' => 'Zbirka nije pronađena.'
            ], 404);
        }

        $imaSliku = $request->hasFile('slika');
        $imaPdf = $request->hasFile('pdf');

        if (!$imaSliku && !$imaPdf) {
            return response()->json([
                'message' => 'Nije poslana nijedna datoteka.'
            ], 422);
        }

        $podaci = [];
        $novaSlika = null;
        $noviPdf = null;

        if ($imaSliku) {
            $rezultat = $this->spremiSliku(
                $request->file('slika')
            );

            if (!$rezultat['uspjeh']) {
                return response()->json([
                    'message' => $rezultat['poruka']
                ], 422);
            }

            $novaSlika = $rezultat['putanja'];
            $podaci['slika_putanja'] = $novaSlika;
        }

        if ($imaPdf) {
            $rezultat = $this->spremiPdf(
                $request->file('pdf')
            );

            if (!$rezultat['uspjeh']) {
                $this->obrisiDatoteku($novaSlika);

                return response()->json([
                    'message' => $rezultat['poruka']
                ], 422);
            }

            $noviPdf = $rezultat['putanja'];
            $podaci['pdf_putanja'] = $noviPdf;
        }

        try {
            $staraSlika = $zbirka->slika_putanja;
            $stariPdf = $zbirka->pdf_putanja;

            $zbirka->update($podaci);

            // Stare datoteke brišemo tek nakon uspješnog updatea.
            if ($novaSlika) {
                $this->obrisiDatoteku($staraSlika);
            }

            if ($noviPdf) {
                $this->obrisiDatoteku($stariPdf);
            }
        } catch (\Throwable $e) {
            $this->obrisiDatoteku($novaSlika);
            $this->obrisiDatoteku($noviPdf);

            return response()->json([
                'message' => 'Datoteke nisu mogle biti spremljene.'
            ], 500);
        }

        return response()->json([
            'uspjeh' => true,
            'poruka' => 'Datoteke su uspješno spremljene.',
            'slika_putanja' => $zbirka->slika_putanja,
            'pdf_putanja' => $zbirka->pdf_putanja
        ]);
    }

    public function destroy(
        Request $request,
        $id,
        DozvoleService $dozvoleService
    ) {
        if ($odgovor = $this->provjeriUpravljanje(
            $request,
            $dozvoleService
        )) {
            return $odgovor;
        }

        $zbirka = Zbirka::find($id);

        if (!$zbirka) {
            return response()->json([
                'message' => 'Zbirka nije pronađena.'
            ], 404);
        }

        $slika = $zbirka->slika_putanja;
        $pdf = $zbirka->pdf_putanja;

        $zbirka->delete();

        $this->obrisiDatoteku($slika);
        $this->obrisiDatoteku($pdf);

        return response()->json([
            'uspjeh' => true,
            'poruka' => 'Zbirka je uspješno obrisana.'
        ]);
    }
}