<script setup>
import { onMounted } from 'vue'
import { router } from '@inertiajs/vue3'

// Trang này đã deprecated — dùng Events/Transfer/Pending.vue thay thế
// Route mới: events.transfer.pending (TransferEventController@pendingIndex)
onMounted(() => {
  router.visit(route('events.transfer.pending'), { replace: true })
})
</script>

<template>
  <div class="flex items-center justify-center min-h-[40vh] text-white/30 text-sm gap-2">
    <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
      <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
      <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
    </svg>
    Đang chuyển hướng...
  </div>
</template>
