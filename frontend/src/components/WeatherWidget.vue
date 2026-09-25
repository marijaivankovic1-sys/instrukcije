<template>
  <div class="card mb-4 shadow-sm border-0 bg-light">
    <div class="card-body p-3">
      <div class="d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center">
          <img v-if="prognoza && prognoza.ikona" :src="prognoza.ikona" alt="Vrijeme" style="width: 48px; height: 48px;" class="me-2" />
          <div v-else class="display-6 me-3">🌤️</div>
          <div>
            <h6 class="mb-0 fw-bold">Vremenska prognoza — {{ grad }}</h6>
            <small class="text-muted" v-if="prognoza">
              <strong>{{ prognoza.temperatura }}°C</strong> | {{ prognoza.opis }} | Vjetar: {{ prognoza.vjetar }} km/h
            </small>
            <small class="text-muted" v-else-if="ucitavanje">Dohvaćanje podataka...</small>
            <small class="text-danger" v-else>Prognoza trenutno nije dostupna.</small>
          </div>
        </div>
        <span class="badge bg-primary">WeatherAPI</span>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'WeatherWidget',
  data() {
    return {
      grad: 'Mostar',
      prognoza: null,
      ucitavanje: true,
      apiKey: 'd35a68993802497fb13155812260203'
    }
  },
  mounted() {
    this.dohvatiPrognozu()
  },
  methods: {
    async dohvatiPrognozu() {
      try {
        const url = `https://api.weatherapi.com/v1/current.json?key=${this.apiKey}&q=${this.grad}&lang=hr`
        const res = await fetch(url)
        const data = await res.json()

        if (data && data.current) {
          this.prognoza = {
            temperatura: data.current.temp_c,
            opis: data.current.condition.text,
            ikona: 'https:' + data.current.condition.icon,
            vjetar: data.current.wind_kph
          }
        }
      } catch (err) {
        console.error('Greška pri dohvaćanju prognoze s WeatherAPI:', err)
      } finally {
        this.ucitavanje = false
      }
    }
  }
}
</script>