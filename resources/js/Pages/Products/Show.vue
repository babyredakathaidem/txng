<script setup>
import { Head, Link, router } from '@inertiajs/vue3'
import { ref, computed } from 'vue'
import axios from 'axios'
import { Card, CardHeader, CardTitle, CardDescription, CardContent, CardFooter } from '@/Components/ui/card/index.js'
import { Button } from '@/Components/ui/button/index.js'
import { Badge } from '@/Components/ui/badge/index.js'
import { Input } from '@/Components/ui/input/index.js'
import { Label } from '@/Components/ui/label/index.js'
import { Checkbox } from '@/Components/ui/checkbox/index.js'
import { Separator } from '@/Components/ui/separator/index.js'
import { 
  ArrowLeft, 
  Plus, 
  Archive, 
  ChevronUp, 
  ChevronDown, 
  Trash2, 
  CheckCircle,
  AlertTriangle,
  Info,
  ClipboardList,
  QrCode,
  RefreshCw
} from 'lucide-vue-next'

const props = defineProps({
  product: { type: Object, required: true },
})

const p = props.product

const statusLabel = (s) => s === 'active' ? 'Hoạt động' : 'Ẩn'
const statusVariant = (s) => s === 'active' ? 'default' : 'secondary'

const batchStatusLabel = (s) => ({ active: 'Hoạt động', completed: 'Hoàn thành', recalled: 'Thu hồi' }[s] ?? s)
const batchStatusVariant = (s) => ({ active: 'default', completed: 'secondary', recalled: 'destructive' }[s] ?? 'outline')

// ── Quy trình sản xuất ────────────────────────────────────
const steps = ref((p.processes ?? []).map((s, i) => ({ ...s, _tmp_id: i })))
const saving = ref(false)
const saveMsg = ref('')

const defaultStep = () => ({
  _tmp_id: Date.now(),
  id: null,
  step_order: steps.value.length + 1,
  name_vi: '',
  cte_code: '',
  description: '',
  is_required: true,
})

function addStep() {
  steps.value.push(defaultStep())
  reorder()
}

function removeStep(idx) {
  steps.value.splice(idx, 1)
  reorder()
}

function moveUp(idx) {
  if (idx === 0) return
  ;[steps.value[idx - 1], steps.value[idx]] = [steps.value[idx], steps.value[idx - 1]]
  reorder()
}

function moveDown(idx) {
  if (idx === steps.value.length - 1) return
  ;[steps.value[idx], steps.value[idx + 1]] = [steps.value[idx + 1], steps.value[idx]]
  reorder()
}

function reorder() {
  steps.value.forEach((s, i) => (s.step_order = i + 1))
}

async function saveProcesses() {
  saving.value = true
  saveMsg.value = ''
  try {
    await axios.post(route('products.processes.sync', p.id), { steps: steps.value })
    saveMsg.value = 'Đã lưu quy trình thành công!'
    setTimeout(() => (saveMsg.value = ''), 3000)
  } catch (e) {
    saveMsg.value = 'Lỗi khi lưu: ' + (e.response?.data?.message ?? e.message)
  } finally {
    saving.value = false
  }
}
</script>

<template>
  <Head :title="`Sản phẩm: ${p.name}`" />

  <div class="max-w-6xl mx-auto space-y-8 pb-20">
    <!-- Header -->
    <div class="flex items-start justify-between flex-wrap gap-6">
      <div class="space-y-3">
        <nav class="flex items-center gap-2 text-[10px] font-black uppercase tracking-[0.3em] text-muted-foreground/60 bg-muted/20 w-fit px-4 py-1.5 rounded-full border border-border/50">
          <Link :href="route('products.index')" class="hover:text-primary transition-colors">Danh mục sản phẩm</Link>
          <span>/</span>
          <span class="text-foreground ">{{ p.name }}</span>
        </nav>
        <div class="flex items-center gap-5">
           <div class="text-4xl shrink-0 grayscale hover:grayscale-0 transition-all duration-500 bg-card p-2 rounded-2xl shadow-sm border border-border/50">{{ p.category?.icon }}</div>
           <div>
              <h1 class="text-4xl font-black tracking-tighter text-foreground uppercase">{{ p.name }}</h1>
              <div class="flex items-center gap-3 mt-1.5">
                 <Badge :variant="statusVariant(p.status)" class="text-[9px] font-black uppercase tracking-widest">{{ statusLabel(p.status) }}</Badge>
                 <Separator orientation="vertical" class="h-4" />
                 <span class="text-[10px] font-bold text-muted-foreground uppercase tracking-[0.2em]">{{ p.category?.name_vi ?? 'N/A' }}</span>
              </div>
           </div>
        </div>
      </div>

      <div class="flex items-center gap-3 pt-4">
        <Button variant="outline" size="sm" as-child class="font-black uppercase text-[9px] tracking-widest h-10 px-5 rounded-xl hover:bg-muted transition-all">
           <Link :href="route('products.index')">
              <ArrowLeft class="w-4 h-4 mr-2" /> Quay lại
           </Link>
        </Button>
        <Button size="sm" as-child class="font-black uppercase text-[9px] tracking-[0.2em] h-10 px-6 rounded-xl shadow-xl shadow-primary/20 hover:scale-105 transition-all">
           <Link :href="route('batches.index', { product_id: p.id })">
              <Plus class="w-4 h-4 mr-2" /> Tạo lô hàng
           </Link>
        </Button>
      </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
      
      <!-- Cột Trái: Thông tin & Lô hàng -->
      <div class="lg:col-span-7 space-y-6">
        
        <!-- Info Card -->
        <Card class="border-border/50 bg-card/50 backdrop-blur-sm overflow-hidden rounded-[2.5rem] shadow-sm hover:shadow-xl transition-all duration-500">
          <div class="grid grid-cols-1 md:grid-cols-5 h-full">
             <div class="md:col-span-2 bg-muted/30 flex items-center justify-center border-r border-border/30 min-h-[250px] relative overflow-hidden group">
                <div class="absolute inset-0 bg-primary/5 opacity-0 group-hover:opacity-100 transition-opacity duration-500 z-0"></div>
                <img v-if="p.image_path" :src="`/storage/${p.image_path}`" class="w-full h-full object-cover relative z-10 group-hover:scale-110 transition-transform duration-700" :alt="p.name" />
                <div v-else class="flex flex-col items-center gap-3 opacity-30 text-muted-foreground  relative z-10">
                   <Archive class="w-12 h-12 stroke-[1.5px]" />
                   <span class="text-[9px] uppercase font-black tracking-[0.3em]">No Image</span>
                </div>
             </div>
             <div class="md:col-span-3 p-8 space-y-8 flex flex-col justify-center">
                <div class="grid grid-cols-2 gap-8">
                   <div class="space-y-1.5">
                      <Label class="text-[9px] font-black uppercase text-muted-foreground/60 tracking-[0.2em]">Mã GTIN</Label>
                      <p class="font-mono text-sm font-black tracking-tighter text-foreground">{{ p.gtin || '—' }}</p>
                   </div>
                   <div class="space-y-1.5">
                      <Label class="text-[9px] font-black uppercase text-muted-foreground/60 tracking-[0.2em]">Đơn vị tính</Label>
                      <p class="text-sm font-black uppercase tracking-tighter text-foreground">{{ p.unit || '—' }}</p>
                   </div>
                   <div class="space-y-1.5">
                      <Label class="text-[9px] font-black uppercase text-muted-foreground/60 tracking-[0.2em]">Sản lượng liên kết</Label>
                      <p class="text-sm font-black tracking-tight text-foreground">{{ p.batches?.length ?? 0 }} <span class="text-[10px] text-muted-foreground uppercase tracking-widest ml-1">Lô hàng</span></p>
                   </div>
                   <div class="space-y-1.5">
                      <Label class="text-[9px] font-black uppercase text-muted-foreground/60 tracking-[0.2em]">Quy chuẩn</Label>
                      <p class="text-sm font-black tracking-tight text-primary">{{ steps.length }} <span class="text-[10px] text-primary/60 uppercase tracking-widest ml-1">Bước CTE</span></p>
                   </div>
                </div>
                
                <Separator border-dashed class="opacity-50" />
                
                <div class="space-y-2">
                   <Label class="text-[9px] font-black uppercase text-muted-foreground/60 tracking-[0.2em]">Mô tả sản phẩm</Label>
                   <p class="text-[11px] font-bold text-muted-foreground leading-relaxed  line-clamp-3">{{ p.description || 'Chưa có mô tả chi tiết.' }}</p>
                </div>
             </div>
          </div>
        </Card>

        <!-- Batches List -->
        <Card class="border-border/50 bg-card/50 backdrop-blur-sm overflow-hidden rounded-[2.5rem] shadow-sm">
          <CardHeader class="border-b bg-muted/30 py-5 px-8">
            <div class="flex items-center justify-between w-full">
               <div class="flex items-center gap-4">
                  <div class="p-2.5 rounded-xl bg-background border border-border/50 text-muted-foreground shadow-sm">
                    <Archive class="w-5 h-5" />
                  </div>
                  <CardTitle class="text-xs font-black uppercase tracking-[0.3em] ">Các lô hàng đã khởi tạo</CardTitle>
               </div>
               <Badge variant="secondary" class="font-black text-[10px] uppercase tracking-widest px-3 py-1 bg-background">{{ p.batches?.length ?? 0 }} Lô</Badge>
            </div>
          </CardHeader>
          <CardContent class="p-0">
            <div class="overflow-y-auto max-h-[400px]">
              <div v-if="p.batches?.length" class="divide-y divide-border/50">
                <div v-for="b in p.batches" :key="b.id"
                  class="flex flex-col sm:flex-row sm:items-center justify-between p-6 hover:bg-muted/30 transition-colors group gap-4">
                  <div class="space-y-2">
                    <div class="font-mono text-base font-black text-primary tracking-tighter uppercase cursor-pointer hover:underline">{{ b.code }}</div>
                    <div class="flex items-center gap-3 text-[9px] font-black text-muted-foreground/60 uppercase tracking-widest">
                       <span v-if="b.production_date">SX: <span class="text-foreground">{{ b.production_date }}</span></span>
                       <span v-if="b.production_date && b.expiry_date">•</span>
                       <span v-if="b.expiry_date">HSD: <span class="text-foreground">{{ b.expiry_date }}</span></span>
                    </div>
                  </div>
                  <div class="flex items-center gap-5 sm:justify-end">
                    <div class="text-right hidden sm:block">
                       <p class="text-[8px] font-black uppercase tracking-[0.2em] text-muted-foreground/40 mb-1">Published</p>
                       <p class="text-xs font-black tracking-tight">{{ b.events_count }} <span class="text-[9px] uppercase tracking-widest text-muted-foreground">Events</span></p>
                    </div>
                    <Badge :variant="batchStatusVariant(b.status)" class="text-[9px] font-black uppercase tracking-widest h-6 rounded-full px-3">
                       {{ batchStatusLabel(b.status) }}
                    </Badge>
                    <div class="flex gap-2">
                      <Button variant="outline" size="icon" as-child class="h-9 w-9 rounded-xl text-primary border-primary/20 hover:bg-primary hover:text-white transition-all shadow-sm" title="Ghi dữ liệu">
                        <Link :href="route('events.index', { batch_id: b.id })">
                          <ClipboardList class="w-4 h-4" />
                        </Link>
                      </Button>
                      <Button variant="ghost" size="icon" as-child class="h-9 w-9 rounded-xl hover:bg-muted transition-all" title="Mã QR">
                        <a :href="route('batches.qrs', b.id)">
                          <QrCode class="w-4 h-4" />
                        </a>
                      </Button>
                    </div>
                  </div>
                </div>
              </div>
              <div v-else class="flex flex-col items-center justify-center py-24 text-muted-foreground">
                <Info class="w-10 h-10 opacity-20 mb-4" />
                <p class="text-[10px] font-black uppercase tracking-widest opacity-40">Chưa có lô hàng liên kết</p>
                <Button variant="link" as-child class="mt-4 text-primary font-black uppercase text-[10px] tracking-widest hover:scale-105 transition-transform">
                   <Link :href="route('batches.index', { product_id: p.id })">Khởi tạo lô đầu tiên →</Link>
                </Button>
              </div>
            </div>
          </CardContent>
        </Card>
      </div>

      <!-- Cột Phải: Quy trình sản xuất -->
      <div class="lg:col-span-5 space-y-6">
        <Card class="border-primary/20 bg-card/50 backdrop-blur-xl shadow-2xl shadow-primary/10 rounded-[2.5rem] overflow-hidden sticky top-8">
          <CardHeader class="border-b bg-primary/5 py-5 px-8">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between w-full gap-4">
              <div class="flex items-center gap-4">
                <div class="p-2.5 rounded-xl bg-primary/10 text-primary shadow-inner">
                  <RefreshCw class="w-5 h-5" />
                </div>
                <div>
                   <CardTitle class="text-sm font-black uppercase tracking-widest text-primary ">Quy trình sản xuất</CardTitle>
                   <CardDescription class="text-[9px] font-black uppercase tracking-[0.2em] text-primary/40 mt-0.5">Định nghĩa các bước CTE</CardDescription>
                </div>
              </div>
              <div class="flex gap-2 shrink-0">
                <Button variant="outline" size="sm" @click="addStep" class="h-9 px-4 text-[9px] font-black uppercase tracking-widest border-primary/20 hover:bg-primary/10 text-primary rounded-xl transition-all">
                  <Plus class="w-3.5 h-3.5 mr-1" /> Thêm
                </Button>
                <Button size="sm" @click="saveProcesses" :disabled="saving" class="h-9 px-4 text-[9px] font-black uppercase tracking-widest shadow-lg shadow-primary/20 rounded-xl transition-all">
                  {{ saving ? 'Saving...' : 'Lưu quy trình' }}
                </Button>
              </div>
            </div>
          </CardHeader>
          <CardContent class="p-0">
            <div v-if="saveMsg" class="px-8 pt-6 pb-2">
               <div :class="saveMsg.startsWith('Lỗi') ? 'bg-destructive/5 text-destructive border-destructive/20' : 'bg-emerald-500/5 text-emerald-600 border-emerald-500/20'"
                 class="px-4 py-3 rounded-2xl border text-[10px] font-black uppercase tracking-widest flex items-center gap-3 shadow-inner">
                 <component :is="saveMsg.startsWith('Lỗi') ? AlertTriangle : CheckCircle" class="w-4 h-4" />
                 {{ saveMsg }}
               </div>
            </div>

            <div class="overflow-y-auto max-h-[500px] p-8 pt-4">
              <div v-if="steps.length === 0" class="flex flex-col items-center justify-center py-24 text-muted-foreground border-2 border-dashed rounded-[2rem] bg-background/50 border-border/50 opacity-60">
                <p class="text-[10px] uppercase font-black tracking-[0.2em]">Quy trình chưa được thiết lập</p>
              </div>

              <div v-else class="space-y-5 relative">
                <div class="absolute left-[38px] top-6 bottom-6 w-0.5 bg-border/50 rounded-full z-0 hidden sm:block"></div>
                <div v-for="(step, idx) in steps" :key="step._tmp_id"
                  class="rounded-[2rem] border bg-background/50 p-6 group relative transition-all duration-300 shadow-sm hover:shadow-xl hover:border-primary/40 z-10 backdrop-blur-sm">
                  
                  <div class="flex flex-col sm:flex-row sm:items-start gap-6">
                    <!-- Step Control -->
                    <div class="flex sm:flex-col items-center justify-between sm:justify-start gap-2 shrink-0 bg-muted/30 sm:bg-transparent p-2 sm:p-0 rounded-2xl">
                      <Button variant="ghost" size="icon" @click="moveUp(idx)" :disabled="idx === 0" class="h-8 w-8 text-muted-foreground hover:text-primary rounded-xl">
                         <ChevronUp class="w-5 h-5" />
                      </Button>
                      <div class="w-10 h-10 rounded-2xl bg-primary/10 border-2 border-primary/20 flex items-center justify-center text-primary font-black text-lg  shadow-inner">
                        {{ step.step_order }}
                      </div>
                      <Button variant="ghost" size="icon" @click="moveDown(idx)" :disabled="idx === steps.length - 1" class="h-8 w-8 text-muted-foreground hover:text-primary rounded-xl">
                         <ChevronDown class="w-5 h-5" />
                      </Button>
                    </div>

                    <!-- Step Fields -->
                    <div class="flex-1 space-y-5 w-full">
                      <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div class="space-y-2">
                          <Label class="text-[9px] font-black uppercase text-muted-foreground/60 tracking-[0.2em] px-1">Tên bước thực hiện *</Label>
                          <Input v-model="step.name_vi" class="h-11 text-xs font-black tracking-tight rounded-xl shadow-inner bg-card/50" placeholder="VD: Thu hoạch lúa..." required />
                        </div>
                        <div class="space-y-2">
                          <Label class="text-[9px] font-black uppercase text-muted-foreground/60 tracking-[0.2em] px-1">CTE Code (Tùy chọn)</Label>
                          <Input v-model="step.cte_code" class="h-11 text-xs font-mono font-bold rounded-xl shadow-inner bg-card/50" placeholder="growing, harvesting..." />
                        </div>
                      </div>
                      
                      <div class="space-y-2">
                        <Label class="text-[9px] font-black uppercase text-muted-foreground/60 tracking-[0.2em] px-1">Mô tả quy chuẩn</Label>
                        <Input v-model="step.description" class="h-11 text-xs font-medium  rounded-xl shadow-inner bg-card/50 placeholder:opacity-40" placeholder="Ghi chú kỹ thuật hoặc tiêu chuẩn cần đạt..." />
                      </div>

                      <div class="flex flex-wrap items-center justify-between gap-4 pt-3 border-t border-dashed border-border/50">
                         <div class="flex items-center space-x-3">
                            <Checkbox :id="`req-${idx}`" :checked="step.is_required" @update:checked="v => step.is_required = v" class="data-[state=checked]:bg-primary" />
                            <Label :for="`req-${idx}`" class="text-[9px] font-black uppercase tracking-[0.2em] cursor-pointer text-muted-foreground group-hover:text-foreground transition-colors">Bắt buộc ghi nhận</Label>
                         </div>
                         <Button variant="ghost" size="sm" @click="removeStep(idx)" class="h-8 px-3 rounded-lg text-[9px] font-black uppercase tracking-widest text-muted-foreground hover:bg-destructive/10 hover:text-destructive transition-colors">
                            <Trash2 class="w-3.5 h-3.5 mr-1.5" /> Xóa
                         </Button>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </CardContent>
          <CardFooter class="border-t bg-primary/5 py-5 px-8">
             <div class="flex items-start gap-3 text-[9px] text-primary/60 font-black uppercase tracking-widest  leading-relaxed">
                <Info class="w-4 h-4 shrink-0 mt-0.5 opacity-50" />
                Khi lô hàng được tạo từ sản phẩm này, hệ thống sẽ tự động tạo sẵn các bản ghi "Draft" tương ứng với quy trình trên để nhân viên ghi dữ liệu thuận tiện.
             </div>
          </CardFooter>
        </Card>
      </div>

    </div>
  </div>
</template>
