<template>
  <div class="container py-4">

    <!-- PRETRAŽIVANJE -->
    <div class="card p-4 shadow-sm mb-4 border-0">
      <h5 class="fw-bold mb-3">
        Pretraživanje termina
      </h5>

      <form
        @submit.prevent="dohvatiTermine"
        class="row g-3"
      >

        <div class="col-md-4">
          <label class="form-label small text-muted">
            Predmet
          </label>

          <select
            v-model="filteri.predmet_id"
            class="form-select"
          >
            <option value="">
              Svi predmeti
            </option>

            <option
              v-for="p in predmeti"
              :key="p.id"
              :value="p.id"
            >
              {{ p.naziv }}
            </option>
          </select>
        </div>


        <div class="col-md-3">
          <label class="form-label small text-muted">
            Datum
          </label>

          <input
            type="date"
            v-model="filteri.datum"
            class="form-control"
          />
        </div>


        <div class="col-md-3">
          <label class="form-label small text-muted">
            Instruktor
          </label>

          <input
            type="text"
            v-model="filteri.instruktor"
            class="form-control"
            placeholder="Ime ili prezime..."
          />
        </div>


        <div class="col-md-2 d-flex align-items-end">
          <button
            type="submit"
            class="btn btn-primary w-100 fw-semibold"
          >
            Pretraži
          </button>
        </div>

      </form>
    </div>


    <!-- UČITAVANJE -->
    <div
      v-if="ucitavanje"
      class="text-center py-5"
    >
      <div
        class="spinner-border text-primary"
        role="status"
      ></div>
    </div>


    <!-- NEMA TERMINA -->
    <div
      v-else-if="termini.length === 0"
      class="alert alert-info text-center py-4 shadow-sm"
    >
      Trenutno nema slobodnih termina koji odgovaraju
      odabranim kriterijima.
    </div>


    <!-- TERMINI -->
    <div
      v-else
      class="row g-4 mb-5"
    >

      <div
        v-for="t in termini"
        :key="t.id"
        class="col-md-6 col-lg-4"
      >

        <div class="card h-100 shadow-sm border-0">

          <div class="card-body d-flex flex-column p-4">

            <div
              class="d-flex justify-content-between align-items-start mb-3"
            >

              <span
                class="badge bg-primary-subtle text-primary fw-bold px-3 py-2 rounded-pill fs-6"
              >
                {{ t.predmet_naziv }}
              </span>

              <span class="fs-5 fw-bold text-success">
                {{ Number(t.cijena).toFixed(2) }} KM
              </span>

            </div>


            <h6 class="card-subtitle my-2 text-dark fw-semibold">
              Instruktor:
              {{ t.instruktor_ime }}
              {{ t.instruktor_prezime }}
            </h6>


            <div class="my-2 small text-secondary">

              <div>
                📅 {{ formatDatum(t.datum) }}
              </div>

              <div>
                ⏰
                {{
                  t.vrijeme_od
                    ? t.vrijeme_od.substring(0, 5)
                    : ''
                }}
                -
                {{
                  t.vrijeme_do
                    ? t.vrijeme_do.substring(0, 5)
                    : ''
                }}
                h
              </div>

            </div>


            <div
              class="mt-auto pt-3 border-top d-flex gap-2 flex-wrap"
            >

              <!-- GOST -->
              <router-link
                v-if="!korisnik"
                to="/login"
                class="btn btn-sm btn-outline-primary w-100"
              >
                Prijavite se za rezervaciju
              </router-link>


              <!-- STUDENT -->
              <button
                v-else-if="jeStudent"
                @click="otvoriModal(t)"
                class="btn btn-sm btn-success w-100 fw-semibold"
                :disabled="slanje"
              >
                Rezerviraj termin
              </button>


              <!-- ADMIN / TUTOR -->
              <button
                v-if="mozeUpravljatiTermin(t)"
                @click="otvoriUrediTerminModal(t)"
                class="btn btn-sm btn-outline-primary flex-fill fw-semibold"
                :disabled="slanje"
              >
                Uredi
              </button>


              <button
                v-if="mozeUpravljatiTermin(t)"
                @click="obrisiTermin(t.id)"
                class="btn btn-sm btn-outline-danger flex-fill fw-semibold"
                :disabled="slanje"
              >
                Obriši
              </button>

            </div>

          </div>

        </div>

      </div>

    </div>


    <!-- ================================================= -->
    <!-- MODAL ZA REZERVACIJU -->
    <!-- ================================================= -->

    <div
      v-if="prikaziModal"
      class="modal fade show d-block"
      style="background-color: rgba(0,0,0,0.5);"
    >

      <div class="modal-dialog modal-dialog-centered">

        <div class="modal-content shadow">

          <div class="modal-header">

            <h5 class="modal-title fw-bold">
              Rezervacija termina
            </h5>

            <button
              type="button"
              class="btn-close"
              @click="zatvoriModal"
            ></button>

          </div>


          <div
            class="modal-body"
            v-if="odabraniTermin"
          >

            <p class="mb-2">
              <strong>Predmet:</strong>
              {{ odabraniTermin.predmet_naziv }}
            </p>


            <p class="mb-2">
              <strong>Instruktor:</strong>

              {{ odabraniTermin.instruktor_ime }}
              {{ odabraniTermin.instruktor_prezime }}
            </p>


            <p class="mb-3">

              <strong>Termin:</strong>

              {{ formatDatum(odabraniTermin.datum) }}

              (
              {{
                odabraniTermin.vrijeme_od
                  ? odabraniTermin.vrijeme_od.substring(0, 5)
                  : ''
              }}
              -
              {{
                odabraniTermin.vrijeme_do
                  ? odabraniTermin.vrijeme_do.substring(0, 5)
                  : ''
              }}
              h)

            </p>


            <div class="mb-3">

              <label class="form-label fw-semibold small">
                Napomena (opcionalno):
              </label>

              <textarea
                v-model="forma.napomena"
                class="form-control"
                rows="3"
                placeholder="Unesite dodatne napomene za instruktora..."
              ></textarea>

            </div>

            <div class="mb-3">
              <label class="form-label fw-semibold small">
                PDF dokument (opcionalno):
              </label>
              <input
                type="file"
                class="form-control"
                accept="application/pdf,.pdf"
                @change="odaberiPrivitak"
              />
              <div class="form-text">
                Možete priložiti jedan PDF dokument, najviše 10 MB.
              </div>
            </div>

          </div>


          <div class="modal-footer">

            <button
              type="button"
              class="btn btn-secondary"
              @click="zatvoriModal"
              :disabled="slanje"
            >
              Odustani
            </button>


            <button
              type="button"
              class="btn btn-primary fw-semibold"
              @click="potvrdiRezervaciju"
              :disabled="slanje"
            >

              <span
                v-if="slanje"
                class="spinner-border spinner-border-sm me-1"
                role="status"
              ></span>

              Potvrdi rezervaciju

            </button>

          </div>

        </div>

      </div>

    </div>


    <!-- ================================================= -->
    <!-- MODAL ZA UREĐIVANJE TERMINA -->
    <!-- ================================================= -->

    <div
      v-if="prikaziUrediTerminModal"
      class="modal fade show d-block"
      style="background-color: rgba(0,0,0,0.5);"
    >

      <div class="modal-dialog modal-dialog-centered">

        <div class="modal-content shadow">

          <div class="modal-header">

            <h5 class="modal-title fw-bold">
              Uredi termin
            </h5>

            <button
              type="button"
              class="btn-close"
              @click="zatvoriUrediTerminModal"
            ></button>

          </div>


          <form @submit.prevent="spremiUredjeniTermin">

            <div class="modal-body">


              <div class="mb-3">

                <label class="form-label fw-semibold">
                  Predmet:
                </label>

                <select
                  v-model="urediTermin.predmet_id"
                  class="form-select"
                  required
                >

                  <option value="">
                    -- Odaberi predmet --
                  </option>

                  <option
                    v-for="p in predmeti"
                    :key="p.id"
                    :value="p.id"
                  >
                    {{ p.naziv }}
                  </option>

                </select>

              </div>


              <div class="mb-3">

                <label class="form-label fw-semibold">
                  Datum:
                </label>

                <input
                  type="date"
                  v-model="urediTermin.datum"
                  class="form-control"
                  required
                />

              </div>


              <div class="row">

                <div class="col-md-6 mb-3">

                  <label class="form-label fw-semibold">
                    Vrijeme od:
                  </label>

                  <input
                    type="time"
                    v-model="urediTermin.vrijeme_od"
                    class="form-control"
                    required
                  />

                </div>


                <div class="col-md-6 mb-3">

                  <label class="form-label fw-semibold">
                    Vrijeme do:
                  </label>

                  <input
                    type="time"
                    v-model="urediTermin.vrijeme_do"
                    class="form-control"
                    required
                  />

                </div>

              </div>


              <div class="mb-3">

                <label class="form-label fw-semibold">
                  Cijena (KM):
                </label>

                <input
                  type="number"
                  step="0.5"
                  min="0"
                  v-model="urediTermin.cijena"
                  class="form-control"
                  required
                />

              </div>

            </div>


            <div class="modal-footer">

              <button
                type="button"
                class="btn btn-secondary"
                @click="zatvoriUrediTerminModal"
                :disabled="spremanjeUredjivanja"
              >
                Odustani
              </button>


              <button
                type="submit"
                class="btn btn-primary fw-semibold"
                :disabled="spremanjeUredjivanja"
              >

                {{
                  spremanjeUredjivanja
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

export default {

  name: 'TerminiView',


  data() {

    return {

      korisnik: null,

      predmeti: [],
      termini: [],

      filteri: {
        predmet_id: '',
        datum: '',
        instruktor: ''
      },

      ucitavanje: false,
      slanje: false,

      prikaziModal: false,
      odabraniTermin: null,

      forma: {
        napomena: '',
        privitak: null
      },

      prikaziUrediTerminModal: false,
      spremanjeUredjivanja: false,

      urediTermin: {
        id: null,
        predmet_id: '',
        datum: '',
        vrijeme_od: '',
        vrijeme_do: '',
        cijena: 0
      }

    }
  },


  computed: {

    jeStudent() {

      if (!this.korisnik) {
        return false
      }


      const uloga = (
        this.korisnik.uloga ||
        this.korisnik.role ||
        ''
      ).toLowerCase()


      return (
        uloga === 'student' ||
        uloga === 'učenik' ||
        uloga === 'ucenik'
      )
    }

  },


  mounted() {

    const podaci =
      localStorage.getItem('korisnik') ||
      localStorage.getItem('user')


    if (podaci) {

      try {

        this.korisnik =
          JSON.parse(podaci)

      } catch (e) {

        console.error(
          'Greška pri parsiranju korisnika:',
          e
        )
      }
    }


    this.dohvatiPredmete()

    this.dohvatiTermine()
  },


  methods: {


    // =====================================================
    // MOŽE LI KORISNIK UPRAVLJATI TERMINOM
    // =====================================================

    mozeUpravljatiTermin(termin) {

      if (!this.korisnik || !termin) {
        return false
      }


      const uloga = (
        this.korisnik.uloga ||
        this.korisnik.role ||
        ''
      ).toLowerCase()


      if (
        uloga === 'admin' ||
        uloga === 'administrator' ||
        uloga === 'super administrator'
      ) {
        return true
      }


      if (
        uloga === 'tutor' ||
        uloga === 'instruktor'
      ) {

        const korisnikId =
          this.korisnik.id ||
          this.korisnik.korisnik_id ||
          this.korisnik.user_id


        return (
          Number(termin.tutor_id) ===
          Number(korisnikId)
        )
      }


      return false
    },


    // =====================================================
    // PREDMETI
    // =====================================================

    async dohvatiPredmete() {

      try {

        const res = await fetch(
          `${API_BASE_URL}/predmeti`,
          {
            credentials: 'include'
          }
        )


        const data =
          await res.json()


        if (!res.ok) {

          console.error(
            data.messages?.error ||
            data.poruka ||
            'Greška pri učitavanju predmeta.'
          )

          this.predmeti = []

          return
        }


        if (Array.isArray(data)) {

          this.predmeti = data

        } else if (
          Array.isArray(data.predmeti)
        ) {

          this.predmeti =
            data.predmeti

        } else {

          this.predmeti = []
        }

      } catch (err) {

        console.error(
          'Greška pri učitavanju predmeta:',
          err
        )

        this.predmeti = []
      }
    },


    // =====================================================
    // TERMINI
    // =====================================================

    async dohvatiTermine() {

      this.ucitavanje = true


      try {

        const res = await fetch(
          `${API_BASE_URL}/termini`,
          {
            credentials: 'include'
          }
        )


        const data =
          await res.json()


        if (!res.ok) {

          console.error(
            data.messages?.error ||
            data.poruka ||
            'Greška pri učitavanju termina.'
          )

          this.termini = []

          return
        }


        let rezultati = []


        if (
          Array.isArray(data.termini)
        ) {

          rezultati =
            data.termini

        } else if (
          Array.isArray(data)
        ) {

          rezultati =
            data
        }


        // FILTER 1 - PREDMET
        if (
          this.filteri.predmet_id
        ) {

          rezultati =
            rezultati.filter(
              termin =>
                Number(termin.predmet_id) ===
                Number(
                  this.filteri.predmet_id
                )
            )
        }


        // FILTER 2 - DATUM
        if (
          this.filteri.datum
        ) {

          rezultati =
            rezultati.filter(
              termin =>
                termin.datum ===
                this.filteri.datum
            )
        }


        // FILTER 3 - INSTRUKTOR
        if (
          this.filteri.instruktor.trim()
        ) {

          const trazeni =
            this.filteri.instruktor
              .trim()
              .toLowerCase()


          rezultati =
            rezultati.filter(
              termin => {

                const instruktor =
                  `${
                    termin.instruktor_ime || ''
                  } ${
                    termin.instruktor_prezime || ''
                  }`
                    .toLowerCase()


                return instruktor.includes(
                  trazeni
                )
              }
            )
        }


        this.termini =
          rezultati

      } catch (err) {

        console.error(
          'Greška pri učitavanju termina:',
          err
        )

        this.termini = []

      } finally {

        this.ucitavanje = false
      }
    },


    // =====================================================
    // REZERVACIJA
    // =====================================================

    otvoriModal(termin) {

      this.odabraniTermin =
        termin

      this.forma.napomena =
        ''
      this.forma.privitak = null

      this.prikaziModal =
        true
    },


    zatvoriModal() {

      this.prikaziModal =
        false

      this.odabraniTermin =
        null

      this.forma.napomena =
        ''
      this.forma.privitak = null
    },


    odaberiPrivitak(event) {
      const datoteka = event.target.files?.[0] || null

      if (!datoteka) {
        this.forma.privitak = null
        return
      }

      if (
        datoteka.type !== 'application/pdf' &&
        !datoteka.name.toLowerCase().endsWith('.pdf')
      ) {
        alert('Dopušten je samo PDF dokument.')
        event.target.value = ''
        this.forma.privitak = null
        return
      }

      if (datoteka.size > 10 * 1024 * 1024) {
        alert('PDF dokument smije imati najviše 10 MB.')
        event.target.value = ''
        this.forma.privitak = null
        return
      }

      this.forma.privitak = datoteka
    },


    async potvrdiRezervaciju() {

      if (!this.odabraniTermin) {
        return
      }


      this.slanje = true


      try {

        const formData = new FormData()

        formData.append(
          'termin_id',
          this.odabraniTermin.id
        )

        formData.append(
          'napomena',
          this.forma.napomena || ''
        )

        if (this.forma.privitak) {
          formData.append(
            'privitak',
            this.forma.privitak
          )
        }

        const res = await fetch(
          `${API_BASE_URL}/rezervacije`,
          {
            method: 'POST',
            credentials: 'include',
            body: formData
          }
        )


        const data =
          await res.json()


        if (
          res.ok &&
          data.uspjeh
        ) {

          alert(
            data.poruka ||
            'Rezervacija je uspješno poslana.'
          )


          this.zatvoriModal()

          await this.dohvatiTermine()

        } else {

          alert(
            data.messages?.error ||
            data.poruka ||
            'Greška prilikom rezervacije.'
          )
        }

      } catch (err) {

        console.error(
          'Greška pri rezervaciji:',
          err
        )


        alert(
          'Mrežna greška prilikom rezervacije.'
        )

      } finally {

        this.slanje = false
      }
    },


    // =====================================================
    // OTVARANJE UREĐIVANJA
    // =====================================================

    otvoriUrediTerminModal(termin) {

      this.urediTermin = {

        id:
          termin.id,

        predmet_id:
          termin.predmet_id,

        datum:
          termin.datum,

        vrijeme_od:
          termin.vrijeme_od
            ? termin.vrijeme_od.substring(
                0,
                5
              )
            : '',

        vrijeme_do:
          termin.vrijeme_do
            ? termin.vrijeme_do.substring(
                0,
                5
              )
            : '',

        cijena:
          termin.cijena
      }


      this.prikaziUrediTerminModal =
        true
    },


    zatvoriUrediTerminModal() {

      this.prikaziUrediTerminModal =
        false


      this.urediTermin = {

        id: null,
        predmet_id: '',
        datum: '',
        vrijeme_od: '',
        vrijeme_do: '',
        cijena: 0

      }
    },


    // =====================================================
    // UREĐIVANJE TERMINA
    // =====================================================

    async spremiUredjeniTermin() {

      if (
        this.urediTermin.vrijeme_do <=
        this.urediTermin.vrijeme_od
      ) {

        alert(
          'Vrijeme završetka mora biti nakon vremena početka.'
        )

        return
      }


      this.spremanjeUredjivanja =
        true


      try {

        const res = await fetch(
          `${API_BASE_URL}/termini/${this.urediTermin.id}`,
          {
            method: 'PUT',

            credentials: 'include',

            headers: {
              'Content-Type':
                'application/json'
            },

            body: JSON.stringify({

              predmet_id:
                this.urediTermin.predmet_id,

              datum:
                this.urediTermin.datum,

              vrijeme_od:
                this.urediTermin.vrijeme_od,

              vrijeme_do:
                this.urediTermin.vrijeme_do,

              cijena:
                this.urediTermin.cijena

            })
          }
        )


        const data =
          await res.json()


        if (
          res.ok &&
          data.uspjeh
        ) {

          alert(
            data.poruka ||
            'Termin je uspješno uređen.'
          )


          this.zatvoriUrediTerminModal()

          await this.dohvatiTermine()

        } else {

          alert(
            data.messages?.error ||
            data.poruka ||
            'Greška pri uređivanju termina.'
          )
        }

      } catch (err) {

        console.error(
          'Greška pri uređivanju termina:',
          err
        )


        alert(
          'Mrežna greška pri uređivanju termina.'
        )

      } finally {

        this.spremanjeUredjivanja =
          false
      }
    },


    // =====================================================
    // BRISANJE TERMINA
    // =====================================================

    async obrisiTermin(terminId) {

      if (
        !confirm(
          'Jeste li sigurni da želite obrisati ovaj termin?'
        )
      ) {
        return
      }


      this.slanje =
        true


      try {

        const res = await fetch(
          `${API_BASE_URL}/termini/${terminId}`,
          {
            method: 'DELETE',
            credentials: 'include'
          }
        )


        const data =
          await res.json()


        if (
          res.ok &&
          data.uspjeh
        ) {

          alert(
            data.poruka ||
            'Termin je uspješno obrisan.'
          )


          await this.dohvatiTermine()

        } else {

          alert(
            data.messages?.error ||
            data.poruka ||
            'Greška pri brisanju termina.'
          )
        }

      } catch (err) {

        console.error(
          'Greška pri brisanju termina:',
          err
        )


        alert(
          'Mrežna greška pri brisanju termina.'
        )

      } finally {

        this.slanje =
          false
      }
    },


    // =====================================================
    // FORMAT DATUMA
    // =====================================================

    formatDatum(datumStr) {

      if (!datumStr) {
        return ''
      }


      const dijelovi =
        datumStr.split('-')


      if (
        dijelovi.length === 3
      ) {

        return (
          `${dijelovi[2]}.` +
          `${dijelovi[1]}.` +
          `${dijelovi[0]}.`
        )
      }


      return datumStr
    }

  }
}
</script>