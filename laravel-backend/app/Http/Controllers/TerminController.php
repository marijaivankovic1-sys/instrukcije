<?php

namespace App\Http\Controllers;

use App\Models\Termin;
use App\Services\DozvoleService;
use Illuminate\Http\Request;

class TerminController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | GET /api/termini
    |--------------------------------------------------------------------------
    | Prikazuju se samo slobodni budući termini.
    */
    public function index()
    {
        $danas = date('Y-m-d');
        $sada = date('H:i:s');

        $termini = Termin::query()
            ->select([
                'termini.id',
                'termini.tutor_id',
                'termini.predmet_id',
                'termini.datum',
                'termini.vrijeme_od',
                'termini.vrijeme_do',
                'termini.cijena',
                'termini.status',
                'predmeti.naziv as predmet_naziv',
                'korisnici.ime as instruktor_ime',
                'korisnici.prezime as instruktor_prezime',
            ])
            ->join(
                'predmeti',
                'predmeti.id',
                '=',
                'termini.predmet_id'
            )
            ->join(
                'korisnici',
                'korisnici.id',
                '=',
                'termini.tutor_id'
            )
            ->where('termini.status', 'slobodan')
            ->where(function ($query) use ($danas, $sada) {
                $query
                    ->where('termini.datum', '>', $danas)
                    ->orWhere(function ($query) use ($danas, $sada) {
                        $query
                            ->where('termini.datum', $danas)
                            ->where('termini.vrijeme_od', '>', $sada);
                    });
            })
            ->orderBy('termini.datum', 'asc')
            ->orderBy('termini.vrijeme_od', 'asc')
            ->get();

        return response()->json([
            'uspjeh' => true,
            'termini' => $termini
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | GET /api/termini/{id}
    |--------------------------------------------------------------------------
    */
    public function show($id)
    {
        $termin = Termin::query()
            ->select([
                'termini.id',
                'termini.tutor_id',
                'termini.predmet_id',
                'termini.datum',
                'termini.vrijeme_od',
                'termini.vrijeme_do',
                'termini.cijena',
                'termini.status',
                'predmeti.naziv as predmet_naziv',
                'korisnici.ime as instruktor_ime',
                'korisnici.prezime as instruktor_prezime',
            ])
            ->join(
                'predmeti',
                'predmeti.id',
                '=',
                'termini.predmet_id'
            )
            ->join(
                'korisnici',
                'korisnici.id',
                '=',
                'termini.tutor_id'
            )
            ->where('termini.id', $id)
            ->first();

        if (!$termin) {
            return response()->json([
                'message' => 'Termin nije pronađen.'
            ], 404);
        }

        return response()->json([
            'uspjeh' => true,
            'termin' => $termin
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | POST /api/termini
    |--------------------------------------------------------------------------
    */
    public function store(
        Request $request,
        DozvoleService $dozvoleService
    ) {
        if (!$request->session()->get('prijavljen')) {
            return response()->json([
                'message' => 'Korisnik nije prijavljen.'
            ], 401);
        }

        $korisnikId =
            (int) $request->session()->get('korisnik_id');

        if (
            !$dozvoleService->korisnikImaDozvolu(
                $korisnikId,
                'upravljanje_terminima'
            )
        ) {
            return response()->json([
                'message' =>
                    'Nemate dozvolu za dodavanje termina.'
            ], 403);
        }

        if (
            empty($request->input('predmet_id')) ||
            empty($request->input('datum')) ||
            empty($request->input('vrijeme_od')) ||
            empty($request->input('vrijeme_do')) ||
            $request->input('cijena') === null
        ) {
            return response()->json([
                'message' =>
                    'Predmet, datum, vrijeme i cijena su obavezni.'
            ], 422);
        }

        if (
            $request->input('vrijeme_do') <=
            $request->input('vrijeme_od')
        ) {
            return response()->json([
                'message' =>
                    'Vrijeme završetka mora biti nakon vremena početka.'
            ], 422);
        }

        $termin = Termin::create([
            'tutor_id' => $korisnikId,
            'predmet_id' => $request->input('predmet_id'),
            'datum' => $request->input('datum'),
            'vrijeme_od' => $request->input('vrijeme_od'),
            'vrijeme_do' => $request->input('vrijeme_do'),
            'cijena' => $request->input('cijena'),
            'status' => $request->input(
                'status',
                'slobodan'
            ),
        ]);

        return response()->json([
            'uspjeh' => true,
            'poruka' => 'Termin je uspješno dodan.',
            'id' => $termin->id
        ], 201);
    }

    /*
    |--------------------------------------------------------------------------
    | PUT /api/termini/{id}
    |--------------------------------------------------------------------------
    */
    public function update(
        Request $request,
        $id,
        DozvoleService $dozvoleService
    ) {
        if (!$request->session()->get('prijavljen')) {
            return response()->json([
                'message' => 'Korisnik nije prijavljen.'
            ], 401);
        }

        $korisnikId =
            (int) $request->session()->get('korisnik_id');

        if (
            !$dozvoleService->korisnikImaDozvolu(
                $korisnikId,
                'upravljanje_terminima'
            )
        ) {
            return response()->json([
                'message' =>
                    'Nemate dozvolu za uređivanje termina.'
            ], 403);
        }

        $termin = Termin::find($id);

        if (!$termin) {
            return response()->json([
                'message' => 'Termin nije pronađen.'
            ], 404);
        }

        /*
         * Tutor može uređivati samo svoje termine.
         * Administrator s pregled_svih_rezervacija
         * može uređivati sve termine.
         */
        if (
            !$dozvoleService->korisnikImaDozvolu(
                $korisnikId,
                'pregled_svih_rezervacija'
            ) &&
            (int) $termin->tutor_id !== $korisnikId
        ) {
            return response()->json([
                'message' =>
                    'Možete uređivati samo vlastite termine.'
            ], 403);
        }

        if (
            $request->has('vrijeme_od') &&
            $request->has('vrijeme_do') &&
            $request->input('vrijeme_do') <=
            $request->input('vrijeme_od')
        ) {
            return response()->json([
                'message' =>
                    'Vrijeme završetka mora biti nakon vremena početka.'
            ], 422);
        }

        $podaci = [];

        if ($request->has('predmet_id')) {
            $podaci['predmet_id'] =
                $request->input('predmet_id');
        }

        if ($request->has('datum')) {
            $podaci['datum'] =
                $request->input('datum');
        }

        if ($request->has('vrijeme_od')) {
            $podaci['vrijeme_od'] =
                $request->input('vrijeme_od');
        }

        if ($request->has('vrijeme_do')) {
            $podaci['vrijeme_do'] =
                $request->input('vrijeme_do');
        }

        if ($request->has('cijena')) {
            $podaci['cijena'] =
                $request->input('cijena');
        }

        if ($request->has('status')) {
            $podaci['status'] =
                $request->input('status');
        }

        if (empty($podaci)) {
            return response()->json([
                'message' =>
                    'Nisu poslani podaci za izmjenu.'
            ], 422);
        }

        $termin->update($podaci);

        return response()->json([
            'uspjeh' => true,
            'poruka' => 'Termin je uspješno uređen.'
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | DELETE /api/termini/{id}
    |--------------------------------------------------------------------------
    */
    public function destroy(
        Request $request,
        $id,
        DozvoleService $dozvoleService
    ) {
        if (!$request->session()->get('prijavljen')) {
            return response()->json([
                'message' => 'Korisnik nije prijavljen.'
            ], 401);
        }

        $korisnikId =
            (int) $request->session()->get('korisnik_id');

        if (
            !$dozvoleService->korisnikImaDozvolu(
                $korisnikId,
                'upravljanje_terminima'
            )
        ) {
            return response()->json([
                'message' =>
                    'Nemate dozvolu za brisanje termina.'
            ], 403);
        }

        $termin = Termin::find($id);

        if (!$termin) {
            return response()->json([
                'message' => 'Termin nije pronađen.'
            ], 404);
        }

        /*
         * Tutor može brisati samo svoje termine.
         * Administrator može brisati sve.
         */
        if (
            !$dozvoleService->korisnikImaDozvolu(
                $korisnikId,
                'pregled_svih_rezervacija'
            ) &&
            (int) $termin->tutor_id !== $korisnikId
        ) {
            return response()->json([
                'message' =>
                    'Možete brisati samo vlastite termine.'
            ], 403);
        }

        $termin->delete();

        return response()->json([
            'uspjeh' => true,
            'poruka' => 'Termin je uspješno obrisan.'
        ]);
    }
}