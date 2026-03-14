<script setup>
import { Head, useForm, usePage, Link } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import { Card, CardHeader, CardTitle, CardDescription, CardContent, CardFooter } from '@/Components/ui/card/index.js'
import { Button } from '@/Components/ui/button/index.js'
import { Input } from '@/Components/ui/input/index.js'
import { Label } from '@/Components/ui/label/index.js'
import { Badge } from '@/Components/ui/badge/index.js'
import { Avatar, AvatarFallback } from '@/Components/ui/avatar/index.js'
import { Separator } from '@/Components/ui/separator/index.js'
import { 
  Key, 
  Building2, 
  ShieldCheck,
  User,
  CheckCircle,
  ChevronRight,
  Fingerprint,
  Mail,
  Lock,
  Activity
} from 'lucide-vue-next'

const page       = usePage()
const user       = computed(() => page.props.auth?.user)
const enterprise = computed(() => page.props.auth?.enterprise)
const isStaff    = computed(() => user.value?.role === 'enterprise_staff')

const roleInfo = computed(() => {
  if (user.value?.is_super_admin)              return { label: 'Super Admin',      variant: 'default', color: 'text-yellow-500' }
  if (user.value?.role === 'enterprise_admin') return { label: 'Quản trị viên DN', variant: 'default', color: 'text-primary' }
  if (user.value?.role === 'enterprise_staff') return { label: 'Nhân viên DN',      variant: 'secondary', color: 'text-muted-foreground' }
  return { label: 'Người dùng', variant: 'outline', color: 'text-muted-foreground' }
})

const enterpriseStatus = computed(() => ({
  approved: { label: 'Đã duyệt', variant: 'default' },
  pending:  { label: 'Chờ duyệt', variant: 'secondary' },
  rejected: { label: 'Bị từ chối', variant: 'destructive' },
}[enterprise.value?.status] ?? { label: '—', variant: 'outline' }))

const initials = computed(() => {
  const n = user.value?.name ?? ''
  return n.split(' ').map(w => w[0]).slice(-2).join('').toUpperCase() || 'U'
})

const showPw = ref(false)
const pwForm = useForm({
  current_password:      '',
  password:              '',
  password_confirmation: '',
})
function submitPw() {
  pwForm.put(route('password.update'), {
    onSuccess: () => { pwForm.reset(); showPw.value = false },
  })
}

const PERM_LABELS = {
  'enterprise.products.view':       { label: 'Xem sản phẩm',    icon: '📦' },
  'enterprise.products.manage':     { label: 'Quản lý sản phẩm', icon: '📦' },
  'enterprise.batches.view':        { label: 'Xem lô hàng',      icon: '🗂️' },
  'enterprise.batches.manage':      { label: 'Quản lý lô hàng',  icon: '🗂️' },
  'enterprise.trace_events.view':   { label: 'Xem sự kiện',      icon: '🔍' },
  'enterprise.trace_events.create': { label: 'Tạo sự kiện',      icon: '➕' },
  'enterprise.trace_events.manage': { label: 'Quản lý sự kiện',  icon: '✏️' },
  'enterprise.qrcodes.view':        { label: 'Xem QR Codes',     icon: '📱' },
  'enterprise.qrcodes.manage':      { label: 'Quản lý QR',       icon: '📱' },
}
</script>

<template>
  <Head title="Hồ sơ cá nhân" />

  <div class="max-w-4xl mx-auto space-y-8 pb-20">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-4">
      <div>
        <p class="text-[10px] font-black uppercase tracking-[0.3em] text-primary mb-1">Account Management</p>
        <h1 class="text-4xl font-black tracking-tighter text-foreground">Hồ sơ cá nhân</h1>
        <p class="text-muted-foreground font-medium text-sm mt-1 opacity-70">Quản lý thông tin tài khoản và quyền hạn truy cập hệ thống.</p>
      </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
      
      <!-- Cột Trái -->
      <div class="lg:col-span-5 space-y-6">
        <!-- Profile Card -->
        <Card class="border-border/50 bg-card/50 backdrop-blur-sm overflow-hidden rounded-[2.5rem] shadow-sm hover:shadow-xl transition-all duration-500 group">
          <CardContent class="p-10 space-y-8">
            <div class="flex flex-col items-center text-center space-y-4">
              <Avatar class="h-32 w-32 rounded-[2rem] border-4 border-primary/10 shadow-2xl group-hover:scale-105 transition-transform duration-500">
                <AvatarFallback class="bg-primary/10 text-primary text-5xl font-black ">{{ initials }}</AvatarFallback>
              </Avatar>
              <div>
                <h2 class="text-3xl font-black tracking-tighter text-foreground uppercase  leading-none">{{ user?.name }}</h2>
                <div class="flex items-center justify-center gap-2 mt-3">
                   <Badge :variant="roleInfo.variant" class="font-black uppercase text-[9px] tracking-widest px-3 py-1">{{ roleInfo.label }}</Badge>
                </div>
              </div>
            </div>

            <Separator border-dashed class="opacity-50" />

            <div class="space-y-5">
               <div class="flex items-center gap-4 group/item">
                  <div class="p-2.5 rounded-xl bg-muted/50 text-muted-foreground group-hover/item:text-primary transition-colors">
                     <Mail class="w-4 h-4" />
                  </div>
                  <div class="min-w-0">
                     <p class="text-[8px] font-black uppercase tracking-widest text-muted-foreground/40 mb-0.5">Email Address</p>
                     <p class="text-sm font-bold text-foreground truncate">{{ user?.email }}</p>
                  </div>
               </div>
               <div v-if="enterprise" class="flex items-center gap-4 group/item">
                  <div class="p-2.5 rounded-xl bg-muted/50 text-muted-foreground group-hover/item:text-primary transition-colors">
                     <Building2 class="w-4 h-4" />
                  </div>
                  <div class="min-w-0">
                     <p class="text-[8px] font-black uppercase tracking-widest text-muted-foreground/40 mb-0.5">Associated Entity</p>
                     <p class="text-sm font-bold text-foreground truncate uppercase ">{{ enterprise.name }}</p>
                  </div>
               </div>
            </div>

            <Button variant="outline" class="w-full h-12 rounded-2xl font-black uppercase tracking-widest text-[10px] border-dashed border-2 hover:bg-primary/5 hover:text-primary hover:border-primary/40 transition-all shadow-sm" @click="showPw = !showPw">
               <Lock class="w-4 h-4 mr-2" /> {{ showPw ? 'Đóng form bảo mật' : 'Thay đổi mật khẩu' }}
            </Button>
          </CardContent>
        </Card>

        <!-- Doanh nghiệp Info -->
        <Card v-if="enterprise" class="bg-muted/20 border-dashed border-2 rounded-[2.5rem] border-border/50 shadow-inner overflow-hidden">
          <CardHeader class="pb-4 border-b border-border/30 bg-muted/30 px-8 py-5">
             <div class="flex items-center gap-3">
                <Building2 class="w-4 h-4 text-primary opacity-40" />
                <CardTitle class="text-[10px] font-black uppercase tracking-[0.3em] text-muted-foreground/60">Pháp nhân chủ quản</CardTitle>
             </div>
          </CardHeader>
          <CardContent class="p-8 space-y-6">
            <div class="grid grid-cols-2 gap-6">
               <div class="space-y-1.5">
                  <Label class="text-[8px] font-black uppercase text-muted-foreground/40 tracking-[0.2em]">Mã định danh</Label>
                  <p class="font-mono text-sm font-black text-primary  tracking-tighter">{{ enterprise.code ?? '—' }}</p>
               </div>
               <div class="space-y-1.5">
                  <Label class="text-[8px] font-black uppercase text-muted-foreground/40 tracking-[0.2em]">Trạng thái DN</Label>
                  <div>
                     <Badge :variant="enterpriseStatus.variant" class="text-[8px] font-black uppercase tracking-widest">{{ enterpriseStatus.label }}</Badge>
                  </div>
               </div>
            </div>
            <p class="text-[10px] font-bold text-muted-foreground/60 leading-relaxed  border-t border-border/30 pt-4">Mọi dữ liệu bạn ghi nhận sẽ được liên kết trực tiếp với pháp nhân này trên nền tảng Blockchain.</p>
          </CardContent>
        </Card>
      </div>

      <!-- Cột Phải -->
      <div class="lg:col-span-7 space-y-8">
        
        <!-- Form Đổi MK -->
        <Transition
          enter-active-class="transition-all duration-500 ease-out"
          enter-from-class="opacity-0 -translate-y-4 scale-95"
          enter-to-class="opacity-100 translate-y-0 scale-100"
          leave-active-class="transition-all duration-300 ease-in"
          leave-from-class="opacity-100 scale-100"
          leave-to-class="opacity-0 -translate-y-4 scale-95"
        >
          <Card v-if="showPw" class="border-primary/30 bg-card/50 backdrop-blur-xl shadow-2xl shadow-primary/10 rounded-[2.5rem] overflow-hidden">
            <CardHeader class="border-b bg-primary/5 py-6 px-8">
              <div class="flex items-center gap-4">
                 <div class="p-2.5 rounded-xl bg-primary text-white shadow-lg shadow-primary/20">
                    <ShieldCheck class="w-5 h-5" />
                 </div>
                 <div>
                    <CardTitle class="text-xl font-black  tracking-tighter uppercase">Cập nhật bảo mật</CardTitle>
                    <CardDescription class="text-[10px] font-black uppercase tracking-widest text-primary/40 mt-0.5">Account Security Refresh</CardDescription>
                 </div>
              </div>
            </CardHeader>
            <CardContent class="p-8 space-y-8">
              <div class="space-y-3">
                <Label class="text-[10px] font-black uppercase tracking-[0.2em] px-1  text-muted-foreground">Mật khẩu hiện tại *</Label>
                <div class="relative group">
                   <Key class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-muted-foreground/20 group-focus-within:text-primary transition-colors" />
                   <Input type="password" v-model="pwForm.current_password" class="h-14 pl-12 rounded-2xl bg-muted/30 border-border/50 font-mono tracking-[0.5em] focus:bg-background focus:ring-primary/20 transition-all shadow-inner" placeholder="••••••••" />
                </div>
                <p v-if="pwForm.errors.current_password" class="text-xs font-black text-destructive  uppercase tracking-widest px-2">{{ pwForm.errors.current_password }}</p>
              </div>
              
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-8">
                <div class="space-y-3">
                  <Label class="text-[10px] font-black uppercase tracking-[0.2em] px-1  text-muted-foreground">Mật khẩu mới *</Label>
                  <Input type="password" v-model="pwForm.password" class="h-14 rounded-2xl bg-muted/30 border-border/50 font-mono tracking-[0.5em] focus:bg-background focus:ring-primary/20 shadow-inner" placeholder="••••••••" />
                  <p v-if="pwForm.errors.password" class="text-xs font-black text-destructive  uppercase tracking-widest px-2">{{ pwForm.errors.password }}</p>
                </div>
                <div class="space-y-3">
                  <Label class="text-[10px] font-black uppercase tracking-[0.2em] px-1  text-muted-foreground">Xác nhận mật khẩu *</Label>
                  <Input type="password" v-model="pwForm.password_confirmation" class="h-14 rounded-2xl bg-muted/30 border-border/50 font-mono tracking-[0.5em] focus:bg-background focus:ring-primary/20 shadow-inner" placeholder="••••••••" />
                  <p v-if="pwForm.errors.password_confirmation" class="text-xs font-black text-destructive  uppercase tracking-widest px-2">{{ pwForm.errors.password_confirmation }}</p>
                </div>
              </div>

              <div v-if="pwForm.recentlySuccessful" class="flex items-center gap-3 p-4 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-600 text-[10px] font-black uppercase tracking-[0.2em] animate-in zoom-in shadow-inner">
                <CheckCircle class="w-5 h-5 shadow-[0_0_12px_rgba(16,185,129,0.5)] rounded-full" /> Mật khẩu đã được cập nhật thành công!
              </div>
            </CardContent>
            <CardFooter class="bg-muted/10 p-8 pt-0 flex gap-4">
               <Button variant="ghost" class="h-14 px-8 rounded-2xl font-black uppercase tracking-widest text-[10px] hover:bg-muted" @click="showPw = false; pwForm.reset()">Hủy bỏ</Button>
               <Button class="flex-1 h-14 rounded-2xl font-black uppercase tracking-[0.3em] text-[10px] shadow-2xl shadow-primary/30 active:scale-95 transition-all group overflow-hidden" :disabled="pwForm.processing" @click="submitPw">
                  <div class="absolute inset-0 bg-white/20 translate-y-14 group-hover:translate-y-0 transition-transform duration-500 opacity-20"></div>
                  <span class="relative z-10">{{ pwForm.processing ? 'SAVING...' : 'XÁC NHẬN CẬP NHẬT' }}</span>
               </Button>
            </CardFooter>
          </Card>
        </Transition>

        <!-- Quyền hạn & Phân vai -->
        <Card class="border-border/50 bg-card/50 backdrop-blur-sm overflow-hidden rounded-[2.5rem] shadow-sm relative group">
          <CardHeader class="border-b bg-muted/30 py-6 px-8">
             <div class="flex items-center gap-4">
                <div class="p-2.5 rounded-xl bg-background border border-border/50 text-muted-foreground group-hover:text-primary transition-colors shadow-sm">
                  <Activity class="w-5 h-5" />
                </div>
                <CardTitle class="text-xs font-black uppercase tracking-[0.3em] text-foreground  group-hover:text-primary transition-colors">Quyền hạn tài khoản</CardTitle>
             </div>
          </CardHeader>
          <CardContent class="p-8">
            <div v-if="user?.permissions?.length" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div
                v-for="perm in user.permissions"
                :key="perm"
                class="flex items-center gap-4 p-4 rounded-2xl bg-muted/30 border border-border/50 shadow-inner group/perm hover:bg-background transition-colors duration-300"
              >
                <div class="w-10 h-10 rounded-xl bg-background flex items-center justify-center text-xl shadow-sm group-hover/perm:scale-110 transition-transform">
                   {{ PERM_LABELS[perm]?.icon ?? '•' }}
                </div>
                <div>
                   <p class="text-[10px] font-black uppercase tracking-widest text-foreground">{{ PERM_LABELS[perm]?.label ?? perm }}</p>
                   <p class="text-[8px] font-mono font-bold text-muted-foreground opacity-40 mt-0.5 tracking-tight">{{ perm }}</p>
                </div>
              </div>
            </div>
            <div v-else class="flex flex-col items-center justify-center py-20 border-2 border-dashed border-border/50 rounded-[2rem] bg-muted/10 text-muted-foreground opacity-60">
              <ShieldCheck class="w-16 h-16 opacity-10 mb-4" />
              <p class="text-[10px] font-black uppercase tracking-[0.2em]">Tài khoản chưa được phân quyền</p>
              <p class="text-[9px] mt-2  font-bold uppercase tracking-widest">Liên hệ Quản trị viên doanh nghiệp của bạn.</p>
            </div>
          </CardContent>
          <CardFooter class="border-t bg-muted/5 p-6 px-8">
             <div class="flex items-start gap-3 text-[9px] text-muted-foreground/60  font-bold leading-relaxed uppercase tracking-widest">
                <Info class="w-4 h-4 shrink-0 text-primary opacity-40 mt-0.5" />
                Phân quyền được thiết lập bởi Quản trị viên dựa trên vai trò thực tế của bạn trong chuỗi giá trị nông sản.
             </div>
          </CardFooter>
        </Card>
      </div>
    </div>
  </div>
</template>
