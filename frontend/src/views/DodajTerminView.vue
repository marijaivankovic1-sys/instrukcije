<template>
  <div class="container py-4">
    <div class="row justify-content-center">
      <div class="col-md-8 col-lg-6">
        <div class="card shadow-sm border-0 p-4">
          <h3 class="mb-4 text-center fw-bold">Dodaj novi termin</h3>

          <div
            v-if="poruka"
            :class="['alert', greska ? 'alert-danger' : 'alert-success']"
            role="alert"
          >
            {{ poruka }}
          </div>

          <form @submit.prevent="spremiTermin">

            <!-- Prikaz polja s imenom kada je prijavljen Instruktor / Tutor -->
            <div v-if="jeInstruktor" class="mb-3">
              <label class="form-label fw-semibold">Instruktor:</label>

              <input
                type="text"
                class="form-control bg-light"
                :value="prijavljeniIme"
                disabled
              />
            </div>

            <!-- Prikaz padajućeg izbornika samo ako je Admin -->
            <div v-else class="mb-3">
              <label class="form-label fw-semibold">
                Odaberi instruktora:
              </label>

              <select
                v-model="noviTermin.tutor_id"
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

            <!-- Predmet -->
            <div class="mb-3">
              <label class="form-label fw-semibold">Predmet:</label>

              <select
                v-model="noviTermin.predmet_id"
                class="form-select"
                required
              >
                <option value="">
                  -- Odaberi predmet --
                </option>

                <option
                  v-for="p in predmeti"
                  :key="p.id"
                  :value="p.id"
                >
                  {{ p.naziv }}
                </option>
              </select>
            </div>

            <!-- DATEPICKER -->
            <div class="mb-3">
              <label class="form-label fw-semibold">Datum:</label>

              <VueDatePicker
                v-model="odabraniDatum"
                :enable-time-picker="false"
                :min-date="new Date()"
                auto-apply
                placeholder="Odaberi datum"
                format="dd.MM.yyyy"
                @update:model-value="postaviDatum"
              />
            </div>

            <!-- Vrijeme -->
            <div class="row">
              <div class="col-md-6 mb-3">
                <label class="form-label fw-semibold">
                  Vrijeme od:
                </label>

                <input
                  type="time"
                  v-model="noviTermin.vrijeme_od"
                  class="form-control"
                  required
                />
              </div>

              <div class="col-md-6 mb-3">
                <label class="form-label fw-semibold">
                  Vrijeme do:
                </label>

                <input
                  type="time"
                  v-model="noviTermin.vrijeme_do"
                  class="form-control"
                  required
                />
              </div>
            </div>

            <!-- Cijena -->
            <div class="mb-3">
              <label class="form-label fw-semibold">
                Cijena (BAM):
              </label>

              <input
                type="number"
                step="0.5"
                v-model="noviTermin.cijena"
                class="form-control"
                placeholder="20.00"
                required
              />
            </div>

            <!-- WYSIWYG EDITOR -->
            <div class="mb-4">
              <label class="form-label fw-semibold">
                Napomena:
              </label>

              <div
                id="editor-container"
                style="height: 150px; background: white;"
              ></div>
            </div>

            <button
              type="submit"
              class="btn btn-primary w-100 py-2 fw-semibold"
              :disabled="ucitavanje"
            >
              {{
                ucitavanje
                  ? 'Spremanje...'
                  : 'Spremi termin'
              }}
            </button>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { VueDatePicker } from '@vuepic/vue-datepicker'
import '@vuepic/vue-datepicker/dist/main.css'
import { API_BASE_URL } from '@/config/api'

const apiFetch = (url, options = {}) => {
  return fetch(url, {
    ...options,
    credentials: 'include',
    headers: {
      Accept: 'application/json',
      ...(options.headers || {})
    }
  })
}

export default {
  name: 'DodajTerminView',

  components: {
    VueDatePicker
  },

  data() {
    return {
      jeInstruktor: false,
      prijavljeniIme: '',
      instruktori: [],
      predmeti: [],

      odabraniDatum: null,

      noviTermin: {
        tutor_id: '',
        predmet_id: '',
        datum: '',
        vrijeme_od: '',
        vrijeme_do: '',
        cijena: 20,
        opis: ''
      },

      poruka: '',
      greska: false,
      ucitavanje: false,
      quillEditor: null
    }
  },

  mounted() {
    this.ucitajKorisnika()
    this.ucitajPredmete()
    this.inicijalizirajQuill()
  },

  methods: {
    async procitajJson(res) {
      try {
        return await res.json()
      } catch {
        return {}
      }
    },

    porukaGreske(data, zadana) {
      return (
        data?.messages?.error ||
        data?.message ||
        data?.poruka ||
        zadana
      )
    },

    postaviDatum(datum) {
      if (!datum) {
        this.noviTermin.datum = ''
        return
      }

      const godina = datum.getFullYear()
      const mjesec = String(
        datum.getMonth() + 1
      ).padStart(2, '0')

      const dan = String(
        datum.getDate()
      ).padStart(2, '0')

      this.noviTermin.datum =
        `${godina}-${mjesec}-${dan}`
    },

    inicijalizirajQuill() {
      if (!document.getElementById('quill-css')) {
        const cssLink =
          document.createElement('link')

        cssLink.id = 'quill-css'
        cssLink.rel = 'stylesheet'
        cssLink.href =
          'https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.snow.css'

        document.head.appendChild(cssLink)
      }

      if (!window.Quill) {
        const script =
          document.createElement('script')

        script.src =
          'https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.js'

        script.onload = () => {
          this.pokreniQuill()
        }

        document.head.appendChild(script)
      } else {
        this.pokreniQuill()
      }
    },

    pokreniQuill() {
      setTimeout(() => {
        if (
          window.Quill &&
          document.getElementById(
            'editor-container'
          )
        ) {
          this.quillEditor =
            new window.Quill(
              '#editor-container',
              {
                theme: 'snow',

                modules: {
                  toolbar: [
                    [
                      'bold',
                      'italic',
                      'underline'
                    ],
                    [
                      { list: 'ordered' },
                      { list: 'bullet' }
                    ],
                    ['clean']
                  ]
                }
              }
            )

          this.quillEditor.on(
            'text-change',
            () => {
              this.noviTermin.opis =
                this.quillEditor.root.innerHTML
            }
          )
        }
      }, 100)
    },

    ucitajKorisnika() {
      const podaci =
        localStorage.getItem('korisnik') ||
        localStorage.getItem('user')

      if (!podaci) {
        this.jeInstruktor = false
        return
      }

      try {
        const k = JSON.parse(podaci)

        const uloga = (
          k.uloga ||
          k.role ||
          k.tip ||
          ''
        ).toString().trim().toLowerCase()

        const korisnikId =
          k.id ||
          k.korisnik_id ||
          k.user_id

        this.prijavljeniIme =
          `${k.ime || ''} ${k.prezime || ''}`.trim()

        if (
          uloga === 'instruktor' ||
          uloga === 'tutor'
        ) {
          this.jeInstruktor = true
          this.noviTermin.tutor_id =
            korisnikId
        } else {
          this.jeInstruktor = false
          this.ucitajInstruktore()
        }

      } catch (e) {
        console.error(
          'Greška pri parsiranju korisnika:',
          e
        )

        this.jeInstruktor = false
      }
    },

    async ucitajInstruktore() {
      try {
        const res = await apiFetch(
          `${API_BASE_URL}/korisnici`
        )

        const data =
          await this.procitajJson(res)

        if (!res.ok) {
          this.instruktori = []
          this.poruka =
            this.porukaGreske(
              data,
              'Instruktore nije moguće dohvatiti.'
            )
          this.greska = true
          return
        }

        const korisnici =
          Array.isArray(data?.korisnici)
            ? data.korisnici
            : (
              Array.isArray(data)
                ? data
                : []
            )

        this.instruktori =
          korisnici.filter(k => {
            const uloga = (
              k?.uloga ||
              k?.role ||
              ''
            ).toString().trim().toLowerCase()

         return (
  uloga === 'tutor' ||
  uloga === 'instruktor' ||
  uloga === 'admin' ||
  uloga === 'administrator' ||
  uloga === 'super administrator'
)
          })

      } catch (err) {
        console.error(
          'Greška pri dohvatu instruktora:',
          err
        )

        this.instruktori = []
        this.poruka =
          'Greška pri povezivanju s poslužiteljem.'
        this.greska = true
      }
    },

    async ucitajPredmete() {
      try {
        const res = await apiFetch(
          `${API_BASE_URL}/predmeti`
        )

        const data =
          await this.procitajJson(res)

        if (!res.ok) {
          this.predmeti = []
          return
        }

        this.predmeti =
          Array.isArray(data?.predmeti)
            ? data.predmeti
            : (
              Array.isArray(data)
                ? data
                : []
            )

      } catch (err) {
        console.error(
          'Greška pri dohvatu predmeta:',
          err
        )

        this.predmeti = []
      }
    },

    async spremiTermin() {
      this.poruka = ''
      this.greska = false

      if (!this.noviTermin.tutor_id) {
        this.poruka =
          'Molimo odaberite instruktora.'
        this.greska = true
        return
      }

      if (!this.noviTermin.predmet_id) {
        this.poruka =
          'Molimo odaberite predmet.'
        this.greska = true
        return
      }

      if (!this.noviTermin.datum) {
        this.poruka =
          'Molimo odaberite datum termina.'
        this.greska = true
        return
      }

      if (
        !this.noviTermin.vrijeme_od ||
        !this.noviTermin.vrijeme_do
      ) {
        this.poruka =
          'Molimo unesite vrijeme početka i završetka.'
        this.greska = true
        return
      }

      if (
        this.noviTermin.vrijeme_do <=
        this.noviTermin.vrijeme_od
      ) {
        this.poruka =
          'Vrijeme završetka mora biti nakon vremena početka.'
        this.greska = true
        return
      }

      this.ucitavanje = true

      if (this.quillEditor) {
        this.noviTermin.opis =
          this.quillEditor.root.innerHTML
      }

      try {
        const res = await apiFetch(
          `${API_BASE_URL}/termini`,
          {
            method: 'POST',

            headers: {
              'Content-Type':
                'application/json'
            },

            body: JSON.stringify(
              this.noviTermin
            )
          }
        )

        const data =
          await this.procitajJson(res)

        if (
          res.ok &&
          (
            data?.uspjeh === true ||
            data?.success === true ||
            data?.uspjeh === undefined
          )
        ) {
          this.poruka =
            data?.poruka ||
            'Termin je uspješno dodan!'

          this.greska = false

          const trenutniTutorId =
            this.jeInstruktor
              ? this.noviTermin.tutor_id
              : ''

          this.noviTermin = {
            tutor_id:
              trenutniTutorId,

            predmet_id: '',

            datum: '',

            vrijeme_od: '',

            vrijeme_do: '',

            cijena: 20,

            opis: ''
          }

          this.odabraniDatum = null

          if (this.quillEditor) {
            this.quillEditor.setText('')
          }
        } else {
          this.poruka =
            this.porukaGreske(
              data,
              'Greška pri spremanju termina.'
            )

          this.greska = true
        }

      } catch (err) {
        console.error(
          'Greška pri slanju termina:',
          err
        )

        this.poruka =
          'Došlo je do greške pri komunikaciji s poslužiteljem.'

        this.greska = true

      } finally {
        this.ucitavanje = false
      }
    }
  }
}
</script>
