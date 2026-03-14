import { ref } from "vue";

const count = ref(0);
const toasts = ref([]);

function toast({ title, description, action, variant = "default", duration = 5000 }) {
  const id = count.value++;
  const newToast = {
    id,
    title,
    description,
    action,
    variant,
    duration,
    open: true,
  };

  toasts.value.push(newToast);

  setTimeout(() => {
    toasts.value = toasts.value.filter((t) => t.id !== id);
  }, duration);

  return {
    id,
    dismiss: () => {
      toasts.value = toasts.value.filter((t) => t.id !== id);
    },
  };
}

export function useToast() {
  return {
    toasts,
    toast,
  };
}
