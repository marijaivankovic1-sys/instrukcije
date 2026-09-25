<template>
  <div class="row justify-content-center">

    <div class="col-md-6">

      <h2 class="mb-3">
        Registracija korisnika
      </h2>


      <div
        v-if="poruka"
        :class="[
          'alert',
          greska
            ? 'alert-danger'
            : 'alert-success'
        ]"
        role="alert"
      >
        {{ poruka }}
      </div>


      <form @submit.prevent="handleRegister">

        <div class="mb-3">

          <label class="form-label">
            Ime:
          </label>

          <input
            type="text"
            v-model.trim="form.ime"
            class="form-control"
            required
          />

        </div>


        <div class="mb-3">

          <label class="form-label">
            Prezime:
          </label>

          <input
            type="text"
            v-model.trim="form.prezime"
            class="form-control"
            required
          />

        </div>


        <div class="mb-3">

          <label class="form-label">
            Email adresa:
          </label>

          <input
            type="email"
            v-model.trim="form.email"
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
            v-model="form.lozinka"
            class="form-control"
            required
          />

        </div>


        <div class="alert alert-info">
          Registracijom se kreira korisnički račun
          s ulogom <strong>Student</strong>.
        </div>


        <button
          type="submit"
          class="btn btn-primary"
          :disabled="ucitavanje"
        >

          {{
            ucitavanje
              ? 'Registracija u tijeku...'
              : 'Registriraj se'
          }}

        </button>

      </form>

    </div>

  </div>
</template>


<script>
import {
  API_BASE_URL
} from '@/config/api'


export default {

  name: 'RegisterView',


  data() {

    return {

      form: {
        ime: '',
        prezime: '',
        email: '',
        lozinka: ''
      },

      poruka: '',
      greska: false,
      ucitavanje: false
    }
  },


  methods: {

    async handleRegister() {

      this.ucitavanje = true
      this.poruka = ''
      this.greska = false


      try {

        const response = await fetch(
          `${API_BASE_URL}/registracija`,
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
              ime:
                this.form.ime,

              prezime:
                this.form.prezime,

              email:
                this.form.email,

              lozinka:
                this.form.lozinka
            })
          }
        )


        const data =
          await response.json()


        if (
          response.ok &&
          data.uspjeh
        ) {

          this.greska = false

          this.poruka =
            data.poruka ||
            'Registracija je uspješna.'


          setTimeout(() => {

            this.$router.push('/login')

          }, 1500)

        } else {

          this.greska = true

          this.poruka =
            data.messages?.error ||
            data.poruka ||
            'Registracija nije uspjela.'
        }

      } catch (err) {

        console.error(
          'Greška pri registraciji:',
          err
        )


        this.greska = true

        this.poruka =
          'Došlo je do pogreške pri povezivanju s poslužiteljem.'

      } finally {

        this.ucitavanje = false
      }
    }

  }
}
</script>