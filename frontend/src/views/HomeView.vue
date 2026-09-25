<template>
  <div class="container py-4">

    <!-- ================================================= -->
    <!-- HERO BANNER -->
    <!-- ================================================= -->
    <div class="hero-banner text-white rounded-4 shadow-sm mb-4">
      <div class="row align-items-center w-100">

        <!-- LIJEVA STRANA -->
        <div class="col-lg-8 hero-content">

          <h1 class="display-5 fw-bold mb-3">
            PLUS I MINUS
          </h1>

          <h2 class="display-5 fw-bold mb-3 hero-title">
            Dobrodošli na sustav za rezervaciju poduka i instrukcija!
          </h2>

          <p class="lead mb-4 hero-description">
            Vaša središnja platforma za jednostavno pronalaženje,
            rezervaciju i upravljanje instrukcijama.
            Povežite se s kvalificiranim instruktorima u nekoliko klikova.
          </p>

          <router-link
            to="/termini"
            class="btn btn-light text-primary btn-lg fw-bold shadow-sm"
          >
            Pregledaj slobodne termine &rarr;
          </router-link>

        </div>


        <!-- DESNA STRANA - VRIJEME -->
        <div
          v-if="vrijeme"
          class="col-lg-4 weather-container mt-4 mt-lg-0"
        >

          <div class="weather-card">

            <div class="fw-bold text-primary mb-1">
              Mostar Vrijeme
            </div>

            <div class="d-flex align-items-center justify-content-between">

              <div>
                <div class="fs-3 fw-bold">
                  {{ vrijeme.temp }}°C
                </div>

                <div class="small text-muted">
                  {{ vrijeme.opis }}
                </div>
              </div>

              <div class="weather-icon">
                ☀️
              </div>

            </div>

          </div>

        </div>

      </div>
    </div>


    <!-- ================================================= -->
    <!-- INFORMACIJE O PLATFORMI -->
    <!-- ================================================= -->

    <div class="row g-4 mt-2">

      <!-- RAZNI PREDMETI -->
      <div class="col-md-4">

        <div class="card feature-card h-100 p-4 border-0 shadow-sm text-center">

          <div class="fs-1 mb-3">
            📚
          </div>

          <h5 class="fw-bold">
            Razni predmeti
          </h5>

          <p class="text-muted small">
            Pronađite pomoć iz matematike, programiranja,
            mreža i brojnih drugih stručnih predmeta.
          </p>

          <router-link
            to="/termini"
            class="feature-link"
          >
            Pogledaj predmete &rarr;
          </router-link>

        </div>

      </div>


      <!-- BRZA REZERVACIJA -->
      <div class="col-md-4">

        <div class="card feature-card h-100 p-4 border-0 shadow-sm text-center">

          <div class="fs-1 mb-3">
            📅
          </div>

          <h5 class="fw-bold">
            Brza rezervacija
          </h5>

          <p class="text-muted small">
            Lako odaberite termine koji vam odgovaraju
            te priložite materijale za pripremu instrukcija.
          </p>

          <router-link
            to="/termini"
            class="feature-link"
          >
            Rezerviraj termin &rarr;
          </router-link>

        </div>

      </div>


      <!-- RECENZIJE -->
      <div class="col-md-4">

        <div class="card feature-card h-100 p-4 border-0 shadow-sm text-center">

          <div class="fs-1 mb-3">
            ⭐
          </div>

          <h5 class="fw-bold">
            Provjerene recenzije
          </h5>

          <p class="text-muted small">
            Pročitajte dojmove drugih studenata
            i odaberite instruktora koji najviše
            odgovara vašim potrebama.
          </p>

          <router-link
            to="/recenzije"
            class="feature-link"
          >
            Pogledaj recenzije &rarr;
          </router-link>

        </div>

      </div>

    </div>

  </div>
</template>


<script>
export default {

  name: 'HomeView',

  data() {
    return {
      vrijeme: null
    }
  },

  mounted() {
    this.dohvatiVrijeme()
  },

  methods: {

    async dohvatiVrijeme() {

      try {

        const res = await fetch(
          'https://api.open-meteo.com/v1/forecast?latitude=43.3438&longitude=17.8078&current_weather=true'
        )

        const data = await res.json()

        if (data.current_weather) {

          this.vrijeme = {
            temp: Math.round(
              data.current_weather.temperature
            ),
            opis: 'Sunčano / Vedro'
          }

        }

      } catch (err) {

        console.log('Prognoza nedostupna')

      }

    }

  }

}
</script>


<style scoped>

/* ================================================= */
/* HERO BANNER */
/* ================================================= */

.hero-banner {

  position: relative;

  min-height: 500px;

  padding: 55px;

  display: flex;
  align-items: center;

  overflow: hidden;

  background-image:

    /* zatamnjenje zbog čitljivosti teksta */
    linear-gradient(
      90deg,
      rgba(3, 35, 62, 0.94) 0%,
      rgba(3, 35, 62, 0.83) 37%,
      rgba(3, 35, 62, 0.30) 67%,
      rgba(3, 35, 62, 0.05) 100%
    ),

    /* naša matematička slika */
    url('@/assets/hero-matematika.png');

  background-size: cover;

  background-position: center;

  background-repeat: no-repeat;

}


/* ================================================= */
/* TEKST */
/* ================================================= */

.hero-content {

  position: relative;

  z-index: 2;

}


.hero-title {

  max-width: 760px;

  line-height: 1.15;

}


.hero-description {

  max-width: 760px;

  line-height: 1.6;

  color: rgba(
    255,
    255,
    255,
    0.92
  );

}


/* ================================================= */
/* VRIJEME */
/* ================================================= */

.weather-container {

  position: relative;

  z-index: 3;

  display: flex;

  justify-content: flex-end;

}


.weather-card {

  width: 185px;

  padding: 18px;

  background: rgba(
    255,
    255,
    255,
    0.97
  );

  color: #212529;

  border-radius: 12px;

  box-shadow:
    0 6px 20px
    rgba(0, 0, 0, 0.18);

}


.weather-icon {

  font-size: 2rem;

}


/* ================================================= */
/* DONJE KARTICE */
/* ================================================= */

.feature-card {

  border-radius: 16px;

  transition:
    transform 0.2s ease,
    box-shadow 0.2s ease;

}


.feature-card:hover {

  transform: translateY(-4px);

  box-shadow:
    0 10px 25px
    rgba(0, 0, 0, 0.10) !important;

}


.feature-link {

  color: #0d6efd;

  font-weight: 700;

  text-decoration: none;

}


.feature-link:hover {

  text-decoration: underline;

}


/* ================================================= */
/* TABLET / MOBITEL */
/* ================================================= */

@media (max-width: 991px) {

  .hero-banner {

    min-height: 480px;

    padding: 40px;

    background-position: 62% center;

  }


  .weather-container {

    justify-content: flex-start;

  }

}


@media (max-width: 768px) {

  .hero-banner {

    min-height: auto;

    padding: 35px 28px;

    /*
      Na mobitelu dodatno zatamnimo sliku
      kako bi tekst ostao čitljiv.
    */

    background-image:

      linear-gradient(
        rgba(3, 35, 62, 0.88),
        rgba(3, 35, 62, 0.88)
      ),

      url('@/assets/hero-matematika.png');

    background-position: 65% center;

  }


  .hero-title {

    font-size: 2rem;

  }


  .hero-description {

    font-size: 1rem;

  }


  .weather-card {

    width: 100%;

    max-width: 220px;

  }

}

</style>