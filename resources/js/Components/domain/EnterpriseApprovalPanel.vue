<script setup>
import UiButton from '@/Components/ui/UiButton.vue'
import UiTextarea from '@/Components/ui/UiTextarea.vue'
import { ref } from 'vue'

const props = defineProps({ enterprise:Object })
const emit = defineEmits(['approve','reject'])
const reason = ref('')
</script>

<template>
  <div class="space-y-4">
    <div class="text-white/80 text-sm">Duyệt/Từ chối doanh nghiệp</div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
      <UiButton variant="solid" @click="emit('approve')">Duyệt</UiButton>
      <UiButton variant="outline" @click="emit('reject', reason)">Từ chối</UiButton>
    </div>

    <div>
      <div class="text-sm text-white/70 mb-2">Lý do từ chối</div>
      <UiTextarea v-model="reason" placeholder="Nhập lý do..." />
    </div>
  </div>
</template>
