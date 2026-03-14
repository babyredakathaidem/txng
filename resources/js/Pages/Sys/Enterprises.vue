<script setup>
import { Head, Link, router } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import { Card, CardHeader, CardTitle, CardDescription, CardContent } from '@/Components/ui/card/index.js'
import { Button } from '@/Components/ui/button/index.js'
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
import { Input } from '@/Components/ui/input/index.js'
import { Label } from '@/Components/ui/label/index.js'
import { 
  Building2, 
  CheckCircle, 
  XCircle, 
  Lock, 
  Unlock, 
  Eye,
  Search,
  Users,
  AlertCircle
} from 'lucide-vue-next'

const props = defineProps({
  status: String,
  enterprises: Object,
})

const blockingId     = ref(null)
const blockReason    = ref('')

const openBlock  = (id) => { blockingId.value = id; blockReason.value = '' }
const submitBlock = () => {
  router.post(route('sys.enterprises.block', blockingId.value), { reason: blockReason.value })
  blockingId.value = null
}
const unblock = (id) => router.post(route('sys.enterprises.unblock', id))

const activeStatus = computed(() => props.status || 'pending')

const headers = [
  { key: 'id', label: 'ID' },
  { key: 'name', label: 'Thông tin doanh nghiệp' },
  { key: 'code', label: 'Mã định danh' },
  { key: 'contact', label: 'Liên hệ' },
  { key: 'status', label: 'Trạng thái' },
  { key: 'actions', label: 'Thao tác' },
]

const rejectingId = ref(null)
const rejectReason = ref('')

const setStatus = (s) => {
  router.get(route('sys.enterprises.index'), { status: s }, { preserveState: true, replace: true })
}

const approve = (id) => router.post(route('sys.enterprises.approve', id))

const openReject = (id) => {
  rejectingId.value = id
  rejectReason.value = ''
}

const submitReject = () => {
  router.post(route('sys.enterprises.reject', rejectingId.value), { reason: rejectReason.value })
  rejectingId.value = null
}

const badgeVariant = (s) => {
  if (s === 'approved') return 'default'
  if (s === 'pending') return 'secondary'
  if (s === 'rejected' || s === 'blocked') return 'destructive'
  return 'outline'
}

const pillCls = (s) =>
  `px-4 py-2 rounded-full text-[10px] font-black uppercase tracking-widest border transition-all duration-300 ${
    activeStatus.value === s
      ? 'border-primary/50 bg-primary text-primary-foreground shadow-lg shadow-primary/20 scale-105'
      : 'border-border/50 bg-card/50 text-muted-foreground hover:bg-card hover:text-foreground'
  }`
</script>

<template>
  <Head title="Duyệt doanh nghiệp" />

  <div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-4">
      <div>
        <p class="text-[10px] font-black uppercase tracking-[0.3em] text-primary mb-1">System Administration</p>
        <h1 class="text-4xl font-black tracking-tighter text-foreground">Quản lý đối tác</h1>
        <p class="text-muted-foreground font-medium text-sm mt-1 opacity-70">Duyệt hồ sơ đăng ký doanh nghiệp và quản lý trạng thái hoạt động.</p>
      </div>
      <div class="flex items-center gap-2 text-[10px] font-black uppercase tracking-widest text-muted-foreground/40 bg-muted/20 px-4 py-2 rounded-full border border-border/50">
         <Users class="w-3 h-3 mr-1" />
         {{ enterprises.total }} Total Partners
      </div>
    </div>

    <!-- Tabs -->
    <div class="flex gap-3 flex-wrap p-1 bg-muted/30 rounded-full w-fit border border-border/50">
      <button :class="pillCls('pending')" @click="setStatus('pending')">Chờ duyệt</button>
      <button :class="pillCls('approved')" @click="setStatus('approved')">Đã duyệt</button>
      <button :class="pillCls('rejected')" @click="setStatus('rejected')">Đã từ chối</button>
      <button :class="pillCls('all')" @click="setStatus('all')">Tất cả</button>
    </div>

    <!-- Table -->
    <Card class="border-border/50 bg-card/50 backdrop-blur-sm overflow-hidden">
      <CardContent class="p-0">
        <Table>
          <TableHeader class="bg-muted/50">
            <TableRow>
              <TableHead v-for="h in headers" :key="h.key" class="text-[10px] font-black uppercase tracking-widest">{{ h.label }}</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            <TableRow
              v-for="e in enterprises.data"
              :key="e.id"
              class="group hover:bg-muted/30 transition-colors"
            >
              <TableCell class="font-mono text-[10px] font-black text-muted-foreground opacity-40">#{{ e.id }}</TableCell>

              <TableCell>
                <div class="font-black text-foreground tracking-tight text-base">{{ e.name }}</div>
                <div class="text-[10px] font-bold text-muted-foreground/60 uppercase tracking-widest mt-1">MST: {{ e.tax_code || e.business_code || '—' }}</div>
              </TableCell>

              <TableCell>
                <Badge variant="outline" class="font-mono text-[10px] font-black tracking-tighter border-primary/20 text-primary bg-primary/5 uppercase">{{ e.code || 'NO_CODE' }}</Badge>
              </TableCell>

              <TableCell>
                <div class="space-y-1">
                  <div class="flex items-center gap-2 text-xs font-bold text-foreground">
                    <span class="opacity-40 tracking-widest uppercase text-[9px]">Email:</span> {{ e.email }}
                  </div>
                  <div class="flex items-center gap-2 text-xs font-bold text-foreground">
                    <span class="opacity-40 tracking-widest uppercase text-[9px]">Phone:</span> {{ e.phone }}
                  </div>
                </div>
              </TableCell>

              <TableCell>
                <Badge :variant="badgeVariant(e.status)" class="font-black text-[9px] uppercase tracking-widest">{{ e.status }}</Badge>
              </TableCell>

              <TableCell>
                <div class="flex gap-1.5 flex-wrap">
                  <Button variant="ghost" size="icon" as-child title="Chi tiết" class="h-8 w-8 hover:bg-primary/10 hover:text-primary transition-colors">
                    <Link :href="route('sys.enterprises.show', e.id)">
                      <Eye class="w-4 h-4" />
                    </Link>
                  </Button>

                  <template v-if="e.status === 'pending'">
                    <Button variant="ghost" size="icon" @click="approve(e.id)" title="Duyệt" class="h-8 w-8 hover:bg-green-500/10 hover:text-green-500">
                      <CheckCircle class="w-4 h-4" />
                    </Button>
                    <Button variant="ghost" size="icon" @click="openReject(e.id)" title="Từ chối" class="h-8 w-8 hover:bg-destructive/10 hover:text-destructive">
                      <XCircle class="w-4 h-4" />
                    </Button>
                  </template>

                  <Button v-if="e.status === 'approved'" variant="ghost" size="icon" @click="openBlock(e.id)" title="Khóa" class="h-8 w-8 hover:bg-destructive/10 hover:text-destructive">
                    <Lock class="w-4 h-4" />
                  </Button>
                  <Button v-if="e.status === 'blocked'" variant="ghost" size="icon" @click="unblock(e.id)" title="Mở khóa" class="h-8 w-8 hover:bg-green-500/10 hover:text-green-500">
                    <Unlock class="w-4 h-4" />
                  </Button>
                </div>
              </TableCell>
            </TableRow>

            <TableRow v-if="!enterprises.data.length">
              <TableCell :colspan="headers.length" class="h-60 text-center">
                <div class="flex flex-col items-center justify-center text-muted-foreground space-y-4">
                   <div class="p-4 rounded-full bg-muted">
                     <Building2 class="w-8 h-8 opacity-20" />
                   </div>
                   <p class="text-[10px] font-black uppercase tracking-widest opacity-40">Không tìm thấy doanh nghiệp nào.</p>
                </div>
              </TableCell>
            </TableRow>
          </TableBody>
        </Table>
      </CardContent>
    </Card>

    <!-- Pagination -->
    <div v-if="enterprises.last_page > 1" class="flex items-center justify-between">
      <div class="text-[10px] font-black uppercase tracking-widest text-muted-foreground/60">
        Trang <span class="text-foreground">{{ enterprises.current_page }}</span> / {{ enterprises.last_page }}
      </div>
      <div class="flex gap-2">
        <Button variant="outline" size="sm" :disabled="!enterprises.prev_page_url" as-child class="font-black text-[10px] uppercase tracking-widest">
           <Link :href="enterprises.prev_page_url ?? '#'" preserve-state>Trước</Link>
        </Button>
        <Button variant="outline" size="sm" :disabled="!enterprises.next_page_url" as-child class="font-black text-[10px] uppercase tracking-widest">
           <Link :href="enterprises.next_page_url ?? '#'" preserve-state>Sau</Link>
        </Button>
      </div>
    </div>
  </div>


  <!-- Dialog Từ chối -->
  <Dialog :open="!!rejectingId" @update:open="(v) => (!v && (rejectingId = null))">
    <DialogContent>
      <DialogHeader>
        <DialogTitle>Lý do từ chối</DialogTitle>
        <DialogDescription>Cho doanh nghiệp biết lý do hồ sơ của họ không được chấp nhận.</DialogDescription>
      </DialogHeader>
      <div class="py-4">
        <Label for="rejectReason">Nội dung phản hồi *</Label>
        <Input id="rejectReason" v-model="rejectReason" placeholder="VD: Thiếu bản scan Giấy phép kinh doanh..." />
      </div>
      <DialogFooter>
        <Button variant="outline" @click="rejectingId = null">Hủy</Button>
        <Button variant="destructive" @click="submitReject">Xác nhận từ chối</Button>
      </DialogFooter>
    </DialogContent>
  </Dialog>

  <!-- Dialog Khóa -->
  <Dialog :open="!!blockingId" @update:open="(v) => (!v && (blockingId = null))">
    <DialogContent>
      <DialogHeader>
        <DialogTitle>Khóa tài khoản đối tác</DialogTitle>
        <DialogDescription>Doanh nghiệp bị khóa sẽ không thể truy cập hệ thống.</DialogDescription>
      </DialogHeader>
      <div class="py-4">
        <Label for="blockReason">Lý do khóa *</Label>
        <Input id="blockReason" v-model="blockReason" placeholder="VD: Vi phạm điều khoản dịch vụ..." />
      </div>
      <DialogFooter>
        <Button variant="outline" @click="blockingId = null">Hủy</Button>
        <Button variant="destructive" @click="submitBlock">Xác nhận khóa</Button>
      </DialogFooter>
    </DialogContent>
  </Dialog>
</template>
