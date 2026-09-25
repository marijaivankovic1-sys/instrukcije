<template>
  <div id="app">

    <nav class="navbar navbar-expand-lg navbar-dark main-navbar mb-4 shadow-sm">
      <div class="container">

        <!-- LOGO -->
        <router-link class="navbar-brand fw-bold" to="/">
          PLUS I MINUS
        </router-link>

        <!-- MOBILNI GUMB -->
        <button
          class="navbar-toggler"
          type="button"
          data-bs-toggle="collapse"
          data-bs-target="#navbarNav"
        >
          <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarNav">

          <!-- LIJEVI DIO NAVIGACIJE -->
          <ul class="navbar-nav me-auto">

            <li class="nav-item">
              <router-link class="nav-link" to="/">
                Početna
              </router-link>
            </li>

            <li class="nav-item">
              <router-link class="nav-link" to="/termini">
                Termini
              </router-link>
            </li>

            <li class="nav-item">
              <router-link class="nav-link" to="/zbirke">
                Zbirke
              </router-link>
            </li>

            <li class="nav-item">
              <router-link class="nav-link" to="/recenzije">
                Recenzije
              </router-link>
            </li>

            <li class="nav-item">
              <router-link class="nav-link" to="/vizija">
                Vizija projekta
              </router-link>
            </li>

            <!-- STUDENT -->
            <li v-if="jeStudent" class="nav-item">
              <router-link class="nav-link" to="/moje-rezervacije">
                Moje rezervacije
              </router-link>
            </li>

            <li v-if="jeStudent" class="nav-item">
              <router-link class="nav-link" to="/kosarica">
                🛒 Košarica
              </router-link>
            </li>

            <!-- TUTOR / ADMIN -->
            <li v-if="jeInstruktorIliAdmin" class="nav-item">
              <router-link class="nav-link" to="/zahtjevi">
                Pristigli zahtjevi
              </router-link>
            </li>

            <li v-if="jeInstruktorIliAdmin" class="nav-item">
              <router-link class="nav-link" to="/dodaj-termin">
                Dodaj termin
              </router-link>
            </li>

            <!-- ADMIN -->
            <li v-if="jeAdmin" class="nav-item">
              <router-link class="nav-link" to="/admin">
                Upravljačka ploča
              </router-link>
            </li>

            <li v-if="jeAdmin" class="nav-item">
              <router-link class="nav-link" to="/admin-zbirke">
                Upravljanje zbirkama
              </router-link>
            </li>

          </ul>

          <!-- DESNI DIO NAVIGACIJE -->
          <div class="d-flex align-items-center text-white">

            <template v-if="korisnik">

              <span class="me-3 small">
                <strong>
                  {{ korisnik.ime }}
                  {{ korisnik.prezime }}
                </strong>

                <span class="badge bg-light text-primary ms-1">
                  {{ prikazUloge }}
                </span>
              </span>

              <button
                @click="odjava"
                class="btn btn-outline-light btn-sm"
                :disabled="odjavaUTijeku"
              >
                {{ odjavaUTijeku ? 'Odjava...' : 'Odjavi se' }}
              </button>

            </template>

            <template v-else>

              <router-link
                to="/login"
                class="btn btn-outline-light btn-sm me-2"
              >
                Prijava
              </router-link>

              <router-link
                to="/register"
                class="btn btn-light text-primary fw-bold btn-sm"
              >
                Registracija
              </router-link>

            </template>

          </div>

        </div>
      </div>
    </nav>

    <!-- SADRŽAJ STRANICE -->
    <main class="container mb-5">
      <router-view />
    </main>

  </div>
</template>

<script>
import { API_BASE_URL } from '@/config/api'

export default {

  name: 'App',

  data() {
    return {
      korisnik: null,
      odjavaUTijeku: false
    }
  },

  computed: {

    uloga() {
      if (!this.korisnik) {
        return ''
      }

      return (
        this.korisnik.uloga ||
        this.korisnik.role ||
        ''
      ).toLowerCase()
    },

    prikazUloge() {
      if (this.jeInstruktor) {
        return 'Tutor'
      }

      if (this.jeAdmin) {
        return 'Admin'
      }

      if (this.jeStudent) {
        return 'Student'
      }

      return (
        this.korisnik?.uloga ||
        this.korisnik?.role ||
        ''
      )
    },

    jeStudent() {
      return (
        this.uloga === 'student' ||
        this.uloga === 'učenik' ||
        this.uloga === 'ucenik'
      )
    },

    jeInstruktor() {
      return (
        this.uloga === 'instruktor' ||
        this.uloga === 'tutor'
      )
    },

    jeAdmin() {
      return (
        this.uloga === 'admin' ||
        this.uloga === 'administrator' ||
        this.uloga === 'super administrator'
      )
    },

    jeInstruktorIliAdmin() {
      return this.jeInstruktor || this.jeAdmin
    }

  },

  mounted() {
    this.ucitaj()
  },

  watch: {
    $route() {
      this.ucitaj()
    }
  },

  methods: {

    ucitaj() {
      const podaci =
        localStorage.getItem('korisnik') ||
        localStorage.getItem('user')

      if (!podaci) {
        this.korisnik = null
        return
      }

      try {
        this.korisnik = JSON.parse(podaci)
      } catch (e) {
        console.error(
          'Greška pri učitavanju korisnika:',
          e
        )

        localStorage.removeItem('korisnik')
        localStorage.removeItem('user')

        this.korisnik = null
      }
    },

    async odjava() {

      if (this.odjavaUTijeku) {
        return
      }

      this.odjavaUTijeku = true

      try {

        const response = await fetch(
          `${API_BASE_URL}/odjava`,
          {
            method: 'POST',
            credentials: 'include',
            headers: {
              'Content-Type': 'application/json'
            }
          }
        )

        const data = await response.json()

        if (!data.uspjeh) {
          console.warn(
            data.poruka ||
            'Backend odjava nije potvrđena.'
          )
        }

      } catch (err) {

        console.error(
          'Greška pri odjavi s poslužitelja:',
          err
        )

      } finally {

        localStorage.removeItem('korisnik')
        localStorage.removeItem('user')

        this.korisnik = null
        this.odjavaUTijeku = false

        this.$router.push('/login')
      }
    }

  }
}
</script>

<style scoped>
.main-navbar {
  background-color: #0d3b5f !important;
}
</style>