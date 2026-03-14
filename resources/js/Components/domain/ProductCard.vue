<script setup>
import UiCard from '@/Components/ui/UiCard.vue'
defineProps({ product:Object })
</script>

<template>
  <UiCard>
    <div class="flex items-start justify-between gap-4">
      <div>
        <div class="text-white/90 font-extrabold">{{ product?.name }}</div>
        <div class="text-white/60 text-sm mt-1">GTIN: {{ product?.gtin || '—' }}</div>
        <div class="text-white/60 text-sm mt-1">{{ product?.description || '' }}</div>
      </div>
      <div class="text-brand-300 font-bold text-sm">{{ product?.status || '' }}</div>
    </div>
  </UiCard>
</template>
