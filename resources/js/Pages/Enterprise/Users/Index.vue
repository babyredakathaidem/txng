<script setup>
import { Head, useForm, router, Link } from '@inertiajs/vue3'
import { ref, computed } from 'vue'
import { Card, CardHeader, CardTitle, CardDescription, CardContent, CardFooter } from '@/Components/ui/card/index.js'
import { Button } from '@/Components/ui/button/index.js'
import { Badge } from '@/Components/ui/badge/index.js'
import { Input } from '@/Components/ui/input/index.js'
import { Label } from '@/Components/ui/label/index.js'
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/Components/ui/dialog/index.js'
import { Separator } from '@/Components/ui/separator/index.js'
import { 
  UserPlus, 
  Mail, 
  ShieldCheck, 
  Pencil, 
  Trash2,
  Fingerprint,
  Key,
  CheckCircle,
  Users,
  ChevronRight,
  ArrowLeft,
  Lock,
  Settings2,
  Activity,
  User
} from 'lucide-vue-next'

const props = defineProps({
  staffList: Array,
  availablePermissions: Object,
})

const showingModal = ref(false)
const isEditing   = ref(false)
const editingId    = ref(null)

const form = useForm({
  name:        '',
  email:       '',
  password:    '',
  permissions: [],
})

const openCreate = () => {
  form.reset()
  form.permissions = []
  isEditing.value = false
  showingModal.value = true
}

const openEdit = (u) => {
  form.name        = u.name
  form.email       = u.email
  form.permissions = Array.isArray(u.permissions) ? u.permissions : []
  form.password    = ''
  editingId.value  = u.id
  isEditing.value  = true
  showingModal.value = true
}

const submit = () => {
  if (isEditing.value) {
    form.put(route('enterprise.users.update', editingId.value), {
      onSuccess: () => closeModal(),
    })
  } else {
    form.post(route('enterprise.users.store'), {
      onSuccess: () => closeModal(),
    })
  }
}

const remove = (u) => {
  if (!confirm(`Xóa tài khoản của "${u.name}"?`)) return
  router.delete(route('enterprise.users.destroy', u.id))
}

const closeModal = () => {
  showingModal.value = false
  form.reset()
}

const togglePerm = (key) => {
  const idx = form.permissions.indexOf(key)
  if (idx >= 0) form.permissions.splice(idx, 1)
  else form.permissions.push(key)
}
</script>

<template>
  <Head title="Quản trị nhân sự — AGU" />

  <div class="max-w-5xl mx-auto space-y-8 pb-20">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-6">
      <div class="space-y-3">
        <nav class="flex items-center gap-2 text-[10px] font-black uppercase tracking-[0.3em] text-muted-foreground/60 bg-muted/20 w-fit px-4 py-1.5 rounded-full border border-border/50">
          <Link :href="route('enterprise.settings.show')" class="hover:text-primary transition-colors">Settings</Link>
          <ChevronRight class="w-3 h-3 opacity-20" />
          <span class="text-foreground ">Human Resources</span>
        </nav>
        <div class="flex items-center gap-5">
           <div class="p-4 rounded-[1.5rem] bg-primary/10 text-primary shadow-inner">
              <Users class="w-8 h-8" />
           </div>
           <div>
              <h1 class="text-4xl font-black tracking-tighter text-foreground uppercase ">Đội ngũ nhân sự</h1>
              <p class="text-muted-foreground font-medium mt-1 uppercase text-[10px] tracking-[0.2em] opacity-60">Phân quyền chi tiết cho nhân viên trong chuỗi cung ứng</p>
           </div>
        </div>
      </div>

      <Button @click="openCreate" class="h-14 px-8 font-black uppercase tracking-widest text-xs shadow-2xl shadow-primary/20 rounded-2xl group active:scale-95 transition-all">
        <UserPlus class="w-5 h-5 mr-3 group-hover:scale-110 transition-transform" />
        Thêm nhân viên mới
      </Button>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
      
      <!-- Cột Trái: Stats & Guide -->
      <div class="lg:col-span-4 space-y-6">
        <Card class="border-border/50 bg-card/50 backdrop-blur-sm overflow-hidden relative rounded-[2.5rem] group hover:shadow-2xl transition-all duration-500 shadow-sm">
          <div class="absolute -right-8 -bottom-8 opacity-5 text-primary rotate-12 group-hover:scale-110 group-hover:-rotate-12 transition-all duration-1000">
            <Users class="w-48 h-48" />
          </div>
          <CardContent class="p-8 space-y-6 relative z-10">
            <div class="p-4 w-fit bg-primary/10 rounded-2xl text-primary shadow-inner">
              <Activity class="w-8 h-8" />
            </div>
            <div>
              <h3 class="text-sm font-black text-muted-foreground/60 uppercase tracking-[0.2em]">Tổng số nhân sự</h3>
              <div class="flex items-baseline gap-3 mt-1">
                <span class="text-5xl font-black text-primary tracking-tighter ">{{ staffList.length }}</span>
                <span class="text-[10px] uppercase font-black text-muted-foreground/40 tracking-[0.3em]">User(s)</span>
              </div>
            </div>
            <p class="text-[11px] font-bold text-muted-foreground/60 leading-relaxed  uppercase tracking-tighter">Các tài khoản nhân viên được phép truy cập và thực hiện các nghiệp vụ ghi nhận dữ liệu theo phân quyền.</p>
          </CardContent>
        </Card>

        <Card class="bg-muted/20 border-dashed border-2 rounded-[2rem] border-border/50 shadow-inner">
          <CardHeader class="pb-4 border-b border-border/30">
             <div class="flex items-center gap-3 text-[9px] text-muted-foreground/60 font-black uppercase tracking-[0.3em]">
                <ShieldCheck class="w-4 h-4 text-primary opacity-40" /> Bảo mật hệ thống
             </div>
          </CardHeader>
          <CardContent class="pt-6">
             <p class="text-[11px] text-muted-foreground/60 leading-relaxed  font-medium">
                "Mỗi tài khoản nhân viên cần được phân bổ quyền hạn tối thiểu phù hợp với vai trò thực tế để đảm bảo tính toàn vẹn của dữ liệu truy xuất nguồn gốc (TCVN 12850:2019)."
             </p>
          </CardContent>
        </Card>
      </div>

      <!-- Cột Phải: Danh sách -->
      <div class="lg:col-span-8 space-y-4">
        <div v-if="staffList.length === 0" class="flex flex-col items-center justify-center py-40 border-2 border-dashed rounded-[3rem] bg-card/20 text-muted-foreground border-border/30">
           <div class="p-8 rounded-full bg-muted/20 mb-8 animate-pulse shadow-inner">
             <UserPlus class="w-16 h-16 opacity-10" />
           </div>
           <p class="text-[10px] font-black uppercase tracking-[0.3em] opacity-40">Chưa có tài khoản nhân viên</p>
           <Button variant="link" @click="openCreate" class="mt-4 text-primary font-black uppercase text-[11px] tracking-widest hover:scale-105 transition-all">Tạo ngay tài khoản đầu tiên →</Button>
        </div>

        <div v-else class="grid grid-cols-1 gap-4">
          <Card v-for="u in staffList" :key="u.id" class="group border-border/50 bg-card/50 backdrop-blur-sm hover:bg-card hover:border-primary/50 transition-all duration-500 overflow-hidden shadow-sm hover:shadow-2xl rounded-[2rem]">
            <CardContent class="p-6 px-8">
              <div class="flex items-center gap-8">
                <div class="w-20 h-20 rounded-[1.5rem] bg-primary/5 border-2 border-primary/10 flex items-center justify-center text-primary shrink-0 shadow-inner group-hover:scale-105 group-hover:bg-primary group-hover:text-white transition-all duration-500">
                  <span class="text-3xl font-black uppercase ">{{ u.name[0] }}</span>
                </div>
                
                <div class="flex-1 min-w-0">
                  <div class="flex items-center gap-3">
                     <h3 class="text-xl font-black text-foreground uppercase  tracking-tighter group-hover:text-primary transition-colors truncate">{{ u.name }}</h3>
                     <Badge v-if="u.role === 'enterprise_admin'" variant="secondary" class="h-5 text-[9px] font-black uppercase tracking-widest bg-background border border-primary/20 text-primary">Admin</Badge>
                  </div>
                  <div class="flex items-center gap-2 text-[10px] text-muted-foreground font-black uppercase tracking-widest mt-1.5 opacity-40 group-hover:opacity-100 transition-all">
                    <Mail class="w-3 h-3" />
                    <span class="truncate">{{ u.email }}</span>
                  </div>
                  
                  <div class="flex flex-wrap gap-2 mt-5">
                    <Badge v-for="p in (u.permissions || [])" :key="p" variant="outline" 
                      class="text-[8px] h-5 px-3 bg-muted/30 text-muted-foreground font-black uppercase tracking-widest border-border/50 shadow-sm hover:bg-primary/10 hover:text-primary transition-colors">
                      {{ p.split('.').pop() }}
                    </Badge>
                    <span v-if="!(u.permissions || []).length" class="text-[9px] text-muted-foreground/30  font-bold uppercase tracking-widest">Chưa có quyền hạn...</span>
                  </div>
                </div>
                
                <div class="flex items-center gap-2 opacity-0 group-hover:opacity-100 translate-x-4 group-hover:translate-x-0 transition-all duration-500">
                  <Button variant="ghost" size="icon" @click="openEdit(u)" class="h-11 w-11 rounded-2xl hover:bg-primary/10 hover:text-primary shadow-sm hover:shadow-md active:scale-90">
                    <Pencil class="w-5 h-5" />
                  </Button>
                  <Button variant="ghost" size="icon" @click="remove(u)" class="h-11 w-11 rounded-2xl hover:bg-destructive/10 hover:text-destructive shadow-sm hover:shadow-md active:scale-90">
                    <Trash2 class="w-5 h-5" />
                  </Button>
                </div>
              </div>
            </CardContent>
          </Card>
        </div>
      </div>
    </div>

    <!-- DIALOG FORM -->
    <Dialog :open="showingModal" @update:open="(v) => (!v && closeModal())">
      <DialogContent class="sm:max-w-[700px] max-h-[90vh] overflow-y-auto rounded-[3rem] p-0 border-border/50 bg-card/95 backdrop-blur-2xl">
        <div class="p-10 space-y-10">
          <DialogHeader>
            <div class="flex items-center gap-6 mb-4">
               <div class="p-5 rounded-[2rem] bg-primary text-white shadow-2xl shadow-primary/40 group">
                  <User class="w-8 h-8 group-hover:scale-110 transition-transform duration-500" />
               </div>
               <div>
                  <DialogTitle class="text-3xl font-black  tracking-tighter uppercase text-foreground">
                     {{ isEditing ? 'Cập nhật tài khoản' : 'Thêm nhân viên mới' }}
                  </DialogTitle>
                  <DialogDescription class="font-bold text-[10px] uppercase tracking-[0.3em] text-muted-foreground/60 mt-1">Khai báo thông tin định danh hệ thống</DialogDescription>
               </div>
            </div>
          </DialogHeader>

          <form @submit.prevent="submit" class="space-y-10">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
              <!-- Name -->
              <div class="space-y-3">
                <Label class="text-[10px] font-black uppercase tracking-[0.3em] text-primary/60 px-1">Họ và tên nhân viên *</Label>
                <div class="relative group">
                  <Fingerprint class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-muted-foreground/30 group-focus-within:text-primary transition-colors" />
                  <Input v-model="form.name" class="h-14 pl-12 font-black tracking-tight rounded-2xl bg-muted/30 border-border/50 focus:bg-background focus:ring-primary/20 transition-all text-lg" placeholder="NGUYỄN VĂN A" required />
                </div>
                <p v-if="form.errors.name" class="text-xs font-black text-destructive uppercase tracking-widest  px-2">{{ form.errors.name }}</p>
              </div>

              <!-- Email -->
              <div class="space-y-3">
                <Label class="text-[10px] font-black uppercase tracking-[0.3em] text-primary/60 px-1">Địa chỉ Email *</Label>
                <div class="relative group">
                  <Mail class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-muted-foreground/30 group-focus-within:text-primary transition-colors" />
                  <Input v-model="form.email" type="email" class="h-14 pl-12 font-bold rounded-2xl bg-muted/30 border-border/50 focus:bg-background focus:ring-primary/20" placeholder="staff@example.com" :disabled="isEditing" required />
                </div>
                <p v-if="form.errors.email" class="text-xs font-black text-destructive uppercase tracking-widest  px-2">{{ form.errors.email }}</p>
              </div>

              <!-- Password -->
              <div v-if="!isEditing" class="md:col-span-2 space-y-3">
                <Label class="text-[10px] font-black uppercase tracking-[0.3em] text-primary/60 px-1">Mật khẩu khởi tạo *</Label>
                <div class="relative group">
                  <Key class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-muted-foreground/30 group-focus-within:text-primary transition-colors" />
                  <Input v-model="form.password" type="password" class="h-14 pl-12 font-mono text-xl tracking-[0.5em] rounded-2xl bg-muted/30 border-border/50 focus:bg-background focus:ring-primary/20" placeholder="••••••••" required />
                </div>
                <p v-if="form.errors.password" class="text-xs font-black text-destructive uppercase tracking-widest  px-2">{{ form.errors.password }}</p>
              </div>

              <!-- Permissions Grid -->
              <div class="md:col-span-2 space-y-5">
                <div class="flex items-center gap-3 px-1">
                   <Settings2 class="w-4 h-4 text-primary" />
                   <Label class="text-[10px] font-black uppercase tracking-[0.3em] text-foreground">Phân quyền nghiệp vụ</Label>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 p-8 rounded-[2.5rem] bg-muted/30 border border-dashed border-border/50 shadow-inner">
                  <button 
                    v-for="(label, key) in availablePermissions" 
                    :key="key"
                    type="button"
                    @click="togglePerm(key)"
                    class="flex items-center gap-4 p-5 rounded-2xl border transition-all duration-300 text-left group"
                    :class="form.permissions.includes(key) ? 'bg-primary/5 border-primary shadow-lg ring-4 ring-primary/5' : 'bg-background/50 border-border/50 hover:border-primary/40 hover:bg-background shadow-sm'"
                  >
                    <div class="w-8 h-8 rounded-xl border-2 flex items-center justify-center transition-all duration-500 shadow-inner"
                      :class="form.permissions.includes(key) ? 'bg-primary border-primary text-primary-foreground rotate-12 scale-110' : 'bg-muted/50 border-border group-hover:border-primary/40'">
                      <CheckCircle v-if="form.permissions.includes(key)" class="w-5 h-5" />
                    </div>
                    <span class="text-[11px] font-black transition-colors uppercase tracking-widest leading-none" :class="form.permissions.includes(key) ? 'text-primary' : 'text-muted-foreground/60 group-hover:text-foreground'">
                      {{ label }}
                    </span>
                  </button>
                </div>
                <p v-if="form.errors.permissions" class="text-xs font-black text-destructive uppercase tracking-widest  mt-2 px-4">{{ form.errors.permissions }}</p>
              </div>
            </div>

            <DialogFooter class="pt-10 gap-4">
              <Button variant="ghost" type="button" @click="closeModal" class="h-14 px-8 font-black uppercase tracking-[0.2em] text-[10px] rounded-2xl hover:bg-muted transition-all">Hủy bỏ</Button>
              <Button type="submit" :disabled="form.processing" class="h-14 px-12 font-black uppercase tracking-[0.3em] text-xs shadow-2xl shadow-primary/40 rounded-2xl active:scale-95 transition-all group overflow-hidden relative">
                <div class="absolute inset-0 bg-white/20 translate-y-14 group-hover:translate-y-0 transition-transform duration-500 opacity-20"></div>
                <Lock class="w-4 h-4 mr-3 relative z-10" />
                <span class="relative z-10">{{ form.processing ? 'ĐANG XỬ LÝ...' : (isEditing ? 'LƯU THAY ĐỔI' : 'TẠO TÀI KHOẢN') }}</span>
              </Button>
            </DialogFooter>
          </form>
        </div>
      </DialogContent>
    </Dialog>
  </div>
</template>
