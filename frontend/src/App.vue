<script setup>
import { Toaster } from '@/components/ui/sonner';
import 'vue-sonner/style.css';

</script>

<template>
  <RouterView />
  <Toaster position="top-center" close-button />
</template>

<style scoped></style>
