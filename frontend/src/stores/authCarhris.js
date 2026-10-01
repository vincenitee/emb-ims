import { getErrorMessage } from "@/lib/error";
import http from "@/lib/http";
import { defineStore } from "pinia";
import { computed, ref } from "vue";

export const useAuthCarhrisStore = defineStore('carhrisAuth', () => {
    // State
    const user = ref(null); // will hold the user information

    // Getters
    const isLoggedIn = computed(() => user.value !== null)

    const accessRevoked = ref(false)

    // Actions
    async function login(username, password) {
        try {
            const res = await http.post('/login', { username, password })
            const message = res.data?.message;
            user.value = res.data?.user;

            console.log(message)
        } catch (err) {
            throw new Error(getErrorMessage(err), { cause: err })
        }
    }

    async function fetchMe() {
        try {
            const res = await http.get('/me');
            user.value = res.data;
        } catch (err) {
            if (err.response?.status === 401) {
                user.value = null;
                return;
            } else if (err.response?.status === 403) {
                accessRevoked.value = true
                user.value = null
                return;
            }

            throw new Error(getErrorMessage(err), { cause: err })
        }
    }

    async function logout() {
        try {
            const res = await http.post('/logout');
            return res.data?.message;
        } catch (err) {
            throw new Error(getErrorMessage(err), { cause: err });
        } finally {
            user.value = null;
        }
    }

    return { user, isLoggedIn, accessRevoked, login, fetchMe, logout }
})