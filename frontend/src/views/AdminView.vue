<template>
  <div class="container py-4">
    <h2 class="fw-bold mb-4">Upravljačka ploča</h2>
    <!-- ================================================= -->
    <!-- PORUKA -->
    <!-- ================================================= -->
    <div
      v-if="poruka.tekst"
      :class="[
        'alert',
        poruka.greska ? 'alert-danger' : 'alert-success',
        'alert-dismissible fade show'
      ]"
      role="alert"
    >
      {{ poruka.tekst }}
      <button
        type="button"
        class="btn-close"
        @click="poruka.tekst = ''"
      ></button>
    </div>
    <!-- ================================================= -->
    <!-- 1. ZAHTJEVI ZA REZERVACIJU -->
    <!-- ================================================= -->
    <div class="row mb-5">
      <div class="col-12">
        <div class="card p-4 shadow-sm border-0">
          <div
            class="d-flex justify-content-between align-items-center mb-3"
          >
            <h5 class="fw-bold mb-0">
              Pristigli zahtjevi za rezervacije
            </h5>
            <button
              @click="dohvatiZahtjeve"
              class="btn btn-sm btn-outline-primary"
            >
              Osvježi
            </button>
          </div>
          <div
            v-if="ucitavanjeZahtjevi"
            class="text-center py-4"
          >
            <div
              class="spinner-border text-primary"
              role="status"
            ></div>
          </div>
          <div
            v-else-if="zahtjevi.length === 0"
            class="alert alert-secondary text-center mb-0"
          >
            Trenutno nemate pristiglih zahtjeva za rezervaciju.
          </div>
          <div
            v-else
            class="table-responsive"
          >
            <table class="table table-hover align-middle">
              <thead class="table-light">
                <tr>
                  <th>Student</th>
                  <th>Predmet</th>
                  <th>Instruktor</th>
                  <th>Datum i vrijeme</th>
                  <th>Napomena / Privitak</th>
                  <th>Status</th>
                  <th class="text-end">Akcija</th>
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="z in zahtjevi"
                  :key="z.rezervacija_id || z.id"
                >
                  <td>
                    <strong>
                      {{ z.student_ime }}
                      {{ z.student_prezime }}
                    </strong>
                    <br />
                    <small class="text-muted">
                      {{ z.student_email }}
                    </small>
                  </td>
                  <td>
                    <span class="badge bg-primary">
                      {{ z.predmet_naziv }}
                    </span>
                  </td>
                  <td>
                    <strong>
                      {{ z.instruktor_ime }}
                      {{ z.instruktor_prezime }}
                    </strong>
                  </td>
                  <td>
                    {{ z.datum }}
                    <br />
                    <small class="text-muted">
                      {{ z.vrijeme_od }} - {{ z.vrijeme_do }}
                    </small>
                  </td>
                  <td>
                    <div
                      class="mb-1 small"
                      v-html="z.napomena || 'Nema napomene'"
                    ></div>
                  <a

  v-if="z.privitak_putanja"
  :href="putanjaPrivitka(z.privitak_putanja)"
  target="_blank"
  rel="noopener noreferrer"
  class="btn btn-sm btn-outline-secondary"
>
  Pogledaj privitak
</a>
                  </td>
                  <td>
                    <span
                      :class="getStatusBadgeClass(
                        (z.rezervacija_status || z.status)
                      )"
                    >
                      {{ (z.rezervacija_status || z.status) }}
                    </span>
                  </td>
  <td class="text-end">
  <div class="d-flex justify-content-end align-items-center gap-2 flex-nowrap">

    <template v-if="z.rezervacija_status === 'na čekanju'">
      <button
        class="btn btn-sm btn-success"
        @click="
          odgovoriNaZahtjev(
            z.rezervacija_id,
            'prihvaćeno'
          )
        "
      >
        Prihvati
      </button>

      <button
        class="btn btn-sm btn-warning"
        @click="
          odgovoriNaZahtjev(
            z.rezervacija_id,
            'odbijeno'
          )
        "
      >
        Odbij
      </button>
    </template>

    <span
      v-else
      class="badge bg-secondary align-self-center"
    >
      Obrađeno
    </span>

   <button
  v-if="jeSuperAdmin"
  class="btn btn-sm btn-danger"
  @click="obrisiRezervaciju(z.rezervacija_id)"
>
  Obriši
</button>

  </div>
</td>
                  
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
    <!-- ================================================= -->
    <!-- 2. DODAVANJE PREDMETA I KORISNIKA -->
    <!-- ================================================= -->
    <div class="row g-4 mb-5">
      <!-- DODAVANJE PREDMETA -->
      <div class="col-md-5">
        <div
          class="card p-4 shadow-sm h-100 border-0"
        >
          <h5 class="fw-bold mb-3">
            Dodaj novi predmet
          </h5>
          <form @submit.prevent="dodajPredmet">
            <div class="mb-3">
              <label
                class="form-label text-muted small"
              >
                Naziv predmeta:
              </label>
              <input
                type="text"
                v-model="noviPredmet.naziv"
                class="form-control"
                placeholder="npr. Matematika 1"
                required
              />
            </div>
            <div class="mb-3">
              <label
                class="form-label text-muted small"
              >
                Opis:
              </label>
              <textarea
                v-model="noviPredmet.opis"
                class="form-control"
                rows="3"
                placeholder="Kratki opis predmeta..."
              ></textarea>
            </div>
            <button
              type="submit"
              class="btn btn-primary w-100 fw-semibold"
              :disabled="ucitavanjePredmet"
            >
              {{
                ucitavanjePredmet
                  ? 'Spremanje...'
                  : 'Spremi predmet'
              }}
            </button>
          </form>
        </div>
      </div>
      <!-- DODAVANJE KORISNIKA -->
      <div class="col-md-7">
        <div
          class="card p-4 shadow-sm h-100 border-0"
        >
          <h5 class="fw-bold mb-3">
            Dodaj novog korisnika
          </h5>
          <form @submit.prevent="dodajKorisnika">
            <div class="row g-2 mb-3">
              <div class="col-md-6">
                <label
                  class="form-label text-muted small"
                >
                  Ime:
                </label>
                <input
                  type="text"
                  v-model="noviKorisnik.ime"
                  class="form-control"
                  required
                />
              </div>
              <div class="col-md-6">
                <label
                  class="form-label text-muted small"
                >
                  Prezime:
                </label>
                <input
                  type="text"
                  v-model="noviKorisnik.prezime"
                  class="form-control"
                  required
                />
              </div>
            </div>
            <div class="row g-2 mb-3">
              <div class="col-md-6">
                <label
                  class="form-label text-muted small"
                >
                  Email:
                </label>
                <input
                  type="email"
                  v-model="noviKorisnik.email"
                  class="form-control"
                  required
                />
              </div>
              <div class="col-md-6">
                <label
                  class="form-label text-muted small"
                >
                  Lozinka:
                </label>
                <input
                  type="password"
                  v-model="noviKorisnik.lozinka"
                  class="form-control"
                  required
                />
              </div>
            </div>
            <div class="mb-3">
              <label
                class="form-label text-muted small"
              >
                Uloga:
              </label>
              <select
                v-model="noviKorisnik.uloga"
                class="form-select"
                required
              >
                <option value="Student">
                  Student
                </option>
                <!--
                  U bazi spremamo Tutor,
                  korisniku prikazujemo Instruktor
                -->
                <option value="Tutor">
                  Instruktor
                </option>
                <option value="Admin">
                  Admin
                </option>
              </select>
            </div>
            <button
              type="submit"
              class="btn btn-success w-100 fw-semibold"
              :disabled="ucitavanjeKorisnik"
            >
              {{
                ucitavanjeKorisnik
                  ? 'Spremanje...'
                  : 'Kreiraj korisnika'
              }}
            </button>
          </form>
        </div>
      </div>
    </div>
    <!-- ================================================= -->
    <!-- 3. PREDMETI -->
    <!-- ================================================= -->
    <div class="row mb-5">
      <div class="col-12">
        <div
          class="card p-4 shadow-sm border-0"
        >
          <div
            class="d-flex justify-content-between align-items-center mb-3"
          >
            <h5 class="fw-bold mb-0">
              Predmeti
            </h5>
            <button
              @click="dohvatiPredmete"
              class="btn btn-sm btn-outline-primary"
            >
              Osvježi
            </button>
          </div>
          <div class="table-responsive">
            <table
              class="table table-hover align-middle"
            >
              <thead class="table-light">
                <tr>
                  <th>Naziv</th>
                  <th>Opis</th>
                  <th class="text-end">
                    Akcije
                  </th>
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="p in predmeti"
                  :key="p.id"
                >
                  <td class="fw-semibold">
                    {{ p.naziv }}
                  </td>
                  <td>
                    {{ p.opis || 'Nema opisa' }}
                  </td>
                  <td class="text-end">
                    <div class="btn-group">
                      <button
                        @click="
                          otvoriUrediPredmetModal(p)
                        "
                        class="btn btn-sm btn-outline-primary"
                      >
                        Uredi
                      </button>
                      <button
                        @click="
                          obrisiPredmet(
                            p.id,
                            p.naziv
                          )
                        "
                        class="btn btn-sm btn-outline-danger"
                      >
                        Obriši
                      </button>
                    </div>
                  </td>
                </tr>
                <tr v-if="predmeti.length === 0">
                  <td
                    colspan="3"
                    class="text-center text-muted py-4"
                  >
                    Nema predmeta.
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
    <!-- ================================================= -->
    <!-- 4. DODJELA PREDMETA INSTRUKTORIMA -->
    <!-- ================================================= -->
    <div class="row mb-5">
      <div class="col-12">
        <div
          class="card p-4 shadow-sm border-0"
        >
          <div
            class="d-flex justify-content-between align-items-center mb-4"
          >
            <div>
              <h5 class="fw-bold mb-1">
                Dodjela predmeta instruktorima
              </h5>
              <small class="text-muted">
                Povežite instruktora s jednim ili više predmeta.
              </small>
            </div>
            <button
              @click="osvjeziTutorPredmetPodatke"
              class="btn btn-sm btn-outline-primary"
            >
              Osvježi
            </button>
          </div>
          <!-- FORMA ZA NOVU DODJELU -->
          <form
            @submit.prevent="dodajTutorPredmet"
            class="row g-3 align-items-end mb-4"
          >
            <div class="col-md-5">
              <label
                class="form-label fw-semibold"
              >
                Instruktor:
              </label>
              <select
                v-model="novaDodjela.tutor_id"
                class="form-select"
                required
              >
                <option value="">
                  -- Odaberi instruktora --
                </option>
                <option
                  v-for="t in tutori"
                  :key="t.id"
                  :value="t.id"
                >
                  {{ t.ime }} {{ t.prezime }}
                </option>
              </select>
            </div>
            <div class="col-md-5">
              <label
                class="form-label fw-semibold"
              >
                Predmet:
              </label>
              <select
                v-model="novaDodjela.predmet_id"
                class="form-select"
                required
              >
                <option value="">
                  -- Odaberi predmet --
                </option>
                <option
                  v-for="p in predmetiZaDodjelu"
                  :key="p.id"
                  :value="p.id"
                >
                  {{ p.naziv }}
                </option>
              </select>
            </div>
            <div class="col-md-2">
              <button
                type="submit"
                class="btn btn-success w-100 fw-semibold"
                :disabled="ucitavanjeDodjela"
              >
                {{
                  ucitavanjeDodjela
                    ? 'Spremanje...'
                    : 'Dodijeli'
                }}
              </button>
            </div>
          </form>
          <hr />
          <!-- POPIS POSTOJEĆIH DODJELA -->
          <h6 class="fw-bold mb-3">
            Trenutne dodjele
          </h6>
          <div
            v-if="ucitavanjeVeze"
            class="text-center py-4"
          >
            <div
              class="spinner-border text-primary"
              role="status"
            ></div>
          </div>
          <div
            v-else-if="tutorPredmetVeze.length === 0"
            class="alert alert-secondary mb-0"
          >
            Trenutno nema dodijeljenih predmeta instruktorima.
          </div>
          <div
            v-else
            class="table-responsive"
          >
            <table
              class="table table-hover align-middle"
            >
              <thead class="table-light">
                <tr>
                  <th>Instruktor</th>
                  <th>Predmet</th>
                  <th class="text-end">
                    Akcije
                  </th>
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="veza in tutorPredmetVeze"
                  :key="veza.id"
                >
                  <td>
                    <strong>
                      {{ veza.tutor_ime }}
                      {{ veza.tutor_prezime }}
                    </strong>
                  </td>
                  <td>
                    <span class="badge bg-primary">
                      {{ veza.predmet_naziv }}
                    </span>
                  </td>
                  <td class="text-end">
                    <div class="btn-group">
                      <button
                        @click="
                          otvoriUrediDodjeluModal(
                            veza
                          )
                        "
                        class="btn btn-sm btn-outline-primary"
                      >
                        Uredi
                      </button>
                      <button
                        @click="
                          obrisiTutorPredmet(
                            veza
                          )
                        "
                        class="btn btn-sm btn-outline-danger"
                      >
                        Ukloni
                      </button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
    <!-- ================================================= -->
    <!-- 5. KORISNICI -->
    <!-- ================================================= -->
    <div class="row">
      <div class="col-12">
        <div
          class="card p-4 shadow-sm border-0"
        >
          <div
            class="d-flex justify-content-between align-items-center mb-3"
          >
            <h5 class="fw-bold mb-0">
              Registrirani korisnici
            </h5>
            <button
              @click="dohvatiKorisnike"
              class="btn btn-sm btn-outline-primary"
            >
              Osvježi
            </button>
          </div>
          <div class="table-responsive">
            <table
              class="table table-hover align-middle"
            >
              <thead class="table-light">
                <tr>
                  <th>Ime i prezime</th>
                  <th>Email</th>
                  <th>Uloga</th>
                  <th class="text-end">
                    Akcije
                  </th>
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="k in korisnici"
                  :key="k.id"
                >
                  <td>
                    {{ k.ime }}
                    {{ k.prezime }}
                  </td>
                  <td>
                    {{ k.email }}
                  </td>
                  <td>
                    <span
                      :class="[
                        'badge',
                        ulogaKorisnika(k) === 'Admin'
                          ? 'bg-danger'
                          : ulogaKorisnika(k) === 'Tutor'
                          ? 'bg-primary'
                          : 'bg-secondary'
                      ]"
                    >
                      {{
                        ulogaKorisnika(k) === 'Tutor'
                          ? 'Instruktor'
                          : ulogaKorisnika(k)
                      }}
                    </span>
                  </td>
                  <td class="text-end">
                    <div class="btn-group">
                     <button
                    v-if="jeSuperAdmin || ulogaKorisnika(k) !== 'Super Administrator'"
                    @click="otvoriUrediModal(k)"
                    class="btn btn-sm btn-outline-primary"
                      >
                         Uredi
                    </button>
                      <button
                        v-if="
                          ulogaKorisnika(k) !== 'Admin' &&
                            ulogaKorisnika(k) !== 'Super Administrator'
                        "
                        @click="
                          obrisiKorisnika(
                            k.id,
                            k.ime + ' ' + k.prezime
                          )
                        "
                        class="btn btn-sm btn-outline-danger"
                      >
                        Ukloni
                      </button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
    <!-- ================================================= -->
    <!-- 6. ULOGE I DOZVOLE -->
    <!-- ================================================= -->
    <div class="row mt-4 mb-5">
      <div class="col-12">
        <div class="card p-4 shadow-sm border-0">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
              <h5 class="fw-bold mb-1">Uloge i dozvole</h5>
              <small class="text-muted">
                Odaberite ulogu za prikaz i uređivanje njezinih dozvola.
              </small>
            </div>
            <button
              @click="dohvatiUlogeIDozvole"
              class="btn btn-sm btn-outline-primary"
              :disabled="ucitavanjeUlogeDozvole"
            >
              Osvježi
            </button>
          </div>
          <div
            v-if="ucitavanjeUlogeDozvole"
            class="text-center py-3"
          >
            <div class="spinner-border text-primary" role="status"></div>
          </div>
          <div v-else>
            <div class="row">
              <div class="col-md-6">
                <label class="form-label fw-semibold">Uloga:</label>
                <select
                  v-model="odabranaUlogaId"
                  @change="dohvatiDozvoleUloge"
                  class="form-select"
                >
                  <option value="">-- Odaberi ulogu --</option>
                  <option
                    v-for="u in uloge"
                    :key="u.id"
                    :value="u.id"
                  >
                    {{ u.naziv }}
                  </option>
                </select>
              </div>
            </div>
            <div
              v-if="odabranaUlogaId"
              class="mt-4"
            >
              <h6 class="fw-bold mb-3">Dozvole za odabranu ulogu</h6>
              <div class="row">
                <div
                  v-for="d in dozvole"
                  :key="d.id"
                  class="col-md-6 col-lg-4 mb-3"
                >
                  <div class="form-check">
                    <input
                      :id="'dozvola-' + d.id"
                      v-model="odabraneDozvole"
                      :value="Number(d.id)"
                      class="form-check-input"
                      type="checkbox"
                    />
                    <label
                      class="form-check-label"
                      :for="'dozvola-' + d.id"
                    >
                      <strong>{{ d.naziv }}</strong>
                      <br />
                      <small class="text-muted">{{ d.opis }}</small>
                    </label>
                  </div>
                </div>
              </div>
              <div class="mt-3">
                <button
                  @click="spremiDozvoleUloge"
                  class="btn btn-primary"
                  :disabled="spremanjeDozvola"
                >
                  <span
                    v-if="spremanjeDozvola"
                    class="spinner-border spinner-border-sm me-2"
                  ></span>
                  Spremi dozvole
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    <!-- ================================================= -->
    <!-- MODAL - UREĐIVANJE PREDMETA -->
    <!-- ================================================= -->
    <div
      v-if="prikaziUrediPredmetModal"
      class="modal fade show d-block"
      style="background: rgba(0,0,0,0.5);"
    >
      <div
        class="modal-dialog modal-dialog-centered"
      >
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">
              Uredi predmet
            </h5>
            <button
              type="button"
              class="btn-close"
              @click="
                zatvoriUrediPredmetModal
              "
            ></button>
          </div>
          <form
            @submit.prevent="
              spremiUredjeniPredmet
            "
          >
            <div class="modal-body">
              <div class="mb-3">
                <label class="form-label">
                  Naziv:
                </label>
                <input
                  type="text"
                  v-model="
                    urediPredmet.naziv
                  "
                  class="form-control"
                  required
                />
              </div>
              <div class="mb-3">
                <label class="form-label">
                  Opis:
                </label>
                <textarea
                  v-model="
                    urediPredmet.opis
                  "
                  class="form-control"
                  rows="4"
                ></textarea>
              </div>
            </div>
            <div class="modal-footer">
              <button
                type="button"
                class="btn btn-secondary"
                @click="
                  zatvoriUrediPredmetModal
                "
              >
                Odustani
              </button>
              <button
                type="submit"
                class="btn btn-primary"
                :disabled="
                  ucitavanjeUrediPredmet
                "
              >
                {{
                  ucitavanjeUrediPredmet
                    ? 'Spremanje...'
                    : 'Spremi promjene'
                }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
    <!-- ================================================= -->
    <!-- MODAL - UREĐIVANJE KORISNIKA -->
    <!-- ================================================= -->
    <div
      v-if="prikaziUrediModal"
      class="modal fade show d-block"
      style="background: rgba(0,0,0,0.5);"
    >
      <div
        class="modal-dialog modal-dialog-centered"
      >
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">
              Uredi korisnika
            </h5>
            <button
              type="button"
              class="btn-close"
              @click="zatvoriUrediModal"
            ></button>
          </div>
          <form
            @submit.prevent="
              spremiUredjenogKorisnika
            "
          >
            <div class="modal-body">
              <div class="row g-2 mb-3">
                <div class="col-md-6">
                  <label class="form-label">
                    Ime
                  </label>
                  <input
                    type="text"
                    v-model="
                      urediKorisnik.ime
                    "
                    class="form-control"
                    required
                  />
                </div>
                <div class="col-md-6">
                  <label class="form-label">
                    Prezime
                  </label>
                  <input
                    type="text"
                    v-model="
                      urediKorisnik.prezime
                    "
                    class="form-control"
                    required
                  />
                </div>
              </div>
              <div class="mb-3">
                <label class="form-label">
                  Email
                </label>
                <input
                  type="email"
                  v-model="
                    urediKorisnik.email
                  "
                  class="form-control"
                  required
                />
              </div>
              <div class="mb-3">
                <label class="form-label">
                  Uloga
                </label>
                <select
                  v-model="urediKorisnik.uloga"
                  class="form-select"
                  :disabled="urediKorisnik.uloga === 'Super Administrator'"
                  required
                >
                  <option
                    v-if="urediKorisnik.uloga === 'Super Administrator'"
                    value="Super Administrator"
                  >
                    Super Administrator
                  </option>
                  <option value="Student">Student</option>
                  <option value="Tutor">Instruktor</option>
                  <option value="Admin">Admin</option>
                </select>
                <small
                  v-if="urediKorisnik.uloga === 'Super Administrator'"
                  class="text-muted"
                >
                  Uloga Super Administratora ne može se mijenjati.
                </small>
              </div>
              <div
                class="alert alert-info mb-0"
              >
                Lozinka se ovim uređivanjem ne mijenja.
              </div>
            </div>
            <div class="modal-footer">
              <button
                type="button"
                class="btn btn-secondary"
                @click="zatvoriUrediModal"
              >
                Odustani
              </button>
              <button
                type="submit"
                class="btn btn-primary"
                :disabled="ucitavanjeUredi"
              >
                {{
                  ucitavanjeUredi
                    ? 'Spremanje...'
                    : 'Spremi promjene'
                }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
    <!-- ================================================= -->
    <!-- MODAL - UREĐIVANJE DODJELE TUTOR-PREDMET -->
    <!-- ================================================= -->
    <div
      v-if="prikaziUrediDodjeluModal"
      class="modal fade show d-block"
      style="background: rgba(0,0,0,0.5);"
    >
      <div
        class="modal-dialog modal-dialog-centered"
      >
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">
              Uredi dodjelu predmeta
            </h5>
            <button
              type="button"
              class="btn-close"
              @click="
                zatvoriUrediDodjeluModal
              "
            ></button>
          </div>
          <form
            @submit.prevent="
              spremiUredjenuDodjelu
            "
          >
            <div class="modal-body">
              <div class="mb-3">
                <label class="form-label fw-semibold">
                  Instruktor:
                </label>
                <select
                  v-model="
                    urediDodjela.tutor_id
                  "
                  class="form-select"
                  required
                >
                  <option
                    v-for="t in tutori"
                    :key="t.id"
                    :value="t.id"
                  >
                    {{ t.ime }} {{ t.prezime }}
                  </option>
                </select>
              </div>
              <div class="mb-3">
                <label class="form-label fw-semibold">
                  Predmet:
                </label>
                <select
                  v-model="
                    urediDodjela.predmet_id
                  "
                  class="form-select"
                  required
                >
                  <option
                    v-for="p in predmetiZaDodjelu"
                    :key="p.id"
                    :value="p.id"
                  >
                    {{ p.naziv }}
                  </option>
                </select>
              </div>
            </div>
            <div class="modal-footer">
              <button
                type="button"
                class="btn btn-secondary"
                @click="
                  zatvoriUrediDodjeluModal
                "
              >
                Odustani
              </button>
              <button
                type="submit"
                class="btn btn-primary"
                :disabled="
                  ucitavanjeUrediDodjela
                "
              >
                {{
                  ucitavanjeUrediDodjela
                    ? 'Spremanje...'
                    : 'Spremi promjene'
                }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>
<script>
import { API_BASE_URL } from '@/config/api'
const apiFetch = (url, options = {}) => {
  return fetch(url, {
    ...options,
    credentials: 'include',
    headers: {
      Accept: 'application/json',
      ...(options.headers || {})
    }
  })
}
export default {
  name: 'AdminView',
  data() {
    return {
      noviPredmet: { naziv: '', opis: '' },
      urediPredmet: { id: null, naziv: '', opis: '' },
      noviKorisnik: {
        ime: '', prezime: '', email: '', lozinka: '', uloga: 'Tutor'
      },
      urediKorisnik: {
        id: null, ime: '', prezime: '', email: '', uloga: ''
      },
      tutori: [],
      predmetiZaDodjelu: [],
      tutorPredmetVeze: [],
      novaDodjela: { tutor_id: '', predmet_id: '' },
      urediDodjela: { id: null, tutor_id: '', predmet_id: '' },
      predmeti: [],
      korisnici: [],
      zahtjevi: [],
      // Uloge i dozvole
      uloge: [],
      dozvole: [],
      odabranaUlogaId: '',
      odabraneDozvole: [],
      ucitavanjeUlogeDozvole: false,
      spremanjeDozvola: false,
      prikaziUrediModal: false,
      prikaziUrediPredmetModal: false,
      prikaziUrediDodjeluModal: false,
      poruka: { tekst: '', greska: false },
      ucitavanjePredmet: false,
      ucitavanjeUrediPredmet: false,
      ucitavanjeKorisnik: false,
      ucitavanjeZahtjevi: false,
      ucitavanjeUredi: false,
      ucitavanjeDodjela: false,
      ucitavanjeVeze: false,
      ucitavanjeUrediDodjela: false
    }
  },

  computed: {
  jeSuperAdmin() {
    const podaci =
      localStorage.getItem('korisnik') ||
      localStorage.getItem('user')

    if (!podaci) {
      return false
    }

    try {
      const korisnik = JSON.parse(podaci)

      const uloga = (
        korisnik.uloga ||
        korisnik.role ||
        ''
      ).toString().trim().toLowerCase()

      return (
        uloga === 'super administrator' ||
        uloga === 'superadmin' ||
        uloga === 'super admin'
      )
    } catch (e) {
      console.error(
        'Greška pri provjeri uloge korisnika:',
        e
      )

      return false
    }
  }
},
  mounted() {
    this.dohvatiKorisnike()
    this.dohvatiPredmete()
    this.dohvatiZahtjeve()
    this.dohvatiUlogeIDozvole()
  },
  methods: {
    async procitajJson(res) {
      try { return await res.json() } catch { return {} }
    },
    porukaGreske(data, zadana) {
      return data?.messages?.error || data?.message || data?.poruka || zadana
    },
    jeUspjeh(res, data) {
      return res.ok && (
        data?.uspjeh === true ||
        data?.success === true ||
        data?.uspjeh === undefined
      )
    },
    ulogaKorisnika(korisnik) {
      const uloga = (korisnik?.uloga || '').toString().trim().toLowerCase()
      if (
        uloga === 'super administrator' ||
        uloga === 'superadmin' ||
        uloga === 'super admin'
      ) return 'Super Administrator'
      if (uloga === 'instruktor' || uloga === 'tutor') return 'Tutor'
      if (uloga === 'admin' || uloga === 'administrator') return 'Admin'
      return 'Student'
    },
    putanjaPrivitka(putanja) {
      if (!putanja) return '#'
      if (putanja.startsWith('http://') || putanja.startsWith('https://')) return putanja
      const backendUrl = API_BASE_URL.replace(/\/api\/?$/, '')
      return `${backendUrl}/${putanja.replace(/^\/+/, '')}`
    },
    async dohvatiZahtjeve() {
      this.ucitavanjeZahtjevi = true
      try {
        const res = await apiFetch(`${API_BASE_URL}/rezervacije?\_t=${Date.now()}`)
        const data = await this.procitajJson(res)
        if (!res.ok) {
          this.zahtjevi = []
          this.poruka = { tekst: this.porukaGreske(data, 'Greška pri dohvaćanju rezervacija.'), greska: true }
          return
        }
        const lista = Array.isArray(data?.rezervacije) ? data.rezervacije : (Array.isArray(data) ? data : [])
        this.zahtjevi = lista.map(z => ({
          ...z,
          rezervacija_id: z.rezervacija_id ?? z.id,
          rezervacija_status: z.rezervacija_status ?? z.status,
          instruktor_ime: z.instruktor_ime ?? z.tutor_ime ?? '',
          instruktor_prezime: z.instruktor_prezime ?? z.tutor_prezime ?? ''
        }))
      } catch (err) {
        console.error('Greška pri dohvaćanju zahtjeva:', err)
        this.zahtjevi = []
        this.poruka = { tekst: 'Greška pri povezivanju s poslužiteljem.', greska: true }
      } finally {
        this.ucitavanjeZahtjevi = false
      }
    },
    async odgovoriNaZahtjev(rezervacija_id, status) {
      try {
        const res = await apiFetch(`${API_BASE_URL}/rezervacije/${rezervacija_id}`, {
          method: 'PUT',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ status })
        })
        const data = await this.procitajJson(res)
        if (this.jeUspjeh(res, data)) {
          this.poruka = {
            tekst: data.poruka || (status === 'prihvaćeno' ? 'Rezervacija je prihvaćena.' : 'Rezervacija je odbijena.'),
            greska: false
          }
          await this.dohvatiZahtjeve()
        } else {
          this.poruka = { tekst: this.porukaGreske(data, 'Greška pri obradi zahtjeva.'), greska: true }
        }
      } catch (err) {
        console.error(err)
        this.poruka = { tekst: 'Greška pri povezivanju s poslužiteljem.', greska: true }
      }
    },
async obrisiRezervaciju(rezervacija_id) {
  if (
    !confirm(
      'Jeste li sigurni da želite obrisati ovu rezervaciju?'
    )
  ) {
    return
  }

  try {
    const res = await apiFetch(
      `${API_BASE_URL}/rezervacije/${rezervacija_id}`,
      {
        method: 'DELETE'
      }
    )

    const data = await this.procitajJson(res)

    if (this.jeUspjeh(res, data)) {
      this.poruka = {
        tekst:
          data.poruka ||
          'Rezervacija je uspješno obrisana.',
        greska: false
      }

      await this.dohvatiZahtjeve()
    } else {
      this.poruka = {
        tekst: this.porukaGreske(
          data,
          'Greška pri brisanju rezervacije.'
        ),
        greska: true
      }
    }
  } catch (err) {
    console.error(
      'Greška pri brisanju rezervacije:',
      err
    )

    this.poruka = {
      tekst: 'Greška pri povezivanju s poslužiteljem.',
      greska: true
    }
  }
},





    async dohvatiPredmete() {
      try {
        const res = await apiFetch(`${API_BASE_URL}/predmeti`)
        const data = await this.procitajJson(res)
        if (!res.ok) {
          this.predmeti = []
          this.predmetiZaDodjelu = []
          return
        }
        const lista = Array.isArray(data?.predmeti) ? data.predmeti : (Array.isArray(data) ? data : [])
        this.predmeti = lista
        this.predmetiZaDodjelu = [...lista]
      } catch (err) {
        console.error('Greška pri dohvaćanju predmeta:', err)
        this.predmeti = []
        this.predmetiZaDodjelu = []
      }
    },
    async dodajPredmet() {
      this.ucitavanjePredmet = true
      try {
        const res = await apiFetch(`${API_BASE_URL}/predmeti`, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(this.noviPredmet)
        })
        const data = await this.procitajJson(res)
        if (this.jeUspjeh(res, data)) {
          this.poruka = { tekst: data.poruka || 'Predmet je uspješno dodan.', greska: false }
          this.noviPredmet = { naziv: '', opis: '' }
          await this.dohvatiPredmete()
        } else {
          this.poruka = { tekst: this.porukaGreske(data, 'Greška pri dodavanju predmeta.'), greska: true }
        }
      } catch (err) {
        console.error(err)
        this.poruka = { tekst: 'Greška s poslužiteljem.', greska: true }
      } finally {
        this.ucitavanjePredmet = false
      }
    },
    otvoriUrediPredmetModal(predmet) {
      this.urediPredmet = { id: predmet.id, naziv: predmet.naziv, opis: predmet.opis || '' }
      this.prikaziUrediPredmetModal = true
    },
    zatvoriUrediPredmetModal() {
      this.prikaziUrediPredmetModal = false
      this.urediPredmet = { id: null, naziv: '', opis: '' }
    },
    async spremiUredjeniPredmet() {
      this.ucitavanjeUrediPredmet = true
      try {
        const res = await apiFetch(`${API_BASE_URL}/predmeti/${this.urediPredmet.id}`, {
          method: 'PUT',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ naziv: this.urediPredmet.naziv, opis: this.urediPredmet.opis })
        })
        const data = await this.procitajJson(res)
        if (this.jeUspjeh(res, data)) {
          this.poruka = { tekst: data.poruka || 'Predmet je uspješno uređen.', greska: false }
          this.zatvoriUrediPredmetModal()
          await this.dohvatiPredmete()
          await this.dohvatiTutorPredmetVeze()
        } else {
          this.poruka = { tekst: this.porukaGreske(data, 'Greška pri uređivanju predmeta.'), greska: true }
        }
      } catch (err) {
        console.error(err)
        this.poruka = { tekst: 'Greška pri povezivanju s poslužiteljem.', greska: true }
      } finally {
        this.ucitavanjeUrediPredmet = false
      }
    },
    async obrisiPredmet(id, naziv) {
      if (!confirm(`Jeste li sigurni da želite obrisati predmet "${naziv}"?`)) return
      try {
        const res = await apiFetch(`${API_BASE_URL}/predmeti/${id}`, { method: 'DELETE' })
        const data = await this.procitajJson(res)
        if (this.jeUspjeh(res, data)) {
          this.poruka = { tekst: data.poruka || 'Predmet je uspješno obrisan.', greska: false }
          await this.dohvatiPredmete()
          await this.dohvatiTutorPredmetVeze()
        } else {
          this.poruka = { tekst: this.porukaGreske(data, 'Greška pri brisanju predmeta.'), greska: true }
        }
      } catch (err) {
        console.error(err)
        this.poruka = { tekst: 'Greška pri povezivanju s poslužiteljem.', greska: true }
      }
    },
    async dohvatiKorisnike() {
      try {
        const res = await apiFetch(`${API_BASE_URL}/korisnici`)
        const data = await this.procitajJson(res)
        if (!res.ok) {
          this.korisnici = []
          this.tutori = []
          this.poruka = { tekst: this.porukaGreske(data, 'Korisnike nije moguće dohvatiti.'), greska: true }
          return
        }
        this.korisnici = Array.isArray(data?.korisnici) ? data.korisnici : (Array.isArray(data) ? data : [])
        this.tutori = this.korisnici.filter(k => this.ulogaKorisnika(k) === 'Tutor')
        await this.dohvatiTutorPredmetVeze()
      } catch (err) {
        console.error('Greška pri dohvaćanju korisnika:', err)
        this.korisnici = []
        this.tutori = []
      }
    },
    async dodajKorisnika() {
      this.ucitavanjeKorisnik = true
      try {
        const res = await apiFetch(`${API_BASE_URL}/korisnici`, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(this.noviKorisnik)
        })
        const data = await this.procitajJson(res)
        if (this.jeUspjeh(res, data)) {
          this.poruka = { tekst: data.poruka || 'Korisnik je uspješno dodan.', greska: false }
          this.noviKorisnik = { ime: '', prezime: '', email: '', lozinka: '', uloga: 'Tutor' }
          await this.dohvatiKorisnike()
        } else {
          this.poruka = { tekst: this.porukaGreske(data, 'Greška pri dodavanju korisnika.'), greska: true }
        }
      } catch (err) {
        console.error(err)
        this.poruka = { tekst: 'Greška s poslužiteljem.', greska: true }
      } finally {
        this.ucitavanjeKorisnik = false
      }
    },
    otvoriUrediModal(korisnik) {
      this.urediKorisnik = {
        id: korisnik.id,
        ime: korisnik.ime,
        prezime: korisnik.prezime,
        email: korisnik.email,
        uloga: this.ulogaKorisnika(korisnik)
      }
      this.prikaziUrediModal = true
    },
    zatvoriUrediModal() {
      this.prikaziUrediModal = false
      this.urediKorisnik = { id: null, ime: '', prezime: '', email: '', uloga: '' }
    },
    async spremiUredjenogKorisnika() {
      this.ucitavanjeUredi = true
      try {
        const podaci = {
          ime: this.urediKorisnik.ime,
          prezime: this.urediKorisnik.prezime,
          email: this.urediKorisnik.email
        }
        // Super Administratoru dopuštamo promjenu osobnih podataka,
        // ali njegovu ulogu ne šaljemo backendu i ne mijenjamo je.
        if (this.urediKorisnik.uloga !== 'Super Administrator') {
          podaci.uloga = this.urediKorisnik.uloga
        }
        const res = await apiFetch(
          `${API_BASE_URL}/korisnici/${this.urediKorisnik.id}`,
          {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(podaci)
          }
        )
        const data = await this.procitajJson(res)
        if (this.jeUspjeh(res, data)) {
          this.poruka = {
            tekst: data.poruka || 'Korisnik je uspješno uređen.',
            greska: false
          }
          this.zatvoriUrediModal()
          await this.dohvatiKorisnike()
        } else {
          this.poruka = {
            tekst: this.porukaGreske(data, 'Greška pri uređivanju korisnika.'),
            greska: true
          }
        }
      } catch (err) {
        console.error(err)
        this.poruka = {
          tekst: 'Greška pri povezivanju s poslužiteljem.',
          greska: true
        }
      } finally {
        this.ucitavanjeUredi = false
      }
    },
    async obrisiKorisnika(id, ime) {
      if (!confirm(`Jeste li sigurni da želite ukloniti korisnika ${ime}?`)) return
      try {
        const res = await apiFetch(`${API_BASE_URL}/korisnici/${id}`, { method: 'DELETE' })
        const data = await this.procitajJson(res)
        if (this.jeUspjeh(res, data)) {
          this.poruka = { tekst: data.poruka || 'Korisnik je uspješno obrisan.', greska: false }
          await this.dohvatiKorisnike()
        } else {
          this.poruka = { tekst: this.porukaGreske(data, 'Greška pri brisanju korisnika.'), greska: true }
        }
      } catch (err) {
        console.error(err)
        this.poruka = { tekst: 'Problem s povezivanjem na poslužitelj.', greska: true }
      }
    },
    async osvjeziTutorPredmetPodatke() {
      await this.dohvatiKorisnike()
      await this.dohvatiPredmete()
      await this.dohvatiTutorPredmetVeze()
    },
    async dohvatiTutore() {
      this.tutori = this.korisnici.filter(k => this.ulogaKorisnika(k) === 'Tutor')
    },
    async dohvatiPredmeteZaDodjelu() {
      this.predmetiZaDodjelu = [...this.predmeti]
    },
    async dohvatiTutorPredmetVeze() {
      this.ucitavanjeVeze = true
      try {
        const res = await apiFetch(`${API_BASE_URL}/tutor-predmeti`)
        const data = await this.procitajJson(res)
        if (!res.ok) {
          this.tutorPredmetVeze = []
          return
        }
        this.tutorPredmetVeze = Array.isArray(data?.tutor_predmeti)
          ? data.tutor_predmeti
          : Array.isArray(data?.veze)
            ? data.veze
            : Array.isArray(data?.dodjele)
              ? data.dodjele
              : (Array.isArray(data) ? data : [])
      } catch (err) {
        console.error('Greška pri dohvaćanju veza tutor-predmet:', err)
        this.tutorPredmetVeze = []
      } finally {
        this.ucitavanjeVeze = false
      }
    },
    async dodajTutorPredmet() {
      this.ucitavanjeDodjela = true
      try {
        const res = await apiFetch(`${API_BASE_URL}/tutor-predmeti`, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(this.novaDodjela)
        })
        const data = await this.procitajJson(res)
        if (this.jeUspjeh(res, data)) {
          this.poruka = { tekst: data.poruka || 'Predmet je uspješno dodijeljen instruktoru.', greska: false }
          this.novaDodjela = { tutor_id: '', predmet_id: '' }
          await this.dohvatiTutorPredmetVeze()
        } else {
          this.poruka = { tekst: this.porukaGreske(data, 'Greška pri dodjeli predmeta.'), greska: true }
        }
      } catch (err) {
        console.error(err)
        this.poruka = { tekst: 'Greška pri povezivanju s poslužiteljem.', greska: true }
      } finally {
        this.ucitavanjeDodjela = false
      }
    },
    otvoriUrediDodjeluModal(veza) {
      this.urediDodjela = { id: veza.id, tutor_id: veza.tutor_id, predmet_id: veza.predmet_id }
      this.prikaziUrediDodjeluModal = true
    },
    zatvoriUrediDodjeluModal() {
      this.prikaziUrediDodjeluModal = false
      this.urediDodjela = { id: null, tutor_id: '', predmet_id: '' }
    },
    async spremiUredjenuDodjelu() {
      this.ucitavanjeUrediDodjela = true
      try {
        const res = await apiFetch(`${API_BASE_URL}/tutor-predmeti/${this.urediDodjela.id}`, {
          method: 'PUT',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ tutor_id: this.urediDodjela.tutor_id, predmet_id: this.urediDodjela.predmet_id })
        })
        const data = await this.procitajJson(res)
        if (this.jeUspjeh(res, data)) {
          this.poruka = { tekst: data.poruka || 'Dodjela je uspješno uređena.', greska: false }
          this.zatvoriUrediDodjeluModal()
          await this.dohvatiTutorPredmetVeze()
        } else {
          this.poruka = { tekst: this.porukaGreske(data, 'Greška pri uređivanju dodjele.'), greska: true }
        }
      } catch (err) {
        console.error(err)
        this.poruka = { tekst: 'Greška pri povezivanju s poslužiteljem.', greska: true }
      } finally {
        this.ucitavanjeUrediDodjela = false
      }
    },
    async obrisiTutorPredmet(veza) {
      const naziv = `${veza.tutor_ime} ${veza.tutor_prezime} - ${veza.predmet_naziv}`
      if (!confirm(`Želite li ukloniti dodjelu "${naziv}"?`)) return
      try {
        const res = await apiFetch(`${API_BASE_URL}/tutor-predmeti/${veza.id}`, { method: 'DELETE' })
        const data = await this.procitajJson(res)
        if (this.jeUspjeh(res, data)) {
          this.poruka = { tekst: data.poruka || 'Dodjela je uklonjena.', greska: false }
          await this.dohvatiTutorPredmetVeze()
        } else {
          this.poruka = { tekst: this.porukaGreske(data, 'Greška pri uklanjanju dodjele.'), greska: true }
        }
      } catch (err) {
        console.error(err)
        this.poruka = { tekst: 'Greška pri povezivanju s poslužiteljem.', greska: true }
      }
    },
    async dohvatiUlogeIDozvole() {
      this.ucitavanjeUlogeDozvole = true
      try {
        const [ulogeRes, dozvoleRes] = await Promise.all([
          apiFetch(`${API_BASE_URL}/uloge`),
          apiFetch(`${API_BASE_URL}/dozvole-sve`)
        ])
        const ulogeData = await this.procitajJson(ulogeRes)
        const dozvoleData = await this.procitajJson(dozvoleRes)
        if (!ulogeRes.ok) {
          this.poruka = {
            tekst: this.porukaGreske(ulogeData, 'Greška pri dohvaćanju uloga.'),
            greska: true
          }
          return
        }
        if (!dozvoleRes.ok) {
          this.poruka = {
            tekst: this.porukaGreske(dozvoleData, 'Greška pri dohvaćanju dozvola.'),
            greska: true
          }
          return
        }
        this.uloge = Array.isArray(ulogeData?.uloge)
          ? ulogeData.uloge
          : []
        this.dozvole = Array.isArray(dozvoleData?.dozvole)
          ? dozvoleData.dozvole
          : []
        if (this.odabranaUlogaId) {
          await this.dohvatiDozvoleUloge()
        }
      } catch (err) {
        console.error(err)
        this.poruka = {
          tekst: 'Greška pri povezivanju s poslužiteljem.',
          greska: true
        }
      } finally {
        this.ucitavanjeUlogeDozvole = false
      }
    },
    async dohvatiDozvoleUloge() {
      if (!this.odabranaUlogaId) {
        this.odabraneDozvole = []
        return
      }
      try {
        const res = await apiFetch(
          `${API_BASE_URL}/uloge/${this.odabranaUlogaId}/dozvole`
        )
        const data = await this.procitajJson(res)
        if (!res.ok) {
          this.poruka = {
            tekst: this.porukaGreske(data, 'Greška pri dohvaćanju dozvola uloge.'),
            greska: true
          }
          return
        }
        this.odabraneDozvole = Array.isArray(data?.dozvole)
          ? data.dozvole.map(id => Number(id))
          : []
      } catch (err) {
        console.error(err)
        this.poruka = {
          tekst: 'Greška pri povezivanju s poslužiteljem.',
          greska: true
        }
      }
    },
    async spremiDozvoleUloge() {
      if (!this.odabranaUlogaId) {
        this.poruka = { tekst: 'Odaberite ulogu.', greska: true }
        return
      }
      this.spremanjeDozvola = true
      try {
        const res = await apiFetch(
          `${API_BASE_URL}/uloge/${this.odabranaUlogaId}/dozvole`,
          {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ dozvole: this.odabraneDozvole })
          }
        )
        const data = await this.procitajJson(res)
        if (!res.ok) {
          this.poruka = {
            tekst: this.porukaGreske(data, 'Greška pri spremanju dozvola.'),
            greska: true
          }
          return
        }
        this.poruka = {
          tekst: 'Dozvole su uspješno spremljene.',
          greska: false
        }
        await this.dohvatiDozvoleUloge()
      } catch (err) {
        console.error(err)
        this.poruka = {
          tekst: 'Greška pri povezivanju s poslužiteljem.',
          greska: true
        }
      } finally {
        this.spremanjeDozvola = false
      }
    },
    getStatusBadgeClass(status) {
      if (status === 'prihvaćeno') return 'badge bg-success'
      if (status === 'odbijeno') return 'badge bg-danger'
      return 'badge bg-warning text-dark'
    }
  }
}
</script>
