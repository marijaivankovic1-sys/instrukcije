<?php

namespace App\Http\Controllers;

use App\Models\Korisnik;
use App\Services\DozvoleService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AuthController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Registracija
    |--------------------------------------------------------------------------
    */
    public function registracija(Request $request)
    {
        $data = $request->all();

        if (
            empty($data['ime']) ||
            empty($data['prezime']) ||
            empty($data['email']) ||
            empty($data['lozinka'])
        ) {
            return response()->json([
                'message' =>
                    'Ime, prezime, email i lozinka su obavezni.'
            ], 422);
        }

        $email = trim($data['email']);

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return response()->json([
                'message' => 'Email adresa nije ispravna.'
            ], 422);
        }

        $postojeci = Korisnik::where(
            'email',
            $email
        )->first();

        if ($postojeci) {
            return response()->json([
                'message' =>
                    'Korisnik s ovom email adresom već postoji.'
            ], 422);
        }

        DB::beginTransaction();

        try {
            /*
             * Kreiranje korisnika.
             */
            $korisnik = Korisnik::create([
                'ime' => trim($data['ime']),
                'prezime' => trim($data['prezime']),
                'email' => $email,
                'lozinka' => password_hash(
                    $data['lozinka'],
                    PASSWORD_DEFAULT
                ),
                'uloga' => 'Student',
                'status' => 'aktivan',
            ]);

            /*
             * Pronalazimo normaliziranu Student ulogu.
             */
            $studentUlogaId = DB::table('uloge')
                ->where('naziv', 'Student')
                ->value('id');

            if (!$studentUlogaId) {
                throw new \Exception(
                    'Uloga Student nije pronađena.'
                );
            }

            /*
             * Povezujemo novog korisnika s ulogom Student.
             *
             * DozvoleService koristi tablicu korisnik_uloga,
             * pa je ovaj zapis potreban kako bi novi korisnik
             * imao dozvole svoje uloge.
             */
            DB::table('korisnik_uloga')->insert([
                'korisnik_id' => $korisnik->id,
                'uloga_id' => $studentUlogaId,
            ]);

            DB::commit();

            return response()->json([
                'uspjeh' => true,
                'poruka' => 'Registracija je uspješna.',
                'id' => $korisnik->id
            ], 201);

        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'message' =>
                    'Greška prilikom registracije korisnika.'
            ], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Prijava
    |--------------------------------------------------------------------------
    */
   public function prijava(Request $request)
{
    $data = $request->all();

    if (
        empty($data['email']) ||
        empty($data['lozinka'])
    ) {
        return response()->json([
            'message' =>
                'Email i lozinka su obavezni.'
        ], 422);
    }

    $korisnik = Korisnik::where(
        'email',
        trim($data['email'])
    )->first();

    if (
        !$korisnik ||
        !password_verify(
            $data['lozinka'],
            $korisnik->lozinka
        )
    ) {
        return response()->json([
            'message' =>
                'Email ili lozinka nisu ispravni.'
        ], 401);
    }

    if ($korisnik->status !== 'aktivan') {
        return response()->json([
            'message' =>
                'Korisnički račun nije aktivan.'
        ], 403);
    }

    /*
     * Dohvaćamo stvarnu ulogu korisnika
     * iz normaliziranih tablica.
     */
    $uloga = DB::table('korisnik_uloga')
        ->join(
            'uloge',
            'korisnik_uloga.uloga_id',
            '=',
            'uloge.id'
        )
        ->where(
            'korisnik_uloga.korisnik_id',
            $korisnik->id
        )
        ->value('uloge.naziv');

    /*
     * Ako korisnik nema dodijeljenu ulogu,
     * prijava se ne dopušta.
     */
    if (!$uloga) {
        return response()->json([
            'message' =>
                'Korisniku nije dodijeljena uloga.'
        ], 403);
    }

    /*
     * Spremamo podatke prijavljenog korisnika
     * u Laravel session.
     */
    $request->session()->put([
        'korisnik_id' => $korisnik->id,
        'ime' => $korisnik->ime,
        'prezime' => $korisnik->prezime,
        'email' => $korisnik->email,
        'uloga' => $uloga,
        'prijavljen' => true,
    ]);

    return response()->json([
        'uspjeh' => true,
        'poruka' => 'Prijava je uspješna.',
        'korisnik' => [
            'id' => $korisnik->id,
            'ime' => $korisnik->ime,
            'prezime' => $korisnik->prezime,
            'email' => $korisnik->email,
            'uloga' => $uloga,
        ]
    ]);
}


    /*
    |--------------------------------------------------------------------------
    | Odjava
    |--------------------------------------------------------------------------
    */
    public function odjava(Request $request)
    {
        $request->session()->flush();

        return response()->json([
            'uspjeh' => true,
            'poruka' => 'Odjava je uspješna.'
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Trenutno prijavljeni korisnik
    |--------------------------------------------------------------------------
    */
    public function ja(Request $request)
    {
        if (!$request->session()->get('prijavljen')) {
            return response()->json([
                'uspjeh' => true,
                'prijavljen' => false,
                'korisnik' => null
            ]);
        }

        return response()->json([
            'uspjeh' => true,
            'prijavljen' => true,
            'korisnik' => [
                'id' =>
                    $request->session()->get('korisnik_id'),

                'ime' =>
                    $request->session()->get('ime'),

                'prezime' =>
                    $request->session()->get('prezime'),

                'email' =>
                    $request->session()->get('email'),

                'uloga' =>
                    $request->session()->get('uloga'),
            ]
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Dozvole trenutno prijavljenog korisnika
    |--------------------------------------------------------------------------
    */
    public function dozvole(
        Request $request,
        DozvoleService $dozvoleService
    ) {
        if (!$request->session()->get('prijavljen')) {
            return response()->json([
                'message' =>
                    'Korisnik nije prijavljen.'
            ], 401);
        }

        $korisnikId = (int)
            $request->session()->get('korisnik_id');

        $dozvole = $dozvoleService
            ->dohvatiDozvoleKorisnika($korisnikId);

        return response()->json([
            'uspjeh' => true,
            'korisnik_id' => $korisnikId,
            'dozvole' => $dozvole
        ]);
    }
}