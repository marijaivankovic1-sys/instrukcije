<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */

$routes->get('/', 'Home::index');


/*
|--------------------------------------------------------------------------
| CORS za API
|--------------------------------------------------------------------------
*/

$routes->options('api/(:any)', static function () {
    return response()->setStatusCode(204);
}, ['filter' => 'cors']);


/*
|--------------------------------------------------------------------------
| API rute
|--------------------------------------------------------------------------
*/

$routes->group('api', ['filter' => 'cors'], static function ($routes) {


    /*
    |--------------------------------------------------------------------------
    | Predmeti
    |--------------------------------------------------------------------------
    */

    $routes->get('predmeti', 'Predmeti::index');
    $routes->get('predmeti/(:num)', 'Predmeti::show/$1');
    $routes->post('predmeti', 'Predmeti::create');
    $routes->put('predmeti/(:num)', 'Predmeti::update/$1');
    $routes->delete('predmeti/(:num)', 'Predmeti::delete/$1');


    /*
    |--------------------------------------------------------------------------
    | Termini
    |--------------------------------------------------------------------------
    */

    $routes->get('termini', 'Termini::index');
    $routes->get('termini/(:num)', 'Termini::show/$1');
    $routes->post('termini', 'Termini::create');
    $routes->put('termini/(:num)', 'Termini::update/$1');
    $routes->delete('termini/(:num)', 'Termini::delete/$1');


    /*
    |--------------------------------------------------------------------------
    | Rezervacije
    |--------------------------------------------------------------------------
    */

    $routes->get('rezervacije', 'Rezervacije::index');
    $routes->get('rezervacije/(:num)', 'Rezervacije::show/$1');
    $routes->post('rezervacije', 'Rezervacije::create');
    $routes->put('rezervacije/(:num)', 'Rezervacije::update/$1');
    $routes->delete('rezervacije/(:num)', 'Rezervacije::delete/$1');


    /*
    |--------------------------------------------------------------------------
    | Zbirke
    |--------------------------------------------------------------------------
    */

    $routes->get('zbirke', 'Zbirke::index');
    $routes->get('zbirke/(:num)', 'Zbirke::show/$1');
    $routes->post('zbirke', 'Zbirke::create');

    $routes->post(
        'zbirke/(:num)/datoteke',
        'Zbirke::uploadDatoteka/$1'
    );

    $routes->put('zbirke/(:num)', 'Zbirke::update/$1');
    $routes->delete('zbirke/(:num)', 'Zbirke::delete/$1');


    /*
    |--------------------------------------------------------------------------
    | Košarica
    |--------------------------------------------------------------------------
    */

    $routes->get('kosarica', 'Kosarica::index');
    $routes->get('kosarica/(:num)', 'Kosarica::show/$1');
    $routes->post('kosarica', 'Kosarica::create');
    $routes->put('kosarica/(:num)', 'Kosarica::update/$1');

    $routes->delete(
        'kosarica/sve',
        'Kosarica::obrisiSve'
    );

    $routes->delete(
        'kosarica/(:num)',
        'Kosarica::delete/$1'
    );


    /*
    |--------------------------------------------------------------------------
    | Korisnici
    |--------------------------------------------------------------------------
    */

    $routes->get('korisnici', 'Korisnici::index');
    $routes->get('korisnici/(:num)', 'Korisnici::show/$1');
    $routes->post('korisnici', 'Korisnici::create');
    $routes->put('korisnici/(:num)', 'Korisnici::update/$1');
    $routes->delete('korisnici/(:num)', 'Korisnici::delete/$1');


    /*
    |--------------------------------------------------------------------------
    | Autentifikacija
    |--------------------------------------------------------------------------
    */

    $routes->post('registracija', 'Auth::registracija');
    $routes->post('prijava', 'Auth::prijava');
    $routes->post('odjava', 'Auth::odjava');

    $routes->get('ja', 'Auth::ja');
    $routes->get('dozvole', 'Auth::dozvole');


    /*
    |--------------------------------------------------------------------------
    | Recenzije
    |--------------------------------------------------------------------------
    */

    $routes->get(
        'instruktori',
        'Recenzije::instruktori'
    );

    $routes->get(
        'recenzije',
        'Recenzije::index'
    );

    $routes->get(
        'recenzije/(:num)',
        'Recenzije::show/$1'
    );

    $routes->post(
        'recenzije',
        'Recenzije::create'
    );

    $routes->put(
        'recenzije/(:num)',
        'Recenzije::update/$1'
    );

    $routes->delete(
        'recenzije/(:num)',
        'Recenzije::delete/$1'
    );
/*
|--------------------------------------------------------------------------
| Uloge i dozvole
|--------------------------------------------------------------------------
*/

$routes->get(
    'uloge',
    'UlogeDozvole::uloge'
);

$routes->post(
    'uloge',
    'UlogeDozvole::createUloga'
);

$routes->put(
    'uloge/(:num)',
    'UlogeDozvole::updateUloga/$1'
);

$routes->delete(
    'uloge/(:num)',
    'UlogeDozvole::deleteUloga/$1'
);


$routes->get(
    'dozvole-sve',
    'UlogeDozvole::dozvole'
);

$routes->post(
    'dozvole',
    'UlogeDozvole::createDozvola'
);

$routes->put(
    'dozvole/(:num)',
    'UlogeDozvole::updateDozvola/$1'
);

$routes->delete(
    'dozvole/(:num)',
    'UlogeDozvole::deleteDozvola/$1'
);


$routes->get(
    'uloge/(:num)/dozvole',
    'UlogeDozvole::dozvoleUloge/$1'
);

$routes->put(
    'uloge/(:num)/dozvole',
    'UlogeDozvole::spremiDozvoleUloge/$1'
);

    /*
    |--------------------------------------------------------------------------
    | Tutor - predmet
    |--------------------------------------------------------------------------
    */

    $routes->get(
        'tutor-predmeti',
        'TutorPredmeti::index'
    );

    $routes->get(
        'tutor-predmeti/(:num)',
        'TutorPredmeti::show/$1'
    );

    $routes->post(
        'tutor-predmeti',
        'TutorPredmeti::create'
    );

    $routes->put(
        'tutor-predmeti/(:num)',
        'TutorPredmeti::update/$1'
    );

    $routes->delete(
        'tutor-predmeti/(:num)',
        'TutorPredmeti::delete/$1'
    );

});