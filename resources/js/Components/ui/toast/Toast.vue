<script setup>
import { ToastRoot, useForwardPropsEmits } from "reka-ui";
import { computed } from "vue";
import { toastVariants } from ".";
import { cn } from "@/lib/utils";

const props = defineProps({
  class: { type: null, required: false },
  variant: { type: null, required: false },
  onOpenChange: { type: null, required: false },
  duration: { type: Number, required: false },
  defaultOpen: { type: Boolean, required: false },
  open: { type: Boolean, required: false },
  asChild: { type: Boolean, required: false },
  as: { type: null, required: false },
  type: { type: String, required: false },
});

const emits = defineEmits(["update:open"]);

const forwarded = useForwardPropsEmits(props, emits);
</script>

<template>
  <ToastRoot
    v-bind="forwarded"
    :class="cn(toastVariants({ variant }), props.class)"
    @update:open="emits('update:open', $event)"
  >
    <slot />
  </ToastRoot>
</template>
