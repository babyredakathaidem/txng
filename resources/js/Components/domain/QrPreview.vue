<script setup>
defineProps({ value:{type:String,default:''} })
</script>

<template>
  <div class="bg-white/5 border border-glass rounded-2xl p-4">
    <div class="text-white/80 text-sm font-bold">QR Preview</div>
    <div class="text-white/50 text-xs mt-1">Chưa render QR thật (tuỳ ba muốn dùng thư viện qrcode). Value:</div>
    <div class="mt-2 text-brand-200 break-all text-sm">{{ value }}</div>
  </div>
</template>
