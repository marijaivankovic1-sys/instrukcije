<template>
  <div class="container py-5">

    <div class="text-center mb-5">
      <h1 class="fw-bold">
        Zbirke riješenih zadataka
      </h1>

      <p class="text-muted">
        Pregledaj dostupne zbirke riješenih zadataka.
      </p>
    </div>

    <!-- UČITAVANJE -->
    <div
      v-if="ucitavanje"
      class="text-center"
    >
      <p>Učitavanje zbirki...</p>
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
      class="alert alert-success text-center"
    >
      {{ poruka }}
    </div>

    <!-- AKO NEMA ZBIRKI -->
    <div
      v-if="
        !ucitavanje &&
        !greska &&
        zbirke.length === 0
      "
      class="alert alert-info text-center"
    >
      Trenutno nema dostupnih zbirki.
    </div>

    <!-- ZBIRKE -->
    <div
      v-if="
        !ucitavanje &&
        zbirke.length > 0
      "
      class="row g-4"
    >
      <div
        v-for="zbirka in zbirke"
        :key="zbirka.id"
        class="col-12 col-md-6 col-lg-4"
      >
        <div class="card h-100 shadow-sm">

          <!-- SLIKA -->
          <img
            v-if="zbirka.slika_putanja"
            :src="urlDatoteke(zbirka.slika_putanja)"
            class="card-img-top zbirka-slika"
            :alt="zbirka.naziv"
          />

          <!-- AKO NEMA SLIKE -->
          <div
            v-else
            class="placeholder-slika d-flex align-items-center justify-content-center"
          >
            📘
          </div>

          <div class="card-body d-flex flex-column">

            <span
              class="badge bg-primary mb-2 align-self-start"
            >
              {{ zbirka.predmet_naziv }}
            </span>

            <h5 class="card-title">
              {{ zbirka.naziv }}
            </h5>

            <p class="card-text text-muted">
              {{ zbirka.opis || 'Nema opisa.' }}
            </p>

            <div class="mt-auto">

              <p class="cijena mb-3">
                {{ formatirajCijenu(zbirka.cijena) }} KM
              </p>

              <!-- PDF -->
              <a
                v-if="zbirka.pdf_putanja"
                :href="urlDatoteke(zbirka.pdf_putanja)"
                target="_blank"
                class="btn btn-outline-secondary w-100 mb-2"
              >
                Pogledaj PDF
              </a>

              <!-- SAMO STUDENT MOŽE DODATI U KOŠARICU -->
              <button
                v-if="jeStudent"
                class="btn btn-primary w-100"
                :disabled="dodavanjeId === Number(zbirka.id)"
                @click="dodajUKosaricu(zbirka)"
              >
                <span
                  v-if="dodavanjeId === Number(zbirka.id)"
                >
                  Dodavanje...
                </span>

                <span v-else>
                  Dodaj u košaricu
                </span>
              </button>

            </div>

          </div>
        </div>
      </div>
    </div>

  </div>
</template>


<script>
import { API_BASE_URL } from '@/config/api'

export default {

  name: 'ZbirkeView',

  data() {
    return {
      zbirke: [],
      ucitavanje: true,
      greska: '',
      poruka: '',
      dodavanjeId: null,
      korisnik: null
    }
  },

  computed: {

    // =====================================================
    // PROVJERA JE LI PRIJAVLJEN STUDENT
    // =====================================================

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

    this.ucitajKorisnika()

    this.dohvatiZbirke()
  },

  methods: {

    // =====================================================
    // UČITAJ PRIJAVLJENOG KORISNIKA
    // =====================================================

    ucitajKorisnika() {

      const podaci =
        localStorage.getItem('korisnik') ||
        localStorage.getItem('user')

      if (!podaci) {
        this.korisnik = null
        return
      }

      try {

        this.korisnik = JSON.parse(podaci)

      } catch (error) {

        console.error(
          'Greška pri učitavanju korisnika:',
          error
        )

        this.korisnik = null
      }
    },


    // =====================================================
    // DOHVAT ZBIRKI
    // =====================================================

    async dohvatiZbirke() {

      this.ucitavanje = true
      this.greska = ''

      try {

        const odgovor = await fetch(
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

        if (!odgovor.ok) {

          this.zbirke = []

          this.greska =
            podaci.messages?.error ||
            podaci.message ||
            podaci.poruka ||
            'Zbirke nije moguće dohvatiti.'

          return
        }

        if (Array.isArray(podaci.zbirke)) {

          this.zbirke = podaci.zbirke

        } else if (Array.isArray(podaci)) {

          this.zbirke = podaci

        } else {

          this.zbirke = []
        }

      } catch (error) {

        console.error(
          'Greška pri dohvaćanju zbirki:',
          error
        )

        this.zbirke = []

        this.greska =
          'Došlo je do greške pri povezivanju s poslužiteljem.'

      } finally {

        this.ucitavanje = false
      }
    },


    // =====================================================
    // FORMAT CIJENE
    // =====================================================

    formatirajCijenu(cijena) {

      return Number(
        cijena || 0
      ).toFixed(2)
    },


    // =====================================================
    // URL SLIKE / PDF-a
    // =====================================================

    urlDatoteke(putanja) {

      if (!putanja) {
        return ''
      }

      if (
        putanja.startsWith('http://') ||
        putanja.startsWith('https://')
      ) {
        return putanja
      }

      const backendOrigin =
        API_BASE_URL.replace(/\/api\/?$/, '')

      return `${backendOrigin}/${putanja.replace(/^\/+/, '')}`
    },


    // =====================================================
    // DODAJ U KOŠARICU
    // =====================================================

    async dodajUKosaricu(zbirka) {

      // Dodatna provjera na frontendu
      if (!this.jeStudent) {

        this.greska =
          'Dodavanje u košaricu dostupno je samo studentima.'

        return
      }

      if (
        !zbirka ||
        !zbirka.id
      ) {
        return
      }

      this.poruka = ''
      this.greska = ''

      this.dodavanjeId =
        Number(zbirka.id)

      try {

        const odgovor = await fetch(
          `${API_BASE_URL}/kosarica`,
          {
            method: 'POST',

            credentials: 'include',

            headers: {
              'Content-Type': 'application/json',
              Accept: 'application/json'
            },

            body: JSON.stringify({
              zbirka_id: zbirka.id,
              kolicina: 1
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
            'Zbirka je dodana u košaricu.'

          setTimeout(() => {
            this.poruka = ''
          }, 3000)

        } else {

          this.greska =
            podaci.messages?.error ||
            podaci.message ||
            podaci.poruka ||
            'Zbirku nije moguće dodati u košaricu.'
        }

      } catch (error) {

        console.error(
          'Greška pri dodavanju u košaricu:',
          error
        )

        this.greska =
          'Došlo je do greške pri dodavanju u košaricu.'

      } finally {

        this.dodavanjeId = null
      }
    }

  }
}
</script>


<style scoped>
.zbirka-slika {
  height: 260px;
  object-fit: contain;
  background-color: #f4f6f8;
  padding: 20px;
}

.placeholder-slika {
  height: 220px;
  background-color: #f2f2f2;
  font-size: 70px;
}

.card {
  border: none;
  border-radius: 14px;
  overflow: hidden;
  transition: 0.2s ease;
}

.card:hover {
  transform: translateY(-3px);
}

.cijena {
  font-size: 1.3rem;
  font-weight: 700;
}
</style>