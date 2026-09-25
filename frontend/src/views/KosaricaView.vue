<template>
  <div class="container py-5">

    <div class="text-center mb-5">
      <h1 class="fw-bold">Moja košarica</h1>

      <p class="text-muted">
        Pregledaj odabrane zbirke i ukupnu cijenu.
      </p>
    </div>


    <!-- UČITAVANJE -->
    <div
      v-if="ucitavanje"
      class="text-center"
    >
      <p>Učitavanje košarice...</p>
    </div>


    <!-- GREŠKA -->
    <div
      v-if="greska"
      class="alert alert-danger"
    >
      {{ greska }}
    </div>


    <!-- PORUKA -->
    <div
      v-if="poruka"
      class="alert alert-success"
    >
      {{ poruka }}
    </div>


    <!-- PRAZNA KOŠARICA -->
    <div
      v-if="
        !ucitavanje &&
        !greska &&
        stavke.length === 0
      "
      class="alert alert-info text-center"
    >
      Košarica je prazna.
    </div>


    <!-- STAVKE -->
    <div
      v-if="
        !ucitavanje &&
        stavke.length > 0
      "
      class="row g-4"
    >

      <div class="col-12 col-lg-8">

        <div
          v-for="stavka in stavke"
          :key="stavka.id"
          class="card shadow-sm mb-3"
        >

          <div class="card-body">

            <div class="row align-items-center">

              <div class="col-md-7">

                <span class="badge bg-primary mb-2">
                  {{ stavka.predmet_naziv }}
                </span>

                <h5 class="mb-1">
                  {{ stavka.naziv }}
                </h5>

                <p class="text-muted mb-2">
                  {{ stavka.opis || 'Nema opisa.' }}
                </p>

                <strong>
                  {{ formatirajCijenu(stavka.cijena) }} KM
                </strong>

              </div>


              <div class="col-md-3 mt-3 mt-md-0">

                <label class="form-label">
                  Količina
                </label>

                <input
                  type="number"
                  min="1"
                  class="form-control"
                  v-model.number="stavka.kolicina"
                  @change="promijeniKolicinu(stavka)"
                  :disabled="obrada"
                />

              </div>


              <div
                class="col-md-2 mt-3 mt-md-0 text-md-end"
              >

                <button
                  class="btn btn-outline-danger"
                  @click="obrisiStavku(stavka.id)"
                  :disabled="obrada"
                >
                  Ukloni
                </button>

              </div>

            </div>

          </div>

        </div>

      </div>


      <!-- SAŽETAK -->
      <div class="col-12 col-lg-4">

        <div class="card shadow-sm">

          <div class="card-body">

            <h4 class="mb-4">
              Sažetak
            </h4>


            <div
              class="d-flex justify-content-between mb-3"
            >
              <span>Broj stavki:</span>

              <strong>
                {{ brojStavki }}
              </strong>
            </div>


            <hr />


            <div
              class="d-flex justify-content-between mb-4"
            >
              <span>Ukupno:</span>

              <strong class="ukupno">
                {{ formatirajCijenu(ukupno) }} KM
              </strong>
            </div>


            <button
              class="btn btn-success w-100 mb-2"
              @click="potvrdiKupnju"
              :disabled="obrada"
            >
              Potvrdi kupnju
            </button>


            <button
              class="btn btn-outline-secondary w-100"
              @click="isprazniKosaricu"
              :disabled="obrada"
            >
              Isprazni košaricu
            </button>

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

  name: 'KosaricaView',


  data() {

    return {
      stavke: [],
      ukupno: 0,

      ucitavanje: true,
      obrada: false,

      greska: '',
      poruka: ''
    }
  },


  computed: {

    brojStavki() {

      return this.stavke.reduce(
        (zbroj, stavka) =>
          zbroj + Number(stavka.kolicina || 0),
        0
      )
    }

  },


  mounted() {

    this.dohvatiKosaricu()

  },


  methods: {

    // =====================================================
    // DOHVATI KOŠARICU
    // =====================================================

    async dohvatiKosaricu() {

      this.ucitavanje = true
      this.greska = ''


      try {

        const odgovor = await fetch(
          `${API_BASE_URL}/kosarica`,
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

          this.stavke = []
          this.ukupno = 0

          this.greska =
            podaci.messages?.error ||
            podaci.poruka ||
            'Košaricu nije moguće dohvatiti.'

          return
        }


        if (
          Array.isArray(podaci.stavke)
        ) {

          this.stavke =
            podaci.stavke

        } else if (
          Array.isArray(podaci)
        ) {

          this.stavke =
            podaci

        } else {

          this.stavke = []
        }


        this.izracunajUkupno()

      } catch (error) {

        console.error(
          'Greška pri dohvaćanju košarice:',
          error
        )


        this.stavke = []
        this.ukupno = 0


        this.greska =
          'Došlo je do greške pri dohvaćanju košarice.'

      } finally {

        this.ucitavanje = false
      }
    },


    // =====================================================
    // IZRAČUN UKUPNE CIJENE
    // =====================================================

    izracunajUkupno() {

      this.ukupno =
        this.stavke.reduce(
          (zbroj, stavka) => {

            const cijena =
              Number(stavka.cijena || 0)

            const kolicina =
              Number(stavka.kolicina || 0)


            return zbroj +
              cijena * kolicina

          },
          0
        )
    },


    // =====================================================
    // PROMIJENI KOLIČINU
    // =====================================================

    async promijeniKolicinu(stavka) {

      if (
        !stavka ||
        !stavka.id
      ) {
        return
      }


      if (
        !stavka.kolicina ||
        stavka.kolicina < 1
      ) {
        stavka.kolicina = 1
      }


      this.obrada = true
      this.greska = ''
      this.poruka = ''


      try {

        const odgovor = await fetch(
          `${API_BASE_URL}/kosarica/${stavka.id}`,
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
              kolicina:
                stavka.kolicina
            })
          }
        )


        const podaci =
          await odgovor.json()


        if (
          odgovor.ok &&
          podaci.uspjeh
        ) {

          this.poruka =
            podaci.poruka ||
            'Količina je uspješno promijenjena.'


          await this.dohvatiKosaricu()


          this.ocistiPoruku()

        } else {

          this.greska =
            podaci.messages?.error ||
            podaci.poruka ||
            'Količinu nije moguće promijeniti.'
        }

      } catch (error) {

        console.error(
          'Greška pri promjeni količine:',
          error
        )


        this.greska =
          'Došlo je do greške pri promjeni količine.'

      } finally {

        this.obrada = false
      }
    },


    // =====================================================
    // OBRIŠI STAVKU
    // =====================================================

    async obrisiStavku(stavkaId) {

      if (!stavkaId) {
        return
      }


      this.obrada = true
      this.greska = ''
      this.poruka = ''


      try {

        const odgovor = await fetch(
          `${API_BASE_URL}/kosarica/${stavkaId}`,
          {
            method: 'DELETE',

            credentials: 'include',

            headers: {
              Accept:
                'application/json'
            }
          }
        )


        const podaci =
          await odgovor.json()


        if (
          odgovor.ok &&
          podaci.uspjeh
        ) {

          this.poruka =
            podaci.poruka ||
            'Stavka je uklonjena iz košarice.'


          await this.dohvatiKosaricu()


          this.ocistiPoruku()

        } else {

          this.greska =
            podaci.messages?.error ||
            podaci.poruka ||
            'Stavku nije moguće ukloniti.'
        }

      } catch (error) {

        console.error(
          'Greška pri uklanjanju stavke:',
          error
        )


        this.greska =
          'Došlo je do greške pri uklanjanju stavke.'

      } finally {

        this.obrada = false
      }
    },


    // =====================================================
    // ISPRAZNI KOŠARICU
    // =====================================================

    async isprazniKosaricu() {

      if (
        !confirm(
          'Jeste li sigurni da želite isprazniti košaricu?'
        )
      ) {
        return
      }


      this.obrada = true
      this.greska = ''
      this.poruka = ''


      try {

        const odgovor = await fetch(
          `${API_BASE_URL}/kosarica/sve`,
          {
            method: 'DELETE',

            credentials: 'include',

            headers: {
              Accept:
                'application/json'
            }
          }
        )


        const podaci =
          await odgovor.json()


        if (
          odgovor.ok &&
          podaci.uspjeh
        ) {

          this.poruka =
            podaci.poruka ||
            'Košarica je ispražnjena.'


          await this.dohvatiKosaricu()


          this.ocistiPoruku()

        } else {

          this.greska =
            podaci.messages?.error ||
            podaci.poruka ||
            'Košaricu nije moguće isprazniti.'
        }

      } catch (error) {

        console.error(
          'Greška pri pražnjenju košarice:',
          error
        )


        this.greska =
          'Došlo je do greške pri pražnjenju košarice.'

      } finally {

        this.obrada = false
      }
    },


    // =====================================================
    // POTVRDA KUPNJE
    // =====================================================

    potvrdiKupnju() {

      if (
        this.stavke.length === 0
      ) {
        return
      }


      this.greska = ''

      this.poruka =
        'Kupnja je uspješno potvrđena.'


      this.ocistiPoruku()
    },


    // =====================================================
    // OČISTI PORUKU
    // =====================================================

    ocistiPoruku() {

      setTimeout(() => {

        this.poruka = ''

      }, 3000)
    },


    // =====================================================
    // FORMAT CIJENE
    // =====================================================

    formatirajCijenu(cijena) {

      return Number(
        cijena || 0
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

.ukupno {
  font-size: 1.4rem;
}
</style>