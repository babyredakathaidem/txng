<script setup>
import { Head, useForm, Link } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import { Card, CardHeader, CardTitle, CardDescription, CardContent, CardFooter } from '@/Components/ui/card/index.js'
import { Button } from '@/Components/ui/button/index.js'
import { Input } from '@/Components/ui/input/index.js'
import { Label } from '@/Components/ui/label/index.js'
import { Badge } from '@/Components/ui/badge/index.js'
import { Separator } from '@/Components/ui/separator/index.js'
import { 
  ArrowsUpFromLine, 
  CheckCircle, 
  AlertTriangle,
  ArrowLeft,
  Archive,
  Info
} from 'lucide-vue-next'

const props = defineProps({
  availableBatches: { type: Array, default: () => [] },
})

const selectedIds = ref([])

const form = useForm({
  input_batch_ids:     [],
  output_product_name: '',
})

// ── Computed ──────────────────────────────────────────────

const selectedBatches = computed(() =>
  props.availableBatches.filter(b => selectedIds.value.includes(b.id))
)

const totalQty = computed(() =>
  selectedBatches.value.reduce((s, b) => s + (parseFloat(b.current_quantity) || 0), 0)
)

const units = computed(() =>
  [...new Set(selectedBatches.value.map(b => b.unit).filter(Boolean))]
)
const commonUnit = computed(() => units.value.length === 1 ? units.value[0] : '')
const unitError  = computed(() => {
  if (selectedBatches.value.length < 2) return null
  if (units.value.length > 1)
    return `Các lô có đơn vị khác nhau: ${units.value.join(', ')}. Chỉ gộp được lô cùng đơn vị.`
  return null
})

const commonCerts = computed(() => {
  if (selectedBatches.value.length < 2) return []
  return selectedBatches.value
    .map(b => b.certifications ?? [])
    .reduce((a, b) => a.filter(c => b.includes(c)))
})

const certWarning = computed(() => {
  if (selectedBatches.value.length < 2) return null
  const hasCerts = selectedBatches.value.some(b => (b.certifications ?? []).length > 0)
  if (!hasCerts) return null
  if (commonCerts.value.length === 0)
    return 'Các lô không có chứng chỉ chung. Lô mới sẽ không được kế thừa chứng chỉ nào.'
  return null
})

const canSubmit = computed(() =>
  !form.processing &&
  selectedIds.value.length >= 2 &&
  !unitError.value &&
  form.output_product_name.trim() !== ''
)

function toggleBatch(batch) {
  const idx = selectedIds.value.indexOf(batch.id)
  if (idx >= 0) selectedIds.value.splice(idx, 1)
  else selectedIds.value.push(batch.id)
}

function submit() {
  form.input_batch_ids = [...selectedIds.value]
  form.post(route('batches.merge.store'))
}
</script>

<template>
  <Head title="Nghiệp vụ gộp lô hàng" />

  <div class="max-w-5xl mx-auto space-y-8 pb-20">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-4">
      <div class="flex items-center gap-4">
        <Button variant="ghost" size="icon" as-child class="h-10 w-10 shrink-0 hover:bg-primary/10 hover:text-primary transition-all rounded-full border border-border/50">
           <Link :href="route('batches.index')">
              <ArrowLeft class="w-5 h-5" />
           </Link>
        </Button>
        <div>
          <p class="text-[10px] font-black uppercase tracking-[0.3em] text-primary mb-1">Inventory / Operations</p>
          <h1 class="text-4xl font-black tracking-tighter text-foreground uppercase ">Gộp lô sản phẩm</h1>
          <p class="text-muted-foreground font-medium text-sm mt-1 opacity-70">Hợp nhất nhiều lô hàng hiện có thành một lô mới với định danh mới.</p>
        </div>
      </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
      <!-- Selection Area -->
      <div class="lg:col-span-7 space-y-6">
        <Card class="border-border/50 bg-card/50 backdrop-blur-sm overflow-hidden rounded-[2.5rem] shadow-sm">
          <CardHeader class="border-b bg-muted/30 py-5 px-8">
            <div class="flex items-center justify-between">
               <div class="flex items-center gap-3">
                  <div class="p-2.5 rounded-xl bg-background border border-border/50 text-muted-foreground shadow-sm">
                    <Archive class="w-5 h-5" />
                  </div>
                  <div>
                    <CardTitle class="text-xs font-black uppercase tracking-[0.3em] text-foreground ">Danh sách lô khả dụng</CardTitle>
                    <CardDescription class="text-[9px] font-black uppercase tracking-widest mt-0.5 opacity-60">Chọn ít nhất 2 lô hàng để thực hiện gộp</CardDescription>
                  </div>
               </div>
               <Badge variant="secondary" class="font-black text-[9px] uppercase tracking-widest px-3 py-1 bg-background shadow-inner">{{ selectedIds.length }} Đã chọn</Badge>
            </div>
          </CardHeader>
          <CardContent class="p-0">
            <div class="overflow-y-auto max-h-[500px] p-6">
              <div class="space-y-4">
                <button
                  v-for="b in availableBatches"
                  :key="b.id"
                  type="button"
                  @click="toggleBatch(b)"
                  class="w-full flex items-start justify-between p-5 rounded-3xl border transition-all duration-300 group relative overflow-hidden text-left"
                  :class="selectedIds.includes(b.id)
                    ? 'border-primary bg-primary/5 ring-4 ring-primary/10 shadow-xl'
                    : 'border-border/50 bg-card hover:border-primary/40 hover:bg-muted/50'"
                >
                  <div class="flex-1 min-w-0 space-y-2 relative z-10">
                    <div class="flex items-center gap-3">
                      <span class="font-mono text-sm font-black  tracking-tighter uppercase" :class="selectedIds.includes(b.id) ? 'text-primary' : 'text-foreground'">{{ b.code }}</span>
                      <CheckCircle v-if="selectedIds.includes(b.id)" class="w-4 h-4 text-primary animate-in zoom-in duration-300" />
                    </div>
                    <div class="text-[10px] font-black text-muted-foreground/60 truncate uppercase tracking-widest group-hover:text-foreground/40 transition-colors">{{ b.product_name }}</div>

                    <div v-if="b.certifications?.length" class="flex flex-wrap gap-1 mt-1">
                      <Badge
                        v-for="cert in b.certifications"
                        :key="cert"
                        variant="secondary"
                        class="text-[8px] h-4 font-black uppercase tracking-widest border border-border/50 shadow-sm"
                        :class="selectedIds.includes(b.id) && commonCerts.includes(cert) ? 'bg-primary text-primary-foreground border-primary' : ''"
                      >{{ cert }}</Badge>
                    </div>
                  </div>

                  <div class="text-right shrink-0 ml-4 relative z-10">
                    <div class="text-2xl font-black text-foreground tracking-tighter group-hover:text-primary transition-colors">{{ b.current_quantity }}</div>
                    <div class="text-[10px] font-black uppercase text-muted-foreground tracking-[0.2em] opacity-40">{{ b.unit }}</div>
                  </div>
                </button>

                <div v-if="!availableBatches.length" class="flex flex-col items-center justify-center py-24 text-muted-foreground border-2 border-dashed rounded-[2rem] bg-background/50 border-border/50 opacity-60">
                   <Archive class="w-12 h-12 mb-4 opacity-20" />
                   <p class="text-[10px] uppercase font-black tracking-[0.2em]">Không có lô hàng khả dụng</p>
                </div>
              </div>
            </div>
          </CardContent>
        </Card>
      </div>

      <!-- Result/Config Area -->
      <div class="lg:col-span-5 space-y-6">
        <Card class="border-primary/20 bg-card/50 backdrop-blur-xl shadow-2xl shadow-primary/10 rounded-[2.5rem] overflow-hidden sticky top-8">
          <CardHeader class="border-b bg-primary/5 py-5 px-8">
             <CardTitle class="text-[10px] font-black uppercase tracking-[0.3em] text-primary  flex items-center gap-2">
                <ArrowsUpFromLine class="w-4 h-4" /> Cấu hình lô đầu ra
             </CardTitle>
          </CardHeader>
          <CardContent class="pt-8 space-y-8 px-8">
            <div class="space-y-3">
              <div class="flex items-center justify-between px-1">
                <Label class="text-[10px] font-black uppercase tracking-[0.2em] text-muted-foreground/80">Tên sản phẩm sau gộp *</Label>
                <p v-if="form.errors.output_product_name" class="text-[9px] font-black text-destructive uppercase">{{ form.errors.output_product_name }}</p>
              </div>
              <Input
                v-model="form.output_product_name"
                placeholder="VD: Gạo ST25 xay xát tổng hợp"
                class="h-12 font-black rounded-2xl shadow-inner bg-background/50 border-border/50 focus:ring-primary/20"
              />
            </div>

            <div class="grid grid-cols-2 gap-6">
              <div class="p-4 rounded-3xl bg-muted/30 border border-border/50 space-y-1 shadow-inner group">
                <Label class="text-[9px] font-black uppercase text-muted-foreground/60 tracking-widest">Tổng số lượng</Label>
                <p class="text-xl font-black tracking-tighter transition-colors" :class="selectedBatches.length < 2 ? 'text-muted-foreground/30' : 'text-primary group-hover:scale-105 origin-left'">
                   {{ selectedBatches.length >= 2 ? totalQty : '—' }}
                </p>
              </div>
              <div class="p-4 rounded-3xl bg-muted/30 border border-border/50 space-y-1 shadow-inner group">
                <Label class="text-[9px] font-black uppercase text-muted-foreground/60 tracking-widest">Đơn vị tính</Label>
                <p class="text-xl font-black uppercase tracking-tighter transition-colors" :class="!commonUnit ? 'text-muted-foreground/30' : 'text-foreground'">
                   {{ commonUnit || '—' }}
                </p>
              </div>
            </div>

            <div class="space-y-3">
              <Label class="text-[10px] font-black uppercase text-muted-foreground tracking-[0.3em] px-1 opacity-60">Chứng chỉ kế thừa (Shared)</Label>
              <div class="min-h-[60px] p-4 rounded-3xl bg-muted/20 border border-border/50 flex items-center shadow-inner">
                <div v-if="selectedBatches.length >= 2 && commonCerts.length" class="flex flex-wrap gap-2">
                  <Badge
                    v-for="cert in commonCerts"
                    :key="cert"
                    class="bg-emerald-500/10 text-emerald-600 border-emerald-500/20 font-black text-[9px] uppercase tracking-widest px-3 py-1 rounded-full shadow-sm"
                  >✓ {{ cert }}</Badge>
                </div>
                <p v-else class="text-[10px] font-bold text-muted-foreground/40  uppercase tracking-widest px-2">
                   {{ selectedBatches.length < 2 ? 'Chọn ít nhất 2 lô để xem.' : 'Không có chứng chỉ chung.' }}
                </p>
              </div>
              <p class="text-[9px] text-muted-foreground leading-tight  px-2">Hệ thống chỉ kế thừa các chứng chỉ có mặt ở <span class="font-bold text-foreground">tất cả</span> các lô đầu vào.</p>
            </div>

            <div v-if="unitError" class="p-4 rounded-2xl bg-destructive/5 border border-destructive/20 flex gap-3 text-destructive animate-in slide-in-from-top-4 duration-500 shadow-inner">
               <AlertTriangle class="w-5 h-5 shrink-0 mt-0.5" />
               <p class="text-[10px] font-black leading-relaxed uppercase tracking-widest">{{ unitError }}</p>
            </div>

            <div v-if="certWarning" class="p-4 rounded-2xl bg-amber-500/5 border border-amber-500/20 flex gap-3 text-amber-600 animate-in slide-in-from-top-4 duration-500 shadow-inner">
               <Info class="w-5 h-5 shrink-0 mt-0.5" />
               <p class="text-[10px] font-black leading-relaxed uppercase tracking-widest">{{ certWarning }}</p>
            </div>

            <div v-if="selectedBatches.length >= 2 && !unitError" class="bg-primary/5 p-6 rounded-3xl border border-primary/10 space-y-4 animate-in zoom-in duration-500 shadow-inner">
               <div class="flex justify-between items-center text-xs">
                  <span class="font-bold text-muted-foreground uppercase tracking-widest text-[9px]">Lô thực hiện gộp:</span>
                  <span class="font-black text-foreground">{{ selectedBatches.length }} lô</span>
               </div>
               <div class="flex justify-between items-center text-xs">
                  <span class="font-bold text-muted-foreground uppercase tracking-widest text-[9px]">Tổng đầu ra dự kiến:</span>
                  <span class="font-black text-primary text-base tracking-tighter">{{ totalQty }} <span class="text-[10px] uppercase opacity-60">{{ commonUnit }}</span></span>
               </div>
               <Separator border-dashed class="bg-primary/20" />
               <p class="text-[9px] text-primary/60  font-bold leading-relaxed text-center uppercase tracking-widest">Sau khi gộp, các lô đầu vào sẽ được chuyển trạng thái <span class="font-black">consumed</span>.</p>
            </div>
          </CardContent>
          <CardFooter class="border-t bg-primary/5 py-8 px-8 flex flex-col gap-4">
            <Button
              class="w-full h-16 font-black uppercase tracking-[0.2em] text-[10px] shadow-2xl shadow-primary/30 rounded-2xl group relative overflow-hidden transition-all duration-500 hover:scale-[1.02] active:scale-95"
              :disabled="!canSubmit"
              @click="submit"
            >
              <div class="absolute inset-0 bg-white/20 translate-y-16 group-hover:translate-y-0 transition-transform duration-500 opacity-20"></div>
              <ArrowsUpFromLine class="w-4 h-4 mr-3 relative z-10 group-hover:-translate-y-1 transition-transform" /> 
              <span class="relative z-10">{{ form.processing ? 'ĐANG GỬI LỆNH...' : `XÁC NHẬN GỘP ${selectedIds.length} LÔ` }}</span>
            </Button>
          </CardFooter>
        </Card>
      </div>
    </div>
  </div>
</template>
