<?php

namespace App\Http\Controllers;

use App\Models\TutorPredmet;
use App\Services\DozvoleService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TutorPredmetController extends Controller
{
    private function jeAdministrator(
        int $korisnikId,
        DozvoleService $dozvoleService
    ): bool {
        return $dozvoleService->korisnikImaDozvolu(
            $korisnikId,
            'upravljanje_korisnicima'
        );
    }

    private function korisnikJeTutor(int $korisnikId): bool
    {
        return DB::table('korisnik_uloga')
            ->join(
                'uloge',
                'uloge.id',
                '=',
                'korisnik_uloga.uloga_id'
            )
            ->where(
                'korisnik_uloga.korisnik_id',
                $korisnikId
            )
            ->where('uloge.naziv', 'Tutor')
            ->exists();
    }

    /*
    |--------------------------------------------------------------------------
    | GET /api/tutor-predmeti
    |--------------------------------------------------------------------------
    */
    public function index(Request $request)
    {
        if (!$request->session()->get('prijavljen')) {
            return response()->json([
                'message' => 'Korisnik nije prijavljen.'
            ], 401);
        }

        $veze = TutorPredmet::query()
            ->select([
                'tutor_predmet.id',
                'tutor_predmet.tutor_id',
                'tutor_predmet.predmet_id',
                'korisnici.ime as tutor_ime',
                'korisnici.prezime as tutor_prezime',
                'predmeti.naziv as predmet_naziv',
            ])
            ->join(
                'korisnici',
                'korisnici.id',
                '=',
                'tutor_predmet.tutor_id'
            )
            ->join(
                'predmeti',
                'predmeti.id',
                '=',
                'tutor_predmet.predmet_id'
            )
            ->orderBy('korisnici.prezime')
            ->orderBy('predmeti.naziv')
            ->get();

        return response()->json([
            'uspjeh' => true,
            'tutor_predmeti' => $veze
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | GET /api/tutor-predmeti/{id}
    |--------------------------------------------------------------------------
    */
    public function show(Request $request, $id)
    {
        if (!$request->session()->get('prijavljen')) {
            return response()->json([
                'message' => 'Korisnik nije prijavljen.'
            ], 401);
        }

        $veza = TutorPredmet::query()
            ->select([
                'tutor_predmet.id',
                'tutor_predmet.tutor_id',
                'tutor_predmet.predmet_id',
                'korisnici.ime as tutor_ime',
                'korisnici.prezime as tutor_prezime',
                'predmeti.naziv as predmet_naziv',
            ])
            ->join(
                'korisnici',
                'korisnici.id',
                '=',
                'tutor_predmet.tutor_id'
            )
            ->join(
                'predmeti',
                'predmeti.id',
                '=',
                'tutor_predmet.predmet_id'
            )
            ->where('tutor_predmet.id', $id)
            ->first();

        if (!$veza) {
            return response()->json([
                'message' => 'Veza tutor-predmet nije pronađena.'
            ], 404);
        }

        return response()->json([
            'uspjeh' => true,
            'tutor_predmet' => $veza
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | POST /api/tutor-predmeti
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

        $korisnikId = (int) $request->session()->get('korisnik_id');

        $jeAdmin = $this->jeAdministrator(
            $korisnikId,
            $dozvoleService
        );

        if (
            !$jeAdmin &&
            !$dozvoleService->korisnikImaDozvolu(
                $korisnikId,
                'upravljanje_terminima'
            )
        ) {
            return response()->json([
                'message' =>
                    'Nemate dozvolu za upravljanje predmetima tutora.'
            ], 403);
        }

        $predmetId = (int) $request->input('predmet_id');

        if (!$predmetId) {
            return response()->json([
                'message' => 'Predmet je obavezan.'
            ], 422);
        }

        /*
         * Administrator bira tutora.
         * Tutor uvijek dobiva vlastiti ID iz sessiona.
         */
        if ($jeAdmin) {
            $tutorId = (int) $request->input('tutor_id');

            if (!$tutorId) {
                return response()->json([
                    'message' => 'Tutor je obavezan.'
                ], 422);
            }
        } else {
            $tutorId = $korisnikId;
        }

        if (!$this->korisnikJeTutor($tutorId)) {
            return response()->json([
                'message' => 'Odabrani korisnik nije Tutor.'
            ], 422);
        }

        if (
            !DB::table('predmeti')
                ->where('id', $predmetId)
                ->exists()
        ) {
            return response()->json([
                'message' => 'Predmet nije pronađen.'
            ], 404);
        }

        $postojeca = TutorPredmet::where(
            'tutor_id',
            $tutorId
        )
            ->where('predmet_id', $predmetId)
            ->first();

        if ($postojeca) {
            return response()->json([
                'message' =>
                    'Tutor već ima dodijeljen ovaj predmet.'
            ], 422);
        }

        $veza = TutorPredmet::create([
            'tutor_id' => $tutorId,
            'predmet_id' => $predmetId,
        ]);

        return response()->json([
            'uspjeh' => true,
            'poruka' =>
                'Predmet je uspješno dodijeljen tutoru.',
            'id' => $veza->id
        ], 201);
    }

    /*
    |--------------------------------------------------------------------------
    | PUT /api/tutor-predmeti/{id}
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

        $korisnikId = (int) $request->session()->get('korisnik_id');

        $veza = TutorPredmet::find($id);

        if (!$veza) {
            return response()->json([
                'message' => 'Veza tutor-predmet nije pronađena.'
            ], 404);
        }

        $jeAdmin = $this->jeAdministrator(
            $korisnikId,
            $dozvoleService
        );

        if (
            !$jeAdmin &&
            (
                !$dozvoleService->korisnikImaDozvolu(
                    $korisnikId,
                    'upravljanje_terminima'
                ) ||
                (int) $veza->tutor_id !== $korisnikId
            )
        ) {
            return response()->json([
                'message' =>
                    'Možete uređivati samo vlastite predmete.'
            ], 403);
        }

        $predmetId = (int) $request->input('predmet_id');

        if (!$predmetId) {
            return response()->json([
                'message' => 'Predmet je obavezan.'
            ], 422);
        }

        if (
            !DB::table('predmeti')
                ->where('id', $predmetId)
                ->exists()
        ) {
            return response()->json([
                'message' => 'Predmet nije pronađen.'
            ], 404);
        }

        /*
         * Tutor ostaje vlasnik svoje veze.
         * Administrator može promijeniti i tutora.
         */
        $tutorId = (int) $veza->tutor_id;

        if (
            $jeAdmin &&
            $request->has('tutor_id')
        ) {
            $tutorId = (int) $request->input('tutor_id');

            if (!$this->korisnikJeTutor($tutorId)) {
                return response()->json([
                    'message' =>
                        'Odabrani korisnik nije Tutor.'
                ], 422);
            }
        }

        $postojeca = TutorPredmet::where(
            'tutor_id',
            $tutorId
        )
            ->where('predmet_id', $predmetId)
            ->where('id', '!=', $id)
            ->first();

        if ($postojeca) {
            return response()->json([
                'message' =>
                    'Tutor već ima dodijeljen ovaj predmet.'
            ], 422);
        }

        $veza->update([
            'tutor_id' => $tutorId,
            'predmet_id' => $predmetId,
        ]);

        return response()->json([
            'uspjeh' => true,
            'poruka' =>
                'Veza tutor-predmet je uspješno uređena.'
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | DELETE /api/tutor-predmeti/{id}
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

        $korisnikId = (int) $request->session()->get('korisnik_id');

        $veza = TutorPredmet::find($id);

        if (!$veza) {
            return response()->json([
                'message' => 'Veza tutor-predmet nije pronađena.'
            ], 404);
        }

        $jeAdmin = $this->jeAdministrator(
            $korisnikId,
            $dozvoleService
        );

        if (
            !$jeAdmin &&
            (
                !$dozvoleService->korisnikImaDozvolu(
                    $korisnikId,
                    'upravljanje_terminima'
                ) ||
                (int) $veza->tutor_id !== $korisnikId
            )
        ) {
            return response()->json([
                'message' =>
                    'Možete obrisati samo vlastite predmete.'
            ], 403);
        }

        $veza->delete();

        return response()->json([
            'uspjeh' => true,
            'poruka' =>
                'Veza tutor-predmet je uspješno obrisana.'
        ]);
    }
}