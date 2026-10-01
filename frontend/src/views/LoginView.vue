<script setup>
import { useAuthCarhrisStore } from '@/stores/authCarhris';
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { Card, CardHeader, CardTitle, CardDescription, CardContent } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { Separator } from '@/components/ui/separator';
import { toast } from 'vue-sonner';

const auth = useAuthCarhrisStore()
const router = useRouter()

const username = ref('');
const password = ref('');

const isSubmitting = ref(false)

async function submit() {
    isSubmitting.value = true
    try {
        await auth.login(username.value, password.value);
        router.push('/dashboard')
    } catch (err) {
        const errorMessage = err.message;
        showToast(`Login failed: ${errorMessage}`, 'error')
    } finally {
        isSubmitting.value = false
    }
}

function showToast(title, type = 'default', description, action) {
    const options = {
        description: description ?? '',
        action: action ?? null,
    }

    switch (type) {
        case 'success':
            toast.success(title ?? '', options)
            break
        case 'info':
            toast.info(title ?? '', options)
            break
        case 'warning':
            toast.warning(title ?? '', options)
            break
        case 'error':
            toast.error(title ?? '', options)
            break
        case 'promise':
            // `description` must be the promise itself for this type
            toast.promise(description, {
                loading: title ?? 'Loading...',
                success: 'Done.',
                error: 'Something went wrong.',
            })
            break
        default:
            toast(title ?? '', options)
    }
}

</script>

<template>
    <div class="min-h-screen flex items-center justify-center bg-slate-100 px-4">
        <Card class="w-full max-w-md">
            <CardHeader>
                <img src="@/assets/logo/denr-logo.png" alt="" class="w-26 h-26 mx-auto mb-3">
                <CardTitle class="text-center">
                    EMB-IMS
                </CardTitle>
                <CardDescription class="text-center">
                    Sign in to continue
                </CardDescription>
            </CardHeader>

            <Separator />

            <CardContent>
                <form @submit.prevent="submit">
                    <div class="grid w-full items-center gap-4">
                        <div class="flex flex-col space-y-1.5">
                            <Label for="username">Username</Label>
                            <Input id="username" type="text" v-model="username" autocomplete="username" required
                                class="focus-visible:ring-green-500 focus-visible:border-green-500" />
                        </div>
                        <div class="flex flex-col space-y-1.5">
                            <Label for="password">Password</Label>
                            <Input id="password" type="password" v-model="password" autocomplete="current-password"
                                required class="focus-visible:ring-green-500 focus-visible:border-green-500" />
                        </div>

                        <Button type="submit"
                            class="bg-green-600 hover:bg-green-700 disabled:opacity-60 disabled:cursor-not-allowed flex items-center justify-center">
                            <Spinner v-if="isSubmitting" />
                            {{ isSubmitting ? 'Logging in...' : 'Login' }}
                        </Button>
                    </div>
                </form>
            </CardContent>
        </Card>
    </div>
</template>