```vue
<template>
  <div class="container my-5">
    <!-- Dokumentacija -->
    <div class="card shadow-sm p-4 mb-4 bg-light border-0">
      <div class="row align-items-center">
        <div class="col-md-8">
          <h3>Službena dokumentacija</h3>
          <p class="text-muted mb-0">
            Preuzmite PDF dokument s detaljnim opisom vizije, ciljeva,
            korisnika i funkcionalnosti projekta.
          </p>
        </div>

        <div class="col-md-4 text-md-end mt-3 mt-md-0">
          <a
            href="/vizija.pdf"
            download="vizija.pdf"
            class="btn btn-primary"
          >
            <i class="bi bi-file-earmark-arrow-down-fill me-2"></i>
            Preuzmi PDF dokument
          </a>
        </div>
      </div>
    </div>

    <h1 class="mb-4">Vizija projekta - PLUS I MINUS</h1>

    <!-- Cilj -->
    <h3 class="text-secondary">Cilj projekta</h3>
    <p>
      Cilj projekta PLUS I MINUS je izrada pregledne i funkcionalne web
      aplikacije koja olakšava organizaciju instrukcija. Sustav povezuje
      studente i tutore te omogućuje upravljanje predmetima, terminima,
      rezervacijama i ostalim podacima potrebnim za organizaciju instrukcija.
    </p>

    <!-- Svrha -->
    <h3 class="mt-4">Svrha sustava</h3>
    <p>
      Aplikacija objedinjuje podatke potrebne za organizaciju instrukcija na
      jednom mjestu. Studentima omogućuje jednostavnije pronalaženje i
      rezerviranje odgovarajućih termina, tutorima upravljanje terminima i
      zahtjevima za rezervaciju, a administrativnim korisnicima upravljanje
      sustavom u skladu s dodijeljenim ovlastima.
    </p>

    <!-- Funkcionalnosti -->
    <h3 class="mt-4">Glavne funkcionalnosti</h3>
    <ul>
      <li>
        Registracija, prijava i odjava korisnika.
      </li>

      <li>
        Pregled predmeta i dostupnih termina za instrukcije.
      </li>

      <li>
        Slanje i obrada zahtjeva za rezervaciju termina.
      </li>

      <li>
        Dodavanje napomena i privitaka uz rezervacije.
      </li>

      <li>
        Upravljanje predmetima, terminima, korisnicima i rezervacijama.
      </li>

      <li>
        Rad sa zbirkama, košaricom i recenzijama.
      </li>

      <li>
        Upravljanje korisničkim ulogama i dozvolama.
      </li>
    </ul>

    <!-- Korisničke uloge -->
    <h3 class="mt-4">Korisničke uloge</h3>

    <p>
      <strong>Student:</strong>
      Pregledava dostupne termine, šalje zahtjeve za rezervaciju s napomenama
      i privitcima te prati status svojih rezervacija.
    </p>

    <p>
      <strong>Tutor:</strong>
      Upravlja svojim terminima i predmetima te pregledava i obrađuje
      pristigle zahtjeve za rezervaciju.
    </p>

    <p>
      <strong>Administrator:</strong>
      Pristupa administrativnom dijelu aplikacije i upravlja podacima sustava
      u skladu s dodijeljenim dozvolama.
    </p>

    <p>
      <strong>Super Administrator:</strong>
      Ima najvišu razinu administrativnih ovlasti te pristup posebno
      zaštićenim funkcionalnostima, uključujući upravljanje korisnicima,
      ulogama i dozvolama.
    </p>

    <!-- Tehnologije -->
    <h3 class="mt-4">Tehnologije</h3>
    <p>
      Aplikacija je razvijena korištenjem Vue.js-a i Vitea za korisničko
      sučelje, Laravel PHP frameworka za poslužiteljski dio te MySQL baze
      podataka. Frontend i backend komuniciraju putem API zahtjeva.
    </p>
  </div>
</template>