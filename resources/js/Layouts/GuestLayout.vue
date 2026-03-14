<script setup>
import { Head } from '@inertiajs/vue3'
import PublicHeader from '@/Components/nav/PublicHeader.vue'
import PublicFooter from '@/Components/nav/PublicFooter.vue'

const props = defineProps({
  title: { type: String, default: 'AGU Traceability' },
})
</script>

<template>
  <Head :title="props.title" />
  <div class="min-h-screen bg-cosmic text-white/90">
    <PublicHeader />
    <main><slot /></main>
    <PublicFooter />
  </div>
</template>
