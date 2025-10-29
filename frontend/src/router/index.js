import { createRouter, createWebHistory } from 'vue-router'
import Appointments from '@/components/Appointments.vue'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: '/appointments',
      name: 'appointments',
      component: Appointments,
    },
    {
      path: '/',
      redirect: '/appointments',
    }
  ],
})

export default router
