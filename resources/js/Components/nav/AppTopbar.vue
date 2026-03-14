<script setup>
import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'
import { 
  Menu, 
  X, 
  Search, 
  Bell, 
  Command
} from 'lucide-vue-next'
import ModeToggle from '@/Components/ModeToggle.vue'
import { SidebarTrigger } from '@/Components/ui/sidebar'
import { Button } from '@/Components/ui/button'
import { Input } from '@/Components/ui/input'
import { Separator } from '@/Components/ui/separator'

const page = usePage()
const user = computed(() => page.props.auth?.user)

const isSuperAdmin = computed(() => {
    return user.value?.is_super_admin === true || user.value?.is_super_admin === 1 || user.value?.is_super_admin === '1';
})
</script>

<template>
  <header class="h-20 bg-background/80 backdrop-blur-xl border-b border-border/50 flex items-center justify-between px-6 lg:px-10 z-40 sticky top-0 transition-all duration-500">
    <div class="flex items-center gap-6 flex-1">
      <SidebarTrigger class="p-3 text-muted-foreground hover:text-foreground hover:bg-muted/50 rounded-2xl border border-border transition-all shadow-sm" />
      
      <!-- Global Search Mock -->
      <div class="hidden md:flex items-center relative max-w-md w-full group">
        <Search class="absolute left-4 top-1/2 -translate-y-1/2 size-4 text-muted-foreground/40 group-focus-within:text-primary transition-colors" />
        <Input 
          placeholder="Tìm kiếm nhanh... (Cmd + K)" 
          class="h-11 pl-11 pr-12 rounded-2xl bg-muted/30 border-transparent focus:bg-background focus:ring-primary/20 transition-all shadow-inner"
        />
        <div class="absolute right-3 top-1/2 -translate-y-1/2 flex items-center gap-1 px-2 py-1 rounded-lg bg-background border border-border/50 shadow-sm opacity-40">
           <Command class="size-3" />
           <span class="text-[10px] font-black">K</span>
        </div>
      </div>
    </div>

    <div class="flex items-center gap-4 lg:gap-6">
      <div class="flex items-center gap-2">
        <Button variant="ghost" size="icon" class="h-11 w-11 rounded-2xl relative hover:bg-primary/5 group">
           <Bell class="size-5 text-muted-foreground group-hover:text-primary transition-colors" />
           <span class="absolute top-3 right-3 size-2 bg-primary rounded-full ring-4 ring-background animate-pulse"></span>
        </Button>
        <ModeToggle class="h-11 w-11 rounded-2xl" />
      </div>

      <Separator orientation="vertical" class="h-8 opacity-50 hidden sm:block" />

      <div class="flex items-center gap-4">
        <div v-if="isSuperAdmin" class="hidden lg:flex items-center gap-2 bg-primary/10 text-primary px-5 py-2.5 rounded-full text-[9px] font-black uppercase tracking-[0.2em] border border-primary/20 shadow-lg shadow-primary/5 animate-in fade-in zoom-in duration-700">
           <div class="size-1.5 rounded-full bg-primary animate-pulse"></div>
           Master Instance
        </div>
        
        <div class="hidden sm:block text-right">
           <p class="text-[10px] font-black text-muted-foreground/30 uppercase tracking-[0.3em]">System Status</p>
           <p class="text-[9px] font-bold text-emerald-500 uppercase tracking-widest flex items-center justify-end gap-1">
              <span class="size-1 bg-emerald-500 rounded-full"></span>
              Live v1.0.5
           </p>
        </div>
      </div>
    </div>
  </header>
</template>
