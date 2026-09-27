<template>
  <div class="row justify-content-center">
    <div class="col-md-6 col-lg-5">

      <div class="card shadow-sm border-0 mt-4">
        <div class="card-body p-4">

          <h3 class="card-title text-center mb-4">
            Prijava na sustav
          </h3>

          <!-- GREŠKA -->
          <div
            v-if="greska"
            class="alert alert-danger"
            role="alert"
          >
            {{ greska }}
          </div>

          <!-- FORMA ZA PRIJAVU -->
          <form @submit.prevent="prijava">

            <div class="mb-3">
              <label class="form-label">
                Email:
              </label>

              <input
                type="email"
                v-model="forma.email"
                class="form-control"
                required
              />
            </div>

            <div class="mb-3">
              <label class="form-label">
                Lozinka:
              </label>

              <input
                type="password"
                v-model="forma.lozinka"
                class="form-control"
                required
              />
            </div>

            <button
              type="submit"
              class="btn btn-primary w-100"
              :disabled="ucitavanje"
            >
              <span
                v-if="ucitavanje"
                class="spinner-border spinner-border-sm me-2"
              ></span>

              {{
                ucitavanje
                  ? 'Prijava...'
                  : 'Prijavi se'
              }}
            </button>

          </form>

          <p class="text-center mt-3 mb-0">
            Nemate račun?

            <router-link to="/register">
              Registrirajte se
            </router-link>
          </p>

        </div>
      </div>

    </div>
  </div>
</template>


<script>
import { API_BASE_URL } from '@/config/api'

export default {

  name: 'LoginView',

  data() {
    return {
      forma: {
        email: '',
        lozinka: ''
      },

      greska: '',
      ucitavanje: false
    }
  },

  methods: {

    async prijava() {

      this.greska = ''
      this.ucitavanje = true

      try {

        const response = await fetch(
          `${API_BASE_URL}/prijava`,
          {
            method: 'POST',

            credentials: 'include',

            headers: {
              'Content-Type': 'application/json',
              Accept: 'application/json'
            },

            body: JSON.stringify(this.forma)
          }
        )

        const data = await response.json()

        if (
          response.ok &&
          data.uspjeh
        ) {

          localStorage.setItem(
            'korisnik',
            JSON.stringify(data.korisnik)
          )

          this.$router.push('/')

        } else {

          this.greska =
            data.message ||
            data.poruka ||
            data.messages?.error ||
            'Neispravni podaci za prijavu.'
        }

      } catch (err) {

        console.error(
          'Greška pri prijavi:',
          err
        )

        this.greska =
          'Došlo je do greške prilikom povezivanja s poslužiteljem.'

      } finally {

        this.ucitavanje = false
      }
    }

  }
}
</script>