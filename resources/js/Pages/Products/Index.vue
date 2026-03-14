<script setup>
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3'
import { computed, ref } from 'vue'

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
  Trash2,
  Plus,
  Search,
  Eye,
  Box,
  ChevronRight
} from 'lucide-vue-next'

const props = defineProps({
  products:   { type: Object, default: () => ({}) },
  categories: { type: Array,  default: () => [] },
  filters:    { type: Object, default: () => ({}) },
})

const flash = usePage().props.flash || {}

const list      = computed(() => props.products?.data ?? [])
const paginator = computed(() => props.products)

// ── Search ───────────────────────────────────────────────
const q = ref(props.filters?.q ?? '')
const search = () => {
  router.get(route('products.index'), { q: q.value }, { preserveState: true, replace: true })
}

// ── Helpers ──────────────────────────────────────────────
const statusLabel = (s) => s === 'active' ? 'Hoạt động' : 'Ẩn'
const statusVariant = (s) => s === 'active' ? 'default' : 'secondary'

const khacId = computed(() => props.categories.find(c => c.code === 'khac')?.id ?? null)

const headers = [
  { key: 'name',     label: 'Tên sản phẩm' },
  { key: 'category', label: 'Danh mục' },
  { key: 'gtin',     label: 'GTIN' },
  { key: 'unit',     label: 'Đơn vị' },
  { key: 'status',   label: 'Trạng thái' },
  { key: 'batches',  label: 'Lô' },
  { key: 'actions',  label: 'Thao tác' },
]

// ── Create ───────────────────────────────────────────────
const showCreate = ref(false)

const createForm = useForm({
  name:                 '',
  category_id:          '',
  custom_category_name: '',
  gtin:                 '',
  description:          '',
  unit:                 '',
  status:               'active',
  image:                null,
})

const submitCreate = () => {
  createForm.post(route('products.store'), {
    forceFormData: true,
    preserveState: true,
    preserveScroll: true,
    onSuccess: () => { showCreate.value = false; createForm.reset(); createForm.clearErrors() },
  })
}

// ── Edit ─────────────────────────────────────────────────
const showEdit = ref(false)
const editing  = ref(null)

const editForm = useForm({
  name:                 '',
  category_id:          '',
  custom_category_name: '',
  gtin:                 '',
  description:          '',
  unit:                 '',
  status:               'active',
  image:                null,
})

const openEdit = (p) => {
  editing.value          = p
  editForm.name          = p.name
  editForm.category_id   = String(p.category_id ?? '')
  editForm.custom_category_name = ''
  editForm.gtin          = p.gtin ?? ''
  editForm.description   = p.description ?? ''
  editForm.unit          = p.unit ?? ''
  editForm.status        = p.status
  editForm.image         = null
  showEdit.value         = true
}

const submitEdit = () => {
  if (!editing.value) return
  editForm.post(route('products.update', editing.value.id), {
    forceFormData: true,
    preserveState: true,
    preserveScroll: true,
    onSuccess: () => { showEdit.value = false; editing.value = null; editForm.clearErrors() },
  })
}

// ── Delete ───────────────────────────────────────────────
const removeProduct = (p) => {
  if (!confirm(`Xóa sản phẩm "${p.name}"?`)) return
  router.delete(route('products.destroy', p.id))
}

// ── Pagination ───────────────────────────────────────────
const prevPage = () => { if (paginator.value?.prev_page_url) router.visit(paginator.value.prev_page_url, { preserveState: true }) }
const nextPage = () => { if (paginator.value?.next_page_url) router.visit(paginator.value.next_page_url, { preserveState: true }) }
</script>

<template>
  <Head title="Quản lý sản phẩm" />

  <div class="space-y-8 animate-in fade-in duration-700">
    <!-- Header chuẩn -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
      <div class="space-y-3">
        <nav class="flex items-center gap-2 text-[10px] font-black uppercase tracking-[0.3em] text-muted-foreground/60 bg-muted/20 w-fit px-4 py-1.5 rounded-full border border-border/50">
          <Link href="/dashboard" class="hover:text-primary transition-colors">Home</Link>
          <ChevronRight class="w-3 h-3 opacity-20" />
          <span class="text-foreground ">Product Catalog</span>
        </nav>
        <div>
          <h1 class="text-4xl font-black tracking-tighter text-foreground uppercase ">Danh mục sản phẩm</h1>
          <p class="text-muted-foreground font-medium text-sm mt-1 opacity-70">Khai báo thông tin sản phẩm chính thức để quản lý truy xuất nguồn gốc.</p>
        </div>
      </div>
      
      <div class="flex items-center gap-3 pt-4">
        <Button @click="showCreate = true" class="h-12 px-8 font-black uppercase tracking-widest text-[10px] shadow-xl shadow-primary/20 rounded-2xl group active:scale-95 transition-all">
           <Plus class="w-5 h-5 mr-3 group-hover:scale-110 transition-transform" /> Thêm sản phẩm
        </Button>
      </div>
    </div>

    <!-- Search & Filters -->
    <div class="flex items-center gap-3">
      <div class="relative max-w-sm w-full group">
        <Search class="absolute left-4 top-1/2 -translate-y-1/2 size-4 text-muted-foreground/40 group-focus-within:text-primary transition-colors" />
        <Input 
          v-model="q" 
          @keyup.enter="search" 
          placeholder="Tìm theo tên hoặc GTIN..." 
          class="h-12 pl-11 rounded-2xl bg-card/50 border-border/50 focus:bg-background focus:ring-primary/20 transition-all shadow-inner"
        />
      </div>
      <Button variant="secondary" @click="search" class="h-12 px-6 rounded-2xl font-black uppercase text-[10px] tracking-widest">Tìm</Button>
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
            <TableRow v-for="p in list" :key="p.id" class="group hover:bg-muted/30 transition-colors border-border/50">
              <TableCell class="px-6 py-5">
                <div class="font-black text-foreground tracking-tight text-base uppercase ">{{ p.name }}</div>
                <div v-if="p.description" class="text-[10px] font-bold text-muted-foreground/60 truncate max-w-[200px] mt-1 uppercase tracking-tighter opacity-60">{{ p.description }}</div>
              </TableCell>
              
              <TableCell class="px-6">
                <div class="flex items-center gap-3">
                   <div class="text-xl grayscale group-hover:grayscale-0 transition-all duration-500">{{ p.category?.icon }}</div>
                   <span class="text-[10px] font-black uppercase tracking-widest text-muted-foreground group-hover:text-foreground transition-colors">{{ p.category?.name_vi ?? '—' }}</span>
                </div>
              </TableCell>

              <TableCell class="px-6">
                <code class="font-mono text-[10px] font-black text-primary/60 bg-primary/5 px-2 py-1 rounded-lg border border-primary/10 tracking-tighter ">{{ p.gtin || 'NO_GTIN' }}</code>
              </TableCell>
              
              <TableCell class="px-6">
                <Badge variant="outline" class="uppercase text-[9px] font-black tracking-widest bg-background border-border/50 px-2 py-0.5 rounded-md">{{ p.unit || '—' }}</Badge>
              </TableCell>
              
              <TableCell class="px-6">
                <Badge :variant="statusVariant(p.status)" class="font-black text-[9px] uppercase tracking-widest px-2.5 py-1 rounded-full shadow-sm">{{ statusLabel(p.status) }}</Badge>
              </TableCell>
              
              <TableCell class="px-6 text-center">
                <span class="font-black text-lg tracking-tighter text-primary">{{ p.batches_count }}</span>
              </TableCell>

              <TableCell class="px-6">
                <div class="flex items-center gap-1 justify-end opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                  <Button variant="ghost" size="icon" as-child class="h-9 w-9 rounded-xl hover:bg-primary/10 hover:text-primary transition-all shadow-sm">
                    <Link :href="route('products.show', p.id)">
                      <Eye class="size-4" />
                    </Link>
                  </Button>
                  <Button variant="ghost" size="icon" @click="openEdit(p)" class="h-9 w-9 rounded-xl hover:bg-blue-500/10 hover:text-blue-500 transition-all shadow-sm">
                    <Pencil class="size-4" />
                  </Button>
                  <Button variant="ghost" size="icon" @click="removeProduct(p)" class="h-9 w-9 rounded-xl hover:bg-destructive/10 hover:text-destructive transition-all shadow-sm">
                    <Trash2 class="size-4" />
                  </Button>
                </div>
              </TableCell>
            </TableRow>

            <TableRow v-if="!list.length">
              <TableCell colspan="7" class="h-80 text-center">
                <div class="flex flex-col items-center justify-center text-muted-foreground space-y-6">
                   <div class="p-8 rounded-[2rem] bg-muted/30 shadow-inner">
                     <Box class="size-16 opacity-10 animate-pulse" />
                   </div>
                   <div class="space-y-1">
                      <p class="text-sm font-black uppercase tracking-[0.2em] opacity-40">Chưa có dữ liệu sản phẩm</p>
                      <p class="text-[10px] font-bold  opacity-20 uppercase">Nhấn nút "Thêm sản phẩm" để bắt đầu thiết lập</p>
                   </div>
                </div>
              </TableCell>
            </TableRow>
          </TableBody>
        </Table>
      </CardContent>
    </Card>

    <!-- Pagination -->
    <div v-if="paginator && paginator.last_page > 1" class="flex items-center justify-between px-4">
      <div class="text-[10px] font-black uppercase tracking-[0.3em] text-muted-foreground/40">
        Hiển thị <span class="text-foreground font-black">{{ paginator.from ?? 0 }}</span> – <span class="text-foreground font-black">{{ paginator.to ?? 0 }}</span> / {{ paginator.total ?? 0 }}
      </div>
      <div class="flex gap-3">
        <Button variant="outline" size="sm" :disabled="!paginator.prev_page_url" @click="prevPage" class="h-10 px-6 rounded-xl font-black text-[10px] uppercase tracking-widest border-2 shadow-sm active:scale-95 transition-all">Trước</Button>
        <Button variant="outline" size="sm" :disabled="!paginator.next_page_url" @click="nextPage" class="h-10 px-6 rounded-xl font-black text-[10px] uppercase tracking-widest border-2 shadow-sm active:scale-95 transition-all">Sau</Button>
      </div>
    </div>

  </div>

  <!-- Dialog: Thêm sản phẩm -->
  <Dialog :open="showCreate" @update:open="(v) => (!v && (showCreate = false))">
    <DialogContent class="sm:max-w-[550px] rounded-[2.5rem] p-0 overflow-hidden border-border/50 bg-card/95 backdrop-blur-2xl">
      <DialogHeader class="p-8 pb-0">
        <div class="flex items-center gap-4 mb-2">
           <div class="p-3 rounded-2xl bg-primary text-white shadow-xl shadow-primary/20">
              <Plus class="size-6" />
           </div>
           <div>
              <DialogTitle class="text-2xl font-black  tracking-tighter uppercase">Thêm sản phẩm</DialogTitle>
              <DialogDescription class="text-[10px] font-bold uppercase tracking-widest text-muted-foreground/60 mt-0.5">Khai báo thông tin định danh mới</DialogDescription>
           </div>
        </div>
      </DialogHeader>
      
      <div class="p-8 pt-6 space-y-8">
        <div class="grid gap-6">
          <div class="space-y-3">
            <Label class="text-[10px] font-black uppercase tracking-[0.2em] text-primary/60 px-1 ">Tên sản phẩm chính thức *</Label>
            <Input v-model="createForm.name" placeholder="VD: Gạo ST25 hữu cơ" class="h-12 font-black rounded-xl bg-muted/30 border-border/50 shadow-inner focus:bg-background" />
            <p v-if="createForm.errors.name" class="text-[10px] font-black text-destructive uppercase px-1 ">{{ createForm.errors.name }}</p>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
            <div class="space-y-3">
              <Label class="text-[10px] font-black uppercase tracking-[0.2em] text-muted-foreground/60 px-1">Danh mục chuẩn *</Label>
              <Select v-model="createForm.category_id">
                <SelectTrigger class="h-12 font-bold rounded-xl bg-muted/30 shadow-inner">
                  <SelectValue placeholder="Chọn loại" />
                </SelectTrigger>
                <SelectContent class="rounded-xl">
                  <SelectItem v-for="c in categories" :key="c.id" :value="String(c.id)" class="font-bold">
                    {{ c.icon }} {{ c.name_vi }}
                  </SelectItem>
                </SelectContent>
              </Select>
              <p v-if="createForm.errors.category_id" class="text-[10px] font-black text-destructive uppercase px-1">{{ createForm.errors.category_id }}</p>
            </div>
            
            <div class="space-y-3">
              <Label class="text-[10px] font-black uppercase tracking-[0.2em] text-muted-foreground/60 px-1">Đơn vị đo lường</Label>
              <Input v-model="createForm.unit" placeholder="kg, hộp, bao..." class="h-12 font-bold rounded-xl bg-muted/30 shadow-inner focus:bg-background" />
              <p v-if="createForm.errors.unit" class="text-[10px] font-black text-destructive uppercase px-1">{{ createForm.errors.unit }}</p>
            </div>
          </div>

          <div v-if="createForm.category_id == String(khacId)" class="space-y-3 animate-in zoom-in duration-300">
            <Label class="text-[10px] font-black uppercase tracking-[0.2em] text-primary/60 px-1">Tên danh mục tự định nghĩa</Label>
            <Input v-model="createForm.custom_category_name" class="h-12 font-bold rounded-xl bg-muted/30 border-primary/20 shadow-inner focus:bg-background" />
            <p v-if="createForm.errors.custom_category_name" class="text-[10px] font-black text-destructive uppercase px-1">{{ createForm.errors.custom_category_name }}</p>
          </div>

          <div class="space-y-3">
            <Label class="text-[10px] font-black uppercase tracking-[0.2em] text-muted-foreground/60 px-1">Mã GTIN / Barcode (Nếu có)</Label>
            <Input v-model="createForm.gtin" placeholder="893..." class="h-12 font-mono font-bold rounded-xl bg-muted/30 shadow-inner focus:bg-background tracking-widest" />
            <p v-if="createForm.errors.gtin" class="text-[10px] font-black text-destructive uppercase px-1">{{ createForm.errors.gtin }}</p>
          </div>

          <div class="space-y-3">
            <Label class="text-[10px] font-black uppercase tracking-[0.2em] text-muted-foreground/60 px-1">Mô tả ngắn</Label>
            <Input v-model="createForm.description" class="h-12 rounded-xl bg-muted/30 shadow-inner focus:bg-background  font-medium" />
            <p v-if="createForm.errors.description" class="text-[10px] font-black text-destructive uppercase px-1">{{ createForm.errors.description }}</p>
          </div>

          <div class="space-y-3">
            <Label class="text-[10px] font-black uppercase tracking-[0.2em] text-muted-foreground/60 px-1">Ảnh minh họa sản phẩm</Label>
            <div class="relative border-2 border-dashed border-border/50 rounded-2xl p-6 bg-muted/10 group hover:border-primary/40 transition-all cursor-pointer">
               <input type="file" @change="e => createForm.image = e.target.files[0]" accept="image/*" class="absolute inset-0 opacity-0 cursor-pointer z-10" />
               <div class="flex items-center gap-4">
                  <div class="p-3 rounded-xl bg-background text-muted-foreground group-hover:text-primary transition-colors">
                     <Search class="size-5" />
                  </div>
                  <span class="text-xs font-black uppercase tracking-widest opacity-40 group-hover:opacity-100 transition-opacity">Chọn hoặc kéo thả ảnh vào đây</span>
               </div>
            </div>
            <p v-if="createForm.image" class="text-[9px] font-black text-emerald-600 uppercase ">Đã chọn: {{ createForm.image.name }}</p>
            <p v-if="createForm.errors.image" class="text-[10px] font-black text-destructive uppercase px-1">{{ createForm.errors.image }}</p>
          </div>
        </div>
      </div>

      <DialogFooter class="p-8 bg-muted/30 border-t border-border/50 gap-3">
        <Button variant="ghost" @click="showCreate = false" class="h-12 px-6 rounded-xl font-black uppercase text-[10px] tracking-widest">Hủy bỏ</Button>
        <Button @click="submitCreate" :disabled="createForm.processing" class="h-12 px-10 rounded-xl font-black uppercase text-[10px] tracking-[0.2em] shadow-xl shadow-primary/20 active:scale-95 transition-all">
           Lưu sản phẩm
        </Button>
      </DialogFooter>
    </DialogContent>
  </Dialog>

  <!-- Dialog: Sửa sản phẩm (Tương tự Create nhưng tinh giản) -->
  <Dialog :open="showEdit" @update:open="(v) => (!v && (showEdit = false))">
    <DialogContent class="sm:max-w-[550px] rounded-[2.5rem] p-0 overflow-hidden border-border/50 bg-card/95 backdrop-blur-2xl">
      <DialogHeader class="p-8 pb-0">
        <div class="flex items-center gap-4 mb-2">
           <div class="p-3 rounded-2xl bg-primary text-white shadow-xl shadow-primary/20">
              <Pencil class="size-6" />
           </div>
           <div>
              <DialogTitle class="text-2xl font-black  tracking-tighter uppercase truncate max-w-[300px]">{{ editing?.name }}</DialogTitle>
              <DialogDescription class="text-[10px] font-bold uppercase tracking-widest text-muted-foreground/60 mt-0.5">Cập nhật thông tin chi tiết</DialogDescription>
           </div>
        </div>
      </DialogHeader>
      
      <div class="p-8 pt-6 space-y-8">
        <div class="grid gap-6">
          <div class="space-y-3">
            <Label class="text-[10px] font-black uppercase tracking-[0.2em] text-primary/60 px-1 ">Tên sản phẩm *</Label>
            <Input v-model="editForm.name" class="h-12 font-black rounded-xl bg-muted/30 border-border/50 shadow-inner focus:bg-background" />
            <p v-if="editForm.errors.name" class="text-[10px] font-black text-destructive uppercase px-1 ">{{ editForm.errors.name }}</p>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
            <div class="space-y-3">
              <Label class="text-[10px] font-black uppercase tracking-[0.2em] text-muted-foreground/60 px-1">Danh mục</Label>
              <Select v-model="editForm.category_id">
                <SelectTrigger class="h-12 font-bold rounded-xl bg-muted/30 shadow-inner">
                  <SelectValue />
                </SelectTrigger>
                <SelectContent class="rounded-xl">
                  <SelectItem v-for="c in categories" :key="c.id" :value="String(c.id)" class="font-bold">
                    {{ c.icon }} {{ c.name_vi }}
                  </SelectItem>
                </SelectContent>
              </Select>
            </div>
            
            <div class="space-y-3">
              <Label class="text-[10px] font-black uppercase tracking-[0.2em] text-muted-foreground/60 px-1">Trạng thái</Label>
              <Select v-model="editForm.status">
                <SelectTrigger class="h-12 font-black rounded-xl bg-muted/30 shadow-inner">
                  <SelectValue />
                </SelectTrigger>
                <SelectContent class="rounded-xl">
                  <SelectItem value="active" class="font-black text-emerald-600">HOẠT ĐỘNG</SelectItem>
                  <SelectItem value="inactive" class="font-black text-muted-foreground">ẨN / TẠM NGƯNG</SelectItem>
                </SelectContent>
              </Select>
            </div>
          </div>

          <div class="space-y-3">
            <Label class="text-[10px] font-black uppercase tracking-[0.2em] text-muted-foreground/60 px-1">Ảnh sản phẩm mới (Nếu có)</Label>
            <Input type="file" @change="e => editForm.image = e.target.files[0]" accept="image/*" class="h-12 font-black text-[10px] uppercase rounded-xl bg-muted/30 border-border/50 shadow-inner p-3 cursor-pointer" />
            <p v-if="editForm.errors.image" class="text-[10px] font-black text-destructive uppercase px-1">{{ editForm.errors.image }}</p>
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
