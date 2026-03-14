<script setup>
import UiCard from '@/Components/ui/UiCard.vue'
defineProps({ batch:Object })
</script>

<template>
  <UiCard>
    <div class="flex items-start justify-between gap-4">
      <div>
        <div class="text-white/90 font-extrabold">Lô: {{ batch?.code }}</div>
        <div class="text-white/60 text-sm mt-1">Sản phẩm: {{ batch?.product_name || '—' }}</div>
        <div class="text-white/60 text-sm mt-1">Số lượng: {{ batch?.quantity || '—' }}</div>
      </div>
      <div class="text-white/60 text-sm">{{ batch?.created_at || '' }}</div>
    </div>
  </UiCard>
</template>
