<script setup>
import { CheckboxIndicator, CheckboxRoot, useForwardPropsEmits } from 'reka-ui'
import { Check } from 'lucide-vue-next'
import { cn } from '@/lib/utils'

const props = defineProps({
  checked:      { type: [Boolean, String], required: false },
  defaultChecked: { type: Boolean, required: false },
  required:     { type: Boolean, required: false },
  name:         { type: String, required: false },
  disabled:     { type: Boolean, required: false },
  value:        { type: String, required: false },
  id:           { type: String, required: false },
  class:        { type: String, required: false },
})
const emits = defineEmits(['update:checked'])

const forwarded = useForwardPropsEmits(props, emits)
</script>

<template>
  <CheckboxRoot
    v-bind="forwarded"
    :class="
      cn(
        'peer h-5 w-5 shrink-0 rounded border border-white/20 bg-[#0d1117] ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 data-[state=checked]:bg-brand-500 data-[state=checked]:text-black data-[state=checked]:border-brand-500 transition-all',
        props.class,
      )
    "
  >
    <CheckboxIndicator
      class="flex h-full w-full items-center justify-center text-current"
    >
      <Check class="h-3.5 w-3.5" stroke-width="4" />
    </CheckboxIndicator>
  </CheckboxRoot>
</template>
