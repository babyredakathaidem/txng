<script setup>
import { Head, Link, router, useForm } from '@inertiajs/vue3'
import { computed, ref, watch } from 'vue'

import { Card, CardHeader, CardTitle, CardDescription, CardContent } from '@/Components/ui/card/index.js'
import { Button } from '@/Components/ui/button/index.js'
import { Input } from '@/Components/ui/input/index.js'
import { Badge } from '@/Components/ui/badge/index.js'
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/Components/ui/table/index.js'
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/Components/ui/dialog/index.js'
import { Label } from '@/Components/ui/label/index.js'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/Components/ui/select/index.js'
import {
  Pencil,
  QrCode,
  Scissors,
  Truck,
  AlertTriangle,
  CheckCircle,
  Trash2,
  Archive,
  Plus,
  ArrowDownToLine,
  Minimize2,
  Search,
  ChevronRight,
  History
} from 'lucide-vue-next'

const props = defineProps({
  batches:      { type: [Array, Object], default: () => [] },
  products:     { type: Array,           default: () => [] },
  certificates: { type: Array,           default: () => [] },
  filters:      { type: Object,          default: () => ({}) },
})

const list      = computed(() => Array.isArray(props.batches) ? props.batches : props.batches?.data ?? [])
const paginator = computed(() => Array.isArray(props.batches) ? null : props.batches)

// ── Prefix map ────────────────────────────────────────────
const PREFIX_MAP = {
  lua_gao:      'LG',
  rau_qua:      'RQ',
  thuy_san:     'TS',
  chan_nuoi:    'CN',
  thuc_pham_cb: 'TP',
  khac:         'KH',
}

const headers = [
  { key: 'code',         label: 'Mã lô hàng' },
  { key: 'product_name', label: 'Sản phẩm' },
  { key: 'certs',        label: 'Chứng chỉ' },
  { key: 'quantity',     label: 'Tồn kho' },
  { key: 'status',       label: 'Trạng thái' },
  { key: 'events',       label: 'Sự kiện' },
  { key: 'actions',      label: '' },
]

const statusLabel = (s) => ({
  active:    'Hoạt động',
  completed: 'Hoàn thành',
  recalled:  'Thu hồi',
  split:     'Đã tách',
  consumed:  'Đã dùng',
  received:  'Đã nhận',
  transferred: 'Đã chuyển',
}[s] ?? s)

const statusVariant = (s) => {
  if (s === 'active' || s === 'received') return 'default'
  if (s === 'recalled') return 'destructive'
  if (s === 'split' || s === 'transferred' || s === 'consumed') return 'secondary'
  return 'outline'
}

const batchTypeLabel = (t) => ({
  merged:   'Gộp',
  split:    'Tách',
  received: 'Nhập',
}[t] ?? '')

function getCodePrefix(productId) {
  const p = props.products.find(p => p.id == productId)
  if (!p) return '??'
  return PREFIX_MAP[p.category_code] ?? 'KH'
}

// ── Search ────────────────────────────────────────────────
const q = ref(props.filters?.q ?? '')
let timeout = null
watch(q, (val) => {
  clearTimeout(timeout)
  timeout = setTimeout(() => {
    router.get(route('batches.index'), { q: val || undefined }, { preserveState: true, replace: true })
  }, 300)
})

// ── Create ────────────────────────────────────────────────
const showCreate = ref(false)

const createForm = useForm({
  product_id:      '',
  description:     '',
  production_date: '',
  expiry_date:     '',
  quantity:        '',
  unit:            '',
  certificate_ids: [],
})

function toggleCreateCert(certId) {
  const idx = createForm.certificate_ids.indexOf(certId)
  if (idx >= 0) createForm.certificate_ids.splice(idx, 1)
  else createForm.certificate_ids.push(certId)
}

function submitCreate() {
  createForm.post(route('batches.store'), {
    onSuccess: () => { showCreate.value = false; createForm.reset() },
  })
}

// ── Edit ──────────────────────────────────────────────────
const showEdit = ref(false)
const editing  = ref(null)

const editForm = useForm({
  description:     '',
  production_date: '',
  expiry_date:     '',
  quantity:        '',
  unit:            '',
  certificate_ids: [],
})

function openEdit(b) {
  editing.value            = b
  editForm.description     = b.description     ?? ''
  editForm.production_date = b.production_date ?? ''
  editForm.expiry_date     = b.expiry_date     ?? ''
  editForm.quantity        = b.quantity        ?? ''
  editForm.unit            = b.unit            ?? ''
  editForm.certificate_ids = b.certificates?.map(c => c.id) ?? []
  showEdit.value           = true
}

function toggleEditCert(certId) {
  const idx = editForm.certificate_ids.indexOf(certId)
  if (idx >= 0) editForm.certificate_ids.splice(idx, 1)
  else editForm.certificate_ids.push(certId)
}

function submitEdit() {
  if (!editing.value) return
  editForm.put(route('batches.update', editing.value.id), {
    onSuccess: () => { showEdit.value = false; editing.value = null },
  })
}

// ── Delete ────────────────────────────────────────────────
function removeBatch(b) {
  if (!confirm(`Xóa lô "${b.code}"?`)) return
  router.delete(route('batches.destroy', b.id))
}

// ── Recall ────────────────────────────────────────────────
const showRecall  = ref(false)
const showResolve = ref(false)
const recalling   = ref(null)

const recallForm  = useForm({ reason: '', notice_content: '' })
const resolveForm = useForm({ resolved_note: '' })

function openRecall(b) {
  recalling.value = b
  recallForm.reset()
  showRecall.value = true
}

function submitRecall() {
  recallForm.post(route('batches.recall.store', recalling.value.id), {
    onSuccess: () => { showRecall.value = false; recalling.value = null },
  })
}

function openResolve(b) {
  recalling.value = b
  resolveForm.reset()
  showResolve.value = true
}

function submitResolve() {
  resolveForm.patch(route('batches.recall.resolve', recalling.value.id), {
    onSuccess: () => { showResolve.value = false; recalling.value = null },
  })
}

// ── Pagination ────────────────────────────────────────────
const prevPage = () => { if (paginator.value?.prev_page_url) router.visit(paginator.value.prev_page_url, { preserveState: true }) }
const nextPage = () => { if (paginator.value?.next_page_url) router.visit(paginator.value.next_page_url, { preserveState: true }) }

const canOperate = (b) => ['active', 'received'].includes(b.status)
const isArchived = (b) => ['consumed', 'split', 'recalled'].includes(b.status)
</script>

<template>
  <Head title="Quản lý lô hàng" />

  <div class="space-y-8 animate-in fade-in duration-700">
    <!-- Header chuẩn Dashboard -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
      <div class="space-y-3">
        <nav class="flex items-center gap-2 text-[10px] font-black uppercase tracking-[0.3em] text-muted-foreground/60 bg-muted/20 w-fit px-4 py-1.5 rounded-full border border-border/50">
          <Link href="/dashboard" class="hover:text-primary transition-colors">Home</Link>
          <ChevronRight class="w-3 h-3 opacity-20" />
          <span class="text-foreground ">Inventory Batches</span>
        </nav>
        <div>
          <h1 class="text-4xl font-black tracking-tighter text-foreground uppercase ">Quản lý lô hàng</h1>
          <p class="text-muted-foreground font-medium text-sm mt-1 opacity-70">Theo dõi vòng đời sản phẩm từ lúc gieo trồng đến khi xuất xưởng.</p>
        </div>
      </div>
      
      <div class="flex items-center gap-3 pt-4">
           <Button variant="outline" size="sm" as-child class="h-11 px-5 rounded-xl font-black uppercase text-[9px] tracking-widest border-2 hover:bg-muted transition-all">
             <Link :href="route('events.transfer.pending')">
                <ArrowDownToLine class="w-4 h-4 mr-2" /> Nhận Lô
             </Link>
           </Button>
           <Button variant="outline" size="sm" as-child class="h-11 px-5 rounded-xl font-black uppercase text-[9px] tracking-widest border-2 hover:bg-muted transition-all">
             <Link :href="route('batches.merge.show')">
                <Minimize2 class="w-4 h-4 mr-2" /> Gộp lô
             </Link>
           </Button>
           <Button size="sm" @click="showCreate = true" class="h-11 px-6 rounded-xl font-black uppercase text-[9px] tracking-widest shadow-xl shadow-primary/20 hover:scale-105 transition-all">
             <Plus class="w-4 h-4 mr-2" /> Tạo lô mới
           </Button>
      </div>
    </div>

    <!-- Search & Quick Filter -->
    <div class="flex items-center gap-3">
      <div class="relative max-w-sm w-full group">
        <Search class="absolute left-4 top-1/2 -translate-y-1/2 size-4 text-muted-foreground/40 group-focus-within:text-primary transition-colors" />
        <Input 
          v-model="q" 
          placeholder="Tìm mã lô hoặc tên sản phẩm..." 
          class="h-12 pl-11 rounded-2xl bg-card/50 border-border/50 focus:bg-background focus:ring-primary/20 transition-all shadow-inner"
        />
      </div>
    </div>

    <!-- Table Container -->
    <Card class="border-border/50 bg-card/50 backdrop-blur-sm overflow-hidden rounded-[2.5rem] shadow-sm">
      <CardContent class="p-0">
        <Table>
          <TableHeader class="bg-muted/30">
            <TableRow class="hover:bg-transparent border-border/50">
              <TableHead v-for="h in headers" :key="h.key" class="h-14 text-[10px] font-black uppercase tracking-widest text-muted-foreground/60 px-6">{{ h.label }}</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            <TableRow v-for="b in list" :key="b.id" :class="{ 'opacity-50 grayscale-[0.5]': isArchived(b) }" class="group hover:bg-muted/30 transition-colors border-border/50">
              <TableCell class="px-6 py-5">
                <div class="font-mono text-primary font-black text-base tracking-tighter uppercase ">{{ b.code }}</div>
                <div v-if="b.batch_type && b.batch_type !== 'original'" class="mt-1.5">
                   <Badge variant="outline" class="text-[8px] h-4 px-1.5 font-black uppercase tracking-widest border-primary/20 bg-primary/5 text-primary">
                     TYPE: {{ batchTypeLabel(b.batch_type) }}
                   </Badge>
                </div>
              </TableCell>
              
              <TableCell class="px-6">
                <div class="font-black text-foreground tracking-tight uppercase ">{{ b.product_name }}</div>
              </TableCell>

              <TableCell class="px-6">
                <div v-if="b.certificates?.length" class="flex flex-wrap gap-1 max-w-[200px]">
                  <Badge v-for="cert in b.certificates" :key="cert.id" variant="secondary" class="text-[8px] h-4 px-1.5 font-black uppercase tracking-widest bg-background border border-border/50 shadow-sm">
                    {{ cert.name }}
                  </Badge>
                </div>
                <span v-else class="text-muted-foreground/30 text-[10px] font-black uppercase tracking-widest ">— None</span>
              </TableCell>

              <TableCell class="px-6">
                <div v-if="b.quantity" class="space-y-0.5">
                  <div class="font-black tracking-tighter text-base text-foreground">
                    {{ b.current_quantity ?? b.quantity }}
                    <span class="text-muted-foreground text-[9px] ml-0.5 uppercase tracking-widest font-black opacity-40">{{ b.unit }}</span>
                  </div>
                  <div v-if="b.current_quantity != null && b.quantity && b.current_quantity !== b.quantity" class="text-[8px] text-muted-foreground/40 font-black uppercase  tracking-widest">
                    Original: {{ b.quantity }}
                  </div>
                </div>
                <span v-else class="text-muted-foreground/30 text-[10px] font-black tracking-widest">—</span>
              </TableCell>

              <TableCell class="px-6">
                <Badge :variant="statusVariant(b.status)" class="font-black text-[9px] uppercase tracking-widest px-2.5 py-1 rounded-full shadow-sm">{{ statusLabel(b.status) }}</Badge>
              </TableCell>

              <TableCell class="px-6 text-center">
                <div class="flex flex-col items-center">
                   <span class="font-black text-lg tracking-tighter text-primary">{{ b.events_count ?? 0 }}</span>
                   <span class="text-[8px] font-black uppercase text-muted-foreground/40 tracking-widest">Logs</span>
                </div>
              </TableCell>

              <TableCell class="px-6">
                <div class="flex items-center gap-1 justify-end opacity-0 group-hover:opacity-100 transition-all duration-300 translate-x-2 group-hover:translate-x-0">
                  <Button variant="ghost" size="icon" @click="openEdit(b)" title="Chỉnh sửa" class="h-9 w-9 rounded-xl hover:bg-blue-500/10 hover:text-blue-500 transition-all shadow-sm">
                    <Pencil class="size-4" />
                  </Button>
                  <Button variant="ghost" size="icon" as-child title="Phát hành QR" class="h-9 w-9 rounded-xl hover:bg-primary/10 hover:text-primary transition-all shadow-sm">
                    <Link :href="route('batches.qrs', b.id)">
                      <QrCode class="size-4" />
                    </Link>
                  </Button>
                  <Button variant="ghost" size="icon" as-child title="Xem phả hệ" class="h-9 w-9 rounded-xl hover:bg-emerald-500/10 hover:text-emerald-500 transition-all shadow-sm">
                    <Link :href="route('batches.lineage', b.id)">
                      <History class="size-4" />
                    </Link>
                  </Button>

                  <template v-if="canOperate(b)">
                    <Button variant="ghost" size="icon" as-child title="Tách lô" class="h-9 w-9 rounded-xl hover:bg-amber-500/10 hover:text-amber-500 transition-all shadow-sm">
                      <Link :href="route('batches.split.show', b.id)">
                        <Scissors class="size-4" />
                      </Link>
                    </Button>
                    <Button variant="ghost" size="icon" as-child title="Chuyển giao" class="h-9 w-9 rounded-xl hover:bg-sky-500/10 hover:text-sky-500 transition-all shadow-sm">
                      <Link :href="route('batches.transfer.show', b.id)">
                        <Truck class="size-4" />
                      </Link>
                    </Button>
                    <Button variant="ghost" size="icon" @click="openRecall(b)" title="Thu hồi khẩn cấp" class="h-9 w-9 rounded-xl hover:bg-destructive/10 hover:text-destructive transition-all shadow-sm">
                      <AlertTriangle class="size-4" />
                    </Button>
                  </template>

                  <Button v-if="b.status === 'recalled'" variant="ghost" size="icon" @click="openResolve(b)" title="Xử lý thu hồi" class="h-9 w-9 rounded-xl hover:bg-green-500/10 hover:text-green-500 transition-all shadow-sm">
                    <CheckCircle class="size-4" />
                  </Button>

                  <Button variant="ghost" size="icon" @click="removeBatch(b)" title="Xóa" class="h-9 w-9 rounded-xl hover:bg-destructive/10 hover:text-destructive transition-all shadow-sm">
                    <Trash2 class="size-4" />
                  </Button>
                </div>
              </TableCell>
            </TableRow>

            <TableRow v-if="!list.length">
              <TableCell colspan="7" class="h-80 text-center">
                <div class="flex flex-col items-center justify-center text-muted-foreground space-y-6">
                   <div class="p-8 rounded-[2rem] bg-muted/30 shadow-inner">
                     <Archive class="size-16 opacity-10 animate-pulse" />
                   </div>
                   <div class="space-y-1">
                      <p class="text-sm font-black uppercase tracking-[0.2em] opacity-40">Chưa có lô hàng nào</p>
                      <p class="text-[10px] font-bold  opacity-20 uppercase">Nhấn nút "Tạo lô mới" để bắt đầu sản xuất</p>
                   </div>
                </div>
              </TableCell>
            </TableRow>
          </TableBody>
        </Table>
      </CardContent>
    </Card>

    <!-- Pagination -->
    <div v-if="paginator?.last_page > 1" class="flex items-center justify-between px-4">
      <div class="text-[10px] font-black uppercase tracking-[0.3em] text-muted-foreground/40">
        Trang <span class="text-foreground font-black">{{ paginator.current_page }}</span> / {{ paginator.last_page }}
      </div>
      <div class="flex gap-3">
        <Button variant="outline" size="sm" :disabled="!paginator.prev_page_url" @click="prevPage" class="h-10 px-6 rounded-xl font-black text-[10px] uppercase tracking-widest border-2 shadow-sm active:scale-95 transition-all">Trước</Button>
        <Button variant="outline" size="sm" :disabled="!paginator.next_page_url" @click="nextPage" class="h-10 px-6 rounded-xl font-black text-[10px] uppercase tracking-widest border-2 shadow-sm active:scale-95 transition-all">Sau</Button>
      </div>
    </div>
  </div>

  <!-- Dialog Thu hồi -->
  <Dialog :open="showRecall" @update:open="(v) => (!v && (showRecall = false))">
    <DialogContent class="rounded-[2.5rem] p-0 overflow-hidden border-border/50 bg-card/95 backdrop-blur-2xl">
      <DialogHeader class="p-8 pb-0">
        <div class="flex items-center gap-4 mb-2">
           <div class="p-3 rounded-2xl bg-destructive text-white shadow-xl shadow-destructive/20">
              <AlertTriangle class="size-6" />
           </div>
           <div>
              <DialogTitle class="text-2xl font-black  tracking-tighter uppercase text-destructive">Thu hồi khẩn cấp</DialogTitle>
              <DialogDescription class="text-[10px] font-bold uppercase tracking-widest text-muted-foreground/60 mt-0.5">Lô hàng: {{ recalling?.code }}</DialogDescription>
           </div>
        </div>
      </DialogHeader>
      <div class="p-8 pt-6 space-y-6">
        <div class="p-4 rounded-2xl bg-destructive/5 border border-destructive/20 text-destructive text-[10px] font-black uppercase tracking-widest leading-relaxed  shadow-inner">
          Cảnh báo: Lô hàng này sẽ bị chặn mọi giao dịch và gửi tin báo sự cố đến toàn bộ chuỗi cung ứng liên quan.
        </div>
        <div class="space-y-3">
          <Label class="text-[10px] font-black uppercase tracking-[0.2em] text-muted-foreground/80 px-1 ">Lý do thu hồi chính thức *</Label>
          <Input v-model="recallForm.reason" placeholder="VD: Phát hiện dư lượng thuốc BVTV vượt ngưỡng..." class="h-12 font-bold rounded-xl bg-muted/30 border-border/50 shadow-inner focus:bg-background" />
          <p v-if="recallForm.errors.reason" class="text-[10px] font-black text-destructive uppercase px-1 ">{{ recallForm.errors.reason }}</p>
        </div>
        <div class="space-y-3">
          <Label class="text-[10px] font-black uppercase tracking-[0.2em] text-muted-foreground/80 px-1 ">Nội dung thông báo đối tác</Label>
          <Input v-model="recallForm.notice_content" placeholder="Hướng dẫn xử lý cho các bên đang giữ hàng..." class="h-12 font-medium rounded-xl bg-muted/30 border-border/50 shadow-inner focus:bg-background" />
        </div>
      </div>
      <DialogFooter class="p-8 bg-muted/30 border-t border-border/50 gap-3">
        <Button variant="ghost" @click="showRecall = false" class="h-12 px-6 rounded-xl font-black uppercase text-[10px] tracking-widest">Hủy bỏ</Button>
        <Button variant="destructive" @click="submitRecall" :disabled="recallForm.processing" class="h-12 px-10 rounded-xl font-black uppercase text-[10px] tracking-[0.2em] shadow-xl shadow-destructive/20 active:scale-95 transition-all">
           Xác nhận thu hồi
        </Button>
      </DialogFooter>
    </DialogContent>
  </Dialog>

  <!-- Dialog Tạo lô mới -->
  <Dialog :open="showCreate" @update:open="(v) => (!v && (showCreate = false))">
    <DialogContent class="sm:max-w-[600px] rounded-[2.5rem] p-0 overflow-hidden border-border/50 bg-card/95 backdrop-blur-2xl">
      <DialogHeader class="p-8 pb-0">
        <div class="flex items-center gap-4 mb-2">
           <div class="p-3 rounded-2xl bg-primary text-white shadow-xl shadow-primary/20">
              <Plus class="size-6" />
           </div>
           <div>
              <DialogTitle class="text-2xl font-black  tracking-tighter uppercase">Khởi tạo lô hàng</DialogTitle>
              <DialogDescription class="text-[10px] font-bold uppercase tracking-widest text-muted-foreground/60 mt-0.5">Thiết lập đơn vị định danh sản xuất</DialogDescription>
           </div>
        </div>
      </DialogHeader>
      <div class="p-8 pt-6 space-y-8">
        <div class="grid gap-6">
          <div class="space-y-3">
            <Label class="text-[10px] font-black uppercase tracking-[0.2em] text-primary/60 px-1 ">Sản phẩm liên kết *</Label>
            <Select v-model="createForm.product_id">
              <SelectTrigger class="h-12 font-black rounded-xl bg-muted/30 border-border/50 shadow-inner">
                <SelectValue placeholder="— Chọn sản phẩm trong danh mục —" />
              </SelectTrigger>
              <SelectContent class="rounded-xl">
                <SelectItem v-for="p in products" :key="p.id" :value="String(p.id)" class="font-bold">
                  {{ p.name }} <span class="text-[10px] text-muted-foreground ml-1" v-if="p.gtin">({{ p.gtin }})</span>
                </SelectItem>
              </SelectContent>
            </Select>
            <p v-if="createForm.errors.product_id" class="text-[10px] font-black text-destructive uppercase px-1 ">{{ createForm.errors.product_id }}</p>
            <div v-if="createForm.product_id" class="text-[9px] font-black text-primary/40 uppercase tracking-widest px-1 ">
              Mã lô dự kiến: <span class="text-primary font-mono">{{ getCodePrefix(createForm.product_id) }}-XXXXXX</span>
            </div>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
            <div class="space-y-3">
              <Label class="text-[10px] font-black uppercase tracking-[0.2em] text-muted-foreground/60 px-1">Ngày sản xuất</Label>
              <Input type="date" v-model="createForm.production_date" class="h-12 font-bold rounded-xl bg-muted/30 border-border/50 shadow-inner" />
            </div>
            <div class="space-y-3">
              <Label class="text-[10px] font-black uppercase tracking-[0.2em] text-muted-foreground/60 px-1">Hạn sử dụng</Label>
              <Input type="date" v-model="createForm.expiry_date" class="h-12 font-bold rounded-xl bg-muted/30 border-border/50 shadow-inner" />
            </div>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
            <div class="space-y-3">
              <Label class="text-[10px] font-black uppercase tracking-[0.2em] text-muted-foreground/60 px-1">Số lượng khởi tạo *</Label>
              <Input type="number" v-model="createForm.quantity" class="h-12 font-black text-lg tracking-tighter rounded-xl bg-muted/30 border-border/50 shadow-inner" placeholder="0.00" />
              <p v-if="createForm.errors.quantity" class="text-[10px] font-black text-destructive uppercase px-1 ">{{ createForm.errors.quantity }}</p>
            </div>
            <div class="space-y-3">
              <Label class="text-[10px] font-black uppercase tracking-[0.2em] text-muted-foreground/60 px-1">Đơn vị tính *</Label>
              <Input v-model="createForm.unit" placeholder="VD: kg, bao, tấn..." class="h-12 font-black uppercase tracking-widest rounded-xl bg-muted/30 border-border/50 shadow-inner" />
              <p v-if="createForm.errors.unit" class="text-[10px] font-black text-destructive uppercase px-1 ">{{ createForm.errors.unit }}</p>
            </div>
          </div>

          <div class="space-y-3">
            <Label class="text-[10px] font-black uppercase tracking-[0.2em] text-muted-foreground/60 px-1">Chứng chỉ chất lượng áp dụng</Label>
            <div class="flex flex-wrap gap-2 p-4 rounded-2xl bg-muted/20 border border-dashed border-border/50 shadow-inner">
               <Badge 
                 v-for="cert in certificates" 
                 :key="cert.id" 
                 :variant="createForm.certificate_ids.includes(cert.id) ? 'default' : 'outline'"
                 class="cursor-pointer px-3 py-1.5 text-[9px] font-black uppercase tracking-widest transition-all shadow-sm"
                 @click="toggleCreateCert(cert.id)"
               >
                 <CheckCircle v-if="createForm.certificate_ids.includes(cert.id)" class="size-3 mr-1.5" />
                 {{ cert.name }}
               </Badge>
               <p v-if="!certificates.length" class="text-[9px] font-bold text-muted-foreground/40  uppercase tracking-widest px-2">Chưa có chứng chỉ hệ thống</p>
            </div>
          </div>
        </div>
      </div>
      <DialogFooter class="p-8 bg-muted/30 border-t border-border/50 gap-3">
        <Button variant="ghost" @click="showCreate = false" class="h-12 px-6 rounded-xl font-black uppercase text-[10px] tracking-widest">Hủy bỏ</Button>
        <Button @click="submitCreate" :disabled="createForm.processing" class="h-12 px-10 rounded-xl font-black uppercase text-[10px] tracking-[0.2em] shadow-xl shadow-primary/20 active:scale-95 transition-all">
           Xác nhận tạo lô
        </Button>
      </DialogFooter>
    </DialogContent>
  </Dialog>

  <!-- Dialog Sửa lô -->
  <Dialog :open="showEdit" @update:open="(v) => (!v && (showEdit = false))">
    <DialogContent class="sm:max-w-[600px] rounded-[2.5rem] p-0 overflow-hidden border-border/50 bg-card/95 backdrop-blur-2xl">
      <DialogHeader class="p-8 pb-0">
        <div class="flex items-center gap-4 mb-2">
           <div class="p-3 rounded-2xl bg-primary text-white shadow-xl shadow-primary/20">
              <Pencil class="size-6" />
           </div>
           <div>
              <DialogTitle class="text-2xl font-black  tracking-tighter uppercase font-mono">{{ editing?.code }}</DialogTitle>
              <DialogDescription class="text-[10px] font-bold uppercase tracking-widest text-muted-foreground/60 mt-0.5">Cập nhật thông số kỹ thuật</DialogDescription>
           </div>
        </div>
      </DialogHeader>
      <div class="p-8 pt-6 space-y-8">
        <div class="grid gap-6">
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
            <div class="space-y-3">
              <Label class="text-[10px] font-black uppercase tracking-[0.2em] text-muted-foreground/60 px-1">Ngày sản xuất</Label>
              <Input type="date" v-model="editForm.production_date" class="h-12 font-bold rounded-xl bg-muted/30 border-border/50 shadow-inner" />
            </div>
            <div class="space-y-3">
              <Label class="text-[10px] font-black uppercase tracking-[0.2em] text-muted-foreground/60 px-1">Hạn sử dụng</Label>
              <Input type="date" v-model="editForm.expiry_date" class="h-12 font-bold rounded-xl bg-muted/30 border-border/50 shadow-inner" />
            </div>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
            <div class="space-y-3">
              <Label class="text-[10px] font-black uppercase tracking-[0.2em] text-muted-foreground/60 px-1">Số lượng gốc</Label>
              <Input type="number" v-model="editForm.quantity" class="h-12 font-black text-lg tracking-tighter rounded-xl bg-muted/30 border-border/50 shadow-inner" />
            </div>
            <div class="space-y-3">
              <Label class="text-[10px] font-black uppercase tracking-[0.2em] text-muted-foreground/60 px-1">Đơn vị</Label>
              <Input v-model="editForm.unit" class="h-12 font-black uppercase tracking-widest rounded-xl bg-muted/30 border-border/50 shadow-inner" />
            </div>
          </div>

          <div class="space-y-3">
            <Label class="text-[10px] font-black uppercase tracking-[0.2em] text-muted-foreground/60 px-1 ">Ghi chú / Mô tả</Label>
            <Input v-model="editForm.description" class="h-12 rounded-xl bg-muted/30 border-border/50 shadow-inner font-medium " />
          </div>

          <div class="space-y-3">
            <Label class="text-[10px] font-black uppercase tracking-[0.2em] text-muted-foreground/60 px-1">Chứng chỉ áp dụng</Label>
            <div class="flex flex-wrap gap-2 p-4 rounded-2xl bg-muted/20 border border-dashed border-border/50 shadow-inner">
               <Badge 
                 v-for="cert in certificates" 
                 :key="cert.id" 
                 :variant="editForm.certificate_ids.includes(cert.id) ? 'default' : 'outline'"
                 class="cursor-pointer px-3 py-1.5 text-[9px] font-black uppercase tracking-widest transition-all shadow-sm"
                 @click="toggleEditCert(cert.id)"
               >
                 <CheckCircle v-if="editForm.certificate_ids.includes(cert.id)" class="size-3 mr-1.5" />
                 {{ cert.name }}
               </Badge>
            </div>
          </div>
        </div>
      </div>
      <DialogFooter class="p-8 bg-muted/30 border-t border-border/50 gap-3">
        <Button variant="ghost" @click="showEdit = false" class="h-12 px-6 rounded-xl font-black uppercase text-[10px] tracking-widest">Hủy bỏ</Button>
        <Button @click="submitEdit" :disabled="editForm.processing" class="h-12 px-10 rounded-xl font-black uppercase text-[10px] tracking-[0.2em] shadow-xl shadow-primary/20 active:scale-95 transition-all">
           Lưu thay đổi
        </Button>
      </DialogFooter>
    </DialogContent>
  </Dialog>
</template>
