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
  Scissors, 
  Plus, 
  Trash2, 
  ArrowLeft,
  AlertTriangle,
  CheckCircle,
  Archive
} from 'lucide-vue-next'

const props = defineProps({
  batch: { type: Object, required: true },
})

const available = computed(() => props.batch.current_quantity ?? props.batch.quantity ?? 0)

const form = useForm({
  children: [
    { quantity: '', note: '' },
    { quantity: '', note: '' },
  ],
  reason: '',
})

const totalSplit  = computed(() => form.children.reduce((s, c) => s + (parseFloat(c.quantity) || 0), 0))
const remaining   = computed(() => available.value - totalSplit.value)
const isOverLimit = computed(() => totalSplit.value > available.value)

function addRow() {
  form.children.push({ quantity: '', note: '' })
}

function removeRow(i) {
  if (form.children.length <= 2) return
  form.children.splice(i, 1)
}

function submit() {
  form.post(route('batches.split.store', props.batch.id))
}
</script>

<template>
  <Head title="Nghiệp vụ tách lô hàng" />

  <div class="max-w-4xl mx-auto space-y-8 pb-20">
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
          <h1 class="text-4xl font-black tracking-tighter text-foreground uppercase ">Tách lô hàng</h1>
          <p class="text-muted-foreground font-medium text-sm mt-1 opacity-70">Phân nhỏ lô gốc thành nhiều lô con để phân phối hoặc xử lý.</p>
        </div>
      </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
      <div class="lg:col-span-8 space-y-6">
        
        <Card class="border-border/50 bg-card/50 backdrop-blur-sm overflow-hidden rounded-[2.5rem] shadow-sm">
          <CardHeader class="border-b bg-muted/30 py-5 px-8">
            <div class="flex items-center justify-between">
               <div class="flex items-center gap-3">
                  <div class="p-2.5 rounded-xl bg-background border border-border/50 text-muted-foreground shadow-sm">
                    <Archive class="w-5 h-5" />
                  </div>
                  <CardTitle class="text-xs font-black uppercase tracking-[0.3em] text-foreground ">Phân bổ lô con</CardTitle>
               </div>
               <Button variant="outline" size="sm" @click="addRow" class="h-9 px-4 text-[9px] font-black uppercase tracking-widest rounded-xl hover:bg-primary/10 hover:text-primary hover:border-primary/40 transition-all shadow-sm">
                  <Plus class="w-3.5 h-3.5 mr-1.5" /> Thêm dòng
               </Button>
            </div>
          </CardHeader>
          <CardContent class="p-8 space-y-4">
            <div
              v-for="(child, i) in form.children"
              :key="i"
              class="group relative flex flex-col sm:flex-row items-start gap-6 p-6 rounded-3xl bg-background/50 border border-border/50 hover:border-primary/40 transition-all shadow-sm hover:shadow-xl"
            >
              <div class="flex flex-row sm:flex-col items-center gap-2 shrink-0">
                 <div class="w-10 h-10 rounded-2xl bg-primary/10 text-primary border-2 border-primary/20 flex items-center justify-center text-sm font-black  shadow-inner">
                    {{ i + 1 }}
                 </div>
                 <div class="hidden sm:block w-[2px] h-full bg-border border-dashed"></div>
              </div>

              <div class="flex-1 w-full grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="space-y-2">
                  <Label class="text-[9px] uppercase font-black tracking-[0.2em] text-muted-foreground/60 px-1">Số lượng ({{ batch.unit }}) *</Label>
                  <Input
                    type="number"
                    step="0.01"
                    min="0"
                    v-model="child.quantity"
                    placeholder="0.00"
                    class="h-12 text-lg font-black tracking-tighter bg-card/50 shadow-inner rounded-2xl focus:bg-background focus:ring-primary/20"
                  />
                  <p v-if="form.errors[`children.${i}.quantity`]" class="text-[10px] font-black text-destructive uppercase tracking-widest  px-1">{{ form.errors[`children.${i}.quantity`] }}</p>
                </div>
                <div class="space-y-2">
                  <Label class="text-[9px] uppercase font-black tracking-[0.2em] text-muted-foreground/60 px-1">Ghi chú (Tùy chọn)</Label>
                  <Input
                    v-model="child.note"
                    placeholder="VD: Xuất khẩu, bán lẻ..."
                    class="h-12 font-medium  bg-card/50 shadow-inner rounded-2xl focus:bg-background focus:ring-primary/20 placeholder:opacity-40"
                  />
                </div>
              </div>

              <Button
                v-if="form.children.length > 2"
                variant="ghost"
                size="icon"
                @click="removeRow(i)"
                class="absolute -right-2 -top-2 h-8 w-8 rounded-full bg-destructive/10 text-destructive opacity-0 group-hover:opacity-100 transition-opacity hover:bg-destructive hover:text-white shadow-sm"
              >
                <Trash2 class="w-3.5 h-3.5" />
              </Button>
            </div>
          </CardContent>
        </Card>

        <Card class="border-border/50 bg-card/50 backdrop-blur-sm overflow-hidden rounded-[2.5rem] shadow-sm">
          <CardHeader class="border-b bg-muted/30 py-5 px-8">
             <CardTitle class="text-xs font-black uppercase tracking-[0.3em] text-foreground ">Thông tin nghiệp vụ</CardTitle>
          </CardHeader>
          <CardContent class="p-8 space-y-3">
            <Label class="text-[10px] font-black uppercase tracking-[0.2em] text-muted-foreground px-1">Lý do tách lô (Tùy chọn)</Label>
            <Input
              v-model="form.reason"
              placeholder="Nhập lý do thực hiện tách lô để lưu vết..."
              class="h-14 rounded-2xl bg-muted/30 border-border/50 font-medium  shadow-inner focus:bg-background placeholder:opacity-40"
            />
            <p v-if="form.errors.reason" class="text-xs font-black text-destructive  uppercase tracking-widest px-2">{{ form.errors.reason }}</p>
          </CardContent>
        </Card>
      </div>

      <!-- Sidebar Summary -->
      <div class="lg:col-span-4 space-y-6">
        <Card class="border-primary/20 bg-card/50 backdrop-blur-xl shadow-2xl shadow-primary/10 rounded-[2.5rem] overflow-hidden sticky top-8">
          <CardHeader class="border-b bg-primary/5 py-5 px-6">
             <CardTitle class="text-[10px] font-black uppercase tracking-[0.3em] text-primary  flex items-center gap-2">
                <Scissors class="w-4 h-4" /> Tổng quan tách lô
             </CardTitle>
          </CardHeader>
          <CardContent class="pt-8 space-y-6 px-6">
            
            <div class="space-y-1">
               <Label class="text-[9px] font-black uppercase text-muted-foreground/60 tracking-widest px-1">Lô gốc đang chọn</Label>
               <div class="p-4 rounded-2xl bg-muted/30 border border-border/50 shadow-inner">
                  <div class="font-mono text-lg font-black text-foreground tracking-tighter">{{ batch.code }}</div>
                  <div class="text-[10px] font-bold text-muted-foreground uppercase tracking-widest mt-1 truncate">{{ batch.product_name }}</div>
               </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
               <div class="p-3 rounded-2xl bg-muted/20 border border-border/50 space-y-1 text-center shadow-inner">
                  <Label class="text-[8px] font-black uppercase text-muted-foreground/60 tracking-[0.2em]">Khả dụng</Label>
                  <p class="text-xl font-black tracking-tighter text-foreground">{{ available }} <span class="text-[9px] uppercase tracking-widest opacity-40">{{ batch.unit }}</span></p>
               </div>
               <div class="p-3 rounded-2xl bg-muted/20 border border-border/50 space-y-1 text-center shadow-inner group transition-colors" :class="isOverLimit ? 'border-destructive bg-destructive/10' : ''">
                  <Label class="text-[8px] font-black uppercase tracking-[0.2em]" :class="isOverLimit ? 'text-destructive/60' : 'text-muted-foreground/60'">Đã phân bổ</Label>
                  <p class="text-xl font-black tracking-tighter" :class="isOverLimit ? 'text-destructive' : 'text-primary'">{{ totalSplit }} <span class="text-[9px] uppercase tracking-widest opacity-40">{{ batch.unit }}</span></p>
               </div>
            </div>
            
            <div class="p-5 rounded-3xl bg-muted/10 border-2 border-dashed border-border/50 flex items-center justify-between group">
              <span class="text-[10px] font-black uppercase tracking-widest text-muted-foreground/60">Tồn dư gốc</span>
              <span class="text-2xl font-black tracking-tighter transition-colors" :class="remaining < 0 ? 'text-destructive' : 'text-foreground'">
                {{ remaining }} <span class="text-[10px] uppercase font-bold text-muted-foreground/40">{{ batch.unit }}</span>
              </span>
            </div>

            <div v-if="isOverLimit" class="p-4 rounded-2xl bg-destructive/5 border border-destructive/20 flex items-start gap-3 text-destructive shadow-inner animate-in slide-in-from-top-2">
               <AlertTriangle class="w-5 h-5 shrink-0 mt-0.5" />
               <p class="text-[10px] font-black leading-relaxed uppercase tracking-widest">Số lượng phân bổ vượt quá khả dụng ({{ available }} {{ batch.unit }}).</p>
            </div>

            <div v-else-if="totalSplit > 0" class="p-4 rounded-2xl bg-emerald-500/5 border border-emerald-500/20 flex items-start gap-3 text-emerald-600 shadow-inner animate-in slide-in-from-top-2">
               <CheckCircle class="w-5 h-5 shrink-0 mt-0.5" />
               <p class="text-[10px] font-black leading-relaxed uppercase tracking-widest">Dữ liệu hợp lệ để thực hiện tách lô.</p>
            </div>
          </CardContent>
          <CardFooter class="border-t bg-primary/5 py-6 px-6 flex flex-col gap-4">
            <Button
              class="w-full h-14 font-black uppercase tracking-[0.2em] text-[10px] shadow-xl shadow-primary/30 rounded-2xl group relative overflow-hidden transition-all duration-500 hover:scale-[1.02] active:scale-95"
              :disabled="form.processing || isOverLimit || totalSplit <= 0"
              @click="submit"
            >
              <div class="absolute inset-0 bg-white/20 translate-y-14 group-hover:translate-y-0 transition-transform duration-500 opacity-20"></div>
              <Scissors class="w-4 h-4 mr-3 relative z-10 group-hover:rotate-180 transition-transform duration-700" /> 
              <span class="relative z-10">{{ form.processing ? 'ĐANG XỬ LÝ...' : 'XÁC NHẬN TÁCH LÔ' }}</span>
            </Button>
            <Button variant="ghost" class="w-full h-10 text-[9px] font-black uppercase tracking-widest rounded-xl hover:bg-muted" as-child>
               <Link :href="route('batches.index')">Hủy bỏ</Link>
            </Button>
          </CardFooter>
        </Card>
      </div>
    </div>
  </div>
</template>
