<script setup>
import { TabsRoot, useForwardPropsEmits } from 'reka-ui'

const props = defineProps({
  defaultValue: { type: String, required: false },
  modelValue: { type: String, required: false },
  orientation: { type: String, default: 'horizontal' },
  dir: { type: String, required: false },
  activationMode: { type: String, default: 'automatic' },
})
const emits = defineEmits(['update:modelValue'])

const forwarded = useForwardPropsEmits(props, emits)
</script>

<template>
  <TabsRoot v-bind="forwarded">
    <slot />
  </TabsRoot>
</template>
