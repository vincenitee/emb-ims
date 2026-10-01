import { getErrorMessage } from "@/lib/error";
import http from "@/lib/http";
import { defineStore } from "pinia";
import { computed, ref } from "vue";

export const useAuthAdminStore = defineStore('adminAuth', () => {
    // State
    const admin = ref(null)

    // Getter
    const isLoggedIn = computed(() => admin.value !== null);

    const accessRevoked = ref(false)

    // Actions  
    async function login(username, password) {
        try {
            const res = await http.post('/admin/login', { username, password })
            const message = res.data?.message
            const user = res.data?.user
            admin.value = user

            console.log(message)
        } catch (err) {
            throw new Error(getErrorMessage(err), { cause: err })
        }
    }

    async function fetchMe() {
        try {
            const res = await http.get('/admin/me')
            const user = res.data;
            admin.value = user
        } catch (err) {
            const status = err.response?.status;

            if (status === 401) {
                admin.value = null
                return;
            } else if (status === 403) {
                accessRevoked.value = true;
                admin.value = null;
                return;
            }

            throw new Error(getErrorMessage(err), { cause: err })
        }
    }

    async function logout() {
        try {
            const res = await http.post('/admin/logout');
            return res.data?.message;
        } catch (err) {
            throw new Error(getErrorMessage(err), { cause: err })
        } finally {
            admin.value = null
        }
    }

    return { admin, isLoggedIn, login, fetchMe, logout }

});