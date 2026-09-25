<template>
  <div class="container py-5">

    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h1 class="fw-bold mb-1">Upravljanje zbirkama</h1>

        <p class="text-muted mb-0">
          Dodavanje, uređivanje i upravljanje zbirkama riješenih zadataka.
        </p>
      </div>

      <button
        class="btn btn-primary"
        @click="otvoriDodavanje"
      >
        + Nova zbirka
      </button>
    </div>


    <!-- PORUKA -->
    <div
      v-if="poruka"
      class="alert alert-success"
    >
      {{ poruka }}
    </div>


    <!-- GREŠKA -->
    <div
      v-if="greska"
      class="alert alert-danger"
    >
      {{ greska }}
    </div>


    <!-- UČITAVANJE -->
    <div
      v-if="ucitavanje"
      class="text-center py-5"
    >
      <div
        class="spinner-border text-primary mb-3"
        role="status"
      ></div>

      <div>
        Učitavanje zbirki...
      </div>
    </div>


    <!-- TABLICA -->
    <div
      v-else
      class="card shadow-sm"
    >
      <div class="card-body">

        <div class="table-responsive">

          <table class="table align-middle">

            <thead>
              <tr>
                <th>ID</th>
                <th>Naziv</th>
                <th>Predmet</th>
                <th>Cijena</th>
                <th class="text-end">
                  Akcije
                </th>
              </tr>
            </thead>


            <tbody>

              <tr
                v-for="zbirka in zbirke"
                :key="zbirka.id"
              >

                <td>
                  {{ zbirka.id }}
                </td>


                <td>
                  <strong>
                    {{ zbirka.naziv }}
                  </strong>

                  <div class="small text-muted">
                    {{ zbirka.opis }}
                  </div>
                </td>


                <td>
                  {{ zbirka.predmet_naziv }}
                </td>


                <td>
                  {{ formatirajCijenu(zbirka.cijena) }} KM
                </td>


                <td class="text-end">

                  <button
                    class="btn btn-sm btn-outline-primary me-2"
                    @click="otvoriUredivanje(zbirka)"
                  >
                    Uredi
                  </button>


                  <button
                    class="btn btn-sm btn-outline-danger"
                    @click="obrisiZbirku(zbirka)"
                  >
                    Obriši
                  </button>

                </td>

              </tr>


              <tr v-if="zbirke.length === 0">

                <td
                  colspan="5"
                  class="text-center text-muted py-4"
                >
                  Nema zbirki.
                </td>

              </tr>

            </tbody>

          </table>

        </div>

      </div>
    </div>


    <!-- ================================================= -->
    <!-- FORMA ZA DODAVANJE / UREĐIVANJE -->
    <!-- ================================================= -->

    <div
      v-if="prikaziFormu"
      class="card shadow-sm mt-4"
    >
      <div class="card-body">

        <h4 class="mb-4">
          {{
            uredivanje
              ? 'Uredi zbirku'
              : 'Dodaj novu zbirku'
          }}
        </h4>


        <form @submit.prevent="spremi">

          <div class="row g-3">


            <!-- PREDMET -->
            <div class="col-md-6">

              <label class="form-label">
                Predmet
              </label>

              <select
                v-model="forma.predmet_id"
                class="form-select"
                required
              >

                <option value="">
                  Odaberite predmet
                </option>

                <option
                  v-for="predmet in predmeti"
                  :key="predmet.id"
                  :value="predmet.id"
                >
                  {{ predmet.naziv }}
                </option>

              </select>

            </div>


            <!-- NAZIV -->
            <div class="col-md-6">

              <label class="form-label">
                Naziv zbirke
              </label>

              <input
                v-model="forma.naziv"
                type="text"
                class="form-control"
                required
              />

            </div>


            <!-- OPIS -->
            <div class="col-12">

              <label class="form-label">
                Opis
              </label>

              <textarea
                v-model="forma.opis"
                class="form-control"
                rows="3"
              ></textarea>

            </div>


            <!-- CIJENA -->
            <div class="col-md-4">

              <label class="form-label">
                Cijena
              </label>

              <input
                v-model="forma.cijena"
                type="number"
                min="0"
                step="0.01"
                class="form-control"
                required
              />

            </div>


            <!-- SLIKA -->
            <div class="col-md-4">

              <label class="form-label">
                Putanja slike
              </label>

              <input
                v-model="forma.slika_putanja"
                type="text"
                class="form-control"
                placeholder="Opcionalno"
              />

            </div>


            <!-- PDF -->
            <div class="col-md-4">

              <label class="form-label">
                Putanja PDF-a
              </label>

              <input
                v-model="forma.pdf_putanja"
                type="text"
                class="form-control"
                placeholder="Opcionalno"
              />

            </div>

          </div>


          <!-- GUMBI -->
          <div class="mt-4">

            <button
              type="submit"
              class="btn btn-success me-2"
              :disabled="spremanje"
            >
              {{
                spremanje
                  ? 'Spremanje...'
                  : 'Spremi'
              }}
            </button>


            <button
              type="button"
              class="btn btn-outline-secondary"
              @click="zatvoriFormu"
            >
              Odustani
            </button>

          </div>

        </form>

      </div>
    </div>

  </div>
</template>


<script>

import {
  API_BASE_URL
} from '@/config/api'


export default {

  name: 'AdminZbirkeView',


  data() {

    return {

      zbirke: [],

      predmeti: [],


      ucitavanje: true,

      spremanje: false,


      prikaziFormu: false,

      uredivanje: false,


      poruka: '',

      greska: '',


      forma: {

        id: null,

        predmet_id: '',

        naziv: '',

        opis: '',

        cijena: '',

        slika_putanja: '',

        pdf_putanja: ''

      }

    }

  },


  mounted() {

    this.ucitajZbirke()

    this.ucitajPredmete()

  },


  methods: {


    // =====================================================
    // DOHVATI ZBIRKE
    // =====================================================

    async ucitajZbirke() {

      this.ucitavanje = true

      this.greska = ''


      try {

        const odgovor =
          await fetch(
            `${API_BASE_URL}/zbirke`,
            {

              method: 'GET',

              credentials: 'include',

              headers: {
                Accept: 'application/json'
              }

            }
          )


        const podaci =
          await odgovor.json()


        if (
          odgovor.ok &&
          podaci.uspjeh
        ) {

          this.zbirke =
            Array.isArray(
              podaci.zbirke
            )
              ? podaci.zbirke
              : []

        } else {

          this.zbirke = []


          this.greska =
            podaci.messages?.error ||
            podaci.poruka ||
            'Zbirke nije moguće dohvatiti.'

        }

      } catch (error) {

        console.error(
          'Greška pri dohvaćanju zbirki:',
          error
        )


        this.zbirke = []


        this.greska =
          'Greška pri dohvaćanju zbirki.'

      } finally {

        this.ucitavanje = false

      }

    },


    // =====================================================
    // DOHVATI PREDMETE
    // =====================================================

    async ucitajPredmete() {

      try {

        const odgovor =
          await fetch(
            `${API_BASE_URL}/predmeti`,
            {

              method: 'GET',

              credentials: 'include',

              headers: {
                Accept: 'application/json'
              }

            }
          )


        const podaci =
          await odgovor.json()


        if (!odgovor.ok) {

          this.predmeti = []


          console.error(
            podaci.messages?.error ||
            podaci.poruka ||
            'Predmete nije moguće dohvatiti.'
          )


          return

        }


        if (
          Array.isArray(
            podaci.predmeti
          )
        ) {

          this.predmeti =
            podaci.predmeti

        } else if (
          Array.isArray(podaci)
        ) {

          this.predmeti =
            podaci

        } else {

          this.predmeti = []

        }

      } catch (error) {

        console.error(
          'Greška pri dohvaćanju predmeta:',
          error
        )


        this.predmeti = []

      }

    },


    // =====================================================
    // NOVA ZBIRKA
    // =====================================================

    otvoriDodavanje() {

      this.uredivanje = false

      this.prikaziFormu = true


      this.poruka = ''

      this.greska = ''


      this.forma = {

        id: null,

        predmet_id: '',

        naziv: '',

        opis: '',

        cijena: '',

        slika_putanja: '',

        pdf_putanja: ''

      }

    },


    // =====================================================
    // UREĐIVANJE
    // =====================================================

    otvoriUredivanje(
      zbirka
    ) {

      this.uredivanje = true

      this.prikaziFormu = true


      this.poruka = ''

      this.greska = ''


      this.forma = {

        id:
          zbirka.id,

        predmet_id:
          zbirka.predmet_id,

        naziv:
          zbirka.naziv,

        opis:
          zbirka.opis || '',

        cijena:
          zbirka.cijena,

        slika_putanja:
          zbirka.slika_putanja || '',

        pdf_putanja:
          zbirka.pdf_putanja || ''

      }

    },


    // =====================================================
    // ZATVORI FORMU
    // =====================================================

    zatvoriFormu() {

      this.prikaziFormu = false

      this.uredivanje = false

    },


    // =====================================================
    // SPREMI ZBIRKU
    // =====================================================

    async spremi() {

      this.spremanje = true

      this.greska = ''

      this.poruka = ''


      try {

        const podaci = {

          predmet_id:
            this.forma.predmet_id,

          naziv:
            this.forma.naziv,

          opis:
            this.forma.opis,

          cijena:
            this.forma.cijena,

          slika_putanja:
            this.forma.slika_putanja ||
            null,

          pdf_putanja:
            this.forma.pdf_putanja ||
            null

        }


        let url =
          `${API_BASE_URL}/zbirke`


        let metoda =
          'POST'


        if (
          this.uredivanje
        ) {

          url =
            `${API_BASE_URL}/zbirke/${this.forma.id}`


          metoda =
            'PUT'

        }


        const odgovor =
          await fetch(
            url,
            {

              method:
                metoda,

              credentials:
                'include',

              headers: {

                'Content-Type':
                  'application/json',

                Accept:
                  'application/json'

              },

              body:
                JSON.stringify(
                  podaci
                )

            }
          )


        const rezultat =
          await odgovor.json()


        if (
          odgovor.ok &&
          rezultat.uspjeh
        ) {

          this.poruka =
            rezultat.poruka ||
            'Zbirka je uspješno spremljena.'


          this.zatvoriFormu()


          await this.ucitajZbirke()

        } else {

          this.greska =
            rezultat.messages?.error ||
            rezultat.poruka ||
            'Zbirku nije moguće spremiti.'

        }

      } catch (error) {

        console.error(
          'Greška pri spremanju zbirke:',
          error
        )


        this.greska =
          'Došlo je do greške pri spremanju zbirke.'

      } finally {

        this.spremanje = false

      }

    },


    // =====================================================
    // BRISANJE ZBIRKE
    // =====================================================

    async obrisiZbirku(
      zbirka
    ) {

      const potvrda =
        confirm(
          `Želite li obrisati zbirku "${zbirka.naziv}"?`
        )


      if (!potvrda) {

        return

      }


      this.greska = ''

      this.poruka = ''


      try {

        const odgovor =
          await fetch(
            `${API_BASE_URL}/zbirke/${zbirka.id}`,
            {

              method:
                'DELETE',

              credentials:
                'include',

              headers: {
                Accept:
                  'application/json'
              }

            }
          )


        const rezultat =
          await odgovor.json()


        if (
          odgovor.ok &&
          rezultat.uspjeh
        ) {

          this.poruka =
            rezultat.poruka ||
            'Zbirka je obrisana.'


          await this.ucitajZbirke()

        } else {

          this.greska =
            rezultat.messages?.error ||
            rezultat.poruka ||
            'Zbirku nije moguće obrisati.'

        }

      } catch (error) {

        console.error(
          'Greška pri brisanju zbirke:',
          error
        )


        this.greska =
          'Došlo je do greške pri brisanju zbirke.'

      }

    },


    // =====================================================
    // FORMAT CIJENE
    // =====================================================

    formatirajCijenu(
      cijena
    ) {

      return Number(
        cijena
      ).toFixed(2)

    }

  }

}

</script>


<style scoped>

.card {
  border: none;
  border-radius: 14px;
}


.table th {
  white-space: nowrap;
}

</style>