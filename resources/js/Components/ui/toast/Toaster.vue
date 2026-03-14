<script setup>
import { useToast } from "./use-toast";
import Toast from "./Toast.vue";
import ToastClose from "./ToastClose.vue";
import ToastDescription from "./ToastDescription.vue";
import ToastProvider from "./ToastProvider.vue";
import ToastTitle from "./ToastTitle.vue";
import ToastViewport from "./ToastViewport.vue";
import { CheckCircle2, AlertTriangle, Info, ShieldAlert } from 'lucide-vue-next';

const { toasts } = useToast();
</script>

<template>
  <ToastProvider>
    <Toast v-for="toast in toasts" :key="toast.id" v-bind="toast" class="transition-all duration-500 ease-in-out">
      <div class="flex gap-3 items-center w-full">
        <!-- Minimal Icon -->
        <div class="shrink-0">
           <CheckCircle2 v-if="toast.variant === 'default' || !toast.variant" class="size-4" />
           <ShieldAlert v-else-if="toast.variant === 'agu'" class="size-4" />
           <AlertTriangle v-else-if="toast.variant === 'destructive'" class="size-4" />
           <Info v-else class="size-4" />
        </div>

        <div class="grid gap-0.5 flex-1 min-w-0">
          <ToastTitle v-if="toast.title" class="text-[10px] font-black tracking-widest leading-none uppercase">
            {{ toast.title }}
          </ToastTitle>
          <ToastDescription v-if="toast.description" class="text-[10px] font-bold opacity-80 leading-snug">
            {{ toast.description }}
          </ToastDescription>
        </div>
      </div>
      
      <component :is="toast.action" />
      <ToastClose class="opacity-40 hover:opacity-100 transition-opacity" />
    </Toast>
    <ToastViewport />
  </ToastProvider>
</template>
