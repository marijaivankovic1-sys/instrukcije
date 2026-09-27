<?php



namespace App\Http\Controllers;


use App\Models\Korisnik;
use App\Models\Rezervacija;
use App\Services\DozvoleService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;



class RezervacijaController extends Controller

{

    /*

    |--------------------------------------------------------------------------

    | GET /api/rezervacije

    |--------------------------------------------------------------------------

    */

    public function index(

        Request $request,

        DozvoleService $dozvoleService

    ) {

        if (!$request->session()->get('prijavljen')) {

            return response()->json([

                'message' => 'Korisnik nije prijavljen.'

            ], 401);

        }



        $korisnikId = (int) $request->session()->get('korisnik_id');



        $query = Rezervacija::query()

            ->select([

                'rezervacije.id',

                'rezervacije.termin_id',

                'rezervacije.student_id',

                'rezervacije.napomena',

                'rezervacije.privitak_putanja',

                'rezervacije.status',

                'rezervacije.datum_rezervacije',



                'termini.datum',

                'termini.vrijeme_od',

                'termini.vrijeme_do',

                'termini.cijena',

                'termini.tutor_id',



                'predmeti.naziv as predmet_naziv',



                'studenti.ime as student_ime',

                'studenti.prezime as student_prezime',



                'tutori.ime as tutor_ime',

                'tutori.prezime as tutor_prezime',

            ])

            ->join(

                'termini',

                'termini.id',

                '=',

                'rezervacije.termin_id'

            )

            ->join(

                'predmeti',

                'predmeti.id',

                '=',

                'termini.predmet_id'

            )

            ->join(

                'korisnici as studenti',

                'studenti.id',

                '=',

                'rezervacije.student_id'

            )

            ->join(

                'korisnici as tutori',

                'tutori.id',

                '=',

                'termini.tutor_id'

            );



        // Administrator vidi sve rezervacije.

        if (

            $dozvoleService->korisnikImaDozvolu(

                $korisnikId,

                'pregled_svih_rezervacija'

            )

        ) {

            return response()->json([

                'uspjeh' => true,

                'rezervacije' => $query->get()

            ]);

        }



        // Tutor vidi rezervacije svojih termina.

        if (

            $dozvoleService->korisnikImaDozvolu(

                $korisnikId,

                'obrada_rezervacija'

            )

        ) {

            return response()->json([

                'uspjeh' => true,

                'rezervacije' => $query

                    ->where('termini.tutor_id', $korisnikId)

                    ->get()

            ]);

        }



        // Student vidi samo svoje rezervacije.

        if (

            $dozvoleService->korisnikImaDozvolu(

                $korisnikId,

                'pregled_vlastitih_rezervacija'

            )

        ) {

            return response()->json([

                'uspjeh' => true,

                'rezervacije' => $query

                    ->where('rezervacije.student_id', $korisnikId)

                    ->get()

            ]);

        }



        return response()->json([

            'message' => 'Nemate dozvolu za pregled rezervacija.'

        ], 403);

    }



    /*

    |--------------------------------------------------------------------------

    | GET /api/rezervacije/{id}

    |--------------------------------------------------------------------------

    */

    public function show(

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



        $rezervacija = Rezervacija::query()

            ->select([

                'rezervacije.id',

                'rezervacije.termin_id',

                'rezervacije.student_id',

                'rezervacije.napomena',

                'rezervacije.privitak_putanja',

                'rezervacije.status',

                'rezervacije.datum_rezervacije',



                'termini.datum',

                'termini.vrijeme_od',

                'termini.vrijeme_do',

                'termini.cijena',

                'termini.tutor_id',



                'predmeti.naziv as predmet_naziv',



                'studenti.ime as student_ime',

                'studenti.prezime as student_prezime',



                'tutori.ime as tutor_ime',

                'tutori.prezime as tutor_prezime',

            ])

            ->join(

                'termini',

                'termini.id',

                '=',

                'rezervacije.termin_id'

            )

            ->join(

                'predmeti',

                'predmeti.id',

                '=',

                'termini.predmet_id'

            )

            ->join(

                'korisnici as studenti',

                'studenti.id',

                '=',

                'rezervacije.student_id'

            )

            ->join(

                'korisnici as tutori',

                'tutori.id',

                '=',

                'termini.tutor_id'

            )

            ->where('rezervacije.id', $id)

            ->first();



        if (!$rezervacija) {

            return response()->json([

                'message' => 'Rezervacija nije pronađena.'

            ], 404);

        }



        // Administrator vidi sve.

        if (

            $dozvoleService->korisnikImaDozvolu(

                $korisnikId,

                'pregled_svih_rezervacija'

            )

        ) {

            return response()->json([

                'uspjeh' => true,

                'rezervacija' => $rezervacija

            ]);

        }



        // Student vidi samo svoju rezervaciju.

        if (

            (int) $rezervacija->student_id === $korisnikId &&

            $dozvoleService->korisnikImaDozvolu(

                $korisnikId,

                'pregled_vlastitih_rezervacija'

            )

        ) {

            return response()->json([

                'uspjeh' => true,

                'rezervacija' => $rezervacija

            ]);

        }



        // Tutor vidi rezervaciju svog termina.

        if (

            (int) $rezervacija->tutor_id === $korisnikId &&

            $dozvoleService->korisnikImaDozvolu(

                $korisnikId,

                'obrada_rezervacija'

            )

        ) {

            return response()->json([

                'uspjeh' => true,

                'rezervacija' => $rezervacija

            ]);

        }



        return response()->json([

            'message' => 'Nemate dozvolu za pregled ove rezervacije.'

        ], 403);

    }



    /*

    |--------------------------------------------------------------------------

    | POST /api/rezervacije

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

        if (
            !$dozvoleService->korisnikImaDozvolu(
                $korisnikId,
                'rezerviranje_termina'
            )
        ) {
            return response()->json([
                'message' => 'Nemate dozvolu za rezerviranje termina.'
            ], 403);
        }

        // Student rezervira za sebe; administrator s pravom pregleda svih rezervacija
        // može poslati student_id i rezervirati termin za odabranog studenta.
        $studentId = $korisnikId;

        if (
            $dozvoleService->korisnikImaDozvolu(
                $korisnikId,
                'pregled_svih_rezervacija'
            ) &&
            $request->filled('student_id')
        ) {
            $studentId = (int) $request->input('student_id');

            $student = DB::table('korisnici')
                ->where('id', $studentId)
                ->first();

            if (!$student) {
                return response()->json([
                    'message' => 'Odabrani student ne postoji.'
                ], 422);
            }

            $jeStudent = DB::table('korisnik_uloga')
                ->join('uloge', 'uloge.id', '=', 'korisnik_uloga.uloga_id')
                ->where('korisnik_uloga.korisnik_id', $studentId)
                ->where('uloge.naziv', 'Student')
                ->exists();

            if (!$jeStudent) {
                return response()->json([
                    'message' => 'Odabrani korisnik nije student.'
                ], 422);
            }
        }

        $terminId = $request->input('termin_id');

        if (empty($terminId)) {
            return response()->json([
                'message' => 'Termin je obavezan.'
            ], 422);
        }

        $termin = DB::table('termini')
            ->where('id', $terminId)
            ->first();

        if (!$termin) {
            return response()->json([
                'message' => 'Termin nije pronađen.'
            ], 404);
        }

        if ($termin->status !== 'slobodan') {
            return response()->json([
                'message' => 'Ovaj termin više nije dostupan.'
            ], 422);
        }

        $postojeca = Rezervacija::where('termin_id', $terminId)
            ->where('student_id', $studentId)
            ->first();

        if ($postojeca) {
            return response()->json([
                'message' => 'Odabrani student već ima rezervaciju za ovaj termin.'
            ], 422);
        }

        $privitakPutanja = null;

        if ($request->hasFile('privitak')) {
            $privitak = $request->file('privitak');

            if (!$privitak->isValid()) {
                return response()->json([
                    'message' => 'PDF dokument nije moguće učitati.'
                ], 422);
            }

            if ($privitak->getSize() > 10 * 1024 * 1024) {
                return response()->json([
                    'message' => 'PDF dokument smije imati najviše 10 MB.'
                ], 422);
            }

            if (
                $privitak->getMimeType() !== 'application/pdf' ||
                strtolower($privitak->getClientOriginalExtension()) !== 'pdf'
            ) {
                return response()->json([
                    'message' => 'Dopušten je samo PDF dokument.'
                ], 422);
            }

            $odredisnaMapa = public_path('uploads/rezervacije');

            if (!File::exists($odredisnaMapa)) {
                File::makeDirectory($odredisnaMapa, 0775, true);
            }

            $novoIme = uniqid('rezervacija_', true) . '.pdf';
            $privitak->move($odredisnaMapa, $novoIme);
            $privitakPutanja = 'uploads/rezervacije/' . $novoIme;
        }

        $rezervacija = Rezervacija::create([
            'termin_id' => $terminId,
            'student_id' => $studentId,
            'napomena' => $request->input('napomena', ''),
            'privitak_putanja' => $privitakPutanja,
            'status' => 'na čekanju',
        ]);

        return response()->json([
            'uspjeh' => true,
            'poruka' => 'Rezervacija je uspješno poslana.',
            'id' => $rezervacija->id
        ], 201);
    }

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



        if (

            !$dozvoleService->korisnikImaDozvolu(

                $korisnikId,

                'obrada_rezervacija'

            )

        ) {

            return response()->json([

                'message' => 'Nemate dozvolu za obradu rezervacija.'

            ], 403);

        }



        $rezervacija = Rezervacija::query()

            ->select([

                'rezervacije.\*',

                'termini.tutor_id'

            ])

            ->join(

                'termini',

                'termini.id',

                '=',

                'rezervacije.termin_id'

            )

            ->where('rezervacije.id', $id)

            ->first();



        if (!$rezervacija) {

            return response()->json([

                'message' => 'Rezervacija nije pronađena.'

            ], 404);

        }



        // Tutor može obrađivati samo svoje termine.

        // Administrator može obrađivati sve.

        if (

            !$dozvoleService->korisnikImaDozvolu(

                $korisnikId,

                'pregled_svih_rezervacija'

            ) &&

            (int) $rezervacija->tutor_id !== $korisnikId

        ) {

            return response()->json([

                'message' =>

                    'Ne možete obrađivati rezervaciju tuđeg termina.'

            ], 403);

        }



        $status = $request->input('status');



        if (empty($status)) {

            return response()->json([

                'message' => 'Status rezervacije je obavezan.'

            ], 422);

        }



        $dozvoljeniStatusi = [

            'na čekanju',

            'prihvaćeno',

            'odbijeno',

        ];



        if (!in_array($status, $dozvoljeniStatusi, true)) {

            return response()->json([

                'message' => 'Status rezervacije nije ispravan.'

            ], 422);

        }



        try {

            DB::transaction(function () use (

                $rezervacija,

                $id,

                $status

            ) {

                Rezervacija::where('id', $id)

                    ->update([

                        'status' => $status

                    ]);



                /*

                |--------------------------------------------------------------

                | Prihvaćena rezervacija

                |--------------------------------------------------------------

                */



                if ($status === 'prihvaćeno') {

                    DB::table('termini')

                        ->where(

                            'id',

                            $rezervacija->termin_id

                        )

                        ->update([

                            'status' => 'rezerviran'

                        ]);



                    DB::table('rezervacije')

                        ->where(

                            'termin_id',

                            $rezervacija->termin_id

                        )

                        ->where('id', '!=', $id)

                        ->where('status', 'na čekanju')

                        ->update([

                            'status' => 'odbijeno'

                        ]);

                }



                /*

                |--------------------------------------------------------------

                | Odbijena rezervacija

                |--------------------------------------------------------------

                */



                if ($status === 'odbijeno') {

                    $postojiPrihvacena =

                        DB::table('rezervacije')

                            ->where(

                                'termin_id',

                                $rezervacija->termin_id

                            )

                            ->where(

                                'status',

                                'prihvaćeno'

                            )

                            ->exists();



                    if (!$postojiPrihvacena) {

                        DB::table('termini')

                            ->where(

                                'id',

                                $rezervacija->termin_id

                            )

                            ->update([

                                'status' => 'slobodan'

                            ]);

                    }

                }

            });

        } catch (\Throwable $e) {

            return response()->json([

                'message' =>

                    'Greška pri promjeni statusa rezervacije.'

            ], 500);

        }



        return response()->json([

            'uspjeh' => true,

            'poruka' =>

                'Status rezervacije je uspješno promijenjen.'

        ]);

    }



    /*

    |--------------------------------------------------------------------------

    | DELETE /api/rezervacije/{id}

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

    $rezervacija = Rezervacija::find($id);

    if (!$rezervacija) {
        return response()->json([
            'message' => 'Rezervacija nije pronađena.'
        ], 404);
    }

    /*
     * Provjeravamo ulogu prijavljenog korisnika.
     */
    $prijavljeniKorisnik = Korisnik::find($korisnikId);

    if (!$prijavljeniKorisnik) {
        return response()->json([
            'message' => 'Prijavljeni korisnik nije pronađen.'
        ], 404);
    }

    $uloga = mb_strtolower(
        trim((string) $prijavljeniKorisnik->uloga)
    );

    $jeSuperAdmin = in_array(
        $uloga,
        [
            'super administrator',
            'superadmin',
            'super admin'
        ],
        true
    );

    /*
     * SUPER ADMINISTRATOR
     *
     * Samo Super Administrator smije trajno
     * obrisati bilo koju rezervaciju.
     */
    if ($jeSuperAdmin) {
        try {
            $privitakPutanja = $rezervacija->privitak_putanja;

            DB::transaction(function () use ($rezervacija) {

                /*
                 * Ako je rezervacija bila prihvaćena,
                 * termin ponovno postaje slobodan.
                 */
                if ($rezervacija->status === 'prihvaćeno') {
                    DB::table('termini')
                        ->where('id', $rezervacija->termin_id)
                        ->update([
                            'status' => 'slobodan'
                        ]);
                }

                $rezervacija->delete();
            });

            /*
             * Brišemo pripadajući privitak ako postoji.
             */
            if (!empty($privitakPutanja)) {
                $relativnaPutanja = ltrim(
                    str_replace('\\', '/', $privitakPutanja),
                    '/'
                );

                $punaPutanja = public_path($relativnaPutanja);

                if (File::exists($punaPutanja)) {
                    File::delete($punaPutanja);
                }
            }

        } catch (\Throwable $e) {
            return response()->json([
                'message' =>
                    'Rezervaciju nije moguće obrisati.'
            ], 500);
        }

        return response()->json([
            'uspjeh' => true,
            'poruka' =>
                'Rezervacija je uspješno obrisana.'
        ]);
    }

    /*
     * ADMIN / TUTOR
     *
     * Ne smiju koristiti DELETE za trajno
     * brisanje tuđih rezervacija.
     */
    if ((int) $rezervacija->student_id !== $korisnikId) {
        return response()->json([
            'message' =>
                'Samo Super Administrator može obrisati ovu rezervaciju.'
        ], 403);
    }

    /*
     * STUDENT
     *
     * Student i dalje može otkazati samo
     * vlastitu rezervaciju ako ima dozvolu.
     */
    if (
        !$dozvoleService->korisnikImaDozvolu(
            $korisnikId,
            'otkazivanje_rezervacije'
        )
    ) {
        return response()->json([
            'message' =>
                'Nemate dozvolu za otkazivanje rezervacije.'
        ], 403);
    }

    try {
        $privitakPutanja = $rezervacija->privitak_putanja;

        DB::transaction(function () use ($rezervacija) {

            if ($rezervacija->status === 'prihvaćeno') {
                DB::table('termini')
                    ->where('id', $rezervacija->termin_id)
                    ->update([
                        'status' => 'slobodan'
                    ]);
            }

            $rezervacija->delete();
        });

        /*
         * Brišemo pripadajući privitak ako postoji.
         */
        if (!empty($privitakPutanja)) {
            $relativnaPutanja = ltrim(
                str_replace('\\', '/', $privitakPutanja),
                '/'
            );

            $punaPutanja = public_path($relativnaPutanja);

            if (File::exists($punaPutanja)) {
                File::delete($punaPutanja);
            }
        }

    } catch (\Throwable $e) {
        return response()->json([
            'message' =>
                'Rezervaciju nije moguće otkazati.'
        ], 500);
    }

    return response()->json([
        'uspjeh' => true,
        'poruka' =>
            'Rezervacija je uspješno otkazana.'
    ]);
}

}