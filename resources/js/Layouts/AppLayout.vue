<script setup>
import { computed, watch, onMounted } from 'vue'
import { usePage } from '@inertiajs/vue3'
import { Toaster } from '@/Components/ui/toast'
import { useToast } from '@/Components/ui/toast/use-toast'
import { SidebarProvider, SidebarInset } from '@/Components/ui/sidebar'
import AppSidebar from '@/Components/nav/AppSidebar.vue'
import AppTopbar from '@/Components/nav/AppTopbar.vue'

const page = usePage()
const { toast } = useToast()

// Hàm xử lý hiển thị thông báo
const showNotifications = (flash) => {
  if (flash?.success) {
    toast({
      title: "Thành công",
      description: flash.success,
      duration: 3000,
    })
  }
  
  if (flash?.error) {
    toast({
      variant: "agu",
      title: "Thông báo bảo mật",
      description: flash.error,
      duration: 5000,
    })
  }
}

// Theo dõi sự thay đổi của flash messages từ server
watch(() => page.props.flash, (newFlash) => {
  showNotifications(newFlash)
}, { deep: true, immediate: true })

</script>

<template>
    <SidebarProvider>
        <div class="min-h-screen bg-background text-foreground flex w-full font-sans transition-colors duration-500 overflow-hidden relative">
            <!-- Unified Sidebar -->
            <AppSidebar />

            <!-- Main Content Inset -->
            <SidebarInset class="flex flex-col min-w-0 bg-muted/5 relative overflow-hidden">
                <!-- Background Decorative Gradients -->
                <div class="absolute -right-40 -bottom-40 w-[600px] h-[600px] bg-primary/5 blur-[120px] rounded-full pointer-events-none opacity-50 z-0"></div>
                <div class="absolute -left-20 -top-20 w-[400px] h-[400px] bg-primary/5 blur-[100px] rounded-full pointer-events-none opacity-30 z-0"></div>

                <!-- Unified Topbar -->
                <AppTopbar />

                <!-- Content Area -->
                <main class="flex-1 overflow-y-auto custom-scrollbar relative z-10 p-6 lg:p-10">
                    <div class="max-w-[1600px] mx-auto">
                        <slot />
                    </div>
                </main>
            </SidebarInset>
        </div>
    </SidebarProvider>

    <!-- Toaster chuẩn Shadcn Vue (Radix UI based) -->
    <Toaster />
</template>

<style>
.custom-scrollbar::-webkit-scrollbar { width: 4px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: oklch(0.646 0.222 41.116 / 0.2); border-radius: 10px; }

/* Animation cho nội dung khi chuyển trang */
.page-enter-active, .page-leave-active { transition: opacity 0.3s, transform 0.3s; }
.page-enter-from, .page-leave-to { opacity: 0; transform: translateY(10px); }
</style>
