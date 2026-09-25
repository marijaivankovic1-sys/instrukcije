<template>
  <div class="container py-4">
    <div class="card p-4 shadow-sm border-0">

      <h4 class="fw-bold mb-4">
        Dojmovi i recenzije
      </h4>

      <!-- DODAVANJE NOVE RECENZIJE -->
      <div
        v-if="jeStudent"
        class="p-3 bg-light rounded-3 mb-4"
      >
        <h6 class="fw-bold mb-3">
          Ostavite recenziju za instruktora
        </h6>

        <div
          v-if="instruktori.length === 0"
          class="alert alert-info small"
        >
          Recenziju možete ostaviti nakon što imate prihvaćenu
          rezervaciju kod instruktora.
        </div>

        <form
          v-else
          @submit.prevent="posaljiRecenziju"
        >
          <div class="row g-3">

            <!-- INSTRUKTOR -->
            <div class="col-md-4">
              <label class="form-label small text-muted">
                Odaberite instruktora
              </label>

              <select
                v-model="novaRecenzija.tutor_id"
                class="form-select"
                required
              >
                <option value="">
                  -- Odaberi instruktora --
                </option>

                <option
                  v-for="i in instruktori"
                  :key="i.id"
                  :value="i.id"
                >
                  {{ i.ime }} {{ i.prezime }}
                </option>
              </select>
            </div>

            <!-- OCJENA -->
            <div class="col-md-3">
              <label class="form-label small text-muted">
                Ocjena (1-5)
              </label>

              <select
                v-model="novaRecenzija.ocjena"
                class="form-select"
                required
              >
                <option value="5">
                  5 ⭐⭐⭐⭐⭐
                </option>

                <option value="4">
                  4 ⭐⭐⭐⭐
                </option>

                <option value="3">
                  3 ⭐⭐⭐
                </option>

                <option value="2">
                  2 ⭐⭐
                </option>

                <option value="1">
                  1 ⭐
                </option>
              </select>
            </div>

            <!-- KOMENTAR -->
            <div class="col-md-5">
              <label class="form-label small text-muted">
                Komentar
              </label>

              <input
                v-model="novaRecenzija.komentar"
                type="text"
                class="form-control"
                placeholder="Napišite vaše iskustvo..."
                required
              />
            </div>

            <div class="col-12 text-end">
              <button
                type="submit"
                class="btn btn-primary btn-sm px-4 fw-semibold"
                :disabled="slanje"
              >
                {{
                  slanje
                    ? 'Objavljivanje...'
                    : 'Objavi recenziju'
                }}
              </button>
            </div>

          </div>
        </form>
      </div>

      <!-- UČITAVANJE -->
      <div
        v-if="ucitavanje"
        class="text-center py-4"
      >
        <div
          class="spinner-border text-primary"
          role="status"
        ></div>
      </div>

      <!-- NEMA RECENZIJA -->
      <div
        v-else-if="recenzije.length === 0"
        class="text-muted small"
      >
        Trenutno nema objavljenih recenzija.
      </div>

      <!-- LISTA RECENZIJA -->
      <div
        v-else
        class="row g-3"
      >
        <div
          v-for="r in recenzije"
          :key="r.id"
          class="col-md-6"
        >
          <div
            class="p-3 border rounded-3 bg-white h-100 shadow-sm"
          >

            <div
              class="d-flex justify-content-between align-items-start mb-2"
            >
              <div>

                <strong class="text-dark d-block">
                  {{ r.student_ime }}
                  {{ r.student_prezime }}
                </strong>

                <small class="text-primary fw-semibold">
                  Instruktor:
                  {{
                    r.tutor_ime && r.tutor_prezime
                      ? `${r.tutor_ime} ${r.tutor_prezime}`
                      : 'Opća recenzija'
                  }}
                </small>

              </div>

              <span
                class="text-warning font-monospace fs-6"
              >
                {{ '★'.repeat(Number(r.ocjena)) }}
              </span>
            </div>

            <p class="text-secondary small mb-3 mt-2">
              {{ r.komentar }}
            </p>

            <!-- UREDI / OBRIŠI VLASTITU RECENZIJU -->
            <div
              v-if="mozeUrediti(r)"
              class="d-flex gap-2 justify-content-end"
            >
              <button
                class="btn btn-sm btn-outline-primary"
                @click="otvoriUrediModal(r)"
              >
                Uredi
              </button>

              <button
                class="btn btn-sm btn-outline-danger"
                @click="obrisiRecenziju(r)"
              >
                Obriši
              </button>
            </div>

          </div>
        </div>
      </div>

      <!-- MODAL ZA UREĐIVANJE -->
      <div
        v-if="prikaziUrediModal"
        class="modal fade show d-block"
        style="background-color: rgba(0,0,0,0.5);"
      >
        <div class="modal-dialog modal-dialog-centered">
          <div class="modal-content">

            <div class="modal-header">
              <h5 class="modal-title fw-bold">
                Uredi recenziju
              </h5>

              <button
                type="button"
                class="btn-close"
                @click="zatvoriUrediModal"
              ></button>
            </div>

            <form @submit.prevent="spremiUredjenuRecenziju">

              <div class="modal-body">

                <!-- INSTRUKTOR SE NE MIJENJA -->
                <div class="mb-3">
                  <label class="form-label fw-semibold">
                    Instruktor
                  </label>

                  <input
                    :value="urediRecenzija.tutor_naziv"
                    type="text"
                    class="form-control"
                    disabled
                  />
                </div>

                <!-- OCJENA -->
                <div class="mb-3">
                  <label class="form-label fw-semibold">
                    Ocjena
                  </label>

                  <select
                    v-model="urediRecenzija.ocjena"
                    class="form-select"
                    required
                  >
                    <option value="5">
                      5 ⭐⭐⭐⭐⭐
                    </option>

                    <option value="4">
                      4 ⭐⭐⭐⭐
                    </option>

                    <option value="3">
                      3 ⭐⭐⭐
                    </option>

                    <option value="2">
                      2 ⭐⭐
                    </option>

                    <option value="1">
                      1 ⭐
                    </option>
                  </select>
                </div>

                <!-- KOMENTAR -->
                <div class="mb-3">
                  <label class="form-label fw-semibold">
                    Komentar
                  </label>

                  <textarea
                    v-model="urediRecenzija.komentar"
                    class="form-control"
                    rows="4"
                    required
                  ></textarea>
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
  </div>
</template>


<script>

import { API_BASE_URL } from '@/config/api'

export default {

  name: 'RecenzijeView',

  data() {
    return {

      korisnik: null,

      recenzije: [],
      instruktori: [],

      novaRecenzija: {
        tutor_id: '',
        ocjena: '5',
        komentar: ''
      },

      urediRecenzija: {
        id: null,
        tutor_id: '',
        tutor_naziv: '',
        ocjena: '5',
        komentar: ''
      },

      prikaziUrediModal: false,

      ucitavanje: false,
      slanje: false,
      spremanjeUredjivanja: false

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

      } catch (err) {

        console.error(
          'Greška pri učitavanju korisnika:',
          err
        )
      }
    }

    this.dohvatiRecenzije()

    if (this.jeStudent) {
      this.dohvatiInstruktore()
    }
  },


  methods: {

    // =====================================================
    // MOŽE LI KORISNIK UREDITI RECENZIJU
    // =====================================================

    mozeUrediti(recenzija) {

      if (!this.korisnik) {
        return false
      }

      const korisnikId =
        Number(
          this.korisnik.id ||
          this.korisnik.korisnik_id ||
          this.korisnik.user_id
        )

      return (
        this.jeStudent &&
        Number(recenzija.student_id) === korisnikId
      )
    },


    // =====================================================
    // DOHVATI RECENZIJE
    // =====================================================

    async dohvatiRecenzije() {

      this.ucitavanje = true

      try {

        const res = await fetch(
          `${API_BASE_URL}/recenzije`,
          {
            method: 'GET',
            credentials: 'include',
            headers: {
              Accept: 'application/json'
            }
          }
        )

        const data = await res.json()

        if (!res.ok) {

          console.error(
            data.poruka ||
            'Recenzije nije moguće dohvatiti.'
          )

          this.recenzije = []

          return
        }

        if (Array.isArray(data.recenzije)) {

          this.recenzije =
            data.recenzije

        } else if (Array.isArray(data)) {

          this.recenzije =
            data

        } else {

          this.recenzije = []
        }

      } catch (err) {

        console.error(
          'Greška pri učitavanju recenzija:',
          err
        )

        this.recenzije = []

      } finally {

        this.ucitavanje = false
      }
    },


    // =====================================================
    // DOHVATI INSTRUKTORE
    // =====================================================

    async dohvatiInstruktore() {

      if (!this.jeStudent) {
        return
      }

      try {

        const res = await fetch(
          `${API_BASE_URL}/instruktori`,
          {
            method: 'GET',
            credentials: 'include',
            headers: {
              Accept: 'application/json'
            }
          }
        )

        const data = await res.json()

        if (!res.ok) {

          console.error(
            data.poruka ||
            'Instruktore nije moguće dohvatiti.'
          )

          this.instruktori = []

          return
        }

        if (Array.isArray(data.instruktori)) {

          this.instruktori =
            data.instruktori

        } else if (Array.isArray(data)) {

          this.instruktori =
            data

        } else {

          this.instruktori = []
        }

      } catch (err) {

        console.error(
          'Greška pri učitavanju instruktora:',
          err
        )

        this.instruktori = []
      }
    },


    // =====================================================
    // DODAJ RECENZIJU
    // =====================================================

    async posaljiRecenziju() {

      if (!this.korisnik) {
        return
      }

      if (!this.novaRecenzija.tutor_id) {

        alert(
          'Odaberite instruktora.'
        )

        return
      }

      this.slanje = true

      try {

        const res = await fetch(
          `${API_BASE_URL}/recenzije`,
          {
            method: 'POST',

            credentials: 'include',

            headers: {
              'Content-Type':
                'application/json',

              Accept:
                'application/json'
            },

            body: JSON.stringify({

              tutor_id:
                this.novaRecenzija.tutor_id,

              ocjena:
                this.novaRecenzija.ocjena,

              komentar:
                this.novaRecenzija.komentar

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
            'Hvala na recenziji!'
          )

          this.novaRecenzija = {
            tutor_id: '',
            ocjena: '5',
            komentar: ''
          }

          await this.dohvatiRecenzije()

          await this.dohvatiInstruktore()

        } else {

          alert(
            data.poruka ||
            data.messages?.error ||
            'Greška pri objavi recenzije.'
          )
        }

      } catch (err) {

        console.error(err)

        alert(
          'Problem s povezivanjem na poslužitelj.'
        )

      } finally {

        this.slanje = false
      }
    },


    // =====================================================
    // OTVORI UREĐIVANJE
    // =====================================================

    otvoriUrediModal(recenzija) {

      this.urediRecenzija = {

        id:
          recenzija.id,

        tutor_id:
          recenzija.tutor_id,

        tutor_naziv:
          `${recenzija.tutor_ime || ''} ${recenzija.tutor_prezime || ''}`.trim(),

        ocjena:
          String(recenzija.ocjena),

        komentar:
          recenzija.komentar || ''

      }

      this.prikaziUrediModal = true
    },


    // =====================================================
    // ZATVORI UREĐIVANJE
    // =====================================================

    zatvoriUrediModal() {

      this.prikaziUrediModal = false

      this.urediRecenzija = {
        id: null,
        tutor_id: '',
        tutor_naziv: '',
        ocjena: '5',
        komentar: ''
      }
    },


    // =====================================================
    // SPREMI UREĐENU RECENZIJU
    // =====================================================

    async spremiUredjenuRecenziju() {

      if (!this.korisnik) {
        return
      }

      this.spremanjeUredjivanja = true

      try {

        const res = await fetch(
          `${API_BASE_URL}/recenzije/${this.urediRecenzija.id}`,
          {
            method: 'PUT',

            credentials: 'include',

            headers: {
              'Content-Type':
                'application/json',

              Accept:
                'application/json'
            },

            body: JSON.stringify({

              ocjena:
                this.urediRecenzija.ocjena,

              komentar:
                this.urediRecenzija.komentar

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
            'Recenzija je uspješno uređena!'
          )

          this.zatvoriUrediModal()

          await this.dohvatiRecenzije()

        } else {

          alert(
            data.poruka ||
            data.messages?.error ||
            'Greška pri uređivanju recenzije.'
          )
        }

      } catch (err) {

        console.error(
          'Greška pri uređivanju recenzije:',
          err
        )

        alert(
          'Problem s povezivanjem na poslužitelj.'
        )

      } finally {

        this.spremanjeUredjivanja = false
      }
    },


    // =====================================================
    // OBRIŠI RECENZIJU
    // =====================================================

    async obrisiRecenziju(recenzija) {

      if (
        !confirm(
          'Jeste li sigurni da želite obrisati ovu recenziju?'
        )
      ) {
        return
      }

      try {

        const res = await fetch(
          `${API_BASE_URL}/recenzije/${recenzija.id}`,
          {
            method: 'DELETE',

            credentials: 'include',

            headers: {
              Accept:
                'application/json'
            }
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
            'Recenzija je uspješno obrisana.'
          )

          await this.dohvatiRecenzije()

          await this.dohvatiInstruktore()

        } else {

          alert(
            data.poruka ||
            data.messages?.error ||
            'Greška pri brisanju recenzije.'
          )
        }

      } catch (err) {

        console.error(
          'Greška pri brisanju recenzije:',
          err
        )

        alert(
          'Problem s povezivanjem na poslužitelj.'
        )
      }
    }

  }

}

</script>