<script setup>
import { computed } from 'vue'
import { usePage, router } from '@inertiajs/vue3'
import { Link } from '@inertiajs/vue3'
import {
  Sidebar, SidebarContent, SidebarFooter, SidebarHeader,
  SidebarMenu, SidebarMenuItem, SidebarMenuButton,
  SidebarGroup, SidebarGroupLabel, SidebarRail,
} from '@/Components/ui/sidebar'
import {
  DropdownMenu, DropdownMenuContent,
  DropdownMenuItem, DropdownMenuTrigger,
  DropdownMenuLabel, DropdownMenuSeparator
} from '@/Components/ui/dropdown-menu'
import {
  LayoutDashboard, Box, Package, Activity, FlaskConical,
  Users, Settings, BarChart3, Building2, ShieldCheck,
  ChevronsUpDown, LogOut, MapPin, User, Bell, 
  CheckCircle2,
  Truck,
  ArrowDownLeft
} from 'lucide-vue-next'

const page   = usePage()
const authUser = computed(() => page.props.auth?.user)
const url    = computed(() => page.url)

const isSuperAdmin = computed(() => {
    const u = authUser.value;
    const isAtSysRoute = url.value.includes('/sys');
    const isSuperField = u?.is_super_admin === true || u?.is_super_admin === 1 || u?.is_super_admin === '1';
    return isSuperField || isAtSysRoute;
})

const isEnterpriseAdmin = computed(() => authUser.value?.role === 'enterprise_admin')

// Helper kiểm tra quyền hạn cho menu
const can = (perms) => {
    if (isEnterpriseAdmin.value || isSuperAdmin.value) return true;
    const userPerms = authUser.value?.permissions ?? [];
    if (typeof perms === 'string') return userPerms.includes(perms);
    return perms.some(p => userPerms.includes(p));
}

const mainNav = computed(() => [
  { label: 'Dashboard',     href: '/dashboard',         icon: LayoutDashboard, show: true },
  { label: 'Sản phẩm',      href: '/products',          icon: Box, show: can(['enterprise.products.view', 'enterprise.products.manage']) },
  { label: 'Lô hàng',       href: '/batches',           icon: Package, show: can(['enterprise.batches.view', 'enterprise.batches.manage']) },
  { label: 'Sự kiện TRACE', href: '/events',            icon: Activity, show: can(['enterprise.trace_events.view', 'enterprise.trace_events.create']) },
].filter(item => item.show))

const enterpriseNav = computed(() => [
  { label: 'Nhân sự DN', href: '/enterprise/users',    icon: Users, show: isEnterpriseAdmin.value },
  { label: 'Cài đặt DN', href: '/enterprise/settings', icon: Settings, show: isEnterpriseAdmin.value },
].filter(item => item.show))

const sysNav = [
  { label: 'Thống kê',     href: '/sys/stats',       icon: BarChart3 },
  { label: 'Duyệt DN',     href: '/sys/enterprises', icon: Building2 },
  { label: 'Cấu hình CTE', href: '/sys/config',      icon: Settings },
  { label: 'Tài khoản',    href: '/sys/users',        icon: ShieldCheck },
]

const isActive = (href) => {
    if (!href) return false;
    if (href === '/dashboard') return url.value === '/dashboard';
    return url.value.startsWith(href);
}
</script>

<template>
  <Sidebar collapsible="icon" class="border-r border-border/50 bg-card/30 backdrop-blur-3xl transition-all duration-500 ease-in-out">
    <!-- Logo header -->
    <SidebarHeader class="h-20 flex items-center justify-center border-b border-border/50 overflow-hidden relative">
      <div class="absolute -left-10 -top-10 w-24 h-24 bg-primary/10 blur-3xl rounded-full"></div>
      <SidebarMenu>
        <SidebarMenuItem>
          <SidebarMenuButton size="lg" as-child class="hover:bg-primary/5 transition-all">
            <Link href="/dashboard" class="flex items-center gap-4 px-2 w-full">
              <div class="flex aspect-square size-10 min-w-[40px] items-center justify-center
                          rounded-2xl bg-primary text-primary-foreground text-sm font-black shadow-lg shadow-primary/20 shrink-0">
                <CheckCircle2 class="size-6" />
              </div>
              <div class="flex flex-col gap-0.5 leading-none">
                <span class="font-black text-2xl tracking-tighter text-foreground uppercase italic">AGU <span class="text-primary font-medium lowercase not-italic">Trace</span></span>
              </div>
            </Link>
          </SidebarMenuButton>
        </SidebarMenuItem>
      </SidebarMenu>
    </SidebarHeader>

    <SidebarContent class="px-3 pt-6 flex-1 overflow-y-auto custom-scrollbar">
      <!-- Main nav -->
      <SidebarGroup>
        <SidebarGroupLabel class="text-[9px] font-black uppercase tracking-[0.3em] text-muted-foreground/30 px-4 py-4 mb-2">Core Workspace</SidebarGroupLabel>
        <SidebarMenu class="space-y-1.5">
          <SidebarMenuItem v-for="item in mainNav" :key="item.href">
            <SidebarMenuButton 
              :is-active="isActive(item.href)" 
              :tooltip="item.label" 
              as-child
              class="h-14 rounded-2xl transition-all duration-300 data-[active=true]:bg-sidebar-accent data-[active=true]:text-sidebar-accent-foreground data-[active=true]:shadow-inner group"
            >
              <Link :href="item.href" class="flex items-center gap-4 px-4 w-full">
                <component :is="item.icon" :class="isActive(item.href) ? 'text-sidebar-accent-foreground' : 'text-muted-foreground group-hover:text-primary transition-colors'" class="size-6 min-w-[24px] shrink-0" />
                <span class="font-bold text-sm tracking-tight uppercase">{{ item.label }}</span>
              </Link>
            </SidebarMenuButton>
          </SidebarMenuItem>
        </SidebarMenu>
      </SidebarGroup>

      <!-- Enterprise nav -->
      <SidebarGroup v-if="!isSuperAdmin && enterpriseNav.length">
        <SidebarGroupLabel class="text-[9px] font-black uppercase tracking-[0.3em] text-muted-foreground/30 px-4 py-4 mb-2">Internal Management</SidebarGroupLabel>
        <SidebarMenu class="space-y-1.5">
          <SidebarMenuItem v-for="item in enterpriseNav" :key="item.href">
            <SidebarMenuButton 
              :is-active="isActive(item.href)" 
              :tooltip="item.label" 
              as-child
              class="h-14 rounded-2xl transition-all duration-300 data-[active=true]:bg-sidebar-accent data-[active=true]:text-sidebar-accent-foreground data-[active=true]:shadow-inner group"
            >
              <Link :href="item.href" class="flex items-center gap-4 px-4 w-full">
                <component :is="item.icon" :class="isActive(item.href) ? 'text-sidebar-accent-foreground' : 'text-muted-foreground group-hover:text-primary transition-colors'" class="size-6 min-w-[24px] shrink-0" />
                <span class="font-bold text-sm tracking-tight uppercase">{{ item.label }}</span>
              </Link>
            </SidebarMenuButton>
          </SidebarMenuItem>
        </SidebarMenu>
      </SidebarGroup>

      <!-- Sys admin nav -->
      <SidebarGroup v-if="isSuperAdmin">
        <SidebarGroupLabel class="text-[9px] font-black uppercase tracking-[0.3em] text-muted-foreground/30 px-4 py-4 mb-2">Master Intelligence</SidebarGroupLabel>
        <SidebarMenu class="space-y-1.5">
          <SidebarMenuItem v-for="item in sysNav" :key="item.href">
            <SidebarMenuButton 
              :is-active="isActive(item.href)" 
              :tooltip="item.label" 
              as-child
              class="h-14 rounded-2xl transition-all duration-300 data-[active=true]:bg-sidebar-accent data-[active=true]:text-sidebar-accent-foreground data-[active=true]:shadow-inner group"
            >
              <Link :href="item.href" class="flex items-center gap-4 px-4 w-full">
                <component :is="item.icon" :class="isActive(item.href) ? 'text-sidebar-accent-foreground' : 'text-muted-foreground group-hover:text-primary transition-colors'" class="size-6 min-w-[24px] shrink-0" />
                <span class="font-bold text-sm tracking-tight uppercase">{{ item.label }}</span>
              </Link>
            </SidebarMenuButton>
          </SidebarMenuItem>
        </SidebarMenu>
      </SidebarGroup>
    </SidebarContent>

    <!-- User footer dropdown -->
    <SidebarFooter class="p-6 bg-muted/20 border-t border-border/50">
      <SidebarMenu>
        <SidebarMenuItem>
          <DropdownMenu>
            <DropdownMenuTrigger as-child>
              <SidebarMenuButton size="lg"
                class="h-16 rounded-2xl transition-all data-[state=open]:bg-card hover:bg-card border border-transparent hover:border-border shadow-sm group px-2">
                <div class="flex aspect-square size-10 min-w-[40px] items-center justify-center
                            rounded-xl bg-primary text-primary-foreground text-sm font-black shadow-lg shadow-primary/20 transition-transform group-hover:scale-105 shrink-0">
                  {{ authUser?.name?.[0]?.toUpperCase() }}
                </div>
                <div class="flex flex-col gap-1 leading-none ml-3 flex-1 min-w-0">
                  <span class="font-black text-sm text-foreground truncate uppercase italic tracking-tighter">{{ authUser?.name }}</span>
                  <span class="text-[9px] font-black text-primary uppercase tracking-[0.2em] truncate">{{ isSuperAdmin ? 'Master' : 'Entity' }}</span>
                </div>
                <ChevronsUpDown class="ml-auto size-4 text-muted-foreground/40 group-hover:text-primary transition-colors shrink-0" />
              </SidebarMenuButton>
            </DropdownMenuTrigger>
            <DropdownMenuContent side="top" align="start" class="w-64 bg-card/95 backdrop-blur-xl border-border rounded-3xl p-2 shadow-2xl animate-in slide-in-from-bottom-2 duration-300">
              <DropdownMenuLabel class="px-4 py-3">
                <div class="text-[9px] font-black uppercase tracking-[0.3em] text-muted-foreground/50">Secure Session</div>
              </DropdownMenuLabel>
              <Link :href="route('profile.edit')">
                <DropdownMenuItem class="rounded-2xl cursor-pointer py-3 px-4 hover:bg-primary/10 hover:text-primary transition-all font-bold uppercase text-[10px] tracking-widest">
                    <User class="mr-3 size-4" /> My Profile
                </DropdownMenuItem>
              </Link>
              <DropdownMenuItem class="rounded-2xl cursor-pointer py-3 px-4 hover:bg-primary/10 hover:text-primary transition-all font-bold uppercase text-[10px] tracking-widest">
                <Bell class="mr-3 size-4" /> Notifications
              </DropdownMenuItem>
              <DropdownMenuSeparator class="bg-border/50 my-2" />
              <DropdownMenuItem @click="router.post('/logout')" class="rounded-2xl cursor-pointer py-3 px-4 text-red-500 hover:bg-red-500/10 transition-all font-black uppercase text-[10px] tracking-widest">
                <LogOut class="mr-3 size-4" />
                Sign Out
              </DropdownMenuItem>
            </DropdownMenuContent>
          </DropdownMenu>
        </SidebarMenuItem>
      </SidebarMenu>
    </SidebarFooter>

    <SidebarRail />
  </Sidebar>
</template>
