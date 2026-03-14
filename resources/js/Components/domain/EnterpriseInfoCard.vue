<script setup>
import UiCard from '@/Components/ui/UiCard.vue'
import EnterpriseStatusPill from './EnterpriseStatusPill.vue'
defineProps({ enterprise:Object })
</script>

<template>
  <UiCard>
    <div class="flex items-start justify-between gap-4">
      <div>
        <div class="text-brand-200 font-extrabold text-lg">{{ enterprise?.name }}</div>
        <div class="text-white/60 text-sm mt-1">{{ enterprise?.full_address || '' }}</div>
        <div class="text-white/60 text-sm mt-1">Mã DN: <span class="text-white/90">{{ enterprise?.business_code }}</span></div>
      </div>
      <EnterpriseStatusPill :status="enterprise?.status" />
    </div>
  </UiCard>
</template>
