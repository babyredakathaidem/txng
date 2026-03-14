<script setup>
import { Head, useForm, router, Link } from '@inertiajs/vue3'
import { ref, computed } from 'vue'
import { Card, CardHeader, CardTitle, CardDescription, CardContent, CardFooter } from '@/Components/ui/card/index.js'
import { Button } from '@/Components/ui/button/index.js'
import { Badge } from '@/Components/ui/badge/index.js'
import { Input } from '@/Components/ui/input/index.js'
import { Label } from '@/Components/ui/label/index.js'
import { Textarea } from '@/Components/ui/textarea/index.js'
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/Components/ui/dialog/index.js'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/Components/ui/select/index.js'
import { Separator } from '@/Components/ui/separator/index.js'
import { 
  Plus, 
  GraduationCap, 
  Calendar, 
  Fingerprint, 
  CheckCircle, 
  XCircle, 
  CloudUpload, 
  Pencil, 
  Building2, 
  Globe, 
  ClipboardCheck, 
  Trash2, 
  Info,
  ChevronRight,
  Activity,
  ShieldCheck,
  FileText
} from 'lucide-vue-next'

const props = defineProps({
  certificates: Array,
  standardNames: Array,
})

const showingModal = ref(false)
const isEditing = ref(false)
const editingId = ref(null)

const form = useForm({
  name: '',
  organization: '',
  certificate_no: '',
  scope: '',
  issue_date: '',
  expiry_date: '',
  image: null,
  status: 'active',
  note: '',
})

const openCreateModal = () => {
  form.reset()
  isEditing.value = false
  showingModal.value = true
}
const openEditModal = (cert) => {
  form.name = cert.name
  form.organization = cert.organization
  form.certificate_no = cert.certificate_no
  form.scope = cert.scope
  form.issue_date = cert.issue_date_raw
  form.expiry_date = cert.expiry_date_raw
  form.status = cert.status
  form.note = cert.note
  form.image = null

  editingId.value = cert.id
  isEditing.value = true
  showingModal.value = true
}

const submit = () => {
  if (isEditing.value) {
    form.transform((data) => ({
      ...data,
      _method: 'PUT',
    })).post(route('certificates.update', editingId.value), {
      forceFormData: true,
      onSuccess: () => closeModal(),
    })
  } else {
    form.post(route('certificates.store'), {
      onSuccess: () => closeModal(),
    })
  }
}

const remove = (id) => {
  if (confirm('Xóa chứng chỉ này? Thao tác này không thể hoàn tác.')) {
    router.delete(route('certificates.destroy', id))
  }
}

const closeModal = () => {
  showingModal.value = false
  form.reset()
}

const statusVariant = (status, isExpired) => {
  if (isExpired || status === 'expired') return 'destructive'
  if (status === 'revoked') return 'secondary'
  return 'default'
}

const statusLabel = (status, isExpired) => {
  if (isExpired || status === 'expired') return 'Hết hạn'
  if (status === 'revoked') return 'Bị thu hồi'
  return 'Đang hiệu lực'
}
</script>

<template>
  <Head title="Năng lực & Chứng nhận" />

  <div class="max-w-6xl mx-auto space-y-8 pb-20">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-6">
      <div class="space-y-3">
        <nav class="flex items-center gap-2 text-[10px] font-black uppercase tracking-[0.3em] text-muted-foreground/60 bg-muted/20 w-fit px-4 py-1.5 rounded-full border border-border/50">
          <Link :href="route('enterprise.settings.show')" class="hover:text-primary transition-colors">Settings</Link>
          <ChevronRight class="w-3 h-3 opacity-20" />
          <span class="text-foreground ">Competencies</span>
        </nav>
        <div class="flex items-center gap-5">
           <div class="p-4 rounded-[1.5rem] bg-primary/10 text-primary shadow-inner">
              <GraduationCap class="w-8 h-8" />
           </div>
           <div>
              <h1 class="text-4xl font-black tracking-tighter text-foreground uppercase ">Hồ sơ năng lực</h1>
              <p class="text-muted-foreground font-medium mt-1 uppercase text-[10px] tracking-[0.2em] opacity-60">VietGAP, GlobalGAP, ISO & Quality Standards</p>
           </div>
        </div>
      </div>

      <Button @click="openCreateModal" class="h-14 px-8 font-black uppercase tracking-widest text-xs shadow-2xl shadow-primary/20 rounded-2xl group active:scale-95 transition-all">
        <Plus class="w-5 h-5 mr-3 group-hover:scale-110 transition-transform" />
        Thêm chứng nhận mới
      </Button>
    </div>

    <!-- Stats Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
      <Card v-for="(stat, i) in [
        { label: 'Tổng chứng nhận', val: certificates.length, icon: GraduationCap, color: 'text-primary', bg: 'bg-primary/5', sub: 'Certificates' },
        { label: 'Đang hiệu lực', val: certificates.filter(c => c.status === 'active' && !c.is_expired).length, icon: ShieldCheck, color: 'text-emerald-600', bg: 'bg-emerald-50', sub: 'Active' },
        { label: 'Cần cập nhật', val: certificates.filter(c => c.status !== 'active' || c.is_expired).length, icon: Activity, color: 'text-destructive', bg: 'bg-destructive/5', sub: 'Review' }
      ]" :key="i" class="border-border/50 bg-card/50 backdrop-blur-sm overflow-hidden rounded-[2rem] shadow-sm hover:shadow-xl transition-all duration-500 group">
        <CardContent class="p-8 flex items-center gap-6">
          <div :class="[stat.bg, 'p-4 rounded-2xl shrink-0 group-hover:scale-110 transition-transform duration-500 shadow-inner']">
            <component :is="stat.icon" class="w-8 h-8" :class="stat.color" />
          </div>
          <div>
            <div class="flex items-center gap-2">
              <span class="text-[9px] text-muted-foreground/60 uppercase font-black tracking-[0.2em]">{{ stat.label }}</span>
              <span class="text-[8px] text-primary font-black uppercase tracking-widest bg-primary/5 px-1.5 py-0.5 rounded shadow-sm opacity-0 group-hover:opacity-100 transition-opacity">{{ stat.sub }}</span>
            </div>
            <div class="text-4xl font-black text-foreground mt-1 tracking-tighter group-hover:text-primary transition-colors">{{ stat.val }}</div>
          </div>
        </CardContent>
      </Card>
    </div>

    <!-- Main Content -->
    <div v-if="certificates.length === 0" class="flex flex-col items-center justify-center py-40 border-2 border-dashed rounded-[3rem] bg-card/20 text-muted-foreground border-border/30">
      <div class="p-10 rounded-full bg-muted/20 mb-8 group hover:scale-110 transition-transform duration-500 shadow-inner">
        <GraduationCap class="w-20 h-20 opacity-10 group-hover:opacity-30 transition-opacity" />
      </div>
      <h3 class="text-xl font-black uppercase tracking-[0.2em] text-foreground">Chưa có dữ liệu năng lực</h3>
      <p class="text-[10px] font-bold mt-3 max-w-xs text-center uppercase tracking-widest opacity-40 ">Hãy khai báo các chứng chỉ chất lượng để gia tăng độ tin cậy cho chuỗi cung ứng của bạn.</p>
      <Button variant="outline" @click="openCreateModal" class="mt-10 font-black uppercase tracking-widest text-[11px] px-10 h-12 rounded-2xl hover:scale-105 transition-all">
         Bắt đầu khai báo ngay
      </Button>
    </div>

    <div v-else class="grid grid-cols-1 lg:grid-cols-2 gap-8">
      <Card v-for="cert in certificates" :key="cert.id" class="group border-border/50 bg-card/50 backdrop-blur-sm hover:bg-card hover:border-primary/50 transition-all duration-500 overflow-hidden relative shadow-sm hover:shadow-2xl rounded-[2.5rem]">
        <div class="absolute -right-8 -top-8 w-32 h-32 bg-primary/5 rounded-full blur-2xl group-hover:scale-150 transition-transform duration-1000"></div>
        
        <CardHeader class="p-8 pb-6 relative z-10">
          <div class="flex items-start justify-between gap-6">
            <div class="flex-1 min-w-0">
              <div class="flex items-center gap-4 mb-3">
                <div class="p-3 rounded-2xl bg-primary/5 text-primary group-hover:bg-primary group-hover:text-white transition-all duration-500 shadow-inner">
                  <ClipboardCheck class="w-6 h-6" />
                </div>
                <CardTitle class="text-2xl font-black text-foreground uppercase  tracking-tighter group-hover:text-primary transition-colors truncate">
                  {{ cert.name }}
                </CardTitle>
              </div>
              <div class="text-[10px] font-black text-muted-foreground/60 flex items-center gap-2 uppercase tracking-widest px-1 group-hover:text-foreground/40 transition-colors">
                <Building2 class="w-3.5 h-3.5 opacity-40" />
                {{ cert.organization || 'Đang cập nhật tổ chức cấp' }}
              </div>
            </div>
            <Badge :variant="statusVariant(cert.status, cert.is_expired)" class="font-black uppercase text-[9px] tracking-[0.2em] px-3 py-1 rounded-full shadow-sm">
              {{ statusLabel(cert.status, cert.is_expired) }}
            </Badge>
          </div>
        </CardHeader>

        <CardContent class="p-8 pt-0 space-y-8 relative z-10">
          <div class="grid grid-cols-2 gap-6">
            <div class="p-4 rounded-3xl bg-muted/30 border border-border/50 shadow-inner group/item">
              <Label class="text-[9px] text-muted-foreground/40 uppercase font-black tracking-[0.2em] mb-2 block px-1">Mã số chứng chỉ</Label>
              <p class="text-sm font-mono font-black text-foreground tracking-tight truncate uppercase ">{{ cert.certificate_no || 'N/A' }}</p>
            </div>
            <div class="p-4 rounded-3xl bg-muted/30 border border-border/50 shadow-inner group/item">
              <Label class="text-[9px] text-muted-foreground/40 uppercase font-black tracking-[0.2em] mb-2 block px-1">Thời hạn hiệu lực</Label>
              <p class="text-sm font-black tracking-tight" :class="cert.is_expired ? 'text-destructive' : 'text-primary'">
                {{ cert.expiry_date || 'VÔ THỜI HẠN' }}
              </p>
            </div>
          </div>

          <div v-if="cert.scope" class="p-5 rounded-3xl bg-primary/5 border border-primary/10 group-hover:bg-background/50 transition-colors">
             <Label class="text-[9px] text-primary/60 uppercase font-black tracking-[0.3em] mb-2 block">Phạm vi áp dụng</Label>
             <p class="text-[11px] font-bold text-muted-foreground/80 leading-relaxed  tracking-tight line-clamp-2 uppercase group-hover:text-foreground transition-colors">{{ cert.scope }}</p>
          </div>
        </CardContent>

        <CardFooter class="border-t border-border/30 bg-muted/10 p-6 px-8 flex items-center justify-between relative z-10">
          <div class="flex items-center gap-3">
            <div class="w-2.5 h-2.5 rounded-full bg-primary animate-pulse shadow-[0_0_12px_rgba(var(--primary),0.8)]"></div>
            <span class="text-[9px] text-muted-foreground/60 font-black uppercase tracking-[0.3em] ">
               Áp dụng: <span class="text-primary font-black">{{ cert.batches_count }}</span> lô hàng
            </span>
          </div>
          <div class="flex gap-2">
            <Button variant="ghost" size="icon" @click="openEditModal(cert)" class="h-11 w-11 rounded-2xl hover:bg-primary/10 hover:text-primary shadow-sm hover:shadow-md active:scale-90 transition-all">
              <Pencil class="w-5 h-5" />
            </Button>
            <Button variant="ghost" size="icon" @click="remove(cert.id)" class="h-11 w-11 rounded-2xl hover:bg-destructive/10 hover:text-destructive shadow-sm hover:shadow-md active:scale-90 transition-all">
              <Trash2 class="w-5 h-5" />
            </Button>
            <Button v-if="cert.image_url" variant="ghost" size="sm" as-child class="font-black uppercase text-[9px] tracking-widest ml-3 px-5 h-11 rounded-2xl border-2 border-dashed border-primary/20 hover:bg-primary/5 hover:border-primary/40 transition-all">
               <a :href="cert.image_url" target="_blank">
                  <FileText class="w-4 h-4 mr-2 opacity-40" /> Minh chứng
               </a>
            </Button>
          </div>
        </CardFooter>
      </Card>
    </div>

    <!-- DIALOG FORM -->
    <Dialog :open="showingModal" @update:open="(v) => (!v && closeModal())">
      <DialogContent class="sm:max-w-[650px] max-h-[90vh] overflow-y-auto">
        <DialogHeader>
          <div class="flex items-center gap-4 mb-2">
             <div class="p-3 rounded-2xl bg-primary/10 text-primary shadow-xl">
                <GraduationCap class="w-7 h-7" />
             </div>
             <div>
                <DialogTitle class="text-2xl font-black  tracking-tighter uppercase">
                   {{ isEditing ? 'Cập nhật năng lực' : 'Khai báo năng lực mới' }}
                </DialogTitle>
                <DialogDescription class="font-medium">Cung cấp bằng chứng về tiêu chuẩn chất lượng sản phẩm.</DialogDescription>
             </div>
          </div>
        </DialogHeader>

        <form @submit.prevent="submit" class="space-y-8 py-4">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <!-- Name -->
            <div class="md:col-span-2 space-y-4">
              <div class="flex items-center justify-between">
                <Label class="text-[10px] font-black uppercase tracking-[0.2em] text-muted-foreground ">Tên chứng chỉ / Tiêu chuẩn *</Label>
                <span class="text-[9px] font-bold text-primary/60 uppercase">Gợi ý chuẩn</span>
              </div>

              <div class="flex flex-wrap gap-2">
                <Badge v-for="name in standardNames" :key="name" 
                  @click="form.name = name"
                  variant="outline"
                  class="cursor-pointer font-black text-[9px] uppercase tracking-tighter hover:bg-primary hover:text-primary-foreground transition-all px-2.5 py-1">
                  {{ name }}
                </Badge>
              </div>

              <div class="relative">
                <Fingerprint class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-muted-foreground opacity-40" />
                <Input v-model="form.name" class="h-11 pl-10 font-bold" placeholder="Gõ tên hoặc chọn từ danh sách gợi ý..." required />
              </div>
              <p v-if="form.errors.name" class="text-xs font-bold text-destructive ">{{ form.errors.name }}</p>
            </div>

            <!-- Organization -->
            <div class="space-y-2">
              <Label class="text-[10px] font-black uppercase tracking-widest text-muted-foreground">Tổ chức cấp phát</Label>
              <div class="relative">
                <Building2 class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-muted-foreground opacity-40" />
                <Input v-model="form.organization" class="h-11 pl-10" placeholder="VD: NHO, SGS, QUACERT..." />
              </div>
              <p v-if="form.errors.organization" class="text-xs text-destructive">{{ form.errors.organization }}</p>
            </div>

            <!-- Certificate No -->
            <div class="space-y-2">
              <Label class="text-[10px] font-black uppercase tracking-widest text-muted-foreground">Số hiệu chứng chỉ</Label>
              <div class="relative">
                <Globe class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-muted-foreground opacity-40" />
                <Input v-model="form.certificate_no" class="h-11 pl-10 font-mono" placeholder="Số hiệu văn bản..." />
              </div>
              <p v-if="form.errors.certificate_no" class="text-xs text-destructive">{{ form.errors.certificate_no }}</p>
            </div>

            <!-- Scope -->
            <div class="md:col-span-2 space-y-2">
              <Label class="text-[10px] font-black uppercase tracking-widest text-muted-foreground">Phạm vi chứng nhận</Label>
              <p v-if="form.errors.scope" class="text-xs text-destructive mb-1">{{ form.errors.scope }}</p>
              <Textarea v-model="form.scope" rows="2" class="bg-muted/5 " placeholder="Ví dụ: Áp dụng cho vùng trồng lúa 20ha tại huyện X, tỉnh Y..." />
            </div>

            <!-- Dates -->
            <div class="space-y-2">
              <Label class="text-[10px] font-black uppercase tracking-widest text-muted-foreground  flex items-center gap-2">
                 <Calendar class="w-3.5 h-3.5" /> Ngày cấp
              </Label>
              <Input v-model="form.issue_date" type="date" class="h-11" />
              <p v-if="form.errors.issue_date" class="text-xs text-destructive">{{ form.errors.issue_date }}</p>
            </div>
            <div class="space-y-2">
              <Label class="text-[10px] font-black uppercase tracking-widest text-muted-foreground  flex items-center gap-2">
                 <Calendar class="w-3.5 h-3.5" /> Ngày hết hạn
              </Label>
              <Input v-model="form.expiry_date" type="date" class="h-11" />
              <p v-if="form.errors.expiry_date" class="text-xs text-destructive">{{ form.errors.expiry_date }}</p>
            </div>

            <!-- Image Upload -->
            <div class="md:col-span-2 space-y-2">
              <Label class="text-[10px] font-black uppercase tracking-widest text-muted-foreground">Tài liệu / Hình ảnh minh chứng</Label>
              <div class="relative border-2 border-dashed rounded-2xl p-6 hover:border-primary/40 transition-all bg-muted/10 group cursor-pointer">
                <input type="file" @change="e => form.image = e.target.files[0]" class="absolute inset-0 opacity-0 cursor-pointer z-10" accept="image/*,application/pdf" />
                <div class="flex items-center gap-6">
                  <div class="w-14 h-14 rounded-2xl bg-background flex items-center justify-center text-muted-foreground group-hover:text-primary shadow-inner transition-colors">
                    <CloudUpload class="w-8 h-8" />
                  </div>
                  <div>
                    <div class="text-sm font-bold text-foreground">Chọn tệp tin hoặc Kéo thả vào đây</div>
                    <div class="text-[10px] text-muted-foreground uppercase font-black mt-1">PNG, JPG, PDF • Max 5MB</div>
                  </div>
                </div>
              </div>
              <div v-if="form.image" class="mt-2 text-[10px] font-black text-emerald-600 uppercase flex items-center gap-2 px-2">
                 <CheckCircle class="w-4 h-4" /> Đã chọn: {{ form.image.name }}
              </div>
              <p v-if="form.errors.image" class="text-xs text-destructive">{{ form.errors.image }}</p>
            </div>
          </div>

          <DialogFooter class="border-t pt-6 gap-3">
            <Button variant="ghost" type="button" @click="closeModal" class="font-bold uppercase tracking-widest text-[10px]">Hủy bỏ</Button>
            <Button type="submit" :disabled="form.processing" class="h-12 px-10 font-black uppercase tracking-[0.1em] shadow-xl shadow-primary/20">
              {{ form.processing ? 'ĐANG XỬ LÝ...' : (isEditing ? 'CẬP NHẬT DỮ LIỆU' : 'XÁC NHẬN KHAI BÁO') }}
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  </div>
</template>
