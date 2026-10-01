<script setup>
import { useAuthAdminStore } from '@/stores/authAdmin';
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { Card, CardHeader, CardTitle, CardDescription, CardContent } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { Separator } from '@/components/ui/separator';
import { showToast } from '@/lib/toast';

const auth = useAuthAdminStore()
const router = useRouter()

const username = ref('');
const password = ref('');

const isSubmitting = ref(false)

async function submit() {
    isSubmitting.value = true
    try {
        await auth.login(username.value, password.value);
        router.push('/system-admin/dashboard')
    } catch (err) {
        showToast(`Login failed: ${err.message}`, 'error')
    } finally {
        isSubmitting.value = false
    }
}
</script>

<template>
    <div class="min-h-screen flex items-center justify-center bg-slate-900 px-4">

        <Card class="w-full max-w-md">
            <CardHeader>
                <img src="@/assets/logo/denr-logo.png" alt="" class="w-26 h-26 mx-auto mb-3">
                <CardTitle class="text-center">
                    System Administration
                </CardTitle>
                <CardDescription class="text-center">
                    Restricted access
                </CardDescription>
            </CardHeader>

            <Separator />

            <CardContent>
                <form @submit.prevent="submit">
                    <div class="grid w-full items-center gap-4">
                        <div class="flex flex-col space-y-1.5">
                            <Label for="admin-username">Username</Label>
                            <Input id="admin-username" type="text" v-model="username" autocomplete="username"
                                required />
                        </div>
                        <div class="flex flex-col space-y-1.5">
                            <Label for="admin-password">Password</Label>
                            <Input id="admin-password" type="password" v-model="password"
                                autocomplete="current-password" required />
                        </div>

                        <Button type="submit"
                            class="bg-slate-800 hover:bg-slate-900 disabled:opacity-60 disabled:cursor-not-allowed flex items-center justify-center">
                            <Spinner v-if="isSubmitting" />
                            {{ isSubmitting ? 'Logging in...' : 'Login' }}
                        </Button>
                    </div>
                </form>
            </CardContent>
        </Card>
    </div>
</template>
