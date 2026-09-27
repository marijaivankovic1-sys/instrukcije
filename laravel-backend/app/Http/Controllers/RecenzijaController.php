<?php

namespace App\Http\Controllers;

use App\Models\Recenzija;
use App\Services\DozvoleService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RecenzijaController extends Controller
{
    private function prijavljeniKorisnikId(Request $request): ?int
    {
        if (!$request->session()->get('prijavljen')) {
            return null;
        }

        return (int) $request->session()->get('korisnik_id');
    }

    private function imaDozvolu(
        int $korisnikId,
        string $dozvola,
        DozvoleService $dozvoleService
    ): bool {
        return $dozvoleService->korisnikImaDozvolu(
            $korisnikId,
            $dozvola
        );
    }

    /*
    |--------------------------------------------------------------------------
    | GET /api/recenzije
    |--------------------------------------------------------------------------
    */
    public function index(
        Request $request,
        DozvoleService $dozvoleService
    ) {
        $korisnikId = $this->prijavljeniKorisnikId($request);

        /*
         * Isto kao u CodeIgniteru:
         * gost može pregledavati recenzije.
         * Ako je korisnik prijavljen, mora imati pregled_recenzija.
         */
        if (
            $korisnikId &&
            !$this->imaDozvolu(
                $korisnikId,
                'pregled_recenzija',
                $dozvoleService
            )
        ) {
            return response()->json([
                'message' => 'Nemate dozvolu za pregled recenzija.'
            ], 403);
        }

        $recenzije = Recenzija::query()
            ->select([
                'recenzije.id',
                'recenzije.tutor_id',
                'recenzije.student_id',
                'recenzije.ocjena',
                'recenzije.komentar',
                'recenzije.datum_recenzije',

                'studenti.ime as student_ime',
                'studenti.prezime as student_prezime',

                'tutori.ime as tutor_ime',
                'tutori.prezime as tutor_prezime',
            ])
            ->join(
                'korisnici as studenti',
                'studenti.id',
                '=',
                'recenzije.student_id'
            )
            ->join(
                'korisnici as tutori',
                'tutori.id',
                '=',
                'recenzije.tutor_id'
            )
            ->orderByDesc('recenzije.datum_recenzije')
            ->get();

        return response()->json([
            'uspjeh' => true,
            'recenzije' => $recenzije
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | GET /api/instruktori
    |--------------------------------------------------------------------------
    */
    public function instruktori(
        Request $request,
        DozvoleService $dozvoleService
    ) {
        $korisnikId = $this->prijavljeniKorisnikId($request);

        if (!$korisnikId) {
            return response()->json([
                'message' => 'Korisnik nije prijavljen.'
            ], 401);
        }

        if (
            !$this->imaDozvolu(
                $korisnikId,
                'pisanje_recenzije',
                $dozvoleService
            )
        ) {
            return response()->json([
                'message' => 'Nemate dozvolu za pisanje recenzije.'
            ], 403);
        }

        $instruktori = DB::table('rezervacije')
            ->distinct()
            ->select([
                'tutori.id',
                'tutori.ime',
                'tutori.prezime',
            ])
            ->join(
                'termini',
                'termini.id',
                '=',
                'rezervacije.termin_id'
            )
            ->join(
                'korisnici as tutori',
                'tutori.id',
                '=',
                'termini.tutor_id'
            )
            ->join(
                'korisnik_uloga',
                'korisnik_uloga.korisnik_id',
                '=',
                'tutori.id'
            )
            ->join(
                'uloge',
                'uloge.id',
                '=',
                'korisnik_uloga.uloga_id'
            )
            ->where(
                'rezervacije.student_id',
                $korisnikId
            )
            ->where(
                'rezervacije.status',
                'prihvaćeno'
            )
            ->where(
                'uloge.naziv',
                'Tutor'
            )
            ->where(
                'tutori.status',
                'aktivan'
            )
            ->orderBy(
                'tutori.prezime',
                'asc'
            )
            ->orderBy(
                'tutori.ime',
                'asc'
            )
            ->get();

        return response()->json([
            'uspjeh' => true,
            'instruktori' => $instruktori
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | GET /api/recenzije/{id}
    |--------------------------------------------------------------------------
    */
    public function show(
        Request $request,
        $id,
        DozvoleService $dozvoleService
    ) {
        $korisnikId = $this->prijavljeniKorisnikId($request);

        if (
            $korisnikId &&
            !$this->imaDozvolu(
                $korisnikId,
                'pregled_recenzija',
                $dozvoleService
            )
        ) {
            return response()->json([
                'message' => 'Nemate dozvolu za pregled recenzija.'
            ], 403);
        }

        $recenzija = Recenzija::query()
            ->select([
                'recenzije.id',
                'recenzije.tutor_id',
                'recenzije.student_id',
                'recenzije.ocjena',
                'recenzije.komentar',
                'recenzije.datum_recenzije',

                'studenti.ime as student_ime',
                'studenti.prezime as student_prezime',

                'tutori.ime as tutor_ime',
                'tutori.prezime as tutor_prezime',
            ])
            ->join(
                'korisnici as studenti',
                'studenti.id',
                '=',
                'recenzije.student_id'
            )
            ->join(
                'korisnici as tutori',
                'tutori.id',
                '=',
                'recenzije.tutor_id'
            )
            ->where('recenzije.id', $id)
            ->first();

        if (!$recenzija) {
            return response()->json([
                'message' => 'Recenzija nije pronađena.'
            ], 404);
        }

        return response()->json([
            'uspjeh' => true,
            'recenzija' => $recenzija
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | POST /api/recenzije
    |--------------------------------------------------------------------------
    */
    public function store(
        Request $request,
        DozvoleService $dozvoleService
    ) {
        $korisnikId = $this->prijavljeniKorisnikId($request);

        if (!$korisnikId) {
            return response()->json([
                'message' => 'Korisnik nije prijavljen.'
            ], 401);
        }

        if (
            !$this->imaDozvolu(
                $korisnikId,
                'pisanje_recenzije',
                $dozvoleService
            )
        ) {
            return response()->json([
                'message' => 'Nemate dozvolu za pisanje recenzije.'
            ], 403);
        }

        $tutorId = $request->input('tutor_id');
        $ocjena = $request->input('ocjena');
        $komentar = trim(
            (string) $request->input('komentar', '')
        );

        if (
            empty($tutorId) ||
            $ocjena === null ||
            $komentar === ''
        ) {
            return response()->json([
                'message' =>
                    'Tutor, ocjena i komentar su obavezni.'
            ], 422);
        }

        $ocjena = (int) $ocjena;

        if ($ocjena < 1 || $ocjena > 5) {
            return response()->json([
                'message' => 'Ocjena mora biti između 1 i 5.'
            ], 422);
        }

        /*
         * Provjera da korisnik zaista ima ulogu Tutor.
         */
        $tutor = DB::table('korisnici')
            ->select('korisnici.id')
            ->join(
                'korisnik_uloga',
                'korisnik_uloga.korisnik_id',
                '=',
                'korisnici.id'
            )
            ->join(
                'uloge',
                'uloge.id',
                '=',
                'korisnik_uloga.uloga_id'
            )
            ->where(
                'korisnici.id',
                (int) $tutorId
            )
            ->where(
                'uloge.naziv',
                'Tutor'
            )
            ->first();

        if (!$tutor) {
            return response()->json([
                'message' => 'Tutor nije pronađen.'
            ], 404);
        }

        if ((int) $tutorId === $korisnikId) {
            return response()->json([
                'message' =>
                    'Ne možete napisati recenziju sami sebi.'
            ], 422);
        }

        $recenzija = Recenzija::create([
            'tutor_id' => (int) $tutorId,
            'student_id' => $korisnikId,
            'ocjena' => $ocjena,
            'komentar' => $komentar,
        ]);

        return response()->json([
            'uspjeh' => true,
            'poruka' => 'Recenzija je uspješno dodana.',
            'id' => $recenzija->id
        ], 201);
    }

    /*
    |--------------------------------------------------------------------------
    | PUT /api/recenzije/{id}
    |--------------------------------------------------------------------------
    */
    public function update(
        Request $request,
        $id,
        DozvoleService $dozvoleService
    ) {
        $korisnikId = $this->prijavljeniKorisnikId($request);

        if (!$korisnikId) {
            return response()->json([
                'message' => 'Korisnik nije prijavljen.'
            ], 401);
        }

        if (
            !$this->imaDozvolu(
                $korisnikId,
                'pisanje_recenzije',
                $dozvoleService
            )
        ) {
            return response()->json([
                'message' =>
                    'Nemate dozvolu za uređivanje recenzije.'
            ], 403);
        }

        $recenzija = Recenzija::find($id);

        if (!$recenzija) {
            return response()->json([
                'message' => 'Recenzija nije pronađena.'
            ], 404);
        }

        if (
            (int) $recenzija->student_id !==
            $korisnikId
        ) {
            return response()->json([
                'message' =>
                    'Možete uređivati samo vlastitu recenziju.'
            ], 403);
        }

        $podaci = [];

        if ($request->has('ocjena')) {
            $ocjena = (int) $request->input('ocjena');

            if ($ocjena < 1 || $ocjena > 5) {
                return response()->json([
                    'message' =>
                        'Ocjena mora biti između 1 i 5.'
                ], 422);
            }

            $podaci['ocjena'] = $ocjena;
        }

        if ($request->has('komentar')) {
            $komentar = trim(
                (string) $request->input('komentar')
            );

            if ($komentar === '') {
                return response()->json([
                    'message' =>
                        'Komentar ne može biti prazan.'
                ], 422);
            }

            $podaci['komentar'] = $komentar;
        }

        if (empty($podaci)) {
            return response()->json([
                'message' =>
                    'Nisu poslani podaci za izmjenu.'
            ], 422);
        }

        $recenzija->update($podaci);

        return response()->json([
            'uspjeh' => true,
            'poruka' =>
                'Recenzija je uspješno uređena.'
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | DELETE /api/recenzije/{id}
    |--------------------------------------------------------------------------
    */
    public function destroy(
        Request $request,
        $id,
        DozvoleService $dozvoleService
    ) {
        $korisnikId = $this->prijavljeniKorisnikId($request);

        if (!$korisnikId) {
            return response()->json([
                'message' => 'Korisnik nije prijavljen.'
            ], 401);
        }

        $recenzija = Recenzija::find($id);

        if (!$recenzija) {
            return response()->json([
                'message' => 'Recenzija nije pronađena.'
            ], 404);
        }

        $jeAdmin = $this->imaDozvolu(
            $korisnikId,
            'upravljanje_korisnicima',
            $dozvoleService
        );

        $jeVlasnik =
            (int) $recenzija->student_id ===
            $korisnikId;

        if (!$jeAdmin && !$jeVlasnik) {
            return response()->json([
                'message' =>
                    'Ne možete obrisati tuđu recenziju.'
            ], 403);
        }

        $recenzija->delete();

        return response()->json([
            'uspjeh' => true,
            'poruka' =>
                'Recenzija je uspješno obrisana.'
        ]);
    }
}