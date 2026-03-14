<script setup>
import { Head, Link } from '@inertiajs/vue3'
import { Card, CardHeader, CardTitle, CardDescription, CardContent, CardFooter } from '@/Components/ui/card/index.js'
import { Button } from '@/Components/ui/button/index.js'
import { Badge } from '@/Components/ui/badge/index.js'
import { Label } from '@/Components/ui/label/index.js'
import { Separator } from '@/Components/ui/separator/index.js'
import { 
  CheckCircle2, 
  Clock, 
  MapPin, 
  User, 
  ExternalLink, 
  Archive,
  Info,
  Globe,
  ShieldCheck,
  Paperclip
} from 'lucide-vue-next'

const props = defineProps({
  event: { type: Object, required: true },
})

const statusVariant = (s) => s === 'published' ? 'default' : 'secondary'
const statusLabel = (s) => s === 'published' ? 'Đã xác thực & Lưu bất biến' : 'Bản nháp / Chưa công bố'
</script>

<template>
  <Head :title="`Bước: ${event.cte_code ?? 'Sự kiện truy xuất'}`" />

  <div class="min-h-screen bg-muted/10 font-sans pb-20">
    <!-- Header Branding -->
    <header class="bg-card border-b border-border shadow-sm px-6 py-4 flex items-center justify-between sticky top-0 z-50 backdrop-blur-md bg-card/80">
      <div class="flex items-center gap-3">
        <div class="w-10 h-10 rounded-2xl bg-primary flex items-center justify-center shadow-lg shadow-primary/20">
          <CheckCircle2 class="w-6 h-6 text-primary-foreground" />
        </div>
        <div class="flex flex-col">
           <span class="font-black text-foreground text-sm uppercase tracking-widest ">AGU <span class="text-primary font-medium lowercase not-">Trace</span></span>
           <span class="text-[8px] font-black text-muted-foreground uppercase tracking-[0.2em]">Product Lifecycle Step</span>
        </div>
      </div>
      <Badge variant="outline" class="text-[9px] font-black uppercase tracking-widest border-primary/20 text-primary">Verify Hub</Badge>
    </header>

    <div class="max-w-lg mx-auto px-4 py-10 space-y-8">

      <!-- Status Indicator -->
      <div class="flex justify-center animate-in fade-in zoom-in duration-500">
        <Badge :variant="statusVariant(event.status)" class="h-10 px-6 rounded-full font-black uppercase tracking-widest text-[10px] shadow-xl shadow-primary/5 border-2 border-background">
           <ShieldCheck v-if="event.status === 'published'" class="w-4 h-4 mr-2" />
           {{ statusLabel(event.status) }}
        </Badge>
      </div>

      <!-- Main Step Detail Card -->
      <Card class="rounded-[2.5rem] border-border/50 shadow-2xl shadow-primary/5 overflow-hidden relative">
        <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-primary/40 via-primary to-primary/40"></div>
        
        <CardHeader class="text-center pb-6 border-b bg-muted/5">
          <div class="space-y-1">
            <Label class="text-[10px] font-black uppercase tracking-[0.2em] text-muted-foreground ">Công đoạn thực hiện (CTE)</Label>
            <CardTitle class="text-3xl font-black text-foreground uppercase  tracking-tighter">{{ event.cte_code ?? 'Sự kiện truy xuất' }}</CardTitle>
          </div>
        </CardHeader>

        <CardContent class="p-0">
          <div class="divide-y divide-border/50">

            <!-- Batch context -->
            <div v-if="event.batch" class="flex items-start gap-4 p-6 hover:bg-muted/30 transition-colors">
              <div class="p-2 rounded-xl bg-primary/10 text-primary">
                 <Archive class="w-5 h-5 stroke-[2px]" />
              </div>
              <div class="space-y-1">
                <Label class="text-[9px] font-black uppercase text-muted-foreground tracking-widest">Lô hàng liên kết</Label>
                <p class="font-mono text-base font-black text-primary ">{{ event.batch.code }}</p>
                <div class="text-xs font-bold text-foreground opacity-80">{{ event.batch.product_name }}</div>
                <div class="text-[10px] font-medium text-muted-foreground  mt-1">{{ event.batch.enterprise?.name }}</div>
              </div>
            </div>

            <!-- Time context -->
            <div class="flex items-start gap-4 p-6 hover:bg-muted/30 transition-colors">
              <div class="p-2 rounded-xl bg-muted text-muted-foreground">
                 <Clock class="w-5 h-5" />
              </div>
              <div class="space-y-1">
                <Label class="text-[9px] font-black uppercase text-muted-foreground tracking-widest">Thời gian ghi nhận</Label>
                <p class="text-sm font-black text-foreground">{{ event.event_time || '—' }}</p>
              </div>
            </div>

            <!-- Who -->
            <div v-if="event.who_name" class="flex items-start gap-4 p-6 hover:bg-muted/30 transition-colors">
              <div class="p-2 rounded-xl bg-muted text-muted-foreground">
                 <User class="w-5 h-5" />
              </div>
              <div class="space-y-1">
                <Label class="text-[9px] font-black uppercase text-muted-foreground tracking-widest">Người thực hiện</Label>
                <p class="text-sm font-black text-foreground">{{ event.who_name }}</p>
              </div>
            </div>

            <!-- Where -->
            <div v-if="event.where_address" class="flex items-start gap-4 p-6 hover:bg-muted/30 transition-colors">
              <div class="p-2 rounded-xl bg-muted text-muted-foreground">
                 <MapPin class="w-5 h-5" />
              </div>
              <div class="space-y-1">
                <Label class="text-[9px] font-black uppercase text-muted-foreground tracking-widest">Địa điểm phát sinh</Label>
                <p class="text-sm font-bold text-foreground ">{{ event.where_address }}</p>
              </div>
            </div>

            <!-- Detailed KDE Data -->
            <div v-if="event.kde_data && Object.keys(event.kde_data).length" class="p-6 bg-muted/20">
              <Label class="text-[9px] font-black uppercase text-muted-foreground tracking-widest mb-4 block">Dữ liệu KDE (What)</Label>
              <div class="grid grid-cols-1 gap-2">
                <div v-for="(val, key) in event.kde_data" :key="key"
                  class="flex justify-between items-center p-3 rounded-xl bg-card border border-border/50 shadow-sm">
                  <span class="text-[10px] font-black text-muted-foreground uppercase tracking-tight">{{ key.replace(/_/g, ' ') }}</span>
                  <span class="text-xs font-black text-foreground ">{{ typeof val === 'object' ? JSON.stringify(val) : val }}</span>
                </div>
              </div>
            </div>

            <!-- Note -->
            <div v-if="event.note" class="p-6">
              <Label class="text-[9px] font-black uppercase text-muted-foreground tracking-widest mb-2 block">Ghi chú & Quy chuẩn</Label>
              <p class="text-sm text-muted-foreground leading-relaxed ">{{ event.note }}</p>
            </div>
          </div>
        </CardContent>
      </Card>

      <!-- Attachments Section -->
      <section v-if="event.attachments?.length" class="space-y-4">
        <div class="flex items-center gap-2 px-2">
           <Paperclip class="w-4 h-4 text-primary" />
           <h3 class="text-[10px] font-black uppercase tracking-widest text-foreground">Tài liệu đính kèm</h3>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
          <Button v-for="att in event.attachments" :key="att.cid" variant="outline" as-child class="h-auto p-4 rounded-2xl border-dashed hover:border-primary/40 hover:bg-primary/5 transition-all group">
            <a :href="att.url" target="_blank" class="flex flex-col items-start gap-1 w-full overflow-hidden">
              <div class="text-[10px] font-black text-foreground uppercase truncate w-full">{{ att.name || 'Minh chứng' }}</div>
              <div class="text-[8px] font-bold text-muted-foreground opacity-60 uppercase">{{ att.mime_type }}</div>
            </a>
          </Button>
        </div>
      </section>

      <!-- Immutable Verification -->
      <Card v-if="event.ipfs_cid" class="rounded-3xl border-emerald-500/20 bg-emerald-50/[0.03] shadow-lg shadow-emerald-500/5 relative overflow-hidden">
        <div class="absolute right-0 top-0 w-24 h-24 bg-emerald-500/5 blur-2xl rounded-full"></div>
        <CardContent class="p-6 space-y-4">
          <div class="flex items-center gap-3">
            <div class="p-2 rounded-xl bg-emerald-100 text-emerald-600 shadow-inner">
               <Globe class="w-6 h-6 stroke-[2.5px]" />
            </div>
            <div>
               <h4 class="text-sm font-black text-emerald-700 uppercase  tracking-tight">Xác thực IPFS Blockchain</h4>
               <p class="text-[9px] font-bold text-emerald-600/60 uppercase tracking-widest">Dữ liệu bất biến — Không thể giả mạo</p>
            </div>
          </div>
          
          <div class="space-y-2">
            <Label class="text-[8px] font-black text-muted-foreground uppercase tracking-widest px-1">Content Identifier (CID)</Label>
            <div class="font-mono text-[10px] text-emerald-700 bg-emerald-50/50 border border-emerald-100 p-3 rounded-xl break-all leading-relaxed shadow-inner ">
               {{ event.ipfs_cid }}
            </div>
          </div>

          <Button v-if="event.ipfs_url" variant="ghost" size="sm" as-child class="w-full h-10 font-black uppercase text-[10px] tracking-widest text-emerald-600 hover:bg-emerald-50">
            <a :href="event.ipfs_url" target="_blank">
              <ExternalLink class="w-4 h-4 mr-2" />
              Kiểm tra dữ liệu gốc trên Gateway
            </a>
          </Button>
        </CardContent>
      </Card>

      <div v-else class="rounded-3xl border-2 border-dashed border-amber-200 bg-amber-50/20 p-6 flex gap-4 animate-in slide-in-from-bottom-2">
        <Info class="w-6 h-6 text-amber-600 shrink-0" />
        <p class="text-[11px] font-medium text-amber-700 leading-relaxed ">
           Dữ liệu công đoạn này hiện đang ở chế độ nội bộ (Bản nháp). Hệ thống sẽ tự động xác thực và lưu trữ bất biến sau khi Doanh nghiệp hoàn tất lệnh công bố (Publish).
        </p>
      </div>

      <!-- Footer Meta -->
      <footer class="text-center space-y-4 pt-10 opacity-40 grayscale hover:opacity-100 hover:grayscale-0 transition-all duration-700">
        <div class="flex justify-center gap-6">
           <div class="text-[9px] font-black uppercase tracking-[0.3em]">TCVN 12850:2019</div>
           <div class="text-[9px] font-black uppercase tracking-[0.3em]">TT02/2024/TT-BKHCN</div>
        </div>
        <p class="text-[10px] font-bold text-muted-foreground ">AGU Traceability — Minh bạch hóa chuỗi giá trị nông sản Việt</p>
      </footer>
    </div>
  </div>
</template>
