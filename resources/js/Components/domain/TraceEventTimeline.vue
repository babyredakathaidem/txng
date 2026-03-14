<script setup>
defineProps({ events:{type:Array,default:()=>[]} })
</script>

<template>
  <div class="space-y-3">
    <div v-for="(e,i) in events" :key="i" class="flex gap-3">
      <div class="flex flex-col items-center">
        <div class="h-3 w-3 rounded-full bg-brand-500"></div>
        <div v-if="i < events.length-1" class="w-px flex-1 bg-white/10"></div>
      </div>
      <div class="bg-white/5 border border-glass rounded-xl p-4 flex-1">
        <div class="flex items-center justify-between gap-3">
          <div class="text-white/90 font-bold">{{ e.title || e.type }}</div>
          <div class="text-xs text-white/50">{{ e.time }}</div>
        </div>
        <div class="text-sm text-white/60 mt-1">{{ e.note }}</div>
      </div>
    </div>
  </div>
</template> 
