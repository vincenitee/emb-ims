<script setup>
import { useAuthCarhrisStore } from '@/stores/authCarhris';
import { storeToRefs } from 'pinia';
import { useRouter } from 'vue-router';

const auth = useAuthCarhrisStore()
const router = useRouter()

const { user } = storeToRefs(auth)

async function logout() {
    try{
        await auth.logout()
    } catch (err) {
        console.error(err.message);
    } finally {
        router.push('/login')
    }
}
</script>

<template>
    <section>
        <h1>Welcome, {{ user?.full_name ?? 'User' }}</h1>

        <button @click="logout">Logout</button>
    </section>
</template>