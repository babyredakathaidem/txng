<script setup>
import { ToastAction } from "reka-ui";
import { computed } from "vue";
import { cn } from "@/lib/utils";

const props = defineProps({
  class: { type: null, required: false },
  altText: { type: String, required: true },
  asChild: { type: Boolean, required: false },
  as: { type: null, required: false },
});
</script>

<template>
  <ToastAction
    v-bind="props"
    :class="
      cn(
        'inline-flex h-8 shrink-0 items-center justify-center rounded-md border bg-transparent px-3 text-xs font-medium ring-offset-background transition-colors hover:bg-secondary focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 group-[.destructive]:border-muted/40 group-[.destructive]:hover:border-destructive group-[.destructive]:hover:bg-destructive group-[.destructive]:hover:text-destructive-foreground group-[.destructive]:focus:ring-destructive group-[.agu]:border-white/20 group-[.agu]:hover:bg-white/10 group-[.agu]:text-white',
        props.class,
      )
    "
  >
    <slot />
  </ToastAction>
</template>
