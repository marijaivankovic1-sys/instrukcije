<?php

$host = 'localhost';
$korisnik = 'root';
$lozinka = '';
$baza = 'instrukcije_db';

$veza = mysqli_connect(
    $host,
    $korisnik,
    $lozinka,
    $baza
);

if (!$veza) {
    die(
        'Povezivanje s bazom nije uspjelo: ' .
        mysqli_connect_error()
    );
}

mysqli_set_charset($veza, 'utf8mb4');