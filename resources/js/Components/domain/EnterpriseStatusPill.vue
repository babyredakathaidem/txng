<script setup>
import UiBadge from '@/Components/ui/UiBadge.vue'
const props = defineProps({ status:{type:String,default:'pending'} })
const map = {
  pending: { text:'Chờ duyệt', variant:'warning' },
  approved:{ text:'Đã duyệt', variant:'success' },
  rejected:{ text:'Từ chối', variant:'danger' },
}
</script>
<template>
  <UiBadge :variant="map[status]?.variant || 'default'">{{ map[status]?.text || status }}</UiBadge>
</template>
