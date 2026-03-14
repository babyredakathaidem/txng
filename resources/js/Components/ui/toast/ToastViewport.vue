<script setup>
import { ToastViewport } from "reka-ui";
import { computed } from "vue";
import { cn } from "@/lib/utils";

const props = defineProps({
  class: { type: null, required: false },
  label: { type: String, required: false },
  asChild: { type: Boolean, required: false },
  as: { type: null, required: false },
});
</script>

<template>
  <ToastViewport
    v-bind="props"
    :class="
      cn(
        'fixed bottom-0 right-0 z-[100] flex max-h-screen w-full flex-col-reverse p-4 sm:bottom-6 sm:right-6 sm:flex-col md:max-w-[420px]',
        props.class,
      )
    "
  />
</template>
