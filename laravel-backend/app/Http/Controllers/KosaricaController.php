<?php

namespace App\Http\Controllers;

use App\Models\Kosarica;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class KosaricaController extends Controller
{
    private function prijavljeniKorisnikId(Request $request): ?int
    {
        if (!$request->session()->get('prijavljen')) {
            return null;
        }

        return (int) $request->session()->get('korisnik_id');
    }

    /*
    |--------------------------------------------------------------------------
    | GET /api/kosarica
    |--------------------------------------------------------------------------
    */
    public function index(Request $request)
    {
        $korisnikId = $this->prijavljeniKorisnikId($request);

        if (!$korisnikId) {
            return response()->json([
                'message' => 'Korisnik nije prijavljen.'
            ], 401);
        }

        $stavke = Kosarica::query()
            ->select([
                'kosarica_stavke.id',
                'kosarica_stavke.korisnik_id',
                'kosarica_stavke.zbirka_id',
                'kosarica_stavke.kolicina',
                'kosarica_stavke.datum_dodavanja',

                'zbirke.naziv',
                'zbirke.cijena',
                'zbirke.slika_putanja',
                'zbirke.pdf_putanja',

                'predmeti.naziv as predmet_naziv',
            ])
            ->selectRaw(
                '(zbirke.cijena * kosarica_stavke.kolicina) AS ukupno'
            )
            ->join(
                'zbirke',
                'zbirke.id',
                '=',
                'kosarica_stavke.zbirka_id'
            )
            ->join(
                'predmeti',
                'predmeti.id',
                '=',
                'zbirke.predmet_id'
            )
            ->where(
                'kosarica_stavke.korisnik_id',
                $korisnikId
            )
            ->get();

        return response()->json([
            'uspjeh' => true,
            'stavke' => $stavke
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | GET /api/kosarica/{id}
    |--------------------------------------------------------------------------
    */
    public function show(Request $request, $id)
    {
        $korisnikId = $this->prijavljeniKorisnikId($request);

        if (!$korisnikId) {
            return response()->json([
                'message' => 'Korisnik nije prijavljen.'
            ], 401);
        }

        $stavka = Kosarica::query()
            ->select([
                'kosarica_stavke.id',
                'kosarica_stavke.korisnik_id',
                'kosarica_stavke.zbirka_id',
                'kosarica_stavke.kolicina',
                'kosarica_stavke.datum_dodavanja',

                'zbirke.naziv',
                'zbirke.cijena',
                'zbirke.slika_putanja',
                'zbirke.pdf_putanja',

                'predmeti.naziv as predmet_naziv',
            ])
            ->selectRaw(
                '(zbirke.cijena * kosarica_stavke.kolicina) AS ukupno'
            )
            ->join(
                'zbirke',
                'zbirke.id',
                '=',
                'kosarica_stavke.zbirka_id'
            )
            ->join(
                'predmeti',
                'predmeti.id',
                '=',
                'zbirke.predmet_id'
            )
            ->where(
                'kosarica_stavke.korisnik_id',
                $korisnikId
            )
            ->where('kosarica_stavke.id', $id)
            ->first();

        if (!$stavka) {
            return response()->json([
                'message' => 'Stavka košarice nije pronađena.'
            ], 404);
        }

        return response()->json([
            'uspjeh' => true,
            'stavka' => $stavka
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | POST /api/kosarica
    |--------------------------------------------------------------------------
    */
    public function store(Request $request)
    {
        $korisnikId = $this->prijavljeniKorisnikId($request);

        if (!$korisnikId) {
            return response()->json([
                'message' => 'Korisnik nije prijavljen.'
            ], 401);
        }

        $zbirkaId = $request->input('zbirka_id');

        if (empty($zbirkaId)) {
            return response()->json([
                'message' => 'Zbirka je obavezna.'
            ], 422);
        }

        $kolicina = $request->has('kolicina')
            ? (int) $request->input('kolicina')
            : 1;

        if ($kolicina < 1) {
            return response()->json([
                'message' => 'Količina mora biti najmanje 1.'
            ], 422);
        }

        $zbirka = DB::table('zbirke')
            ->where('id', $zbirkaId)
            ->first();

        if (!$zbirka) {
            return response()->json([
                'message' => 'Zbirka nije pronađena.'
            ], 404);
        }

        $postojeca = Kosarica::where(
            'korisnik_id',
            $korisnikId
        )
            ->where('zbirka_id', $zbirkaId)
            ->first();

        /*
         * Ako zbirka već postoji u košarici,
         * povećavamo postojeću količinu.
         */
        if ($postojeca) {
            $postojeca->update([
                'kolicina' =>
                    (int) $postojeca->kolicina + $kolicina
            ]);

            return response()->json([
                'uspjeh' => true,
                'poruka' =>
                    'Količina postojeće stavke je povećana.',
                'id' => $postojeca->id
            ]);
        }

        $stavka = Kosarica::create([
            'korisnik_id' => $korisnikId,
            'zbirka_id' => $zbirkaId,
            'kolicina' => $kolicina,
        ]);

        return response()->json([
            'uspjeh' => true,
            'poruka' => 'Zbirka je dodana u košaricu.',
            'id' => $stavka->id
        ], 201);
    }

    /*
    |--------------------------------------------------------------------------
    | PUT /api/kosarica/{id}
    |--------------------------------------------------------------------------
    */
    public function update(Request $request, $id)
    {
        $korisnikId = $this->prijavljeniKorisnikId($request);

        if (!$korisnikId) {
            return response()->json([
                'message' => 'Korisnik nije prijavljen.'
            ], 401);
        }

        $stavka = Kosarica::where(
            'korisnik_id',
            $korisnikId
        )
            ->where('id', $id)
            ->first();

        if (!$stavka) {
            return response()->json([
                'message' => 'Stavka košarice nije pronađena.'
            ], 404);
        }

        if (!$request->has('kolicina')) {
            return response()->json([
                'message' => 'Količina je obavezna.'
            ], 422);
        }

        $kolicina = (int) $request->input('kolicina');

        if ($kolicina < 1) {
            return response()->json([
                'message' => 'Količina mora biti najmanje 1.'
            ], 422);
        }

        $stavka->update([
            'kolicina' => $kolicina
        ]);

        return response()->json([
            'uspjeh' => true,
            'poruka' => 'Količina je uspješno promijenjena.'
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | DELETE /api/kosarica/{id}
    |--------------------------------------------------------------------------
    */
    public function destroy(Request $request, $id)
    {
        $korisnikId = $this->prijavljeniKorisnikId($request);

        if (!$korisnikId) {
            return response()->json([
                'message' => 'Korisnik nije prijavljen.'
            ], 401);
        }

        $stavka = Kosarica::where(
            'korisnik_id',
            $korisnikId
        )
            ->where('id', $id)
            ->first();

        if (!$stavka) {
            return response()->json([
                'message' => 'Stavka košarice nije pronađena.'
            ], 404);
        }

        $stavka->delete();

        return response()->json([
            'uspjeh' => true,
            'poruka' => 'Stavka je uklonjena iz košarice.'
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | DELETE /api/kosarica/sve
    |--------------------------------------------------------------------------
    */
    public function obrisiSve(Request $request)
    {
        $korisnikId = $this->prijavljeniKorisnikId($request);

        if (!$korisnikId) {
            return response()->json([
                'message' => 'Korisnik nije prijavljen.'
            ], 401);
        }

        Kosarica::where(
            'korisnik_id',
            $korisnikId
        )->delete();

        return response()->json([
            'uspjeh' => true,
            'poruka' => 'Košarica je ispražnjena.'
        ]);
    }
}