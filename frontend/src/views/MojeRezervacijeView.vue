<template>
  <div>

    <div class="d-flex justify-content-between align-items-center mb-4">

      <h2>Moje rezervacije</h2>

      <button
        v-if="rezervacije.length > 0"
        @click="isprintaj"
        class="btn btn-outline-secondary btn-print no-print"
      >
        🖨️ Ispiši potvrdu
      </button>

    </div>


    <div
      v-if="poruka"
      :class="[
        'alert',
        greska ? 'alert-danger' : 'alert-success',
        'no-print'
      ]"
      role="alert"
    >
      {{ poruka }}
    </div>


    <div
      v-if="ucitavanje"
      class="text-center py-4 no-print"
    >
      <div
        class="spinner-border text-primary"
        role="status"
      ></div>
    </div>


    <div
      v-else-if="rezervacije.length === 0"
      class="alert alert-info no-print"
    >
      Trenutno nemate aktivnih rezervacija.
    </div>


    <div
      v-else
      class="table-responsive bg-white p-3 rounded border shadow-sm print-container"
    >
      <table class="table table-hover align-middle">

        <thead class="table-dark">
          <tr>
            <th>Predmet</th>
            <th>Instruktor</th>
            <th>Datum i vrijeme</th>
            <th>Cijena</th>
            <th>Status</th>
            <th>Napomena / Privitak</th>
            <th class="no-print">Akcije</th>
          </tr>
        </thead>


        <tbody>

          <tr
            v-for="r in rezervacije"
            :key="r.rezervacija_id || r.id"
          >

            <td class="fw-bold text-primary">
              {{ r.predmet_naziv }}
            </td>


            <td>
              <strong>
                {{ r.tutor_ime || r.instruktor_ime || '' }}
                {{ r.tutor_prezime || r.instruktor_prezime || '' }}
              </strong>
            </td>


            <td>
              {{ formatDatum(r.datum) }}

              <br />

              <small class="text-muted">
                {{ formatVrijeme(r.vrijeme_od) }}
                -
                {{ formatVrijeme(r.vrijeme_do) }}
                h
              </small>
            </td>


            <td>
              {{ Number(r.cijena || 0).toFixed(2) }} BAM
            </td>


            <td>
              <span
                :class="[
                  'badge',
                  getStatusBadgeClass(
                    r.rezervacija_status || r.status
                  )
                ]"
              >
                {{
                  r.rezervacija_status ||
                  r.status ||
                  'na čekanju'
                }}
              </span>
            </td>


            <td>

              <span v-if="r.napomena">
                {{ r.napomena }}
              </span>

              <span
                v-else
                class="text-muted fst-italic"
              >
                Bez napomene
              </span>


              <template v-if="r.privitak_putanja">

                <br />

                <a
                  :href="urlPrivitka(r.privitak_putanja)"
                  target="_blank"
                  class="btn btn-sm btn-outline-info mt-1 no-print"
                >
                  Pogledaj privitak
                </a>

              </template>

            </td>


            <td class="no-print">

              <button
                @click="
                  otkaziRezervaciju(
                    r.rezervacija_id || r.id
                  )
                "
                class="btn btn-sm btn-outline-danger"
                :disabled="
                  otkazivanjeId ===
                  Number(r.rezervacija_id || r.id)
                "
              >
                {{
                  otkazivanjeId ===
                  Number(r.rezervacija_id || r.id)
                    ? 'Otkazivanje...'
                    : 'Otkaži'
                }}
              </button>

            </td>

          </tr>

        </tbody>

      </table>
    </div>

  </div>
</template>


<script>

import {
  API_BASE_URL
} from '@/config/api'


export default {

  name: 'MyReservationsView',


  data() {

    return {

      rezervacije: [],

      korisnik: null,

      ucitavanje: false,

      otkazivanjeId: null,

      poruka: '',

      greska: false

    }

  },


  mounted() {

    this.inicijalizirajKorisnika()

  },


  methods: {


    // =====================================================
    // ISPIS
    // =====================================================

    isprintaj() {

      window.print()

    },


    // =====================================================
    // KORISNIK
    // =====================================================

    inicijalizirajKorisnika() {

      const data =
        localStorage.getItem('korisnik') ||
        localStorage.getItem('user')


      if (!data) {

        this.$router.push('/login')

        return
      }


      try {

        this.korisnik =
          JSON.parse(data)


        const uloga = (
          this.korisnik.uloga ||
          this.korisnik.role ||
          ''
        ).toLowerCase()


        if (
          uloga === 'tutor' ||
          uloga === 'instruktor'
        ) {

          this.$router.push('/zahtjevi')

          return
        }


        if (
          uloga === 'admin' ||
          uloga === 'administrator' ||
          uloga === 'super administrator'
        ) {

          this.$router.push('/admin')

          return
        }


        this.dohvatiRezervacije()

      } catch (e) {

        console.error(
          'Greška pri dohvaćanju korisnika:',
          e
        )

        this.$router.push('/login')
      }

    },


    // =====================================================
    // DOHVAĆANJE REZERVACIJA
    // =====================================================

    async dohvatiRezervacije() {

      this.ucitavanje = true

      this.poruka = ''

      this.greska = false


      try {

        const response =
          await fetch(
            `${API_BASE_URL}/rezervacije`,
            {

              method: 'GET',

              credentials: 'include',

              headers: {
                Accept: 'application/json'
              }

            }
          )


        const resData =
          await response.json()


        if (!response.ok) {

          this.rezervacije = []

          this.poruka =
            resData.messages?.error ||
            resData.poruka ||
            'Greška pri dohvaćanju rezervacija.'

          this.greska = true

          return
        }


        if (
          Array.isArray(
            resData.rezervacije
          )
        ) {

          this.rezervacije =
            resData.rezervacije

        } else if (
          Array.isArray(resData)
        ) {

          this.rezervacije =
            resData

        } else {

          this.rezervacije = []

        }

      } catch (err) {

        console.error(
          'Greška pri dohvaćanju rezervacija:',
          err
        )


        this.rezervacije = []

        this.poruka =
          'Došlo je do greške pri dohvaćanju rezervacija.'

        this.greska = true

      } finally {

        this.ucitavanje = false

      }

    },


    // =====================================================
    // OTKAZIVANJE REZERVACIJE
    // =====================================================

    async otkaziRezervaciju(
      rezervacijaId
    ) {

      if (
        !confirm(
          'Jeste li sigurni da želite otkazati ovu rezervaciju?'
        )
      ) {

        return
      }


      this.otkazivanjeId =
        Number(rezervacijaId)

      this.poruka = ''

      this.greska = false


      try {

        const response =
          await fetch(
            `${API_BASE_URL}/rezervacije/${rezervacijaId}`,
            {

              method: 'DELETE',

              credentials: 'include',

              headers: {
                Accept: 'application/json'
              }

            }
          )


        const resData =
          await response.json()


        if (
          response.ok &&
          resData.uspjeh
        ) {

          this.poruka =
            resData.poruka ||
            'Rezervacija je uspješno otkazana.'

          this.greska = false

          await this.dohvatiRezervacije()

        } else {

          this.poruka =
            resData.messages?.error ||
            resData.poruka ||
            'Greška pri otkazivanju rezervacije.'

          this.greska = true

        }

      } catch (err) {

        console.error(
          'Greška pri otkazivanju rezervacije:',
          err
        )


        this.poruka =
          'Došlo je do greške pri otkazivanju.'

        this.greska = true

      } finally {

        this.otkazivanjeId = null

      }

    },


    // =====================================================
    // URL PRIVITKA
    // =====================================================

    urlPrivitka(
      putanja
    ) {

      if (!putanja) {

        return '#'
      }


      if (
        putanja.startsWith('http://') ||
        putanja.startsWith('https://')
      ) {

        return putanja
      }


      const backendOrigin =
        API_BASE_URL.replace(
          /\/api\/?$/,
          ''
        )


      return `${backendOrigin}/${putanja.replace(/^\/+/, '')}`

    },


    // =====================================================
    // FORMAT DATUMA
    // =====================================================

    formatDatum(
      datumStr
    ) {

      if (!datumStr) {

        return ''
      }


      const [
        godina,
        mjesec,
        dan
      ] =
        datumStr.split('-')


      return `${dan}.${mjesec}.${godina}.`

    },


    // =====================================================
    // FORMAT VREMENA
    // =====================================================

    formatVrijeme(
      vrijemeStr
    ) {

      if (!vrijemeStr) {

        return ''
      }


      return vrijemeStr.substring(
        0,
        5
      )

    },


    // =====================================================
    // BOJA STATUSA
    // =====================================================

    getStatusBadgeClass(
      status
    ) {

      const st = (
        status || ''
      ).toLowerCase()


      if (
        st.includes('prihva') ||
        st === 'approved' ||
        st === 'odobreno'
      ) {

        return 'bg-success'
      }


      if (
        st.includes('odbij') ||
        st === 'rejected'
      ) {

        return 'bg-danger'
      }


      return 'bg-warning text-dark'

    }

  }

}

</script>


<style scoped>

@media print {

  .no-print,
  button,
  .btn {

    display: none !important;
  }


  .print-container {

    box-shadow: none !important;

    border: none !important;

    padding: 0 !important;
  }


  table {

    width: 100% !important;

    border-collapse: collapse !important;
  }


  th,
  td {

    border: 1px solid #000 !important;

    padding: 8px !important;

    color: #000 !important;
  }


  .table-dark {

    background-color: #f2f2f2 !important;

    color: #000 !important;
  }

}

</style>