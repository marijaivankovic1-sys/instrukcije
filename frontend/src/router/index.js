import { createRouter, createWebHistory } from 'vue-router'

import HomeView from '../views/HomeView.vue'
import TerminiView from '../views/TerminiView.vue'
import RecenzijeView from '../views/RecenzijeView.vue'
import LoginView from '../views/LoginView.vue'
import RegisterView from '../views/RegisterView.vue'
import MojeRezervacijeView from '../views/MojeRezervacijeView.vue'
import PristigliZahtjeviView from '../views/PristigliZahtjeviView.vue'
import DodajTerminView from '../views/DodajTerminView.vue'
import AdminView from '../views/AdminView.vue'
import ZbirkeView from '../views/ZbirkeView.vue'
import KosaricaView from '../views/KosaricaView.vue'
import AdminZbirkeView from '../views/AdminZbirkeView.vue'

const routes = [

  {path: '/',name: 'Home',component: HomeView},
  {path: '/termini', name: 'Termini',component: TerminiView},
  {path: '/recenzije', name: 'Recenzije', component: RecenzijeView},
  {path: '/login', name: 'Login', component: LoginView},
  {path: '/register', name: 'Register', component: RegisterView},
  {path: '/moje-rezervacije', name: 'MojeRezervacije', component: MojeRezervacijeView},
  {path: '/zahtjevi', name: 'PristigliZahtjevi', component: PristigliZahtjeviView},
  {path: '/dodaj-termin',name: 'DodajTermin', component: DodajTerminView},
  {path: '/admin',name: 'Admin', component: AdminView},
  {path: '/vizija',name: 'vision',component: () => import('../views/VisionView.vue')},
  {path: '/zbirke',name: 'zbirke',component: ZbirkeView},
  {path: '/kosarica',name: 'kosarica',component: KosaricaView},
  {path: '/admin-zbirke',name: 'admin-zbirke',component: AdminZbirkeView,
    meta: {
      requiresAdmin: true
    }
  }

]

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes
})


// ======================================================
// ZAŠTITA ADMIN RUTA
// ======================================================

router.beforeEach((to, from, next) => {

  const podaci =
    localStorage.getItem('korisnik') ||
    localStorage.getItem('user')

  let korisnik = null

  if (podaci) {

    try {

      korisnik = JSON.parse(podaci)

    } catch (error) {

      console.error(
        'Greška pri čitanju korisnika:',
        error
      )

      korisnik = null
    }
  }


  const uloga = (
    korisnik?.uloga ||
    korisnik?.role ||
    ''
  ).toLowerCase()


  // Ako ruta zahtijeva admina,
  // a korisnik nije admin
  if (
    to.meta.requiresAdmin &&
    uloga !== 'admin'
  ) {

    next('/')
    return
  }


  next()
})


export default router