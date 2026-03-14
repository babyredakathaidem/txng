<script setup>
import { onMounted } from 'vue'
import { router } from '@inertiajs/vue3'

// Trang này đã deprecated — BatchTransferController thay bằng TransferEventController
// Redirect sang form chuyển giao mới hỗ trợ multi-batch + GS1 AI(400)/AI(00)
defineProps({
  batch: { type: Object, default: null },
})

onMounted(() => {
  router.visit(route('transfer.out.create'), { replace: true })
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
