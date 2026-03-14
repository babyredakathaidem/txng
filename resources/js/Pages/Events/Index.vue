<script setup>
import { ref, computed, watch } from 'vue'
import { Head, Link, useForm, router } from '@inertiajs/vue3'
import axios from 'axios'

import { Card, CardHeader, CardTitle, CardDescription, CardContent, CardFooter } from '@/Components/ui/card/index.js'
import { Button } from '@/Components/ui/button/index.js'
import { Badge } from '@/Components/ui/badge/index.js'
import { Label } from '@/Components/ui/label/index.js'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/Components/ui/select/index.js'
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/Components/ui/dialog/index.js'
import { Input } from '@/Components/ui/input/index.js'
import { Textarea } from '@/Components/ui/textarea/index.js'
import { Separator } from '@/Components/ui/separator/index.js'
import { 
  ClipboardCheck, 
  RefreshCw, 
  Truck, 
  QrCode,
  Eye,
  Pencil,
  Trash2,
  CloudUpload,
  Link as LinkIcon,
  Cpu,
  Paperclip,
  History,
  Info,
  MapPin,
  Box,
  ChevronRight,
  Activity
} from 'lucide-vue-next'

const props = defineProps({
  batches: Array,
  events:  Object,
  filters: Object,
})

// ── Batch selector ────────────────────────────────────────
const batchId       = ref(props.filters?.batch_id ? String(props.filters.batch_id) : null)
const selectedBatch = computed(() => props.batches?.find(b => String(b.id) === batchId.value) ?? null)

watch(batchId, (val) => {
  router.visit(route('events.index', val ? { batch_id: val } : {}), {
    preserveState: true, preserveScroll: true, replace: true,
  })
})

// ── Upload attachment ─────────────────────────────────────
const uploadingEvent = ref(null)
const uploadProgress = ref(0)

async function uploadFile(ev, file) {
  if (!file) return
  uploadingEvent.value = ev.id
  const fd = new FormData()
  fd.append('file', file)
  try {
    await axios.post(route('events.attachments.store', ev.id), fd, {
      headers: { 'Content-Type': 'multipart/form-data' },
      onUploadProgress: (e) => {
        uploadProgress.value = Math.round((e.loaded * 100) / e.total)
      },
    })
    router.reload({ only: ['events'] })
  } catch (e) {
    alert('Upload thất bại: ' + (e.response?.data?.error || e.message))
  } finally {
    uploadingEvent.value = null
    uploadProgress.value = 0
  }
}

// ── Edit ──────────────────────────────────────────────────
const editingEvent  = ref(null)
const editKdeValues = ref({})
const editForm = useForm({
  cte_code:      '',
  event_time:    '',
  kde_data:      {},
  who_name:      '',
  where_address: '',
  where_lat:     null,
  where_lng:     null,
  why_reason:    '',
  note:          '',
})

function openEdit(ev) {
  editingEvent.value  = ev
  editKdeValues.value = { ...(ev.kde_data ?? {}) }
  editForm.cte_code   = ev.cte_code
  editForm.event_time = ev.event_time?.slice(0, 16) ?? ''
  editForm.who_name   = ev.who_name ?? ''
  editForm.where_address = ev.where_address ?? ''
  editForm.where_lat   = ev.where_lat
  editForm.where_lng   = ev.where_lng
  editForm.why_reason  = ev.why_reason ?? ''
  editForm.note       = ev.note ?? ''
}

function submitEdit() {
  editForm.kde_data = { ...editKdeValues.value }
  editForm.put(route('events.update', editingEvent.value.id), {
    onSuccess: () => { editingEvent.value = null },
  })
}

// ── Delete / Publish ──────────────────────────────────────
function deleteEvent(ev) {
  if (ev.status === 'published') return
  if (!confirm('Xóa sự kiện "' + getCteName(ev) + '"?')) return
  router.delete(route('events.destroy', ev.id))
}

function publishEvent(ev) {
  if (ev.status === 'published') return
  if (!confirm('Publish lên IPFS sẽ KHÓA VĨNH VIỄN sự kiện này. Tiếp tục?')) return
  router.post(route('events.publish', ev.id))
}

// ── Xem chi tiết ─────────────────────────────────────────
const viewingEvent = ref(null)
function openView(ev) { viewingEvent.value = ev }

// ── QR Bước sản xuất ─────────────────────────────────────
function printStepQr(ev) {
  if (!ev.event_token) {
    alert('Sự kiện này không có mã QR (thiếu token).')
    return
  }
  const url = `${window.location.origin}/step/${ev.event_token}`
  const win = window.open('', '_blank', 'width=400,height=500')
  win.document.write(`
    <html>
      <head>
        <title>QR Bước: ${getCteName(ev)}</title>
        <style>
          body { font-family: sans-serif; text-align: center; padding: 40px; }
          .label { margin-top: 20px; font-weight: bold; font-size: 18px; }
          .id { font-family: monospace; color: #666; font-size: 12px; margin-top: 5px; }
          .qr-img { width: 250px; height: 250px; margin: 20px auto; border: 1px solid #eee; padding: 10px; }
        </style>
      </head>
      <body>
        <div style="text-transform:uppercase;font-size:10px;letter-spacing:2px;color:#999;">AGU TRACEABILITY</div>
        <div class="label">${getCteName(ev)}</div>
        <div class="id">Lô: ${ev.batch?.code ?? ''}</div>
        <img class="qr-img" src="https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=${encodeURIComponent(url)}" />
        <div style="font-size:11px;color:#555;">${url}</div>
        <div style="margin-top:30px;border-top:1px dashed #ccc;padding-top:20px;font-size:10px;color:#999;">
          Dán mã này tại khu vực thực hiện công đoạn sản xuất tương ứng.
        </div>
        <script>window.onload = () => { setTimeout(() => window.print(), 500); }<\/script>
      </body>
    </html>
  `)
}

// ── Helpers ───────────────────────────────────────────────
function getCteName(ev) {
  if (!ev) return ''
  return ev.cte_template?.name_vi ?? ev.cte_code ?? ev.event_type ?? 'Sự kiện'
}
function formatTime(t) {
  if (!t) return ''
  return new Date(t).toLocaleString('vi-VN', {
    day: '2-digit', month: '2-digit', year: 'numeric',
    hour: '2-digit', minute: '2-digit',
  })
}
function shortCid(cid) {
  if (!cid) return ''
  return cid.length > 14 ? cid.slice(0, 8) + '...' + cid.slice(-6) : cid
}
function kdeLabel(key) {
  return key.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase())
}
function batchTypeBadge(b) {
  return ({ merged: '[Gộp]', split: '[Tách]', received: '[Nhận]' })[b?.batch_type] ?? ''
}

const prevEvents = () => { if (props.events?.prev_page_url) router.visit(props.events.prev_page_url, { preserveState: true }) }
const nextEvents = () => { if (props.events?.next_page_url) router.visit(props.events.next_page_url, { preserveState: true }) }
</script>

<template>
  <Head title="Sự kiện truy xuất" />

  <div class="space-y-8 animate-in fade-in duration-700">
    <!-- Header chuẩn Dashboard -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
      <div class="space-y-3">
        <nav class="flex items-center gap-2 text-[10px] font-black uppercase tracking-[0.3em] text-muted-foreground/60 bg-muted/20 w-fit px-4 py-1.5 rounded-full border border-border/50">
          <Link href="/dashboard" class="hover:text-primary transition-colors">Home</Link>
          <ChevronRight class="w-3 h-3 opacity-20" />
          <span class="text-foreground ">Event Logging</span>
        </nav>
        <div>
          <h1 class="text-4xl font-black tracking-tighter text-foreground uppercase ">Ghi nhận sự kiện</h1>
          <p class="text-muted-foreground font-medium text-sm mt-1 opacity-70">Lưu vết dữ liệu (5W) cho từng giai đoạn của chuỗi giá trị.</p>
        </div>
      </div>
    </div>

    <!-- Chọn lô & Stats -->
    <Card class="border-border/50 bg-card/50 backdrop-blur-sm overflow-hidden rounded-[2.5rem] shadow-sm">
      <CardContent class="pt-8 space-y-6 px-8">
        <div class="space-y-3">
          <Label class="text-[10px] font-black uppercase tracking-[0.3em] text-primary/60 px-1 ">Lô hàng đang thao tác *</Label>
          <Select v-model="batchId">
            <SelectTrigger class="h-14 font-black rounded-2xl bg-muted/30 border-border/50 shadow-inner focus:ring-primary/20 text-lg uppercase ">
              <SelectValue placeholder="— Chọn lô hàng cần ghi nhận dữ liệu —" />
            </SelectTrigger>
            <SelectContent class="rounded-2xl">
              <SelectItem v-for="b in batches" :key="b.id" :value="String(b.id)" class="font-bold py-3">
                {{ b.code }} — {{ b.product_name }}{{ batchTypeBadge(b) ? ' ' + batchTypeBadge(b) : '' }} ({{ b.status }})
              </SelectItem>
            </SelectContent>
          </Select>
        </div>
        
        <div v-if="selectedBatch" class="flex flex-wrap items-center gap-3 animate-in zoom-in duration-500">
           <Badge v-for="cert in (selectedBatch.certifications ?? [])" :key="cert" variant="secondary" class="font-black text-[9px] uppercase tracking-widest px-3 py-1 bg-background border border-border/50 shadow-sm">
              {{ cert }}
           </Badge>
           <Separator orientation="vertical" class="h-6" />
           <Badge variant="outline" class="font-black text-[9px] uppercase tracking-widest bg-primary/5 text-primary border-primary/20 px-3 py-1">
              Stock: {{ selectedBatch.current_quantity }} {{ selectedBatch.unit }}
           </Badge>
        </div>
      </CardContent>
    </Card>

    <div v-if="selectedBatch" class="space-y-12" data-aos="fade-up">
      <!-- Quick Actions Grid -->
      <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
        <Button variant="outline" size="lg" as-child class="h-40 flex-col gap-4 rounded-[2.5rem] bg-card/50 hover:bg-card hover:border-sky-500/50 transition-all border-dashed border-2 group shadow-sm hover:shadow-2xl hover:-translate-y-1">
           <Link :href="route('observation-events.create', { batch_id: selectedBatch.id })">
              <div class="p-4 rounded-2xl bg-sky-500/10 text-sky-500 group-hover:bg-sky-500 group-hover:text-white transition-all duration-500 shadow-inner">
                <ClipboardCheck class="w-10 h-10" />
              </div>
              <span class="text-[10px] font-black uppercase tracking-[0.3em]">Quan sát / Kiểm định</span>
           </Link>
        </Button>

        <Button variant="outline" size="lg" as-child class="h-40 flex-col gap-4 rounded-[2.5rem] bg-card/50 hover:bg-card hover:border-primary/50 transition-all border-dashed border-2 group shadow-sm hover:shadow-2xl hover:-translate-y-1">
           <Link :href="route('transformation-events.create', { batch_id: selectedBatch.id })">
              <div class="p-4 rounded-2xl bg-primary/10 text-primary group-hover:bg-primary group-hover:text-white transition-all duration-500 shadow-inner">
                <RefreshCw class="w-10 h-10" />
              </div>
              <span class="text-[10px] font-black uppercase tracking-[0.3em]">Chế biến / Biến đổi</span>
           </Link>
        </Button>

        <Button variant="outline" size="lg" as-child class="h-40 flex-col gap-4 rounded-[2.5rem] bg-card/50 hover:bg-card hover:border-purple-500/50 transition-all border-dashed border-2 group shadow-sm hover:shadow-2xl hover:-translate-y-1">
           <Link :href="route('transfer.out.create', { batch_id: selectedBatch.id })">
              <div class="p-4 rounded-2xl bg-purple-500/10 text-purple-500 group-hover:bg-purple-500 group-hover:text-white transition-all duration-500 shadow-inner">
                <Truck class="w-10 h-10" />
              </div>
              <span class="text-[10px] font-black uppercase tracking-[0.3em]">Luân chuyển hàng</span>
           </Link>
        </Button>
      </div>

      <!-- History Timeline -->
      <Card class="border-border/50 bg-card/50 backdrop-blur-sm overflow-hidden rounded-[2.5rem] shadow-sm relative">
        <div class="absolute inset-0 opacity-[0.03] pointer-events-none" 
             style="background-image: radial-gradient(circle at 2px 2px, currentColor 1px, transparent 0); background-size: 24px 24px;"></div>
        
        <CardHeader class="flex flex-row items-center justify-between border-b bg-muted/30 py-6 px-10 relative z-10">
           <div class="flex items-center gap-4">
              <div class="p-3 rounded-2xl bg-primary/10 text-primary shadow-inner">
                 <History class="w-6 h-6" />
              </div>
              <div>
                <CardTitle class="text-sm font-black uppercase tracking-[0.3em]">Lịch sử sự kiện lô hàng</CardTitle>
                <p class="font-mono text-xs font-black text-primary mt-1 ">{{ selectedBatch.code }}</p>
              </div>
           </div>
           <Badge variant="secondary" class="font-black text-[10px] uppercase tracking-widest px-4 py-1.5 bg-background shadow-sm">{{ events?.total ?? 0 }} Records</Badge>
        </CardHeader>
        <CardContent class="p-0 relative z-10">
           <div v-if="!events?.data?.length" class="flex flex-col items-center justify-center py-32 text-muted-foreground">
              <div class="p-10 rounded-full bg-muted/30 mb-6 shadow-inner animate-pulse">
                <Box class="w-16 h-16 opacity-10" />
              </div>
              <p class="text-[10px] font-black uppercase tracking-[0.3em] opacity-40">Dòng thời gian trống</p>
           </div>
           
           <div v-else class="divide-y divide-border/30">
              <div v-for="ev in events.data" :key="ev.id" class="p-8 hover:bg-muted/30 transition-all duration-500 group relative">
                 <div class="absolute left-0 top-0 bottom-0 w-1.5 bg-primary/0 group-hover:bg-primary transition-all"></div>
                 <div class="flex flex-col md:flex-row items-start justify-between gap-8">
                    <div class="space-y-3 flex-1 min-w-0">
                       <div class="flex items-center gap-4">
                          <h4 class="font-black text-foreground text-2xl tracking-tighter uppercase  group-hover:text-primary transition-colors">{{ getCteName(ev) }}</h4>
                          <Badge :variant="ev.status === 'published' ? 'default' : 'outline'" class="font-black text-[9px] uppercase tracking-widest px-2.5 py-1 rounded-full shadow-sm">
                             {{ ev.status }}
                          </Badge>
                       </div>
                       <p class="text-[10px] text-muted-foreground flex items-center gap-2 font-bold uppercase tracking-[0.2em] opacity-60">
                          <Clock class="w-3.5 h-3.5" />
                          {{ formatTime(ev.event_time) }}
                       </p>
                    </div>

                    <div class="flex items-center gap-2 flex-wrap sm:justify-end">
                       <Button variant="outline" size="sm" @click="printStepQr(ev)" class="h-10 px-4 rounded-xl text-[9px] font-black uppercase tracking-widest border-2 hover:bg-primary/10 hover:text-primary transition-all shadow-sm">
                          <QrCode class="w-4 h-4 mr-2" /> QR STEP
                       </Button>
                       <Button variant="outline" size="sm" @click="openView(ev)" class="h-10 px-4 rounded-xl text-[9px] font-black uppercase tracking-widest border-2 hover:bg-muted transition-all shadow-sm">
                          <Eye class="w-4 h-4 mr-2" /> VIEW
                       </Button>

                       <template v-if="ev.status !== 'published'">
                          <Button variant="ghost" size="sm" @click="openEdit(ev)" class="h-10 px-4 rounded-xl text-[9px] font-black uppercase tracking-widest hover:bg-blue-500/10 hover:text-blue-500 transition-all">SỬA</Button>
                          <Button variant="secondary" size="sm" @click="publishEvent(ev)" class="h-10 px-6 rounded-xl text-[9px] font-black uppercase tracking-widest border-primary/20 text-primary bg-primary/5 hover:bg-primary hover:text-white transition-all shadow-lg shadow-primary/10">PUBLISH</Button>
                          <Button variant="ghost" size="sm" @click="deleteEvent(ev)" class="h-10 px-4 rounded-xl text-[9px] font-black uppercase tracking-widest text-destructive hover:bg-destructive/10">XÓA</Button>
                       </template>
                    </div>
                 </div>

                 <!-- Blockchain evidence -->
                 <div v-if="ev.ipfs_cid || ev.fabric_tx_id" class="mt-6 flex gap-4 flex-wrap">
                    <a v-if="ev.ipfs_url" :href="ev.ipfs_url" target="_blank" class="flex items-center gap-2 text-[9px] font-black uppercase tracking-widest bg-background px-4 py-2 rounded-xl hover:bg-primary/10 hover:text-primary transition-all border border-border/50 shadow-inner group/ipfs">
                       <LinkIcon class="w-3.5 h-3.5 opacity-40 group-hover/ipfs:opacity-100" /> 
                       IPFS: <span class="font-mono text-primary/60">{{ shortCid(ev.ipfs_cid) }}</span>
                    </a>
                    <div v-if="ev.fabric_tx_id" class="flex items-center gap-2 text-[9px] font-black uppercase tracking-widest bg-background px-4 py-2 rounded-xl text-purple-600 border border-purple-100 shadow-inner group/tx">
                       <Cpu class="w-3.5 h-3.5 opacity-40 group-hover/tx:opacity-100" /> 
                       TX: <span class="font-mono">{{ ev.fabric_tx_id.slice(0, 12) }}...</span>
                    </div>
                 </div>

                 <!-- Attachments list -->
                 <div v-if="ev.attachments?.length" class="mt-6 flex flex-wrap gap-3">
                    <a v-for="att in ev.attachments" :key="att.cid" :href="att.url" target="_blank" class="flex items-center gap-2.5 text-[9px] font-black uppercase tracking-widest px-4 py-2 rounded-xl border border-border/50 bg-background hover:bg-muted transition-all shadow-sm group/file">
                       <Paperclip class="w-3.5 h-3.5 text-muted-foreground group-hover/file:text-primary" /> {{ att.name }}
                    </a>
                 </div>

                 <div v-if="ev.status !== 'published'" class="mt-6 pt-2">
                    <label class="inline-flex items-center gap-3 cursor-pointer text-[9px] font-black uppercase tracking-widest text-muted-foreground/60 hover:text-primary transition-all group/upload">
                       <div class="w-8 h-8 rounded-xl bg-muted flex items-center justify-center shadow-inner group-hover/upload:bg-primary/10 group-hover/upload:text-primary transition-colors">
                          <CloudUpload class="w-4 h-4" />
                       </div>
                       {{ uploadingEvent === ev.id ? `Đang xử lý ${uploadProgress}%...` : 'Tải lên chứng từ đính kèm (Ảnh/PDF)' }}
                       <input type="file" class="hidden" @change="uploadFile(ev, $event.target.files?.[0])" accept=".jpg,.jpeg,.png,.pdf,.webp" />
                    </label>
                 </div>
              </div>
           </div>
        </CardContent>
        <CardFooter v-if="events?.last_page > 1" class="border-t bg-muted/10 py-6 px-10 justify-between items-center relative z-10">
           <p class="text-[10px] font-black uppercase tracking-[0.3em] text-muted-foreground/40 ">Page {{ events.current_page }} of {{ events.last_page }}</p>
           <div class="flex gap-3">
              <Button variant="outline" size="sm" @click="prevEvents" :disabled="!events.prev_page_url" class="h-10 px-6 rounded-xl font-black text-[10px] uppercase tracking-widest border-2 shadow-sm active:scale-95 transition-all">Trước</Button>
              <Button variant="outline" size="sm" @click="nextEvents" :disabled="!events.next_page_url" class="h-10 px-6 rounded-xl font-black text-[10px] uppercase tracking-widest border-2 shadow-sm active:scale-95 transition-all">Sau</Button>
           </div>
        </CardFooter>
      </Card>
    </div>

    <!-- Empty global state -->
    <div v-else class="flex flex-col items-center justify-center py-48 border-2 border-dashed rounded-[3rem] bg-card/20 text-muted-foreground border-border/30 shadow-inner">
       <div class="p-12 rounded-full bg-muted/20 mb-8 group hover:scale-110 transition-transform duration-700 shadow-inner">
          <Truck class="w-24 h-24 opacity-10 group-hover:opacity-30 transition-opacity" />
       </div>
       <h3 class="text-2xl font-black uppercase tracking-[0.3em] text-foreground ">Ready for logging</h3>
       <p class="text-[10px] font-bold mt-4 max-w-xs text-center uppercase tracking-widest opacity-40 leading-relaxed ">Vui lòng chọn một lô hàng từ danh sách phía trên để bắt đầu khai báo các sự kiện truy xuất nguồn gốc chuẩn TCVN.</p>
    </div>
  </div>

  <!-- Dialog: Xem chi tiết sự kiện -->
  <Dialog :open="!!viewingEvent" @update:open="(v) => (!v && (viewingEvent = null))">
    <DialogContent class="sm:max-w-[650px] max-h-[90vh] overflow-y-auto rounded-[3rem] p-0 border-border/50 bg-card/95 backdrop-blur-2xl">
      <DialogHeader class="p-10 pb-0">
        <div class="flex items-center gap-6">
           <div class="p-4 rounded-[1.5rem] bg-primary text-white shadow-2xl shadow-primary/40 group">
              <Info class="w-8 h-8 group-hover:scale-110 transition-transform duration-500" />
           </div>
           <div>
              <DialogTitle class="text-3xl font-black  tracking-tighter uppercase text-foreground">
                 {{ getCteName(viewingEvent) }}
              </DialogTitle>
              <div class="flex items-center gap-3 mt-2">
                 <Badge :variant="viewingEvent?.status === 'published' ? 'default' : 'outline'" class="font-black text-[9px] uppercase tracking-widest px-2.5 py-1">
                   {{ viewingEvent?.status }}
                 </Badge>
                 <Separator orientation="vertical" class="h-4" />
                 <span class="font-mono text-[10px] font-black text-primary uppercase tracking-tighter">{{ viewingEvent?.batch?.code }}</span>
              </div>
           </div>
        </div>
      </DialogHeader>

      <div class="p-10 space-y-10">
        <div class="grid grid-cols-1 gap-6">
           <!-- 5W Info blocks -->
           <div v-if="viewingEvent?.who_name" class="p-6 rounded-3xl bg-muted/30 border border-border/50 shadow-inner group/info">
              <Label class="text-[9px] font-black uppercase text-primary/60 tracking-[0.3em] mb-2 block px-1 ">WHO — Người thực hiện</Label>
              <p class="text-lg font-black tracking-tight text-foreground group-hover/info:text-primary transition-colors uppercase ">{{ viewingEvent.who_name }}</p>
           </div>

           <div v-if="viewingEvent?.where_address" class="p-6 rounded-3xl bg-muted/30 border border-border/50 shadow-inner group/info">
              <Label class="text-[9px] font-black uppercase text-primary/60 tracking-[0.3em] mb-2 block px-1 ">WHERE — Địa điểm</Label>
              <p class="text-base font-bold text-foreground leading-snug">{{ viewingEvent.where_address }}</p>
              <div v-if="viewingEvent.where_lat" class="flex items-center gap-2 text-[9px] text-muted-foreground/60 mt-4 font-mono font-black bg-background/50 w-fit px-3 py-1.5 rounded-full border border-border/50 uppercase tracking-widest">
                 <MapPin class="size-3" /> GPS: {{ viewingEvent.where_lat }}, {{ viewingEvent.where_lng }}
              </div>
           </div>

           <div v-if="viewingEvent?.why_reason" class="p-6 rounded-3xl bg-muted/30 border border-border/50 shadow-inner group/info">
              <Label class="text-[9px] font-black uppercase text-primary/60 tracking-[0.3em] mb-2 block px-1 ">WHY — Mục đích nghiệp vụ</Label>
              <p class="text-sm font-bold  text-foreground/80 leading-relaxed">{{ viewingEvent.why_reason }}</p>
           </div>

           <div v-if="viewingEvent?.note" class="p-6 rounded-3xl bg-muted/10 border border-dashed border-border/50 shadow-inner">
              <Label class="text-[9px] font-black uppercase text-muted-foreground/40 tracking-[0.3em] mb-2 block px-1">Ghi chú bổ sung</Label>
              <p class="text-xs text-muted-foreground font-medium  leading-relaxed">{{ viewingEvent.note }}</p>
           </div>
        </div>

        <div v-if="viewingEvent?.kde_data && Object.keys(viewingEvent.kde_data).length" class="space-y-4">
           <div class="flex items-center gap-3 px-1">
              <Activity class="size-4 text-primary" />
              <Label class="text-[10px] font-black uppercase text-foreground tracking-[0.3em] ">WHAT — Dữ liệu KDE chuẩn TCVN</Label>
           </div>
           <div class="rounded-3xl border border-border/50 bg-muted/20 overflow-hidden divide-y divide-border/30 shadow-inner">
              <div v-for="(val, key) in viewingEvent.kde_data" :key="key" class="grid grid-cols-3 p-5 text-[11px] group/kde hover:bg-background/50 transition-colors">
                 <span class="text-muted-foreground font-black uppercase tracking-widest text-[9px]">{{ kdeLabel(key) }}</span>
                 <span class="col-span-2 font-black text-right text-foreground tracking-tight ">{{ val ?? '—' }}</span>
              </div>
           </div>
        </div>
      </div>

      <DialogFooter class="p-8 bg-muted/30 border-t border-border/50 flex sm:justify-between items-center gap-4">
        <div class="flex gap-3">
           <Button v-if="viewingEvent?.ipfs_url" variant="outline" size="sm" as-child class="h-11 px-5 rounded-xl font-black uppercase text-[9px] tracking-widest border-2 shadow-sm hover:bg-primary/10 hover:text-primary transition-all">
              <a :href="viewingEvent.ipfs_url" target="_blank"><LinkIcon class="w-4 h-4 mr-2" /> IPFS ARCHIVE</a>
           </Button>
           <Button v-if="viewingEvent?.status !== 'published'" variant="default" size="sm" @click="publishEvent(viewingEvent); viewingEvent = null" class="h-11 px-8 rounded-xl font-black uppercase text-[9px] tracking-[0.2em] shadow-xl shadow-primary/20 hover:scale-105 active:scale-95 transition-all">
              PUBLISH NOW
           </Button>
        </div>
        <Button variant="ghost" size="sm" @click="viewingEvent = null" class="h-11 px-6 rounded-xl font-black uppercase text-[9px] tracking-widest">ĐÓNG</Button>
      </DialogFooter>
    </DialogContent>
  </Dialog>

  <!-- Dialog: Sửa nhanh sự kiện -->
  <Dialog :open="!!editingEvent" @update:open="(v) => (!v && (editingEvent = null))">
    <DialogContent class="sm:max-w-[550px] rounded-[2.5rem] p-0 overflow-hidden border-border/50 bg-card/95 backdrop-blur-2xl">
      <DialogHeader class="p-8 pb-0">
        <div class="flex items-center gap-4 mb-2">
           <div class="p-3 rounded-2xl bg-primary text-white shadow-xl shadow-primary/20">
              <Pencil class="size-6" />
           </div>
           <div>
              <DialogTitle class="text-2xl font-black  tracking-tighter uppercase">Cập nhật dữ liệu</DialogTitle>
              <DialogDescription class="text-[10px] font-bold uppercase tracking-widest text-muted-foreground/60 mt-0.5">Sửa đổi thông tin sự kiện nháp</DialogDescription>
           </div>
        </div>
      </DialogHeader>
      
      <div class="p-8 pt-6 space-y-8 max-h-[60vh] overflow-y-auto custom-scrollbar">
        <div class="grid gap-6">
          <div class="space-y-3">
            <Label class="text-[10px] font-black uppercase tracking-[0.2em] text-muted-foreground/60 px-1 ">Thời gian diễn ra *</Label>
            <Input type="datetime-local" v-model="editForm.event_time" class="h-12 font-black rounded-xl bg-muted/30 border-border/50 shadow-inner focus:bg-background" />
            <p v-if="editForm.errors.event_time" class="text-[10px] font-black text-destructive uppercase px-1">{{ editForm.errors.event_time }}</p>
          </div>

          <div class="space-y-3">
            <Label class="text-[10px] font-black uppercase tracking-[0.2em] text-muted-foreground/60 px-1 ">Người thực hiện (Who)</Label>
            <Input v-model="editForm.who_name" class="h-12 font-bold rounded-xl bg-muted/30 border-border/50 shadow-inner focus:bg-background" />
          </div>

          <div class="space-y-3">
            <Label class="text-[10px] font-black uppercase tracking-[0.2em] text-muted-foreground/60 px-1 ">Địa điểm (Where)</Label>
            <Input v-model="editForm.where_address" class="h-12 font-bold rounded-xl bg-muted/30 border-border/50 shadow-inner focus:bg-background" />
          </div>

          <div class="space-y-3">
            <Label class="text-[10px] font-black uppercase tracking-[0.2em] text-muted-foreground/60 px-1 ">Mục đích (Why)</Label>
            <Input v-model="editForm.why_reason" class="h-12 font-medium  rounded-xl bg-muted/30 border-border/50 shadow-inner focus:bg-background" />
          </div>

          <div v-if="Object.keys(editKdeValues).length" class="space-y-5 pt-4 border-t border-dashed border-border/50">
             <div class="flex items-center gap-2">
                <Activity class="size-3 text-primary" />
                <Label class="text-[9px] font-black uppercase text-foreground tracking-[0.3em]">Dữ liệu KDE chi tiết (What)</Label>
             </div>
             <div class="grid grid-cols-1 gap-4">
                <div v-for="(val, key) in editKdeValues" :key="key" class="space-y-2">
                   <Label class="text-[8px] font-black uppercase text-muted-foreground/60 px-1">{{ kdeLabel(key) }}</Label>
                   <Input v-model="editKdeValues[key]" class="h-10 text-xs font-bold rounded-xl bg-muted/20 border-border/50 shadow-inner" />
                </div>
             </div>
          </div>

          <div class="space-y-3">
            <Label class="text-[10px] font-black uppercase tracking-[0.2em] text-muted-foreground/60 px-1 ">Ghi chú</Label>
            <Textarea v-model="editForm.note" class="rounded-xl bg-muted/30 border-border/50 shadow-inner focus:bg-background " rows="3" />
          </div>
        </div>
      </div>

      <DialogFooter class="p-8 bg-muted/30 border-t border-border/50 gap-3">
        <Button variant="ghost" @click="editingEvent = null" class="h-12 px-6 rounded-xl font-black uppercase text-[10px] tracking-widest">Hủy bỏ</Button>
        <Button @click="submitEdit" :disabled="editForm.processing" class="h-12 px-10 rounded-xl font-black uppercase text-[10px] tracking-[0.2em] shadow-xl shadow-primary/20 active:scale-95 transition-all">
           {{ editForm.processing ? 'SAVING...' : 'LƯU THAY ĐỔI' }}
        </Button>
      </DialogFooter>
    </DialogContent>
  </Dialog>
</template>
