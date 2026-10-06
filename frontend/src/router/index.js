import { useAuthAdminStore } from '@/stores/authAdmin'
import { useAuthCarhrisStore } from '@/stores/authCarhris'
import AdminDashboardView from '@/views/AdminDashboardView.vue'
import AdminLoginView from '@/views/AdminLoginView.vue'
import DashboardView from '@/views/DashboardView.vue'
import LoginView from '@/views/LoginView.vue'
import { createRouter, createWebHistory } from 'vue-router'

const adminRoutes = {
  path: '/system-admin',
  children: [
    {
      path: 'login', component: AdminLoginView, meta: { guestOnly: true }
    },
    {
      path: 'dashboard', component: AdminDashboardView, meta: { requiresAdminAuth: true },
    }
  ]
}

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    { path: '/login', component: LoginView, meta: { guestOnly: true } },
    { path: '/dashboard', component: DashboardView, meta: { requiresAuth: true } },
    // Not linked from anywhere in the UI -- native_admins is a separate
    // identity path from CARIS, kept off the regular login screen.
    adminRoutes
  ],
})


router.beforeEach((to) => {
  const carhrisAuth = useAuthCarhrisStore()
  const adminAuth = useAuthAdminStore()

  if (to.meta.requiresAuth && !carhrisAuth.isLoggedIn) {
    return '/login'
  }

  if (to.meta.requiresAdminAuth && !adminAuth.isLoggedIn) {
    return '/system-admin/login'
  }

  if (to.meta.guestOnly && carhrisAuth.isLoggedIn) {
    return '/dashboard'
  }

  if (to.meta.guestOnly && adminAuth.isLoggedIn) {
    return '/system-admin/dashboard'
  }
})

export default router
