<?php

namespace App\Http\Controllers;

use App\Services\DozvoleService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UlogeDozvoleController extends Controller
{
    private function provjeriPristup(
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
            'upravljanje_uloga_dozvola'
        )) {
            return response()->json([
                'message' => 'Nemate dozvolu za upravljanje ulogama i dozvolama.'
            ], 403);
        }

        return null;
    }

    public function uloge(
        Request $request,
        DozvoleService $dozvoleService
    ) {
        if ($odgovor = $this->provjeriPristup($request, $dozvoleService)) {
            return $odgovor;
        }

        $uloge = DB::table('uloge')
            ->orderBy('id')
            ->get();

        return response()->json([
            'uspjeh' => true,
            'uloge' => $uloge
        ]);
    }

    public function createUloga(
        Request $request,
        DozvoleService $dozvoleService
    ) {
        if ($odgovor = $this->provjeriPristup($request, $dozvoleService)) {
            return $odgovor;
        }

        $naziv = trim((string) $request->input('naziv', ''));

        if ($naziv === '') {
            return response()->json([
                'message' => 'Naziv uloge je obavezan.'
            ], 422);
        }

        if (DB::table('uloge')->where('naziv', $naziv)->exists()) {
            return response()->json([
                'message' => 'Uloga s tim nazivom već postoji.'
            ], 422);
        }

        $id = DB::table('uloge')->insertGetId([
            'naziv' => $naziv
        ]);

        return response()->json([
            'uspjeh' => true,
            'poruka' => 'Uloga je uspješno dodana.',
            'id' => $id
        ], 201);
    }

    public function updateUloga(
        Request $request,
        $id,
        DozvoleService $dozvoleService
    ) {
        if ($odgovor = $this->provjeriPristup($request, $dozvoleService)) {
            return $odgovor;
        }

        $uloga = DB::table('uloge')->where('id', $id)->first();

        if (!$uloga) {
            return response()->json([
                'message' => 'Uloga nije pronađena.'
            ], 404);
        }

        $naziv = trim((string) $request->input('naziv', ''));

        if ($naziv === '') {
            return response()->json([
                'message' => 'Naziv uloge je obavezan.'
            ], 422);
        }

        $postoji = DB::table('uloge')
            ->where('naziv', $naziv)
            ->where('id', '!=', $id)
            ->exists();

        if ($postoji) {
            return response()->json([
                'message' => 'Uloga s tim nazivom već postoji.'
            ], 422);
        }

        DB::table('uloge')
            ->where('id', $id)
            ->update(['naziv' => $naziv]);

        return response()->json([
            'uspjeh' => true,
            'poruka' => 'Uloga je uspješno uređena.'
        ]);
    }

    public function deleteUloga(
        Request $request,
        $id,
        DozvoleService $dozvoleService
    ) {
        if ($odgovor = $this->provjeriPristup($request, $dozvoleService)) {
            return $odgovor;
        }

        $uloga = DB::table('uloge')->where('id', $id)->first();

        if (!$uloga) {
            return response()->json([
                'message' => 'Uloga nije pronađena.'
            ], 404);
        }

        $koristiSe = DB::table('korisnik_uloga')
            ->where('uloga_id', $id)
            ->exists();

        if ($koristiSe) {
            return response()->json([
                'message' =>
                    'Uloga se ne može obrisati jer je dodijeljena jednom ili više korisnika.'
            ], 422);
        }

        DB::transaction(function () use ($id) {
            DB::table('uloga_dozvola')
                ->where('uloga_id', $id)
                ->delete();

            DB::table('uloge')
                ->where('id', $id)
                ->delete();
        });

        return response()->json([
            'uspjeh' => true,
            'poruka' => 'Uloga je uspješno obrisana.'
        ]);
    }

    public function dozvole(
        Request $request,
        DozvoleService $dozvoleService
    ) {
        if ($odgovor = $this->provjeriPristup($request, $dozvoleService)) {
            return $odgovor;
        }

        $dozvole = DB::table('dozvole')
            ->orderBy('id')
            ->get();

        return response()->json([
            'uspjeh' => true,
            'dozvole' => $dozvole
        ]);
    }

    public function createDozvola(
        Request $request,
        DozvoleService $dozvoleService
    ) {
        if ($odgovor = $this->provjeriPristup($request, $dozvoleService)) {
            return $odgovor;
        }

        $naziv = trim((string) $request->input('naziv', ''));

        if ($naziv === '') {
            return response()->json([
                'message' => 'Naziv dozvole je obavezan.'
            ], 422);
        }

        if (DB::table('dozvole')->where('naziv', $naziv)->exists()) {
            return response()->json([
                'message' => 'Dozvola s tim nazivom već postoji.'
            ], 422);
        }

        $id = DB::table('dozvole')->insertGetId([
            'naziv' => $naziv
        ]);

        return response()->json([
            'uspjeh' => true,
            'poruka' => 'Dozvola je uspješno dodana.',
            'id' => $id
        ], 201);
    }

    public function updateDozvola(
        Request $request,
        $id,
        DozvoleService $dozvoleService
    ) {
        if ($odgovor = $this->provjeriPristup($request, $dozvoleService)) {
            return $odgovor;
        }

        $dozvola = DB::table('dozvole')->where('id', $id)->first();

        if (!$dozvola) {
            return response()->json([
                'message' => 'Dozvola nije pronađena.'
            ], 404);
        }

        $naziv = trim((string) $request->input('naziv', ''));

        if ($naziv === '') {
            return response()->json([
                'message' => 'Naziv dozvole je obavezan.'
            ], 422);
        }

        $postoji = DB::table('dozvole')
            ->where('naziv', $naziv)
            ->where('id', '!=', $id)
            ->exists();

        if ($postoji) {
            return response()->json([
                'message' => 'Dozvola s tim nazivom već postoji.'
            ], 422);
        }

        DB::table('dozvole')
            ->where('id', $id)
            ->update(['naziv' => $naziv]);

        return response()->json([
            'uspjeh' => true,
            'poruka' => 'Dozvola je uspješno uređena.'
        ]);
    }

    public function deleteDozvola(
        Request $request,
        $id,
        DozvoleService $dozvoleService
    ) {
        if ($odgovor = $this->provjeriPristup($request, $dozvoleService)) {
            return $odgovor;
        }

        $dozvola = DB::table('dozvole')->where('id', $id)->first();

        if (!$dozvola) {
            return response()->json([
                'message' => 'Dozvola nije pronađena.'
            ], 404);
        }

        DB::transaction(function () use ($id) {
            DB::table('uloga_dozvola')
                ->where('dozvola_id', $id)
                ->delete();

            DB::table('dozvole')
                ->where('id', $id)
                ->delete();
        });

        return response()->json([
            'uspjeh' => true,
            'poruka' => 'Dozvola je uspješno obrisana.'
        ]);
    }

    public function dozvoleUloge(
        Request $request,
        $ulogaId,
        DozvoleService $dozvoleService
    ) {
        if ($odgovor = $this->provjeriPristup($request, $dozvoleService)) {
            return $odgovor;
        }

        $uloga = DB::table('uloge')
            ->where('id', $ulogaId)
            ->first();

        if (!$uloga) {
            return response()->json([
                'message' => 'Uloga nije pronađena.'
            ], 404);
        }

        $dozvole = DB::table('uloga_dozvola')
            ->where('uloga_id', $ulogaId)
            ->pluck('dozvola_id')
            ->map(fn ($id) => (int) $id)
            ->values();

        return response()->json([
            'uspjeh' => true,
            'uloga' => $uloga,
            'dozvole' => $dozvole
        ]);
    }

    public function spremiDozvoleUloge(
        Request $request,
        $ulogaId,
        DozvoleService $dozvoleService
    ) {
        if ($odgovor = $this->provjeriPristup($request, $dozvoleService)) {
            return $odgovor;
        }

        $uloga = DB::table('uloge')
            ->where('id', $ulogaId)
            ->first();

        if (!$uloga) {
            return response()->json([
                'message' => 'Uloga nije pronađena.'
            ], 404);
        }

        $dozvole = $request->input('dozvole', []);

        if (!is_array($dozvole)) {
            return response()->json([
                'message' => 'Dozvole moraju biti poslane kao polje.'
            ], 422);
        }

        $dozvole = array_values(array_unique(array_map(
            'intval',
            $dozvole
        )));

        if (!empty($dozvole)) {
            $brojPostojecih = DB::table('dozvole')
                ->whereIn('id', $dozvole)
                ->count();

            if ($brojPostojecih !== count($dozvole)) {
                return response()->json([
                    'message' =>
                        'Jedna ili više odabranih dozvola ne postoje.'
                ], 422);
            }
        }

        DB::transaction(function () use ($ulogaId, $dozvole) {
            DB::table('uloga_dozvola')
                ->where('uloga_id', $ulogaId)
                ->delete();

            if (!empty($dozvole)) {
                $redovi = [];

                foreach ($dozvole as $dozvolaId) {
                    $redovi[] = [
                        'uloga_id' => (int) $ulogaId,
                        'dozvola_id' => $dozvolaId,
                    ];
                }

                DB::table('uloga_dozvola')->insert($redovi);
            }
        });

        return response()->json([
            'uspjeh' => true,
            'poruka' => 'Dozvole uloge su uspješno spremljene.'
        ]);
    }
}