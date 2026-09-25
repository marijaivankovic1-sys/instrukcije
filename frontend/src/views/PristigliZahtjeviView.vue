<template>
  <div class="container py-4">

    <div class="card p-4 shadow-sm border-0">

      <h4 class="fw-bold mb-4">
        Pristigli zahtjevi za instrukcije
      </h4>


      <!-- PORUKA -->
      <div
        v-if="poruka"
        :class="[
          'alert',
          greska
            ? 'alert-danger'
            : 'alert-success'
        ]"
      >
        {{ poruka }}
      </div>


      <!-- NEMA OVLASTI -->
      <div
        v-if="!jeInstruktorIliAdmin"
        class="alert alert-warning text-center my-3"
      >
        Nemate ovlasti za pristup ovoj stranici.
        Ova stranica je namijenjena instruktorima
        i administratorima.
      </div>


      <!-- UČITAVANJE -->
      <div
        v-else-if="ucitavanje"
        class="text-center py-4"
      >
        <div
          class="spinner-border text-primary"
          role="status"
        ></div>
      </div>


      <!-- NEMA ZAHTJEVA -->
      <div
        v-else-if="zahtjevi.length === 0"
        class="alert alert-info text-center my-3"
      >
        Trenutno nemate pristiglih zahtjeva za rezervaciju.
      </div>


      <!-- LISTA ZAHTJEVA -->
      <div
        v-else
        class="row g-3"
      >

        <div
          v-for="z in zahtjevi"
          :key="z.rezervacija_id || z.id"
          class="col-12 col-md-6 col-lg-4"
        >

          <div
            class="card h-100 border shadow-sm p-3"
          >

            <!-- PREDMET + STATUS -->
            <div
              class="d-flex justify-content-between align-items-center mb-2"
            >

              <span class="badge bg-primary fs-6">
                {{ z.predmet_naziv }}
              </span>


              <span
                :class="getStatusKlasa(
                  z.rezervacija_status || z.status
                )"
                class="badge"
              >
                {{
                  z.rezervacija_status ||
                  z.status ||
                  'na čekanju'
                }}
              </span>

            </div>


            <!-- STUDENT -->
            <h6 class="fw-bold mb-1">
              Student:
              {{ z.student_ime }}
              {{ z.student_prezime }}
            </h6>


            <!-- INSTRUKTOR -->
            <p class="mb-1">
              <strong>Instruktor:</strong>

              {{
                z.instruktor_ime ||
                z.tutor_ime
              }}

              {{
                z.instruktor_prezime ||
                z.tutor_prezime
              }}
            </p>


            <!-- EMAIL -->
            <p
              v-if="z.student_email"
              class="small text-muted mb-2"
            >
              ✉️ {{ z.student_email }}
            </p>


            <!-- DETALJI -->
            <div
              class="small bg-light p-2 rounded mb-3"
            >

              <div>
                📅
                <strong>Datum:</strong>
                {{ formatDatum(z.datum) }}
              </div>


              <div>
                ⏰
                <strong>Vrijeme:</strong>

                {{ skratiVrijeme(z.vrijeme_od) }}
                -
                {{ skratiVrijeme(z.vrijeme_do) }}
                h
              </div>


              <div
                v-if="z.napomena"
                class="mt-1 text-secondary"
              >
                💬
                <em>
                  "{{ z.napomena }}"
                </em>
              </div>


              <!-- PRIVITAK -->
              <div
                v-if="z.privitak_putanja"
                class="mt-2"
              >

                <a
                  :href="putanjaPrivitka(
                    z.privitak_putanja
                  )"
                  target="_blank"
                  class="btn btn-sm btn-outline-secondary"
                >
                  Pogledaj privitak
                </a>

              </div>

            </div>


            <!-- AKCIJE -->
            <div class="mt-auto">

              <div
                v-if="
                  isNaCekanju(
                    z.rezervacija_status ||
                    z.status
                  )
                "
                class="d-flex gap-2"
              >

                <button
                  @click="
                    promijeniStatus(
                      z.rezervacija_id || z.id,
                      'prihvaćeno'
                    )
                  "
                  class="btn btn-sm btn-success w-100 fw-semibold"
                  :disabled="slanje"
                >
                  Prihvati
                </button>


                <button
                  @click="
                    promijeniStatus(
                      z.rezervacija_id || z.id,
                      'odbijeno'
                    )
                  "
                  class="btn btn-sm btn-outline-danger w-100 fw-semibold"
                  :disabled="slanje"
                >
                  Odbij
                </button>

              </div>


              <!-- VEĆ OBRAĐEN -->
              <div
                v-else
                class="text-center py-2 rounded bg-light border"
              >

                <small class="fw-bold text-muted">

                  Zahtjev je

                  {{
                    isPrihvaceno(
                      z.rezervacija_status ||
                      z.status
                    )
                      ? 'prihvaćen'
                      : 'odbijen'
                  }}.

                </small>

              </div>

            </div>

          </div>

        </div>

      </div>

    </div>

  </div>
</template>


<script>

import {
  API_BASE_URL
} from '@/config/api'


export default {

  name: 'PristigliZahtjeviView',


  data() {

    return {

      korisnik: null,

      zahtjevi: [],

      ucitavanje: false,

      slanje: false,

      poruka: '',

      greska: false

    }
  },


  computed: {

    jeInstruktorIliAdmin() {

      if (!this.korisnik) {
        return false
      }


      const uloga = (
        this.korisnik.uloga ||
        this.korisnik.role ||
        ''
      )
        .toLowerCase()
        .trim()


      return (
        uloga === 'instruktor' ||
        uloga === 'tutor' ||
        uloga === 'admin' ||
        uloga === 'administrator' ||
        uloga === 'super administrator'
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


    if (this.jeInstruktorIliAdmin) {

      this.ucitajZahtjeve()

    }

  },


  methods: {


    // =====================================================
    // DOHVAĆANJE ZAHTJEVA
    // =====================================================

    async ucitajZahtjeve() {

      this.ucitavanje = true

      this.greska = false


      try {

        const res =
          await fetch(
            `${API_BASE_URL}/rezervacije?_t=${Date.now()}`,
            {

              method: 'GET',

              credentials: 'include',

              headers: {
                Accept: 'application/json'
              }

            }
          )


        const data =
          await res.json()


        if (!res.ok) {

          this.zahtjevi = []

          this.greska = true

          this.poruka =
            data.messages?.error ||
            data.poruka ||
            'Zahtjeve nije moguće dohvatiti.'

          return
        }


        if (
          Array.isArray(
            data.rezervacije
          )
        ) {

          this.zahtjevi =
            data.rezervacije

        } else if (
          Array.isArray(data)
        ) {

          this.zahtjevi =
            data

        } else {

          this.zahtjevi = []

        }

      } catch (err) {

        console.error(
          'Greška pri dohvaćanju zahtjeva:',
          err
        )


        this.zahtjevi = []

        this.greska = true

        this.poruka =
          'Problem s povezivanjem na poslužitelj.'

      } finally {

        this.ucitavanje = false

      }

    },


    // =====================================================
    // PROMJENA STATUSA
    // =====================================================

    async promijeniStatus(
      rezervacijaId,
      noviStatus
    ) {

      if (!rezervacijaId) {

        this.greska = true

        this.poruka =
          'Nije pronađen ID rezervacije.'

        return
      }


      this.slanje = true

      this.greska = false

      this.poruka = ''


      try {

        const res =
          await fetch(
            `${API_BASE_URL}/rezervacije/${rezervacijaId}`,
            {

              method: 'PUT',

              credentials: 'include',

              headers: {

                'Content-Type':
                  'application/json',

                Accept:
                  'application/json'

              },

              body:
                JSON.stringify({
                  status: noviStatus
                })

            }
          )


        const data =
          await res.json()


        if (
          res.ok &&
          data.uspjeh
        ) {

          this.greska = false

          this.poruka =
            data.poruka ||
            (
              noviStatus === 'prihvaćeno'
                ? 'Zahtjev je prihvaćen.'
                : 'Zahtjev je odbijen.'
            )


          await this.ucitajZahtjeve()


          setTimeout(() => {

            this.poruka = ''

          }, 3000)

        } else {

          this.greska = true

          this.poruka =
            data.messages?.error ||
            data.poruka ||
            'Greška pri promjeni statusa.'

        }

      } catch (err) {

        console.error(
          'Greška pri promjeni statusa:',
          err
        )


        this.greska = true

        this.poruka =
          'Mrežna greška pri promjeni statusa.'

      } finally {

        this.slanje = false

      }

    },


    // =====================================================
    // PUTANJA PRIVITKA
    // =====================================================

    putanjaPrivitka(putanja) {

      if (!putanja) {
        return '#'
      }


      if (
        putanja.startsWith('http://') ||
        putanja.startsWith('https://')
      ) {
        return putanja
      }


      const backendUrl =
        API_BASE_URL.replace(
          /\/api\/?$/,
          ''
        )


      return `${backendUrl}/${putanja}`

    },


    // =====================================================
    // STATUSI
    // =====================================================

    isNaCekanju(status) {

      if (!status) {
        return true
      }


      const s =
        status
          .toString()
          .toLowerCase()
          .trim()


      return (
        s === '' ||
        s.includes('čekan') ||
        s.includes('cekan') ||
        s === 'pending'
      )

    },


    isPrihvaceno(status) {

      const s =
        (status || '')
          .toString()
          .toLowerCase()


      return (
        s.includes('prihvać') ||
        s.includes('prihvac') ||
        s.includes('approved')
      )

    },


    getStatusKlasa(status) {

      if (!status) {

        return 'bg-warning text-dark'

      }


      const s =
        status
          .toString()
          .toLowerCase()


      if (
        s.includes('prihvać') ||
        s.includes('prihvac')
      ) {

        return 'bg-success'

      }


      if (
        s.includes('odbij')
      ) {

        return 'bg-danger'

      }


      return 'bg-warning text-dark'

    },


    // =====================================================
    // FORMAT DATUMA
    // =====================================================

    formatDatum(datumStr) {

      if (!datumStr) {
        return ''
      }


      const d =
        datumStr.split('-')


      return d.length === 3
        ? `${d[2]}.${d[1]}.${d[0]}.`
        : datumStr

    },


    // =====================================================
    // FORMAT VREMENA
    // =====================================================

    skratiVrijeme(vrijemeStr) {

      return vrijemeStr
        ? vrijemeStr.substring(0, 5)
        : ''

    }

  }

}
</script>