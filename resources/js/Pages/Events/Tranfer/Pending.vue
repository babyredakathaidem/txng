<script setup>
import { Head, router, useForm, Link } from '@inertiajs/vue3'
import { ref } from 'vue'
import { Card, CardHeader, CardTitle, CardDescription, CardContent, CardFooter } from '@/Components/ui/card/index.js'
import { Button } from '@/Components/ui/button/index.js'
import { Badge } from '@/Components/ui/badge/index.js'
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/Components/ui/dialog/index.js'
import { Label } from '@/Components/ui/label/index.js'
import { Textarea } from '@/Components/ui/textarea/index.js'
import { Separator } from '@/Components/ui/separator/index.js'
import { 
  Download, 
  Upload, 
  Truck, 
  CheckCircle, 
  XCircle,
  Plus,
  Info,
  Archive,
  Clock
} from 'lucide-vue-next'

const props = defineProps({
  outgoing: { type: Array, default: () => [] },
  incoming: { type: Array, default: () => [] },
})

function accept(ev) {
  if (!confirm(`Xác nhận nhận ${ev.batch_count} lô từ ${ev.from_name}?`)) return
  router.post(route('events.transfer.accept', ev.id), {}, {
    preserveScroll: true,
  })
}

const showRejectModal  = ref(false)
const rejectingEvent   = ref(null)
const rejectForm       = useForm({ rejection_reason: '' })

function openReject(ev) {
  rejectingEvent.value = ev
  rejectForm.reset()
  showRejectModal.value = true
}

function submitReject() {
  rejectForm.post(route('events.transfer.reject', rejectingEvent.value.id), {
    onSuccess: () => { showRejectModal.value = false },
    preserveScroll: true,
  })
}

function formatTime(str) {
  if (!str) return '—'
  return new Date(str).toLocaleString('vi-VN', {
    day: '2-digit', month: '2-digit', year: 'numeric',
    hour: '2-digit', minute: '2-digit',
  })
}
</script>

<template>
  <Head title="Quản lý chuyển giao hàng hóa" />

  <div class="max-w-5xl mx-auto space-y-8 pb-20">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-4">
      <div>
        <div class="text-[10px] font-black uppercase tracking-[0.3em] text-primary mb-1">Supply Chain / Distribution</div>
        <h1 class="text-4xl font-black  tracking-tighter text-foreground uppercase">Giao dịch luân chuyển</h1>
        <p class="text-muted-foreground font-medium text-sm mt-1 opacity-70">Quản lý các lệnh bàn giao và tiếp nhận lô hàng trong hệ thống.</p>
      </div>
      <Button size="sm" as-child class="h-12 px-6 rounded-2xl font-black uppercase text-[10px] tracking-widest shadow-xl shadow-primary/20 hover:scale-105 transition-all">
         <Link :href="route('transfer.out.create')">
            <Plus class="w-4 h-4 mr-2" /> Tạo lệnh mới
         </Link>
      </Button>
    </div>

    <div class="grid grid-cols-1 gap-12">
      
      <!-- INCOMING SECTION -->
      <section class="space-y-6">
        <div class="flex items-center justify-between px-2">
           <div class="flex items-center gap-3">
              <div class="p-2.5 rounded-xl bg-emerald-500/10 text-emerald-600 shadow-inner">
                 <Download class="w-5 h-5" />
              </div>
              <h2 class="text-lg font-black uppercase tracking-[0.2em] text-foreground ">Lệnh chờ tiếp nhận</h2>
           </div>
           <Badge v-if="incoming.length" variant="default" class="bg-emerald-600 font-black h-6 text-[10px] uppercase tracking-widest px-3 shadow-lg">{{ incoming.length }} Inbox</Badge>
        </div>

        <div v-if="incoming.length" class="grid grid-cols-1 gap-4">
          <Card v-for="ev in incoming" :key="ev.id" class="border-emerald-500/20 bg-emerald-500/5 hover:bg-emerald-500/10 transition-colors relative overflow-hidden rounded-[2.5rem] shadow-sm">
            <div class="absolute left-0 top-0 bottom-0 w-2 bg-emerald-500"></div>
            <CardContent class="p-8">
              <div class="flex flex-col md:flex-row items-start justify-between gap-8">
                <div class="flex-1 min-w-0 space-y-4">
                  <div class="flex items-center gap-3">
                    <Badge variant="outline" class="font-mono text-[10px] font-black border-emerald-500/30 text-emerald-700 bg-emerald-500/5">{{ ev.event_code }}</Badge>
                    <Badge variant="secondary" class="font-black text-[9px] uppercase tracking-widest">{{ ev.batch_count }} Lô hàng</Badge>
                  </div>

                  <div class="space-y-1">
                    <Label class="text-[9px] font-black uppercase text-muted-foreground/60 tracking-[0.2em]">Từ đối tác phân phối:</Label>
                    <div class="flex items-center gap-2">
                       <span class="text-xl font-black text-foreground  tracking-tight">{{ ev.from_name }}</span>
                       <span v-if="ev.from_code" class="text-xs font-mono font-bold text-muted-foreground opacity-40">({{ ev.from_code }})</span>
                    </div>
                  </div>

                  <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 pt-2">
                     <div v-if="ev.batch_summary" class="space-y-1.5 p-3 rounded-2xl bg-background/50 border border-border/50 shadow-inner">
                        <Label class="text-[8px] font-black uppercase text-muted-foreground/60 tracking-[0.2em] px-1">Danh mục lô hàng</Label>
                        <p class="text-[10px] font-mono text-muted-foreground truncate  px-1">{{ ev.batch_summary }}</p>
                     </div>
                     <div v-if="ev.gs1_document_ref" class="space-y-1.5 p-3 rounded-2xl bg-background/50 border border-border/50 shadow-inner">
                        <Label class="text-[8px] font-black uppercase text-muted-foreground/60 tracking-[0.2em] px-1">Chứng từ / HĐ tham chiếu</Label>
                        <p class="text-[11px] font-black text-primary px-1 tracking-tighter">{{ ev.gs1_document_ref }}</p>
                     </div>
                  </div>
                  
                  <div class="flex items-center gap-2 text-[10px] text-muted-foreground font-black uppercase tracking-widest pt-2">
                     <Clock class="w-3.5 h-3.5 opacity-40" /> {{ formatTime(ev.event_time) }}
                  </div>
                </div>

                <div class="flex flex-row md:flex-col gap-3 shrink-0 w-full md:w-48">
                  <Button @click="accept(ev)" class="flex-1 h-14 rounded-2xl font-black uppercase tracking-[0.2em] text-[10px] bg-emerald-600 hover:bg-emerald-700 shadow-xl shadow-emerald-500/30 active:scale-95 transition-all">
                    <CheckCircle class="w-4 h-4 mr-2" /> Nhận hàng
                  </Button>
                  <Button variant="outline" @click="openReject(ev)" class="flex-1 h-12 rounded-2xl font-black uppercase tracking-widest text-[10px] text-muted-foreground hover:text-destructive hover:bg-destructive/10 border-dashed border-2 hover:border-destructive/30 transition-all">
                    <XCircle class="w-4 h-4 mr-2" /> Từ chối
                  </Button>
                </div>
              </div>
            </CardContent>
          </Card>
        </div>

        <div v-else class="flex flex-col items-center justify-center py-24 border-2 border-dashed border-border/50 rounded-[2.5rem] bg-muted/5 text-muted-foreground ">
           <Archive class="w-16 h-16 opacity-10 mb-4" />
           <p class="text-[10px] uppercase font-black tracking-[0.3em] opacity-40">Không có lệnh chờ tiếp nhận</p>
        </div>
      </section>

      <!-- OUTGOING SECTION -->
      <section class="space-y-6">
        <div class="flex items-center justify-between px-2">
           <div class="flex items-center gap-3 opacity-60">
              <div class="p-2.5 rounded-xl bg-primary/10 text-primary shadow-inner">
                 <Upload class="w-5 h-5" />
              </div>
              <h2 class="text-lg font-black uppercase tracking-[0.2em] text-foreground ">Lệnh đã phát đi (Pending)</h2>
           </div>
           <Badge v-if="outgoing.length" variant="outline" class="font-black h-6 text-[10px] uppercase tracking-widest px-3">{{ outgoing.length }} Sent</Badge>
        </div>

        <div v-if="outgoing.length" class="grid grid-cols-1 gap-4 opacity-80 hover:opacity-100 transition-opacity duration-500">
          <Card v-for="ev in outgoing" :key="ev.id" class="border-dashed border-2 border-primary/20 bg-muted/10 relative overflow-hidden rounded-[2.5rem]">
            <CardContent class="p-8">
              <div class="flex flex-col md:flex-row items-center justify-between gap-8">
                <div class="flex-1 min-w-0 space-y-4 w-full">
                  <div class="flex items-center gap-3">
                    <Badge variant="outline" class="font-mono text-[10px] font-black border-primary/30 text-primary bg-background shadow-sm">{{ ev.event_code }}</Badge>
                    <Badge variant="secondary" class="font-black text-[9px] uppercase tracking-widest bg-background shadow-sm">{{ ev.batch_count }} Lô hàng</Badge>
                    <div class="flex items-center gap-1.5 text-[9px] font-black text-amber-600 uppercase tracking-widest bg-amber-500/10 px-2 py-1 rounded-md">
                       <div class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></div>
                       Waiting Partner
                    </div>
                  </div>

                  <div class="space-y-1">
                    <Label class="text-[9px] font-black uppercase text-muted-foreground/60 tracking-[0.2em]">Gửi đến đối tác:</Label>
                    <div class="flex items-center gap-2">
                       <span class="text-xl font-black text-foreground tracking-tight ">{{ ev.to_name }}</span>
                       <span v-if="ev.to_code" class="text-xs font-mono font-bold text-muted-foreground opacity-40">({{ ev.to_code }})</span>
                    </div>
                  </div>

                  <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 pt-2">
                     <div v-if="ev.batch_summary" class="space-y-1.5 p-3 rounded-2xl bg-background border border-border/50">
                        <Label class="text-[8px] font-black uppercase text-muted-foreground/60 tracking-[0.2em] px-1">Nội dung kiện hàng</Label>
                        <p class="text-[10px] font-mono text-muted-foreground truncate px-1">{{ ev.batch_summary }}</p>
                     </div>
                     <div v-if="ev.gs1_document_ref" class="space-y-1.5 p-3 rounded-2xl bg-background border border-border/50">
                        <Label class="text-[8px] font-black uppercase text-muted-foreground/60 tracking-[0.2em] px-1">Tham chiếu vận đơn</Label>
                        <p class="text-[11px] font-black px-1 tracking-tighter">{{ ev.gs1_document_ref }}</p>
                     </div>
                  </div>
                  
                  <div class="flex items-center gap-2 text-[10px] text-muted-foreground font-black uppercase tracking-widest pt-2 opacity-60">
                     <Clock class="w-3.5 h-3.5" /> {{ formatTime(ev.event_time) }}
                  </div>
                </div>

                <div class="shrink-0 flex items-center justify-center p-6 rounded-[2rem] bg-background border border-dashed border-border/50 shadow-inner">
                   <Truck class="w-12 h-12 text-muted-foreground opacity-20 animate-bounce" />
                </div>
              </div>
            </CardContent>
          </Card>
        </div>

        <div v-else class="flex flex-col items-center justify-center py-16 text-muted-foreground ">
           <p class="text-[10px] font-black uppercase tracking-[0.3em] opacity-40">Không có lệnh gửi đi đang chờ</p>
        </div>
      </section>
    </div>

    <!-- REJECT DIALOG -->
    <Dialog :open="showRejectModal" @update:open="(v) => (!v && (showRejectModal = false))">
      <DialogContent class="sm:max-w-[500px]">
        <DialogHeader>
          <DialogTitle class="text-destructive flex items-center gap-3  uppercase font-black text-xl tracking-tighter">
             <XCircle class="w-6 h-6" />
             Từ chối tiếp nhận hàng
          </DialogTitle>
          <DialogDescription class="font-medium mt-2">
            Thông báo từ chối và lý do sẽ được gửi ngược lại cho <span class="font-black text-foreground">{{ rejectingEvent?.from_name }}</span>.
          </DialogDescription>
        </DialogHeader>

        <div class="space-y-6 py-6">
          <div class="space-y-3">
            <Label class="text-[10px] font-black uppercase tracking-[0.2em] px-1">Lý do từ chối *</Label>
            <Textarea
              v-model="rejectForm.rejection_reason"
              placeholder="Ví dụ: Lô hàng không đạt cảm quan, thiếu chứng từ, sai số lượng (tối thiểu 10 ký tự)..."
              rows="5"
              class="h-32 bg-muted/20 rounded-2xl shadow-inner font-medium  placeholder:opacity-50"
            />
            <p v-if="rejectForm.errors.rejection_reason" class="text-[10px] font-black text-destructive uppercase tracking-widest px-1">{{ rejectForm.errors.rejection_reason }}</p>
          </div>
          <div class="p-4 rounded-2xl bg-muted/30 border border-dashed flex gap-3 shadow-inner">
             <Info class="w-5 h-5 text-muted-foreground shrink-0 mt-0.5 opacity-60" />
             <p class="text-[10px] text-muted-foreground font-bold leading-relaxed uppercase tracking-widest  opacity-80">Hành động này sẽ hủy bỏ lệnh chuyển giao hiện tại. Các lô hàng sẽ quay trở lại trạng thái khả dụng trong kho của bên gửi.</p>
          </div>
        </div>

        <DialogFooter>
          <Button variant="ghost" @click="showRejectModal = false" class="font-black uppercase tracking-widest text-[10px] h-12 px-6 rounded-xl hover:bg-muted">Đóng</Button>
          <Button 
            variant="destructive" 
            @click="submitReject"
            :disabled="rejectForm.processing || rejectForm.rejection_reason.length < 10"
            class="font-black uppercase tracking-[0.2em] text-[10px] px-8 h-12 rounded-xl shadow-lg shadow-destructive/20 active:scale-95 transition-all"
          >
            {{ rejectForm.processing ? 'ĐANG GỬI...' : 'XÁC NHẬN TỪ CHỐI' }}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  </div>
</template>
