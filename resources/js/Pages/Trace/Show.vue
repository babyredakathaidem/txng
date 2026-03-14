<script setup>
import { computed, ref } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import VerifyIntegrityBtn from '@/Components/VerifyIntegrityBtn.vue'
import { Card, CardHeader, CardTitle, CardDescription, CardContent, CardFooter } from '@/Components/ui/card/index.js'
import { Button } from '@/Components/ui/button/index.js'
import { Badge } from '@/Components/ui/badge/index.js'
import { Alert, AlertDescription, AlertTitle } from '@/Components/ui/alert/index.js'
import { Separator } from '@/Components/ui/separator/index.js'
import { 
  ShieldCheck, 
  MapPin, 
  Building2, 
  Archive, 
  Clock, 
  ArrowRight, 
  CheckCircle,
  AlertTriangle,
  Globe,
  Paperclip,
  Cpu,
  ClipboardCheck,
  RefreshCw,
  ChevronDown,
  ChevronUp,
  X
} from 'lucide-vue-next'

const props = defineProps({
  mode:       String,
  batch:      Object,
  events:     Array,
  place_name: String,
  expires_at: String,
})

const lightboxUrl       = ref(null)
const lineageExpanded   = ref(false)
const certSectionExpanded = ref(true)

const pageTitle = computed(() =>
  props.batch?.product?.name
    ? `${props.batch.product.name} — Truy xuất nguồn gốc`
    : 'Truy xuất nguồn gốc'
)

const CTE_ICON = {
  planting: '🌱', growing: '🌿', spraying: '💦',
  harvesting: '🌾', harvest: '🌾',
  processing: '⚙️', milling: '⚙️', packaging: '📦', split: '✂️', merge: '🔀',
  storage: '🏭', warehousing: '🏭', inspection: '🔍', quality_check: '🔬',
  shipping: '🚚', transport: '🚚', distribution: '🏪',
  transfer_out: '📤', transfer_in: '📥', transfer: '🔄',
  recall: '🚨', custom: '📋',
}

const CTE_CATEGORY = {
  planting: 'observation', growing: 'observation', spraying: 'observation',
  storage: 'observation', inspection: 'observation', quality_check: 'observation', warehousing: 'observation',
  harvesting: 'transformation', harvest: 'transformation', milling: 'transformation',
  processing: 'transformation', packaging: 'transformation', split: 'transformation', merge: 'transformation',
  transfer_out: 'transfer', transfer_in: 'transfer', transfer: 'transfer',
}

function cteIcon(code) { return CTE_ICON[code] ?? '📋' }
function cteCategory(ev) {
  return ev.event_category ?? CTE_CATEGORY[ev.cte_code] ?? 'observation'
}

const CATEGORY_STYLE = {
  observation:    { label: 'QUAN SÁT', variant: 'secondary', text: 'text-sky-600', bg: 'bg-sky-50' },
  transformation: { label: 'BIẾN ĐỔI', variant: 'default', text: 'text-orange-600', bg: 'bg-orange-50' },
  transfer:       { label: 'CHUYỂN GIAO', variant: 'outline', text: 'text-purple-600', bg: 'bg-purple-50' },
}

function catStyle(ev) {
  return CATEGORY_STYLE[cteCategory(ev)] ?? CATEGORY_STYLE.observation
}

const allCertificates = computed(() => {
  const seen = new Set()
  const out  = []
  for (const c of props.batch?.certificates ?? []) {
    const key = c.name + c.organization
    if (!seen.has(key)) { seen.add(key); out.push({ ...c, source: props.batch?.enterprise?.name ?? 'Lô hàng' }) }
  }
  for (const ev of props.events ?? []) {
    for (const c of ev.certificates ?? []) {
      const key = c.name + c.organization + ev.event_code
      if (!seen.has(key)) { seen.add(key); out.push({ ...c, source: ev.enterprise?.name ?? 'Chuỗi' }) }
    }
  }
  return out
})

const isRecalled     = computed(() => props.batch?.status === 'recalled')
const recallDetails  = computed(() => props.batch?.recall_details ?? null)
const isCascadeRecalled = computed(() => props.batch?.is_cascade_recalled === true)
const cascadeParentCode = computed(() => props.batch?.parent_batch_code ?? null)

function shortCid(cid) {
  if (!cid) return ''
  return cid.length > 16 ? cid.slice(0, 8) + '…' + cid.slice(-6) : cid
}

function kdeLabel(key) {
  const MAP = {
    seed_variety: 'Giống', planting_density: 'Mật độ', method: 'Phương pháp',
    fertilizer_type: 'Loại phân', fertilizer_dose: 'Liều lượng phân', pesticide: 'Thuốc BVTV',
    yield_kg_ha: 'Năng suất', moisture: 'Độ ẩm', impurity: 'Tạp chất',
    vehicle: 'Phương tiện', output_ratio: 'Tỉ lệ TM', capacity_ton_h: 'Công suất',
    package_spec: 'Quy cách', package_qty: 'Số lượng', cert_no: 'Số phiếu',
    criteria: 'Chỉ tiêu', result: 'Kết quả', lab_name: 'Đơn vị',
    vehicle_plate: 'Biển số', driver_name: 'Tài xế', route_from: 'Xuất phát', route_to: 'Điểm đến',
    warehouse_name: 'Tên kho', temp_celsius: 'Nhiệt độ', humidity_pct: 'Độ ẩm',
  }
  return MAP[key] ?? key.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase())
}

function cteName(ev) {
  const NAME = {
    planting: 'Gieo hạt / Trồng', growing: 'Bón phân / Chăm sóc', spraying: 'Phun thuốc',
    harvesting: 'Thu hoạch', harvest: 'Thu hoạch', milling: 'Xay xát', processing: 'Chế biến',
    packaging: 'Đóng gói', split: 'Tách lô', merge: 'Gộp lô',
    storage: 'Lưu kho', inspection: 'Kiểm định', quality_check: 'Kiểm tra CL',
    shipping: 'Vận chuyển', transport: 'Vận chuyển',
    transfer_out: 'Chuyển giao đi', transfer_in: 'Nhận hàng', transfer: 'Chuyển giao',
  }
  return NAME[ev.cte_code] ?? ev.cte_code?.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase()) ?? 'Sự kiện'
}
</script>

<template>
  <Head :title="pageTitle" />

  <!-- Lightbox -->
  <div v-if="lightboxUrl" class="fixed inset-0 z-[100] flex items-center justify-center bg-black/90 backdrop-blur-md p-4 animate-in fade-in duration-300" @click.self="lightboxUrl = null">
    <div class="relative max-w-4xl w-full">
      <Button variant="ghost" size="icon" @click="lightboxUrl = null" class="absolute -top-12 right-0 text-white hover:bg-white/10">
        <X class="w-8 h-8" />
      </Button>
      <img :src="lightboxUrl" class="w-full rounded-2xl shadow-2xl object-contain max-h-[85vh]" />
    </div>
  </div>

  <div class="max-w-2xl mx-auto px-4 py-10 space-y-8">

    <!-- ══ RECALL ALERT ══════════════════════════════════════════════ -->
    <div v-if="isRecalled" class="animate-in zoom-in duration-500">
       <Alert variant="destructive" class="border-2 shadow-2xl shadow-destructive/20 rounded-3xl overflow-hidden bg-destructive/5">
          <AlertTriangle class="h-6 w-6" />
          <AlertTitle class="text-xl font-black uppercase  tracking-tighter mb-2">Sản phẩm bị thu hồi</AlertTitle>
          <AlertDescription class="space-y-4">
             <div v-if="isCascadeRecalled && cascadeParentCode" class="p-3 rounded-xl bg-destructive/10 border border-destructive/20 text-xs font-bold ">
                Cảnh báo liên đới: Lô hàng này có thành phần nguyên liệu từ lô cha [{{ cascadeParentCode }}] đã bị thu hồi trước đó.
             </div>
             <p class="font-black text-sm underline decoration-2 underline-offset-4">{{ recallDetails?.reason }}</p>
             <p class="text-xs  leading-relaxed opacity-80">{{ recallDetails?.notice_content }}</p>
             <div class="pt-2">
                <Badge variant="destructive" class="font-black uppercase text-[10px]">Hành động: Ngưng sử dụng ngay</Badge>
             </div>
          </AlertDescription>
       </Alert>
    </div>

    <!-- ══ PRODUCT & BATCH HEADER ════════════════════════════════════ -->
    <Card class="rounded-[2.5rem] border-border/50 shadow-2xl shadow-primary/5 overflow-hidden relative">
      <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-primary/40 via-primary to-primary/40"></div>
      <div class="absolute -right-12 -top-12 w-48 h-48 bg-primary/5 blur-3xl rounded-full"></div>

      <CardContent class="p-8 space-y-8">
        <!-- Private mode expiry -->
        <div v-if="mode === 'private' && expires_at" class="flex items-center justify-center gap-2 py-2 px-4 rounded-full bg-amber-500/10 border border-amber-500/20 text-amber-600 text-[10px] font-black uppercase tracking-widest  animate-pulse">
           <ShieldCheck class="w-4 h-4" /> 
           QR Bảo mật — Hiệu lực đến: {{ new Date(expires_at).toLocaleString('vi-VN') }}
        </div>

        <div class="flex flex-col sm:flex-row gap-8 items-start sm:items-center">
          <div class="relative shrink-0">
             <div class="absolute inset-0 bg-primary/10 blur-xl rounded-full"></div>
             <img v-if="batch?.product?.image_path"
               :src="batch.product.image_path.startsWith('http') ? batch.product.image_path : `/storage/${batch.product.image_path}`"
               class="relative w-28 h-28 rounded-[2rem] object-cover border-2 border-white shadow-2xl" />
             <div v-else class="relative w-28 h-28 rounded-[2rem] bg-muted border-2 border-white shadow-2xl flex items-center justify-center text-5xl">
                {{ batch?.product?.category?.icon ?? '📦' }}
             </div>
          </div>

          <div class="flex-1 space-y-2">
            <Badge variant="outline" class="font-black uppercase text-[9px] tracking-widest text-primary border-primary/20 bg-primary/5 px-3">
               {{ batch?.product?.category?.name_vi ?? 'Sản phẩm' }}
            </Badge>
            <h1 class="text-3xl font-black  tracking-tighter text-foreground uppercase leading-none">
              {{ batch?.product?.name ?? batch?.product_name }}
            </h1>
            <div class="flex items-center gap-2 text-sm font-bold text-muted-foreground ">
               <Building2 class="w-4 h-4 opacity-60" />
               {{ batch?.enterprise?.name }}
            </div>
          </div>
        </div>

        <!-- Identity Grid -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 p-6 rounded-[2rem] bg-muted/30 border border-border/50">
          <div class="space-y-1">
            <Label class="text-[9px] font-black uppercase text-muted-foreground tracking-widest">Mã lô (AI 10)</Label>
            <p class="font-mono text-sm font-black text-primary">{{ batch.code }}</p>
          </div>
          <div class="space-y-1">
            <Label class="text-[9px] font-black uppercase text-muted-foreground tracking-widest">GTIN (AI 01)</Label>
            <p class="font-mono text-xs font-black text-foreground">{{ batch?.ai01_code ?? batch?.product?.gtin ?? '—' }}</p>
          </div>
          <div class="space-y-1">
            <Label class="text-[9px] font-black uppercase text-muted-foreground tracking-widest">Ngày sản xuất</Label>
            <p class="text-xs font-black text-foreground">{{ batch.production_date || '—' }}</p>
          </div>
          <div class="space-y-1">
            <Label class="text-[9px] font-black uppercase text-muted-foreground tracking-widest">Hạn sử dụng</Label>
            <p class="text-xs font-black text-foreground">{{ batch.expiry_date || '—' }}</p>
          </div>
        </div>

        <div v-if="batch?.gs1_128_label" class="space-y-2">
           <Label class="text-[9px] font-black uppercase text-muted-foreground tracking-widest px-1">GS1-128 Digital Signature</Label>
           <code class="block font-mono text-[10px] text-muted-foreground bg-muted/50 border border-dashed border-border p-3 rounded-xl break-all leading-relaxed shadow-inner ">
             {{ batch.gs1_128_label }}
           </code>
        </div>
      </CardContent>
    </Card>

    <!-- ══ CERTIFICATES SUMMARY ══════════════════════════════════════ -->
    <section v-if="allCertificates.length" class="space-y-4">
       <Button variant="ghost" @click="certSectionExpanded = !certSectionExpanded" class="w-full flex items-center justify-between px-2 hover:bg-transparent">
          <div class="flex items-center gap-2">
             <div class="p-1.5 rounded-lg bg-emerald-100 text-emerald-600">
                <ShieldCheck class="w-4 h-4" />
             </div>
             <h3 class="text-xs font-black uppercase tracking-[0.2em] text-foreground">Chứng nhận tiêu chuẩn</h3>
          </div>
          <component :is="certSectionExpanded ? ChevronUp : ChevronDown" class="w-4 h-4 text-muted-foreground" />
       </Button>

       <div v-if="certSectionExpanded" class="grid grid-cols-1 gap-3 animate-in fade-in slide-in-from-top-2 duration-300">
          <Card v-for="cert in allCertificates" :key="cert.name + cert.source" class="border-emerald-500/20 bg-emerald-50/[0.03] hover:border-emerald-500/40 transition-all overflow-hidden group">
             <CardContent class="p-4 flex items-center gap-4">
                <div class="w-10 h-10 rounded-xl bg-emerald-100 flex items-center justify-center text-emerald-600 shrink-0 group-hover:scale-110 transition-transform">
                   <ClipboardCheck class="w-6 h-6" />
                </div>
                <div class="flex-1 min-w-0">
                   <div class="font-black text-foreground text-sm uppercase  tracking-tight">{{ cert.name }}</div>
                   <div class="flex items-center gap-2 text-[9px] font-bold text-muted-foreground uppercase tracking-tighter mt-0.5">
                      {{ cert.organization }} <Separator orientation="vertical" class="h-2" /> {{ cert.source }}
                   </div>
                   <div v-if="cert.result" class="flex items-center gap-2 text-[9px] font-black mt-1">
                      <Badge variant="outline" class="h-4 px-1.5 border-emerald-200 text-emerald-600 bg-white">
                         {{ cert.result === 'pass' ? '✓ ĐẠT CHUẨN' : '✕ KHÔNG ĐẠT' }}
                      </Badge>
                      <span v-if="cert.expiry_date" class="opacity-40 ">Hết hạn: {{ cert.expiry_date }}</span>
                   </div>
                </div>
                <Button v-if="cert.image_url" variant="ghost" size="sm" @click="lightboxUrl = cert.image_url" class="h-8 text-[10px] font-black uppercase tracking-widest text-primary">Xem</Button>
             </CardContent>
          </Card>
       </div>
    </section>

    <!-- ══ TIMELINE SECTION ═══════════════════════════════════════════ -->
    <section class="space-y-6">
      <div class="flex items-center justify-between px-2">
        <div class="flex items-center gap-2">
           <div class="p-1.5 rounded-lg bg-primary/10 text-primary">
              <Globe class="w-4 h-4" />
           </div>
           <h2 class="text-xs font-black uppercase tracking-[0.2em] text-foreground">Hành trình truy xuất</h2>
        </div>
        <Badge variant="secondary" class="font-black text-[9px] h-5">{{ events?.length ?? 0 }} SỰ KIỆN</Badge>
      </div>

      <div v-if="!events?.length" class="flex flex-col items-center justify-center py-20 border-2 border-dashed rounded-[3rem] bg-muted/5 text-muted-foreground ">
        <p class="text-xs font-bold uppercase tracking-widest">Dữ liệu đang được kết nối...</p>
      </div>

      <div v-else class="relative pl-2">
        <!-- Vertical Journey Line -->
        <div class="absolute left-7 top-4 bottom-0 w-0.5 bg-gradient-to-b from-primary via-border to-transparent"></div>

        <template v-for="(event, idx) in events" :key="event.id">

          <!-- Enterprise Transition Marker -->
          <div v-if="idx === 0 || event.enterprise?.code !== events[idx-1]?.enterprise?.code"
            class="relative z-10 pl-14 py-8 first:pt-0" data-aos="fade-right">
            <div class="absolute left-5 top-1/2 -translate-y-1/2 w-4 h-4 rounded-full bg-background border-2 border-primary flex items-center justify-center shadow-lg">
              <div class="w-1.5 h-1.5 rounded-full bg-primary"></div>
            </div>
            <div class="inline-flex items-center gap-2 px-4 py-2 rounded-2xl bg-white border-2 border-primary/10 text-xs font-black uppercase tracking-widest text-primary  shadow-lg">
              <Building2 class="w-4 h-4" />
              {{ event.enterprise?.name }}
            </div>
          </div>

          <!-- Event Milestone Card -->
          <div class="relative pl-14 pb-12 last:pb-4 group">
            <!-- Icon floating circle -->
            <div :class="[
              'absolute left-4 top-0 z-20 w-7 h-7 rounded-xl border-2 flex items-center justify-center text-sm shadow-xl transition-all duration-500 scale-100 group-hover:scale-110 group-hover:rotate-12',
              cteCategory(event) === 'transfer'       ? 'bg-purple-600 border-purple-200 text-white' :
              cteCategory(event) === 'transformation' ? 'bg-orange-600 border-orange-200 text-white' :
              'bg-white border-primary/20'
            ]">
              {{ cteIcon(event.cte_code) }}
            </div>

            <!-- Card Content -->
            <Card :class="[
              'rounded-[2rem] border-2 transition-all duration-500 relative overflow-hidden shadow-lg hover:shadow-2xl',
              cteCategory(event) === 'transfer'       ? 'border-purple-100 bg-purple-50/[0.02]' :
              cteCategory(event) === 'transformation' ? 'border-orange-100 bg-orange-50/[0.02]' :
              'border-border/50 bg-card'
            ]">
              <!-- Aesthetic watermark -->
              <div class="absolute -right-4 -top-4 text-7xl opacity-[0.03] group-hover:opacity-[0.06] transition-opacity duration-700 pointer-events-none ">
                {{ cteIcon(event.cte_code) }}
              </div>

              <CardHeader class="pb-4">
                <div class="flex justify-between items-start gap-4">
                  <div class="space-y-1">
                    <div class="flex items-center gap-2 flex-wrap">
                      <h3 class="text-lg font-black text-foreground uppercase  tracking-tight group-hover:text-primary transition-colors">{{ cteName(event) }}</h3>
                      <Badge :variant="catStyle(event).variant" class="text-[8px] font-black h-4 px-1.5 uppercase">
                        {{ catStyle(event).label }}
                      </Badge>
                    </div>
                    <div class="flex items-center gap-2 pt-1">
                       <span class="font-mono text-[9px] text-muted-foreground font-bold bg-muted px-1.5 py-0.5 rounded border border-border/50">(251){{ event.event_code }}</span>
                       <span class="flex items-center gap-1 text-[9px] font-black text-muted-foreground/60 uppercase  tracking-tighter">
                          <Clock class="w-3 h-3" /> {{ event.event_time }}
                       </span>
                    </div>
                  </div>
                  
                  <div v-if="event.ipfs_cid" class="shrink-0 flex items-center gap-1.5 px-2.5 py-1.5 rounded-xl bg-emerald-50 border border-emerald-100 text-[8px] font-black text-emerald-600 uppercase tracking-widest shadow-sm">
                    <div class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></div>
                    IPFS Immutable
                  </div>
                </div>
              </CardHeader>

              <CardContent class="space-y-6">
                <!-- 5W Context -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                  <div v-if="event.who_name" class="p-3 rounded-2xl bg-muted/30 border border-border/50 group-hover:bg-white transition-colors">
                    <Label class="text-[8px] font-black uppercase text-muted-foreground tracking-tighter mb-1 block">WHO — Người thực hiện</Label>
                    <p class="text-sm font-black text-foreground">{{ event.who_name }}</p>
                  </div>

                  <div v-if="event.location" class="p-3 rounded-2xl bg-muted/30 border border-border/50 group-hover:bg-white transition-colors">
                    <Label class="text-[8px] font-black uppercase text-muted-foreground tracking-tighter mb-1 block  flex items-center gap-1">
                       <MapPin class="w-2.5 h-2.5" /> WHERE — {{ event.location.name }}
                    </Label>
                    <p class="text-[11px] font-bold text-foreground line-clamp-1 ">{{ event.location.address ?? event.location.province }}</p>
                    <p v-if="event.location.gln" class="text-[8px] text-muted-foreground/60 font-mono mt-0.5">GLN: {{ event.location.gln }}</p>
                  </div>

                  <div v-if="event.why_reason" class="sm:col-span-2 p-3 rounded-2xl bg-primary/[0.03] border border-primary/10">
                    <Label class="text-[8px] font-black uppercase text-primary/60 tracking-tighter mb-1 block">WHY — Mục đích / Tiêu chuẩn</Label>
                    <p class="text-[11px] font-medium text-foreground  leading-relaxed">"{{ event.why_reason }}"</p>
                  </div>

                  <div v-if="cteCategory(event) === 'transfer' && event.output_batch_codes?.length" class="sm:col-span-2 p-3 rounded-2xl bg-purple-50 border border-purple-100 space-y-2">
                    <Label class="text-[8px] font-black uppercase text-purple-600 tracking-tighter block">Lô hàng chuyển giao tiếp theo:</Label>
                    <div class="flex flex-wrap gap-2">
                      <Badge v-for="code in event.output_batch_codes" :key="code" variant="outline" class="font-mono text-[9px] bg-white border-purple-200 text-purple-700">
                        {{ code }}
                      </Badge>
                    </div>
                  </div>
                </div>

                <!-- KDE Details -->
                <div v-if="event.kde_data && Object.keys(event.kde_data).length" class="pt-2">
                   <details class="group/kde">
                      <summary class="flex items-center gap-2 text-[9px] font-black uppercase tracking-widest text-muted-foreground cursor-pointer hover:text-primary transition-colors select-none">
                         <ArrowRight class="w-3 h-3 group-open/kde:rotate-90 transition-transform" />
                         Dữ liệu KDE chi tiết (What)
                      </summary>
                      <div class="mt-3 grid grid-cols-2 gap-2 bg-muted/20 p-4 rounded-2xl border-2 border-dashed border-border shadow-inner">
                         <div v-for="(val, key) in event.kde_data" :key="key" class="border-b border-border/50 last:border-0 pb-1.5 mb-1.5">
                            <span class="text-[8px] font-black text-muted-foreground/60 uppercase block">{{ kdeLabel(key) }}</span>
                            <span class="text-[10px] font-black text-foreground  truncate">{{ val ?? '—' }}</span>
                         </div>
                      </div>
                   </details>
                </div>

                <!-- Inspection Results -->
                <div v-if="event.certificates?.length" class="space-y-3 pt-2">
                   <div class="flex items-center gap-2">
                      <div class="h-px flex-1 bg-border/50"></div>
                      <span class="text-[8px] font-black uppercase text-emerald-600 tracking-widest ">Kết quả kiểm soát chất lượng</span>
                      <div class="h-px flex-1 bg-border/50"></div>
                   </div>
                   <div class="space-y-2">
                      <div v-for="c in event.certificates" :key="c.name" class="flex items-center justify-between p-2.5 rounded-xl border border-emerald-100 bg-emerald-50/20">
                         <div class="flex items-center gap-2">
                            <span class="text-emerald-600 font-bold text-xs">{{ c.result === 'pass' ? '✓' : '✕' }}</span>
                            <span class="text-[10px] font-black text-foreground uppercase tracking-tight">{{ c.name }}</span>
                         </div>
                         <span v-if="c.reference_no" class="text-[9px] font-mono font-bold text-emerald-700/60 ">{{ c.reference_no }}</span>
                      </div>
                   </div>
                </div>
              </CardContent>

              <CardFooter class="border-t bg-muted/5 py-4 flex flex-wrap items-center justify-between gap-4">
                <div class="flex flex-wrap gap-2">
                  <Button v-for="att in event.attachments" :key="att.url" variant="outline" size="sm" as-child class="h-8 rounded-xl px-3 text-[9px] font-black uppercase bg-white border-dashed">
                     <a :href="att.url" target="_blank">
                        <Paperclip class="w-3.5 h-3.5 mr-1.5 opacity-60" /> {{ att.name }}
                     </a>
                  </Button>
                </div>

                <div class="flex items-center gap-3">
                   <VerifyIntegrityBtn
                     v-if="event.ipfs_cid"
                     :event-id="event.id"
                     :ipfs-cid="event.ipfs_cid"
                     :short-cid-fn="shortCid"
                   />
                   <div v-if="event.fabric_tx_id" class="flex items-center gap-1.5 text-[8px] font-black text-purple-600 uppercase tracking-widest  opacity-60" :title="event.fabric_tx_id">
                      <Cpu class="w-3 h-3" /> Blockchain TX
                   </div>
                </div>
              </CardFooter>
            </Card>
          </div>
        </template>
      </div>
    </section>

    <!-- Footer Meta -->
    <div class="text-center pt-12 border-t border-dashed space-y-4">
      <div class="flex flex-wrap justify-center gap-x-6 gap-y-2 opacity-30 grayscale hover:opacity-100 hover:grayscale-0 transition-all duration-500">
         <div class="text-[10px] font-black uppercase tracking-[0.2em]">TCVN 12850:2019</div>
         <div class="text-[10px] font-black uppercase tracking-[0.2em]">TT02/2024/TT-BKHCN</div>
         <div class="text-[10px] font-black uppercase tracking-[0.2em]">GS1 GLOBAL STANDARD</div>
      </div>
      <p class="text-[11px] font-bold text-muted-foreground  leading-relaxed max-w-sm mx-auto">
         Hệ thống được bảo trợ dữ liệu bởi nền tảng <span class="text-primary font-black uppercase">AGU Traceability</span>. Mọi thay đổi trái phép trên hành trình sản phẩm đều được ghi vết và cảnh báo tức thì.
      </p>
    </div>

  </div>
</template>
