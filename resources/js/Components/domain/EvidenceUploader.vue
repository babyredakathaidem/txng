<script setup>
import { ref } from 'vue'
import UiButton from '@/Components/ui/UiButton.vue'
const emit = defineEmits(['change'])
const files = ref([])

const onPick = (e) => {
  files.value = Array.from(e.target.files || [])
  emit('change', files.value)
}
</script>

<template>
  <div class="bg-white/5 border border-glass rounded-2xl p-4">
    <div class="text-white/80 text-sm font-bold">Minh chứng</div>
    <div class="text-white/50 text-xs mt-1">Ảnh/PDF (tuỳ nghiệp vụ)</div>

    <input type="file" multiple class="mt-3 block w-full text-sm text-white/70 file:mr-4 file:rounded-lg file:border-0 file:bg-brand-500 file:px-4 file:py-2 file:font-extrabold file:text-cosmic-950 hover:file:bg-brand-600 transition" @change="onPick" />

    <div v-if="files.length" class="mt-3 space-y-1 text-sm text-white/70">
      <div v-for="f in files" :key="f.name">• {{ f.name }}</div>
    </div>
  </div>
</template>
