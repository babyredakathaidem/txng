import { cva } from "class-variance-authority";

export { default as Toast } from "./Toast.vue";
export { default as ToastViewport } from "./ToastViewport.vue";
export { default as ToastAction } from "./ToastAction.vue";
export { default as ToastClose } from "./ToastClose.vue";
export { default as ToastTitle } from "./ToastTitle.vue";
export { default as ToastDescription } from "./ToastDescription.vue";
export { default as ToastProvider } from "./ToastProvider.vue";
export { default as Toaster } from "./Toaster.vue";

export const toastVariants = cva(
  "group pointer-events-auto relative flex w-full items-center justify-between space-x-4 overflow-hidden rounded-[1.25rem] border p-4 pr-8 shadow-xl transition-all data-[swipe=cancel]:translate-x-0 data-[swipe=end]:translate-x-[var(--radix-toast-swipe-end-x)] data-[swipe=move]:translate-x-[var(--radix-toast-swipe-move-x)] data-[swipe=move]:transition-none data-[state=open]:animate-in data-[state=closed]:animate-out data-[swipe=end]:animate-out data-[state=closed]:fade-out-80 data-[state=closed]:slide-out-to-right-full data-[state=open]:slide-in-from-right-full",
  {
    variants: {
      variant: {
        default: "border-[var(--toast-success-border)] bg-[var(--toast-success-bg)] text-[var(--toast-success-text)] backdrop-blur-md font-black uppercase tracking-widest text-[10px]",
        destructive: "destructive group border-destructive bg-destructive text-destructive-foreground",
        agu: "border-[var(--toast-agu-border)] bg-[var(--toast-agu-bg)] text-[var(--toast-agu-text)] backdrop-blur-md font-black uppercase tracking-widest text-[10px]",
      },
    },
    defaultVariants: {
      variant: "default",
    },
  },
);
