import { useAuthAdminStore } from '@/stores/authAdmin'
import { useAuthCarhrisStore } from '@/stores/authCarhris'
import AdminDashboardView from '@/views/AdminDashboardView.vue'
import AdminLoginView from '@/views/AdminLoginView.vue'
import DashboardView from '@/views/DashboardView.vue'
import LoginView from '@/views/LoginView.vue'
import { createRouter, createWebHistory } from 'vue-router'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    { path: '/login', component: LoginView },
    { path: '/dashboard', component: DashboardView, meta: { requiresAuth: true } },
    // Not linked from anywhere in the UI -- native_admins is a separate
    // identity path from CARIS, kept off the regular login screen.
    { path: '/system-admin/login', component: AdminLoginView },
    { path: '/system-admin/dashboard', component: AdminDashboardView, meta: { requiresAdminAuth: true } },
  ],
})

router.beforeEach((to) => {
  if (to.meta.requiresAuth) {
    const auth = useAuthCarhrisStore()
    if (!auth.isLoggedIn) {
      return '/login'
    }
  }

  if (to.meta.requiresAdminAuth) {
    const auth = useAuthAdminStore()
    if (!auth.isLoggedIn) {
      return '/system-admin/login'
    }
  }
})

export default router
