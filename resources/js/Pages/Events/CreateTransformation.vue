<script setup>
import { ref, computed } from 'vue'
import { Head, useForm, Link } from '@inertiajs/vue3'
import { Card, CardHeader, CardTitle, CardDescription, CardContent, CardFooter } from '@/Components/ui/card/index.js'
import { Button } from '@/Components/ui/button/index.js'
import { Input } from '@/Components/ui/input/index.js'
import { Label } from '@/Components/ui/label/index.js'
import { Badge } from '@/Components/ui/badge/index.js'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/Components/ui/select/index.js'
import { Textarea } from '@/Components/ui/textarea/index.js'
import { Separator } from '@/Components/ui/separator/index.js'
import { 
  RefreshCw, 
  Archive, 
  FlaskConical, 
  MapPin, 
  X,
  Plus,
  Trash2,
  CheckCircle,
  AlertTriangle,
  ArrowRight
} from 'lucide-vue-next'

const props = defineProps({
  batches: Array,
  locations: Array,
  products: Array,
})

const form = useForm({
  cte_code: '',
  event_time: new Date().toISOString().slice(0, 16),
  who_name: '',
  trace_location_id: '',
  kde_data: {},
  note: '',
  input_batches: [],
  output_product_id: '',
  output_quantity: '',
  output_unit: '',
  output_batch_type: 'wip',
  output_production_date: '',
  output_expiry_date: '',
})

function addInputBatch(batchId) {
  const batch = props.batches.find(b => String(b.id) === String(batchId))
  if (!batch || form.input_batches.find(b => b.id === batch.id)) return
  form.input_batches.push({
    id: batch.id,
    code: batch.code,
    product_name: batch.product_name,
    quantity: batch.current_quantity,
    unit: batch.unit
  })
}

function removeInputBatch(id) {
  form.input_batches = form.input_batches.filter(b => b.id !== id)
}

function submit() {
  form.post(route('transformation-events.store'))
}
</script>

<template>
  <Head title="Nghiệp vụ chế biến / Thu hoạch" />

  <div class="max-w-6xl mx-auto space-y-8 pb-20">
    <!-- Header -->
    <div class="flex items-center justify-between gap-4">
      <div>
        <div class="text-[10px] font-black uppercase tracking-[0.3em] text-orange-600 mb-1">Production / Transformation</div>
        <h1 class="text-4xl font-black  tracking-tighter text-foreground uppercase">Chế biến & Biến đổi lô</h1>
        <p class="text-muted-foreground font-medium text-sm mt-1 opacity-70">Ghi nhận quá trình tiêu thụ nguyên liệu và sản xuất lô hàng mới.</p>
      </div>
      <Button variant="ghost" size="icon" as-child class="h-12 w-12 rounded-2xl hover:bg-destructive/10 hover:text-destructive transition-colors">
         <Link :href="route('batches.index')">
            <X class="w-6 h-6" />
         </Link>
      </Button>
    </div>

    <form @submit.prevent="submit" class="space-y-8">
      
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        
        <!-- CỘT TRÁI: ĐẦU VÀO (INPUT) -->
        <Card class="border-orange-500/20 bg-card/50 backdrop-blur-sm shadow-xl shadow-orange-500/5 rounded-[2.5rem] overflow-hidden">
          <CardHeader class="border-b bg-orange-500/5 py-5 px-8">
            <div class="flex items-center justify-between">
               <div class="flex items-center gap-4">
                  <div class="p-2.5 rounded-xl bg-orange-500/10 text-orange-600 shadow-inner">
                    <FlaskConical class="w-5 h-5" />
                  </div>
                  <CardTitle class="text-xs font-black uppercase tracking-[0.3em] text-orange-700 ">Nguyên liệu đầu vào</CardTitle>
               </div>
               <Badge variant="outline" class="font-black text-[9px] uppercase tracking-widest text-orange-600 border-orange-500/20 bg-background shadow-sm px-3 py-1">{{ form.input_batches.length }} Đã chọn</Badge>
            </div>
          </CardHeader>
          <CardContent class="p-8 space-y-6">
            <div class="space-y-3">
              <Label class="text-[10px] font-black uppercase tracking-[0.2em] text-muted-foreground/80 px-1">Thêm nguyên liệu tiêu thụ *</Label>
              <Select @update:modelValue="addInputBatch">
                <SelectTrigger class="h-12 font-black rounded-2xl bg-muted/30 shadow-inner focus:ring-orange-500/20">
                  <SelectValue placeholder="— Chọn lô hàng hiện có —" />
                </SelectTrigger>
                <SelectContent class="rounded-2xl">
                  <SelectItem v-for="b in batches" :key="b.id" :value="String(b.id)" class="font-bold">
                    {{ b.code }} <span class="text-[10px] text-muted-foreground ml-1">({{ b.product_name }})</span>
                  </SelectItem>
                </SelectContent>
              </Select>
            </div>

            <div class="overflow-y-auto max-h-[400px]">
              <div class="space-y-4 pb-4">
                <div v-if="form.input_batches.length === 0" class="flex flex-col items-center justify-center py-24 border-2 border-dashed border-border/50 rounded-[2rem] text-muted-foreground bg-muted/10 opacity-60">
                  <Archive class="w-12 h-12 mb-4 opacity-20" />
                  <p class="text-[10px] uppercase font-black tracking-[0.2em]">Chưa chọn nguyên liệu</p>
                </div>
                
                <div v-for="item in form.input_batches" :key="item.id" 
                  class="p-5 rounded-3xl bg-background/50 border border-border/50 shadow-sm relative transition-all duration-300 hover:border-orange-500/40 hover:shadow-xl">
                  <div class="flex items-start justify-between mb-4">
                    <div class="min-w-0">
                      <div class="font-mono text-base font-black text-orange-600 tracking-tighter uppercase">{{ item.code }}</div>
                      <div class="text-[10px] uppercase font-bold text-muted-foreground mt-0.5 truncate">{{ item.product_name }}</div>
                    </div>
                    <Button variant="ghost" size="icon" @click="removeInputBatch(item.id)" class="h-8 w-8 rounded-full text-muted-foreground hover:bg-destructive/10 hover:text-destructive transition-colors">
                       <Trash2 class="w-4 h-4" />
                    </Button>
                  </div>
                  <div class="flex gap-4 items-end">
                    <div class="flex-1 space-y-2">
                      <Label class="text-[9px] font-black uppercase tracking-[0.2em] text-muted-foreground/60 px-1">Số lượng tiêu thụ</Label>
                      <Input v-model="item.quantity" type="number" step="any" class="h-11 text-xs font-black shadow-inner rounded-xl bg-card/50 focus:bg-background" placeholder="0.00" />
                    </div>
                    <div class="w-24 space-y-2">
                      <Label class="text-[9px] font-black uppercase tracking-[0.2em] text-muted-foreground/60 px-1">Đơn vị</Label>
                      <Input v-model="item.unit" class="h-11 text-xs font-bold uppercase tracking-widest shadow-inner rounded-xl bg-card/50 focus:bg-background" placeholder="VD: kg" />
                    </div>
                  </div>
                </div>
              </div>
            </div>
            
            <div v-if="form.input_batches.length" class="p-4 rounded-2xl bg-orange-500/5 border border-orange-500/20 flex items-start gap-3 text-orange-700 shadow-inner animate-in slide-in-from-bottom-4">
               <AlertTriangle class="w-5 h-5 shrink-0 mt-0.5" />
               <p class="text-[9px] font-black leading-relaxed uppercase tracking-widest ">Lưu ý: Các lô hàng này sẽ được hệ thống tự động cập nhật trạng thái "Consumed" và trừ kho.</p>
            </div>
          </CardContent>
        </Card>

        <!-- CỘT PHẢI: ĐẦU RA (OUTPUT) -->
        <Card class="border-primary/20 bg-card/50 backdrop-blur-sm shadow-xl shadow-primary/5 rounded-[2.5rem] relative overflow-hidden">
          <div class="absolute right-0 top-0 w-40 h-40 bg-primary/10 blur-3xl -z-10 rounded-full transition-opacity duration-1000"></div>
          <CardHeader class="border-b bg-primary/5 py-5 px-8">
            <div class="flex items-center gap-4">
              <div class="p-2.5 rounded-xl bg-primary/10 text-primary shadow-inner">
                <ArrowRight class="w-5 h-5" />
              </div>
              <CardTitle class="text-xs font-black uppercase tracking-[0.3em] text-primary ">Thành phẩm đầu ra</CardTitle>
            </div>
          </CardHeader>
          <CardContent class="p-8 space-y-8">
            <div class="space-y-3">
              <Label class="text-[10px] font-black uppercase tracking-[0.2em] text-muted-foreground/80 px-1">Sản phẩm đầu ra *</Label>
              <Select v-model="form.output_product_id">
                <SelectTrigger class="h-12 font-black rounded-2xl bg-muted/30 shadow-inner focus:ring-primary/20">
                  <SelectValue placeholder="— Chọn sản phẩm thành phẩm —" />
                </SelectTrigger>
                <SelectContent class="rounded-2xl">
                  <SelectItem v-for="p in products" :key="p.id" :value="String(p.id)" class="font-bold">
                    {{ p.name }} <span class="text-[10px] text-muted-foreground ml-1" v-if="p.gtin">({{ p.gtin }})</span>
                  </SelectItem>
                </SelectContent>
              </Select>
              <p v-if="form.errors.output_product_id" class="text-[10px] font-black text-destructive uppercase px-1">{{ form.errors.output_product_id }}</p>
            </div>

            <div class="grid grid-cols-2 gap-6">
              <div class="space-y-3">
                <Label class="text-[10px] font-black uppercase tracking-[0.2em] text-muted-foreground/80 px-1">Số lượng ra *</Label>
                <Input v-model="form.output_quantity" type="number" step="any" placeholder="0.00" class="h-12 font-black text-lg tracking-tighter rounded-2xl bg-muted/30 shadow-inner focus:ring-primary/20" required />
                <p v-if="form.errors.output_quantity" class="text-[10px] font-black text-destructive uppercase px-1">{{ form.errors.output_quantity }}</p>
              </div>
              <div class="space-y-3">
                <Label class="text-[10px] font-black uppercase tracking-[0.2em] text-muted-foreground/80 px-1">Đơn vị tính *</Label>
                <Input v-model="form.output_unit" placeholder="kg, bao, hộp..." class="h-12 font-black uppercase tracking-widest rounded-2xl bg-muted/30 shadow-inner focus:ring-primary/20" required />
                <p v-if="form.errors.output_unit" class="text-[10px] font-black text-destructive uppercase px-1">{{ form.errors.output_unit }}</p>
              </div>
            </div>

            <div class="space-y-3">
              <Label class="text-[10px] font-black uppercase tracking-[0.2em] text-muted-foreground/80 px-1">Loại lô hàng mới</Label>
              <Select v-model="form.output_batch_type">
                <SelectTrigger class="h-12 font-bold rounded-2xl bg-muted/30 shadow-inner focus:ring-primary/20">
                  <SelectValue />
                </SelectTrigger>
                <SelectContent class="rounded-2xl">
                  <SelectItem value="raw_material" class="font-bold">Nguyên liệu</SelectItem>
                  <SelectItem value="wip" class="font-bold">Bán thành phẩm (WIP)</SelectItem>
                  <SelectItem value="finished" class="font-bold text-primary">Thành phẩm hoàn thiện</SelectItem>
                </SelectContent>
              </Select>
              <p v-if="form.errors.output_batch_type" class="text-[10px] font-black text-destructive uppercase px-1">{{ form.errors.output_batch_type }}</p>
            </div>

            <div class="grid grid-cols-2 gap-6">
              <div class="space-y-3">
                <Label class="text-[10px] font-black uppercase tracking-[0.2em] text-muted-foreground/80 px-1">Ngày sản xuất</Label>
                <Input v-model="form.output_production_date" type="date" class="h-12 font-bold rounded-2xl bg-muted/30 shadow-inner focus:ring-primary/20" />
                <p v-if="form.errors.output_production_date" class="text-[10px] font-black text-destructive uppercase px-1">{{ form.errors.output_production_date }}</p>
              </div>
              <div class="space-y-3">
                <Label class="text-[10px] font-black uppercase tracking-[0.2em] text-muted-foreground/80 px-1">Hạn sử dụng</Label>
                <Input v-model="form.output_expiry_date" type="date" class="h-12 font-bold rounded-2xl bg-muted/30 shadow-inner focus:ring-primary/20" />
                <p v-if="form.errors.output_expiry_date" class="text-[10px] font-black text-destructive uppercase px-1">{{ form.errors.output_expiry_date }}</p>
              </div>
            </div>
          </CardContent>
        </Card>
      </div>

      <!-- Section 3: Thông tin sự kiện -->
      <Card class="border-dashed border-2 rounded-[2.5rem] border-border/50 bg-muted/5 overflow-hidden">
        <CardHeader class="border-b border-border/30 bg-muted/20 py-5 px-8">
          <div class="flex items-center gap-4">
            <div class="p-2.5 rounded-xl bg-background border border-border/50 text-muted-foreground shadow-sm">
              <RefreshCw class="w-5 h-5" />
            </div>
            <CardTitle class="text-xs font-black uppercase tracking-[0.3em] text-foreground ">Chi tiết công đoạn thực hiện</CardTitle>
          </div>
        </CardHeader>
        <CardContent class="p-8 space-y-8">
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
            <div class="space-y-3">
              <Label class="text-[10px] font-black uppercase tracking-[0.2em] text-muted-foreground/80 px-1">Loại quy trình *</Label>
              <Select v-model="form.cte_code">
                <SelectTrigger class="h-12 font-black rounded-2xl bg-background shadow-inner focus:ring-primary/20">
                  <SelectValue placeholder="— Chọn quy trình —" />
                </SelectTrigger>
                <SelectContent class="rounded-2xl">
                  <SelectItem value="harvesting" class="font-bold">🚜 Thu hoạch</SelectItem>
                  <SelectItem value="milling" class="font-bold">⚙️ Xay xát / Ép</SelectItem>
                  <SelectItem value="processing" class="font-bold">🏭 Chế biến</SelectItem>
                  <SelectItem value="packaging" class="font-bold">📦 Đóng gói</SelectItem>
                  <SelectItem value="split" class="font-bold">✂️ Tách lô</SelectItem>
                  <SelectItem value="merge" class="font-bold">🔀 Gộp lô</SelectItem>
                </SelectContent>
              </Select>
              <p v-if="form.errors.cte_code" class="text-[10px] font-black text-destructive uppercase px-1">{{ form.errors.cte_code }}</p>
            </div>
            
            <div class="space-y-3">
              <Label class="text-[10px] font-black uppercase tracking-[0.2em] text-muted-foreground/80 px-1">Thời gian sự kiện *</Label>
              <Input v-model="form.event_time" type="datetime-local" class="h-12 font-black rounded-2xl bg-background shadow-inner focus:ring-primary/20" required />
              <p v-if="form.errors.event_time" class="text-[10px] font-black text-destructive uppercase px-1">{{ form.errors.event_time }}</p>
            </div>

            <div class="space-y-3">
              <Label class="text-[10px] font-black uppercase tracking-[0.2em] text-muted-foreground/80 px-1">Người chịu trách nhiệm *</Label>
              <Input v-model="form.who_name" placeholder="Tên cán bộ kỹ thuật" class="h-12 font-bold rounded-2xl bg-background shadow-inner focus:ring-primary/20" required />
              <p v-if="form.errors.who_name" class="text-[10px] font-black text-destructive uppercase px-1">{{ form.errors.who_name }}</p>
            </div>

            <div class="space-y-3">
              <Label class="text-[10px] font-black uppercase tracking-[0.2em] text-muted-foreground/80 px-1">Địa điểm thực hiện</Label>
              <Select v-model="form.trace_location_id">
                <SelectTrigger class="h-12 font-bold rounded-2xl bg-background shadow-inner focus:ring-primary/20">
                  <SelectValue placeholder="— Chọn nhà máy / ruộng —" />
                </SelectTrigger>
                <SelectContent class="rounded-2xl">
                  <SelectItem v-for="loc in locations" :key="loc.id" :value="String(loc.id)">
                    {{ loc.name }} <span class="text-[10px] font-mono text-muted-foreground ml-1">({{ loc.gln }})</span>
                  </SelectItem>
                </SelectContent>
              </Select>
              <p v-if="form.errors.trace_location_id" class="text-[10px] font-black text-destructive uppercase px-1">{{ form.errors.trace_location_id }}</p>
            </div>
          </div>

          <div class="space-y-3">
            <Label class="text-[10px] font-black uppercase tracking-[0.2em] text-muted-foreground/80 px-1">Ghi chú & Mô tả quy trình kỹ thuật</Label>
            <Textarea v-model="form.note" placeholder="Nhập chi tiết về cách chế biến, nhiệt độ, thông số kỹ thuật hoặc các tiêu chuẩn áp dụng..." rows="4" class="rounded-2xl bg-background shadow-inner border-border/50 focus:border-primary/50 focus:ring-primary/20 font-medium  placeholder:opacity-40" />
            <p v-if="form.errors.note" class="text-[10px] font-black text-destructive uppercase px-1">{{ form.errors.note }}</p>
          </div>
        </CardContent>
        <CardFooter class="border-t border-border/30 bg-muted/20 py-8 px-8 flex flex-col sm:flex-row sm:items-center justify-between gap-6">
           <div class="flex items-center gap-3">
              <div v-if="form.input_batches.length && form.output_product_id" class="flex items-center gap-2 text-emerald-600 animate-in zoom-in duration-500">
                 <CheckCircle class="w-5 h-5 animate-pulse shadow-[0_0_12px_rgba(16,185,129,0.8)] rounded-full" />
                 <span class="text-[10px] font-black uppercase tracking-[0.2em]">Dữ liệu sẵn sàng khởi tạo</span>
              </div>
           </div>
           <div class="flex items-center gap-4 w-full sm:w-auto">
              <Button variant="ghost" type="button" as-child class="font-black uppercase tracking-[0.2em] text-[10px] h-14 px-8 rounded-2xl hover:bg-muted transition-all">
                 <Link :href="route('batches.index')">Hủy bỏ</Link>
              </Button>
              <Button 
                type="submit" 
                class="h-14 px-10 font-black uppercase tracking-[0.3em] text-[10px] shadow-2xl shadow-primary/30 rounded-2xl group relative overflow-hidden transition-all duration-500 hover:scale-[1.02] active:scale-95 flex-1 sm:flex-none" 
                :disabled="form.processing || !form.input_batches.length || !form.output_product_id"
              >
                <div class="absolute inset-0 bg-white/20 translate-y-14 group-hover:translate-y-0 transition-transform duration-500 opacity-20"></div>
                <RefreshCw class="w-4 h-4 mr-3 relative z-10 group-hover:rotate-180 transition-transform duration-700" />
                <span class="relative z-10">{{ form.processing ? 'ĐANG XỬ LÝ...' : 'XÁC NHẬN BIẾN ĐỔI' }}</span>
              </Button>
           </div>
        </CardFooter>
      </Card>

    </form>
  </div>
</template>
