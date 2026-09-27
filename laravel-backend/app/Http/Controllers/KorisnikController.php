<?php

namespace App\Http\Controllers;

use App\Models\Korisnik;
use App\Services\DozvoleService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class KorisnikController extends Controller
{
    private function prijavljeniKorisnikId(Request $request): ?int
    {
        if (!$request->session()->get('prijavljen')) {
            return null;
        }

        return (int) $request->session()->get('korisnik_id');
    }

    private function provjeriPristup(
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
            !$dozvoleService->korisnikImaDozvolu(
                $korisnikId,
                'upravljanje_korisnicima'
            )
        ) {
            return response()->json([
                'message' =>
                    'Nemate dozvolu za upravljanje korisnicima.'
            ], 403);
        }

        return null;
    }

    private function dohvatiUlogaId(string $uloga): ?int
    {
        $nazivUloge = match ($uloga) {
            'Admin' => 'Administrator',
            'Tutor' => 'Tutor',
            'Student' => 'Student',
            default => null
        };

        if (!$nazivUloge) {
            return null;
        }

        $id = DB::table('uloge')
            ->where('naziv', $nazivUloge)
            ->value('id');

        return $id ? (int) $id : null;
    }

    private function postaviUloguKorisniku(
        int $korisnikId,
        string $uloga
    ): bool {
        $ulogaId = $this->dohvatiUlogaId($uloga);

        if (!$ulogaId) {
            return false;
        }

        DB::table('korisnik_uloga')
            ->where('korisnik_id', $korisnikId)
            ->delete();

        DB::table('korisnik_uloga')->insert([
            'korisnik_id' => $korisnikId,
            'uloga_id' => $ulogaId
        ]);

        return true;
    }

    /*
    |--------------------------------------------------------------------------
    | GET /api/korisnici
    |--------------------------------------------------------------------------
    */
    public function index(
        Request $request,
        DozvoleService $dozvoleService
    ) {
        if ($zabrana = $this->provjeriPristup(
            $request,
            $dozvoleService
        )) {
            return $zabrana;
        }

        $korisnici = DB::table('korisnici')
            ->select([
                'korisnici.id',
                'korisnici.ime',
                'korisnici.prezime',
                'korisnici.email',
                'korisnici.status',
                'korisnici.datum_registracije',
                'korisnici.uloga as legacy_uloga',
                'uloge.naziv as normalizirana_uloga',
            ])
            ->leftJoin(
                'korisnik_uloga',
                'korisnik_uloga.korisnik_id',
                '=',
                'korisnici.id'
            )
            ->leftJoin(
                'uloge',
                'uloge.id',
                '=',
                'korisnik_uloga.uloga_id'
            )
            ->orderBy('korisnici.id')
            ->get()
            ->map(function ($korisnik) {

                if (
                    $korisnik->normalizirana_uloga ===
                    'Super Administrator'
                ) {
                    $korisnik->uloga = 'Super Administrator';

                } elseif (
                    $korisnik->normalizirana_uloga ===
                    'Administrator'
                ) {
                    $korisnik->uloga = 'Admin';

                } elseif (
                    $korisnik->normalizirana_uloga ===
                    'Tutor'
                ) {
                    $korisnik->uloga = 'Tutor';

                } elseif (
                    $korisnik->normalizirana_uloga ===
                    'Student'
                ) {
                    $korisnik->uloga = 'Student';

                } else {
                    $korisnik->uloga =
                        $korisnik->legacy_uloga ?? 'Student';
                }

                unset(
                    $korisnik->legacy_uloga,
                    $korisnik->normalizirana_uloga
                );

                return $korisnik;
            });

        return response()->json([
            'uspjeh' => true,
            'korisnici' => $korisnici
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | GET /api/korisnici/{id}
    |--------------------------------------------------------------------------
    */
    public function show(
        Request $request,
        $id,
        DozvoleService $dozvoleService
    ) {
        if ($zabrana = $this->provjeriPristup(
            $request,
            $dozvoleService
        )) {
            return $zabrana;
        }

        $korisnik = DB::table('korisnici')
            ->select([
                'korisnici.id',
                'korisnici.ime',
                'korisnici.prezime',
                'korisnici.email',
                'korisnici.status',
                'korisnici.datum_registracije',
                'korisnici.uloga as legacy_uloga',
                'uloge.naziv as normalizirana_uloga',
            ])
            ->leftJoin(
                'korisnik_uloga',
                'korisnik_uloga.korisnik_id',
                '=',
                'korisnici.id'
            )
            ->leftJoin(
                'uloge',
                'uloge.id',
                '=',
                'korisnik_uloga.uloga_id'
            )
            ->where('korisnici.id', $id)
            ->first();

        if (!$korisnik) {
            return response()->json([
                'message' => 'Korisnik nije pronađen.'
            ], 404);
        }

        if (
            $korisnik->normalizirana_uloga ===
            'Super Administrator'
        ) {
            $korisnik->uloga = 'Super Administrator';

        } elseif (
            $korisnik->normalizirana_uloga ===
            'Administrator'
        ) {
            $korisnik->uloga = 'Admin';

        } elseif (
            $korisnik->normalizirana_uloga === 'Tutor'
        ) {
            $korisnik->uloga = 'Tutor';

        } elseif (
            $korisnik->normalizirana_uloga === 'Student'
        ) {
            $korisnik->uloga = 'Student';

        } else {
            $korisnik->uloga =
                $korisnik->legacy_uloga ?? 'Student';
        }

        unset(
            $korisnik->legacy_uloga,
            $korisnik->normalizirana_uloga
        );

        return response()->json([
            'uspjeh' => true,
            'korisnik' => $korisnik
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | POST /api/korisnici
    |--------------------------------------------------------------------------
    */
    public function store(
        Request $request,
        DozvoleService $dozvoleService
    ) {
        if ($zabrana = $this->provjeriPristup(
            $request,
            $dozvoleService
        )) {
            return $zabrana;
        }

        if (
            empty($request->input('ime')) ||
            empty($request->input('prezime')) ||
            empty($request->input('email')) ||
            empty($request->input('lozinka'))
        ) {
            return response()->json([
                'message' =>
                    'Ime, prezime, email i lozinka su obavezni.'
            ], 422);
        }

        $email = trim($request->input('email'));

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return response()->json([
                'message' => 'Email adresa nije ispravna.'
            ], 422);
        }

        if (Korisnik::where('email', $email)->exists()) {
            return response()->json([
                'message' =>
                    'Korisnik s ovom email adresom već postoji.'
            ], 422);
        }

        $uloga = $request->input('uloga', 'Student');
        $status = $request->input('status', 'aktivan');

        if (!in_array(
            $uloga,
            ['Student', 'Tutor', 'Admin'],
            true
        )) {
            return response()->json([
                'message' => 'Uloga nije ispravna.'
            ], 422);
        }

        DB::beginTransaction();

        try {
            $korisnik = Korisnik::create([
                'ime' => trim($request->input('ime')),
                'prezime' => trim($request->input('prezime')),
                'email' => $email,
                'lozinka' => password_hash(
                    $request->input('lozinka'),
                    PASSWORD_DEFAULT
                ),
                'uloga' => $uloga,
                'status' => $status,
            ]);

            if (
                !$this->postaviUloguKorisniku(
                    $korisnik->id,
                    $uloga
                )
            ) {
                throw new \Exception(
                    'Uloga korisnika nije mogla biti povezana.'
                );
            }

            DB::commit();

            return response()->json([
                'uspjeh' => true,
                'poruka' => 'Korisnik je uspješno dodan.',
                'id' => $korisnik->id
            ], 201);

        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Greška pri spremanju korisnika.'
            ], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | PUT/PATCH /api/korisnici/{id}
    |--------------------------------------------------------------------------
    */
    public function update(
        Request $request,
        $id,
        DozvoleService $dozvoleService
    ) {
        if ($zabrana = $this->provjeriPristup(
            $request,
            $dozvoleService
        )) {
            return $zabrana;
        }

        $korisnik = Korisnik::find($id);

        if (!$korisnik) {
            return response()->json([
                'message' => 'Korisnik nije pronađen.'
            ], 404);
        }

        $podaci = [];
        $novaUloga = null;

        if ($request->has('ime')) {
            $ime = trim((string) $request->input('ime'));

            if ($ime === '') {
                return response()->json([
                    'message' => 'Ime ne može biti prazno.'
                ], 422);
            }

            $podaci['ime'] = $ime;
        }

        if ($request->has('prezime')) {
            $prezime = trim(
                (string) $request->input('prezime')
            );

            if ($prezime === '') {
                return response()->json([
                    'message' => 'Prezime ne može biti prazno.'
                ], 422);
            }

            $podaci['prezime'] = $prezime;
        }

        if ($request->has('email')) {
            $email = trim(
                (string) $request->input('email')
            );

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return response()->json([
                    'message' =>
                        'Email adresa nije ispravna.'
                ], 422);
            }

            $postojeci = Korisnik::where('email', $email)
                ->where('id', '!=', $id)
                ->exists();

            if ($postojeci) {
                return response()->json([
                    'message' =>
                        'Korisnik s ovom email adresom već postoji.'
                ], 422);
            }

            $podaci['email'] = $email;
        }

        if ($request->filled('lozinka')) {
            $podaci['lozinka'] = password_hash(
                $request->input('lozinka'),
                PASSWORD_DEFAULT
            );
        }

        if ($request->has('uloga')) {
            $novaUloga = $request->input('uloga');

            if (!in_array(
                $novaUloga,
                ['Student', 'Tutor', 'Admin'],
                true
            )) {
                return response()->json([
                    'message' => 'Uloga nije ispravna.'
                ], 422);
            }

            $podaci['uloga'] = $novaUloga;
        }

        if ($request->has('status')) {
            $podaci['status'] =
                $request->input('status');
        }

        if (empty($podaci) && !$novaUloga) {
            return response()->json([
                'message' =>
                    'Nisu poslani podaci za izmjenu.'
            ], 422);
        }

        DB::beginTransaction();

        try {
            if (!empty($podaci)) {
                $korisnik->update($podaci);
            }

            if ($novaUloga) {
                if (
                    !$this->postaviUloguKorisniku(
                        (int) $id,
                        $novaUloga
                    )
                ) {
                    throw new \Exception(
                        'Uloga korisnika nije mogla biti ažurirana.'
                    );
                }
            }

            DB::commit();

            return response()->json([
                'uspjeh' => true,
                'poruka' =>
                    'Korisnik je uspješno uređen.'
            ]);

        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'message' =>
                    'Greška pri spremanju izmjena korisnika.'
            ], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | DELETE /api/korisnici/{id}
    |--------------------------------------------------------------------------
    */
    public function destroy(
        Request $request,
        $id,
        DozvoleService $dozvoleService
    ) {
        if ($zabrana = $this->provjeriPristup(
            $request,
            $dozvoleService
        )) {
            return $zabrana;
        }

        $korisnik = Korisnik::find($id);

        if (!$korisnik) {
            return response()->json([
                'message' => 'Korisnik nije pronađen.'
            ], 404);
        }

        $prijavljeniId =
            $this->prijavljeniKorisnikId($request);

        if ($prijavljeniId === (int) $id) {
            return response()->json([
                'message' =>
                    'Ne možete obrisati vlastiti korisnički račun.'
            ], 403);
        }

        DB::beginTransaction();

        try {
            DB::table('korisnik_uloga')
                ->where('korisnik_id', $id)
                ->delete();

            $korisnik->delete();

            DB::commit();

            return response()->json([
                'uspjeh' => true,
                'poruka' =>
                    'Korisnik je uspješno obrisan.'
            ]);

        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'message' =>
                    'Korisnika nije moguće obrisati.'
            ], 500);
        }
    }
}