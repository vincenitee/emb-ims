<script setup>
import { useAuthAdminStore } from '@/stores/authAdmin';
import { storeToRefs } from 'pinia';
import { useRouter } from 'vue-router';

const auth = useAuthAdminStore()
const router = useRouter()

const { admin } = storeToRefs(auth)

async function logout() {
    try {
        await auth.logout()
    } catch (err) {
        console.error(err.message);
    } finally {
        router.push('/system-admin/login')
    }
}
</script>

<template>
    <section>
        <h1>Welcome, {{ admin?.full_name ?? 'Admin' }}</h1>

        <button @click="logout">Logout</button>
    </section>
</template>
