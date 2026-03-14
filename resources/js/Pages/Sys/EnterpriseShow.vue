<script setup>
import { Head, Link, router } from '@inertiajs/vue3'
import { computed } from 'vue'
import { Card, CardHeader, CardTitle, CardDescription, CardContent } from '@/Components/ui/card/index.js'
import { Button } from '@/Components/ui/button/index.js'
import { Badge } from '@/Components/ui/badge/index.js'
import { 
  Building2, 
  ArrowLeft,
  CheckCircle,
  FileText,
  Mail,
  Phone,
  MapPin,
  User,
  Fingerprint,
  Calendar,
  ExternalLink,
  Download
} from 'lucide-vue-next'

const props = defineProps({
  enterprise: Object,
  fileUrl: String,
})

const ext = computed(() => {
  if (!props.fileUrl) return ''
  const clean = String(props.fileUrl).split('?')[0]
  const parts = clean.split('.')
  return (parts.length > 1 ? parts.pop() : '').toLowerCase()
})

const isPdf = computed(() => ext.value === 'pdf')
const isImage = computed(() => ['jpg', 'jpeg', 'png', 'webp'].includes(ext.value))

const back = () => window.history.back()
const approve = () => router.post(route('sys.enterprises.approve', props.enterprise.id))

const badgeVariant = (s) => {
  if (s === 'approved') return 'default'
  if (s === 'pending') return 'secondary'
  if (s === 'rejected' || s === 'blocked') return 'destructive'
  return 'outline'
}

const v = (val) => (val === null || val === undefined || val === '' ? '—' : val)
</script>

<template>
  <Head :title="`Chi tiết DN - ${enterprise?.name || ''}`" />

  <div class="max-w-6xl mx-auto space-y-8 pb-20">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-4">
      <div class="flex items-center gap-4">
        <Button variant="ghost" size="icon" @click="back" class="h-10 w-10 shrink-0 hover:bg-primary/10 hover:text-primary transition-all rounded-full border border-border/50">
           <ArrowLeft class="w-5 h-5" />
        </Button>
        <div>
          <p class="text-[10px] font-black uppercase tracking-[0.3em] text-primary mb-1">System Administration</p>
          <h1 class="text-4xl font-black tracking-tighter text-foreground uppercase ">{{ enterprise.name }}</h1>
          <p class="text-muted-foreground font-medium text-sm mt-1 opacity-70">Chi tiết hồ sơ đăng ký doanh nghiệp tham gia nền tảng.</p>
        </div>
      </div>
      
      <div class="flex items-center gap-3">
        <Badge :variant="badgeVariant(enterprise.status)" class="font-black uppercase tracking-widest px-4 py-2 h-10 shadow-lg shadow-primary/10 text-[10px]">
          {{ enterprise.status }}
        </Badge>
        <Button v-if="enterprise.status !== 'approved'" @click="approve" class="h-10 px-6 font-black uppercase tracking-widest text-[10px] shadow-xl shadow-emerald-500/20 bg-emerald-600 hover:bg-emerald-700 hover:scale-105 transition-all">
          <CheckCircle class="w-4 h-4 mr-2" /> Duyệt nhanh
        </Button>
      </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
      <!-- Info -->
      <div class="lg:col-span-7 space-y-6">
        <Card class="border-border/50 bg-card/50 backdrop-blur-sm overflow-hidden rounded-[2.5rem] shadow-sm">
          <CardHeader class="border-b bg-muted/30 py-5 px-8">
             <div class="flex items-center gap-4">
                <div class="p-2.5 rounded-xl bg-background border border-border/50 text-muted-foreground shadow-sm">
                  <Building2 class="w-5 h-5" />
                </div>
                <div>
                  <CardTitle class="text-xs font-black uppercase tracking-[0.3em] text-foreground ">Thông tin định danh</CardTitle>
                  <CardDescription class="text-[9px] font-black uppercase tracking-widest mt-0.5 opacity-60">
                    ID: <span class="text-foreground">#{{ enterprise.id }}</span> • Mã HT: <span class="font-mono text-primary">{{ enterprise.code || 'PENDING' }}</span>
                  </CardDescription>
                </div>
             </div>
          </CardHeader>
          <CardContent class="p-8 space-y-8">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-8">
              <div class="space-y-1.5">
                <div class="text-[9px] font-black uppercase text-muted-foreground/60 tracking-[0.2em] flex items-center gap-2">
                   <Mail class="w-3.5 h-3.5 opacity-40" /> Email hệ thống
                </div>
                <div class="font-bold text-foreground">{{ v(enterprise.email) }}</div>
              </div>

              <div class="space-y-1.5">
                <div class="text-[9px] font-black uppercase text-muted-foreground/60 tracking-[0.2em] flex items-center gap-2">
                   <Phone class="w-3.5 h-3.5 opacity-40" /> Số điện thoại
                </div>
                <div class="font-bold text-foreground">{{ v(enterprise.phone) }}</div>
              </div>

              <div class="space-y-1.5">
                <div class="text-[9px] font-black uppercase text-muted-foreground/60 tracking-[0.2em] flex items-center gap-2">
                   <FileText class="w-3.5 h-3.5 opacity-40" /> Mã số thuế / MSKD
                </div>
                <div class="font-mono font-black tracking-tight text-lg text-primary">{{ v(enterprise.tax_code || enterprise.business_code) }}</div>
              </div>

              <div class="space-y-1.5">
                <div class="text-[9px] font-black uppercase text-muted-foreground/60 tracking-[0.2em] flex items-center gap-2">
                   <Calendar class="w-3.5 h-3.5 opacity-40" /> Ngày cấp / duyệt
                </div>
                <div class="text-xs font-bold text-foreground space-y-1">
                   <div>Đăng ký: <span class="opacity-70">{{ v(enterprise.created_at) }}</span></div>
                   <div v-if="enterprise.approved_at">Đã duyệt: <span class="text-emerald-600">{{ v(enterprise.approved_at) }}</span></div>
                </div>
              </div>

              <div class="sm:col-span-2 space-y-1.5">
                <div class="text-[9px] font-black uppercase text-muted-foreground/60 tracking-[0.2em] flex items-center gap-2">
                   <MapPin class="w-3.5 h-3.5 opacity-40" /> Địa chỉ trụ sở
                </div>
                <div class="font-bold text-foreground p-4 rounded-2xl bg-muted/30 border border-border/50 shadow-inner">
                  {{ [enterprise.address_detail, enterprise.district, enterprise.province].filter(Boolean).join(', ') || '—' }}
                </div>
              </div>
            </div>
          </CardContent>
        </Card>

        <Card class="border-border/50 bg-card/50 backdrop-blur-sm overflow-hidden rounded-[2.5rem] shadow-sm">
          <CardHeader class="border-b bg-muted/30 py-5 px-8">
             <div class="flex items-center gap-4">
                <div class="p-2.5 rounded-xl bg-background border border-border/50 text-muted-foreground shadow-sm">
                  <User class="w-5 h-5" />
                </div>
                <CardTitle class="text-xs font-black uppercase tracking-[0.3em] text-foreground ">Người đại diện pháp luật</CardTitle>
             </div>
          </CardHeader>
          <CardContent class="p-8 space-y-6">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-8">
              <div class="space-y-1.5">
                <div class="text-[9px] font-black uppercase text-muted-foreground/60 tracking-[0.2em]">Họ và tên</div>
                <div class="font-black text-foreground uppercase tracking-tight text-lg">{{ v(enterprise.representative_name) }}</div>
              </div>

              <div class="space-y-1.5">
                <div class="text-[9px] font-black uppercase text-muted-foreground/60 tracking-[0.2em] flex items-center gap-2">
                   <Fingerprint class="w-3.5 h-3.5 opacity-40" /> CCCD/Hộ chiếu
                </div>
                <div class="font-mono font-bold tracking-widest text-foreground">{{ v(enterprise.representative_id) }}</div>
              </div>
            </div>
          </CardContent>
        </Card>
      </div>

      <!-- Attach -->
      <div class="lg:col-span-5 space-y-6">
        <Card class="border-primary/20 bg-card/50 backdrop-blur-xl shadow-2xl shadow-primary/10 rounded-[2.5rem] overflow-hidden sticky top-8">
          <CardHeader class="border-b bg-primary/5 py-5 px-8 flex flex-row items-center justify-between space-y-0">
             <CardTitle class="text-[10px] font-black uppercase tracking-[0.3em] text-primary ">Tài liệu đính kèm</CardTitle>
             <div v-if="fileUrl" class="flex gap-2">
               <Button variant="ghost" size="icon" as-child class="h-8 w-8 text-primary hover:bg-primary/10">
                 <a :href="fileUrl" target="_blank" title="Mở tab mới"><ExternalLink class="w-4 h-4" /></a>
               </Button>
               <Button variant="ghost" size="icon" as-child class="h-8 w-8 text-primary hover:bg-primary/10">
                 <a :href="fileUrl" download title="Tải xuống"><Download class="w-4 h-4" /></a>
               </Button>
             </div>
          </CardHeader>
          <CardContent class="p-6">
            <div v-if="fileUrl" class="space-y-4">
              <div class="relative group rounded-[2rem] overflow-hidden border-2 border-border/50 bg-muted/20 shadow-inner">
                <iframe
                  v-if="isPdf"
                  :src="fileUrl"
                  class="w-full h-[600px] border-none"
                ></iframe>

                <img
                  v-else-if="isImage"
                  :src="fileUrl"
                  class="w-full h-auto object-contain max-h-[600px]"
                  alt="Tài liệu đính kèm"
                />

                <div v-else class="flex flex-col items-center justify-center p-16 text-center h-[300px]">
                  <FileText class="w-12 h-12 text-muted-foreground opacity-20 mb-4" />
                  <div class="text-[10px] font-black uppercase tracking-widest text-muted-foreground/60 mb-6">
                    Không hỗ trợ preview <span class="text-foreground">.{{ ext }}</span>
                  </div>
                  <Button as-child class="h-12 px-8 rounded-xl font-black uppercase text-[9px] tracking-[0.2em] shadow-lg shadow-primary/20">
                     <a :href="fileUrl" target="_blank">Mở tài liệu</a>
                  </Button>
                </div>
              </div>

              <div class="text-[8px] text-muted-foreground break-all opacity-40 font-mono text-center tracking-widest uppercase">
                URL: {{ fileUrl }}
              </div>
            </div>

            <div v-else class="flex flex-col items-center justify-center py-32 rounded-[2rem] border-2 border-dashed border-border/50 bg-muted/10 text-muted-foreground opacity-60">
              <FileText class="w-12 h-12 mb-4 opacity-20" />
              <p class="text-[10px] font-black uppercase tracking-[0.2em]">Không có file đính kèm</p>
            </div>
          </CardContent>
        </Card>
      </div>
    </div>
  </div>
</template>
