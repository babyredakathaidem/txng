<script setup>
import { Head, useForm, usePage, Link, router } from '@inertiajs/vue3'
import { computed } from 'vue'
import { Card, CardHeader, CardTitle, CardDescription, CardContent, CardFooter } from '@/Components/ui/card/index.js'
import { Button } from '@/Components/ui/button/index.js'
import { Input } from '@/Components/ui/input/index.js'
import { Label } from '@/Components/ui/label/index.js'
import { Badge } from '@/Components/ui/badge/index.js'
import { Separator } from '@/Components/ui/separator/index.js'
import { 
  GraduationCap, 
  ChevronRight, 
  Info,
  Building2,
  Phone,
  Mail,
  MapPin,
  User,
  FileText,
  CheckCircle,
  ShieldCheck,
  Fingerprint,
  ExternalLink
} from 'lucide-vue-next'

const props = defineProps({
  enterprise: { type: Object, default: () => ({}) },
})

const flash = usePage().props.flash || {}

const form = useForm({
  name:                          props.enterprise.name                          ?? '',
  phone:                         props.enterprise.phone                         ?? '',
  email:                         props.enterprise.email                         ?? '',
  province:                      props.enterprise.province                      ?? '',
  district:                      props.enterprise.district                      ?? '',
  address_detail:                props.enterprise.address_detail                ?? '',
  representative_name:           props.enterprise.representative_name           ?? '',
  representative_id:             props.enterprise.representative_id             ?? '',
  business_cert_no:              props.enterprise.business_cert_no              ?? '',
  business_cert_issued_place:    props.enterprise.business_cert_issued_place    ?? '',
  business_license_no:           props.enterprise.business_license_no           ?? '',
  business_license_issued_place: props.enterprise.business_license_issued_place ?? '',
})

const submit = () => {
  form.put(route('enterprise.settings.show.update'))
}

const statusLabel = computed(() => {
  const map = { approved: 'Đã duyệt', pending: 'Chờ duyệt', rejected: 'Bị từ chối' }
  return map[props.enterprise.status] ?? props.enterprise.status
})

const badgeVariant = computed(() => {
  if (props.enterprise.status === 'approved') return 'default'
  if (props.enterprise.status === 'rejected') return 'destructive'
  return 'secondary'
})
</script>

<template>
  <Head title="Cài đặt tổ chức" />

  <div class="max-w-5xl mx-auto space-y-8 pb-20">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-6">
      <div>
        <p class="text-[10px] font-black uppercase tracking-[0.3em] text-primary mb-1">Enterprise Workspace</p>
        <h1 class="text-4xl font-black tracking-tighter text-foreground uppercase ">Hồ sơ doanh nghiệp</h1>
        <p class="text-muted-foreground font-medium text-sm mt-1 opacity-70">Quản lý định danh pháp lý và thông tin liên lạc chính thức.</p>
      </div>
      <div class="flex items-center gap-4">
        <div v-if="flash.success" class="flex items-center gap-2 text-emerald-600 text-[10px] font-black uppercase tracking-widest animate-in fade-in slide-in-from-right-4 duration-500 bg-emerald-50 px-4 py-2 rounded-full border border-emerald-100 shadow-sm">
           <CheckCircle class="w-3.5 h-3.5" /> {{ flash.success }}
        </div>
        <Badge :variant="badgeVariant" class="h-9 px-5 font-black uppercase tracking-[0.2em] text-[10px] rounded-full shadow-lg shadow-primary/5">
          {{ statusLabel }}
        </Badge>
      </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
      
      <!-- Cột Trái: Sidebar Info -->
      <div class="lg:col-span-4 space-y-6">
        
        <!-- Năng lực -->
        <Card 
          @click="router.visit(route('certificates.index'))"
          class="border-border/50 bg-card/50 backdrop-blur-sm hover:bg-card hover:border-primary/50 transition-all duration-500 cursor-pointer group relative overflow-hidden shadow-sm hover:shadow-2xl rounded-[2.5rem]"
        >
          <div class="absolute -right-8 -bottom-8 opacity-5 group-hover:opacity-10 transition-all duration-1000 text-primary rotate-12 group-hover:scale-110">
            <GraduationCap class="w-48 h-48" />
          </div>
          <CardContent class="p-8 flex flex-col gap-6 relative z-10">
            <div class="p-4 w-fit bg-primary/10 rounded-2xl text-primary shadow-inner group-hover:bg-primary group-hover:text-white transition-all duration-500">
              <GraduationCap class="w-8 h-8" />
            </div>
            <div>
              <h3 class="text-xl font-black text-foreground group-hover:text-primary transition-colors uppercase  tracking-tighter leading-none">Hồ sơ năng lực</h3>
              <p class="text-[11px] font-bold text-muted-foreground/60 mt-2 leading-relaxed uppercase tracking-tighter">VietGAP, GlobalGAP, ISO... Quản lý các chứng nhận chất lượng.</p>
            </div>
            <div class="flex items-center gap-2 text-[9px] font-black text-primary uppercase tracking-[0.3em] mt-2 group-hover:translate-x-2 transition-transform">
              Quản lý danh mục <ChevronRight class="w-3 h-3" />
            </div>
          </CardContent>
        </Card>

        <!-- Fixed Data -->
        <Card class="bg-muted/20 border-dashed border-2 rounded-[2rem] border-border/50 shadow-inner overflow-hidden">
          <CardHeader class="pb-4 border-b border-border/30 bg-muted/30">
             <div class="flex items-center gap-3 text-[9px] text-muted-foreground/60 font-black uppercase tracking-[0.3em]">
                <ShieldCheck class="w-4 h-4 text-primary opacity-40" /> Định danh hệ thống
             </div>
          </CardHeader>
          <CardContent class="p-8 space-y-6">
            <div class="space-y-2">
              <Label class="text-[9px] font-black uppercase text-muted-foreground/40 tracking-[0.2em] px-1">Mã định danh nội bộ</Label>
              <div class="font-mono text-sm font-black text-primary  bg-background/50 px-4 py-3 rounded-2xl border border-primary/10 shadow-inner tracking-tighter">
                {{ enterprise.code }}
              </div>
            </div>
            <div class="space-y-2">
              <Label class="text-[9px] font-black uppercase text-muted-foreground/40 tracking-[0.2em] px-1">Mã số thuế (MST)</Label>
              <div class="font-mono text-sm font-black text-foreground bg-background/50 px-4 py-3 rounded-2xl border border-border/50 shadow-inner tracking-tighter">
                {{ enterprise.business_code }}
              </div>
            </div>
            <div v-if="enterprise.cert_file_url" class="pt-4">
              <Button variant="outline" size="sm" as-child class="w-full font-black uppercase text-[9px] tracking-widest h-12 border-dashed border-2 rounded-2xl hover:bg-background hover:border-primary/40 transition-all">
                 <a :href="enterprise.cert_file_url" target="_blank">
                    <FileText class="w-4 h-4 mr-2 opacity-40" />
                    Xem bản scan GCN ĐKDN
                 </a>
              </Button>
            </div>
          </CardContent>
        </Card>
      </div>

      <!-- Cột Phải: Form -->
      <div class="lg:col-span-8">
        <form @submit.prevent="submit" class="space-y-8">
          
          <Card class="border-border/50 bg-card/50 backdrop-blur-sm shadow-xl rounded-[2.5rem] overflow-hidden">
            <CardHeader class="border-b bg-muted/30 py-5 px-8">
               <div class="flex items-center gap-4">
                  <div class="p-2.5 rounded-xl bg-background border border-border/50 text-muted-foreground shadow-sm">
                    <Building2 class="w-5 h-5" />
                  </div>
                  <CardTitle class="text-xs font-black uppercase tracking-[0.3em] text-foreground ">Thông tin liên lạc & Địa chỉ</CardTitle>
               </div>
            </CardHeader>
            <CardContent class="p-8 pt-10 space-y-10">
              <div class="space-y-3">
                <Label class="text-[10px] font-black uppercase tracking-[0.2em] text-primary/60 px-1 ">Tên doanh nghiệp chính thức *</Label>
                <div class="relative group">
                  <Fingerprint class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-muted-foreground/20 group-focus-within:text-primary transition-colors" />
                  <Input v-model="form.name" class="h-14 pl-12 font-black tracking-tight rounded-2xl bg-muted/30 border-border/50 focus:bg-background focus:ring-primary/20 text-lg uppercase  shadow-inner" />
                </div>
                <p v-if="form.errors.name" class="text-xs font-black text-destructive mt-1  uppercase tracking-widest px-2">{{ form.errors.name }}</p>
              </div>

              <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <div class="space-y-3">
                  <Label class="text-[10px] font-black uppercase tracking-[0.2em] text-muted-foreground px-1  flex items-center gap-2">
                     <Phone class="w-3.5 h-3.5 opacity-40" /> Số điện thoại *
                  </Label>
                  <Input v-model="form.phone" class="h-14 rounded-2xl bg-muted/30 border-border/50 font-black tracking-widest shadow-inner focus:bg-background" />
                  <p v-if="form.errors.phone" class="text-xs font-black text-destructive mt-1  uppercase tracking-widest px-2">{{ form.errors.phone }}</p>
                </div>
                <div class="space-y-3">
                  <Label class="text-[10px] font-black uppercase tracking-[0.2em] text-muted-foreground px-1  flex items-center gap-2">
                     <Mail class="w-3.5 h-3.5 opacity-40" /> Email hệ thống *
                  </Label>
                  <Input v-model="form.email" type="email" class="h-14 rounded-2xl bg-muted/30 border-border/50 font-bold shadow-inner focus:bg-background" />
                  <p v-if="form.errors.email" class="text-xs font-black text-destructive mt-1  uppercase tracking-widest px-2">{{ form.errors.email }}</p>
                </div>
              </div>

              <Separator border-dashed class="opacity-50" />

              <div class="space-y-8">
                <div class="flex items-center gap-3 px-1">
                  <div class="w-6 h-6 rounded-full bg-primary/10 flex items-center justify-center">
                    <MapPin class="w-3.5 h-3.5 text-primary" />
                  </div>
                  <span class="text-[10px] text-primary font-black uppercase tracking-[0.3em]">Trụ sở chính</span>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                  <div class="space-y-3">
                    <Label class="text-[10px] font-black uppercase tracking-[0.2em] text-muted-foreground/60 px-1">Tỉnh/Thành phố *</Label>
                    <Input v-model="form.province" class="h-14 rounded-2xl bg-muted/30 border-border/50 font-bold shadow-inner focus:bg-background" />
                    <p v-if="form.errors.province" class="text-xs font-black text-destructive mt-1  uppercase tracking-widest px-2">{{ form.errors.province }}</p>
                  </div>
                  <div class="space-y-3">
                    <Label class="text-[10px] font-black uppercase tracking-[0.2em] text-muted-foreground/60 px-1">Quận/Huyện *</Label>
                    <Input v-model="form.district" class="h-14 rounded-2xl bg-muted/30 border-border/50 font-bold shadow-inner focus:bg-background" />
                    <p v-if="form.errors.district" class="text-xs font-black text-destructive mt-1  uppercase tracking-widest px-2">{{ form.errors.district }}</p>
                  </div>
                  <div class="md:col-span-2 space-y-3">
                    <Label class="text-[10px] font-black uppercase tracking-[0.2em] text-muted-foreground/60 px-1">Địa chỉ chi tiết</Label>
                    <Input v-model="form.address_detail" placeholder="SỐ NHÀ, ĐƯỜNG, PHƯỜNG/XÃ..." class="h-14 rounded-2xl bg-muted/30 border-border/50 font-bold shadow-inner focus:bg-background placeholder:opacity-30" />
                    <p v-if="form.errors.address_detail" class="text-xs font-black text-destructive mt-1  uppercase tracking-widest px-2">{{ form.errors.address_detail }}</p>
                  </div>
                </div>
              </div>
            </CardContent>
          </Card>

          <Card class="border-border/50 bg-card/50 backdrop-blur-sm shadow-xl rounded-[2.5rem] overflow-hidden">
            <CardHeader class="border-b bg-muted/30 py-5 px-8">
               <div class="flex items-center gap-4">
                  <div class="p-2.5 rounded-xl bg-background border border-border/50 text-muted-foreground shadow-sm">
                    <User class="w-5 h-5" />
                  </div>
                  <CardTitle class="text-xs font-black uppercase tracking-[0.3em] text-foreground ">Đại diện & Pháp lý</CardTitle>
               </div>
            </CardHeader>
            <CardContent class="p-8 pt-10 space-y-10">
              <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <div class="space-y-3">
                  <Label class="text-[10px] font-black uppercase tracking-[0.2em] text-muted-foreground px-1">Họ tên người đại diện</Label>
                  <Input v-model="form.representative_name" class="h-14 rounded-2xl bg-muted/30 border-border/50 font-black tracking-tight shadow-inner focus:bg-background uppercase" />
                  <p v-if="form.errors.representative_name" class="text-xs font-black text-destructive mt-1  uppercase tracking-widest px-2">{{ form.errors.representative_name }}</p>
                </div>
                <div class="space-y-3">
                  <Label class="text-[10px] font-black uppercase tracking-[0.2em] text-muted-foreground px-1">Số CCCD / Hộ chiếu</Label>
                  <Input v-model="form.representative_id" class="h-14 rounded-2xl bg-muted/30 border-border/50 font-mono font-bold tracking-widest shadow-inner focus:bg-background" />
                  <p v-if="form.errors.representative_id" class="text-xs font-black text-destructive mt-1  uppercase tracking-widest px-2">{{ form.errors.representative_id }}</p>
                </div>
              </div>

              <div class="grid grid-cols-1 md:grid-cols-2 gap-8 pt-2">
                <div class="space-y-3">
                  <Label class="text-[10px] font-black uppercase tracking-[0.2em] text-muted-foreground px-1">Số GCN Đăng ký DN</Label>
                  <Input v-model="form.business_cert_no" class="h-14 rounded-2xl bg-muted/30 border-border/50 font-mono font-bold shadow-inner focus:bg-background" />
                  <p v-if="form.errors.business_cert_no" class="text-xs font-black text-destructive mt-1  uppercase tracking-widest px-2">{{ form.errors.business_cert_no }}</p>
                </div>
                <div class="space-y-3">
                  <Label class="text-[10px] font-black uppercase tracking-[0.2em] text-muted-foreground px-1">Nơi cấp GCN</Label>
                  <Input v-model="form.business_cert_issued_place" class="h-14 rounded-2xl bg-muted/30 border-border/50 font-bold shadow-inner focus:bg-background" />
                  <p v-if="form.errors.business_cert_issued_place" class="text-xs font-black text-destructive mt-1  uppercase tracking-widest px-2">{{ form.errors.business_cert_issued_place }}</p>
                </div>
              </div>
            </CardContent>
            <CardFooter class="border-t bg-primary/5 py-8 px-8 flex items-center justify-between gap-8">
               <div class="flex items-start gap-3 max-w-sm text-[9px] text-primary/60 font-black uppercase tracking-widest  leading-relaxed">
                  <Info class="w-4 h-4 shrink-0 opacity-40 mt-0.5" />
                  Thông tin này sẽ được dùng để xác minh tính pháp lý và in lên các chứng từ truy xuất nguồn gốc chính thức.
               </div>
               <Button
                 type="submit"
                 :disabled="form.processing"
                 class="h-16 px-12 font-black uppercase tracking-[0.3em] text-xs shadow-2xl shadow-primary/30 rounded-2xl group relative overflow-hidden transition-all duration-500 hover:scale-[1.02] hover:shadow-primary/40 active:scale-95 shrink-0"
               >
                 <div class="absolute inset-0 bg-white/20 translate-y-16 group-hover:translate-y-0 transition-transform duration-500 opacity-20"></div>
                 <CheckCircle class="w-4 h-4 mr-3 relative z-10 group-hover:scale-110 transition-transform" />
                 <span class="relative z-10">{{ form.processing ? 'SAVING...' : 'LƯU THAY ĐỔI' }}</span>
               </Button>
            </CardFooter>
          </Card>
        </form>
      </div>
    </div>
  </div>
</template>
