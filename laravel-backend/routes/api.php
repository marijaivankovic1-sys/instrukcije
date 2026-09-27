<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\KosaricaController;
use App\Http\Controllers\KorisnikController;
use App\Http\Controllers\PredmetController;
use App\Http\Controllers\RecenzijaController;
use App\Http\Controllers\RezervacijaController;
use App\Http\Controllers\TerminController;
use App\Http\Controllers\TutorPredmetController;
use App\Http\Controllers\UlogeDozvoleController;
use App\Http\Controllers\ZbirkaController;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


/*
|--------------------------------------------------------------------------
| Laravel korisnik
|--------------------------------------------------------------------------
*/

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


/*
|--------------------------------------------------------------------------
| Predmeti
|--------------------------------------------------------------------------
*/

Route::apiResource(
    'predmeti',
    PredmetController::class
);


/*
|--------------------------------------------------------------------------
| Termini
|--------------------------------------------------------------------------
*/

Route::apiResource(
    'termini',
    TerminController::class
);


/*
|--------------------------------------------------------------------------
| Autentifikacija
|--------------------------------------------------------------------------
*/

Route::post(
    '/registracija',
    [AuthController::class, 'registracija']
);

Route::post(
    '/prijava',
    [AuthController::class, 'prijava']
);

Route::post(
    '/odjava',
    [AuthController::class, 'odjava']
);

Route::get(
    '/ja',
    [AuthController::class, 'ja']
);

Route::get(
    '/dozvole',
    [AuthController::class, 'dozvole']
);


/*
|--------------------------------------------------------------------------
| Korisnici
|--------------------------------------------------------------------------
*/

Route::apiResource(
    'korisnici',
    KorisnikController::class
);


/*
|--------------------------------------------------------------------------
| Uloge i dozvole
|--------------------------------------------------------------------------
*/

Route::get(
    '/uloge',
    [UlogeDozvoleController::class, 'uloge']
);

Route::post(
    '/uloge',
    [UlogeDozvoleController::class, 'createUloga']
);

Route::put(
    '/uloge/{id}',
    [UlogeDozvoleController::class, 'updateUloga']
);

Route::delete(
    '/uloge/{id}',
    [UlogeDozvoleController::class, 'deleteUloga']
);


Route::get(
    '/dozvole-sve',
    [UlogeDozvoleController::class, 'dozvole']
);

Route::post(
    '/dozvole',
    [UlogeDozvoleController::class, 'createDozvola']
);

Route::put(
    '/dozvole/{id}',
    [UlogeDozvoleController::class, 'updateDozvola']
);

Route::delete(
    '/dozvole/{id}',
    [UlogeDozvoleController::class, 'deleteDozvola']
);


Route::get(
    '/uloge/{id}/dozvole',
    [UlogeDozvoleController::class, 'dozvoleUloge']
);

Route::put(
    '/uloge/{id}/dozvole',
    [UlogeDozvoleController::class, 'spremiDozvoleUloge']
);


/*
|--------------------------------------------------------------------------
| Rezervacije
|--------------------------------------------------------------------------
*/

Route::apiResource(
    'rezervacije',
    RezervacijaController::class
);


/*
|--------------------------------------------------------------------------
| Tutor - predmet
|--------------------------------------------------------------------------
*/

Route::apiResource(
    'tutor-predmeti',
    TutorPredmetController::class
);


/*
|--------------------------------------------------------------------------
| Zbirke
|--------------------------------------------------------------------------
*/

Route::get(
    '/zbirke',
    [ZbirkaController::class, 'index']
);

Route::get(
    '/zbirke/{id}',
    [ZbirkaController::class, 'show']
);

Route::post(
    '/zbirke',
    [ZbirkaController::class, 'store']
);

Route::post(
    '/zbirke/{id}/datoteke',
    [ZbirkaController::class, 'uploadDatoteka']
);

Route::put(
    '/zbirke/{id}',
    [ZbirkaController::class, 'update']
);

Route::delete(
    '/zbirke/{id}',
    [ZbirkaController::class, 'destroy']
);


/*
|--------------------------------------------------------------------------
| Košarica
|--------------------------------------------------------------------------
*/

Route::get(
    '/kosarica',
    [KosaricaController::class, 'index']
);

Route::get(
    '/kosarica/{id}',
    [KosaricaController::class, 'show']
);

Route::post(
    '/kosarica',
    [KosaricaController::class, 'store']
);

Route::put(
    '/kosarica/{id}',
    [KosaricaController::class, 'update']
);

/*
 * "sve" mora biti prije /kosarica/{id}
 */
Route::delete(
    '/kosarica/sve',
    [KosaricaController::class, 'obrisiSve']
);

Route::delete(
    '/kosarica/{id}',
    [KosaricaController::class, 'destroy']
);


/*
|--------------------------------------------------------------------------
| Recenzije i instruktori
|--------------------------------------------------------------------------
*/

Route::get(
    '/instruktori',
    [RecenzijaController::class, 'instruktori']
);

Route::get(
    '/recenzije',
    [RecenzijaController::class, 'index']
);

Route::get(
    '/recenzije/{id}',
    [RecenzijaController::class, 'show']
);

Route::post(
    '/recenzije',
    [RecenzijaController::class, 'store']
);

Route::put(
    '/recenzije/{id}',
    [RecenzijaController::class, 'update']
);

Route::delete(
    '/recenzije/{id}',
    [RecenzijaController::class, 'destroy']
);