<script setup>
import { ref } from 'vue'
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
import TraceLocationPicker from '@/Components/events/TraceLocationPicker.vue'
import { 
  ArrowDownLeft, 
  Building2, 
  Archive, 
  FileText, 
  X,
  Plus
} from 'lucide-vue-next'

const props = defineProps({
  locations: Array,
  products:  Array,
})

const form = useForm({
  external_party_name:    '',
  external_ref:           '',
  event_time:             new Date().toISOString().slice(0, 16),
  who_name:               '',
  trace_location_id:      null,
  kde_data:               {},
  note:                   '',
  gs1_document_ref:       '',
  output_product_id:      null,
  output_quantity:        '',
  output_unit:            '',
  output_batch_type:      'raw_material',
  output_production_date: '',
  output_expiry_date:     '',
})

function submit() {
  form.post(route('events.transfer.in'))
}
</script>

<template>
  <Head title="Nhập hàng từ nguồn ngoài" />

  <div class="max-w-5xl mx-auto space-y-8 pb-20">
    <!-- Header -->
    <div class="flex items-center justify-between gap-4">
      <div>
        <div class="text-[10px] font-black uppercase tracking-[0.3em] text-emerald-600 mb-1">Inbound / Procurement</div>
        <h1 class="text-4xl font-black  tracking-tighter text-foreground uppercase">Nhập hàng mới</h1>
        <p class="text-muted-foreground font-medium text-sm mt-1 opacity-70">Ghi nhận sự kiện tiếp nhận hàng hóa và khởi tạo lô hàng nguyên liệu từ bên ngoài.</p>
      </div>
      <Button variant="ghost" size="icon" as-child class="h-12 w-12 rounded-2xl hover:bg-destructive/10 hover:text-destructive transition-colors">
         <Link :href="route('dashboard')">
            <X class="w-6 h-6" />
         </Link>
      </Button>
    </div>

    <form @submit.prevent="submit" class="grid grid-cols-1 lg:grid-cols-12 gap-8">
      
      <div class="lg:col-span-7 space-y-8">
        <!-- Section 1: Nguồn cung -->
        <Card class="border-emerald-500/20 bg-card/50 backdrop-blur-sm shadow-xl shadow-emerald-500/5 rounded-[2.5rem] overflow-hidden">
          <CardHeader class="border-b bg-emerald-500/5 py-5 px-8">
            <div class="flex items-center gap-4">
              <div class="p-2.5 rounded-xl bg-emerald-500/10 text-emerald-600 shadow-inner">
                <Building2 class="w-5 h-5" />
              </div>
              <CardTitle class="text-xs font-black uppercase tracking-[0.3em] text-emerald-700 ">Thông tin nguồn cung</CardTitle>
            </div>
          </CardHeader>
          <CardContent class="p-8 space-y-6">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
              <div class="space-y-3">
                <Label class="text-[10px] font-black uppercase tracking-[0.2em] text-muted-foreground/80 px-1">Nhà cung cấp (External) *</Label>
                <Input v-model="form.external_party_name" placeholder="Ví dụ: Công ty Giống cây trồng..." class="h-12 font-black rounded-2xl bg-muted/30 shadow-inner focus:ring-emerald-500/20" required />
                <p v-if="form.errors.external_party_name" class="text-[10px] font-black text-destructive uppercase px-1">{{ form.errors.external_party_name }}</p>
              </div>
              <div class="space-y-3">
                <Label class="text-[10px] font-black uppercase tracking-[0.2em] text-muted-foreground/80 px-1">Số hóa đơn / Hợp đồng</Label>
                <Input v-model="form.external_ref" placeholder="Ví dụ: HD-2026-001" class="h-12 font-bold rounded-2xl bg-muted/30 shadow-inner focus:ring-emerald-500/20" />
                <p v-if="form.errors.external_ref" class="text-[10px] font-black text-destructive uppercase px-1">{{ form.errors.external_ref }}</p>
              </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
              <div class="space-y-3">
                <Label class="text-[10px] font-black uppercase tracking-[0.2em] text-muted-foreground/80 px-1">Thời gian nhập *</Label>
                <Input v-model="form.event_time" type="datetime-local" class="h-12 font-black rounded-2xl bg-muted/30 shadow-inner focus:ring-emerald-500/20" required />
                <p v-if="form.errors.event_time" class="text-[10px] font-black text-destructive uppercase px-1">{{ form.errors.event_time }}</p>
              </div>
              <div class="space-y-3">
                <Label class="text-[10px] font-black uppercase tracking-[0.2em] text-muted-foreground/80 px-1">Cán bộ tiếp nhận *</Label>
                <Input v-model="form.who_name" placeholder="Tên nhân viên nhập kho" class="h-12 font-bold rounded-2xl bg-muted/30 shadow-inner focus:ring-emerald-500/20" required />
                <p v-if="form.errors.who_name" class="text-[10px] font-black text-destructive uppercase px-1">{{ form.errors.who_name }}</p>
              </div>
            </div>

            <div class="space-y-3">
               <Label class="text-[10px] font-black uppercase tracking-[0.2em] text-muted-foreground/80 px-1">Kho / Địa điểm tiếp nhận</Label>
               <TraceLocationPicker
                 v-model="form.trace_location_id"
                 label="Kho / Địa điểm tiếp nhận"
                 class="shadow-inner rounded-2xl"
               />
               <p v-if="form.errors.trace_location_id" class="text-[10px] font-black text-destructive uppercase px-1">{{ form.errors.trace_location_id }}</p>
            </div>
          </CardContent>
        </Card>

        <!-- Section 2: Chứng từ -->
        <Card class="border-dashed border-2 border-border/50 bg-muted/5 rounded-[2.5rem] overflow-hidden">
          <CardHeader class="border-b border-border/30 bg-muted/20 py-5 px-8">
            <div class="flex items-center gap-4">
              <div class="p-2.5 rounded-xl bg-background border border-border/50 text-muted-foreground shadow-sm">
                <FileText class="w-5 h-5" />
              </div>
              <CardTitle class="text-xs font-black uppercase tracking-[0.3em] text-foreground ">Chứng từ GS1 & Ghi chú</CardTitle>
            </div>
          </CardHeader>
          <CardContent class="p-8 space-y-6">
            <div class="space-y-3">
              <Label class="text-[10px] font-black uppercase tracking-[0.2em] text-muted-foreground/80 px-1">Mã chứng từ GS1 (AI 400)</Label>
              <Input v-model="form.gs1_document_ref" placeholder="(400) ABC-123..." class="h-12 font-mono font-bold rounded-2xl bg-background shadow-inner focus:ring-emerald-500/20" />
              <p v-if="form.errors.gs1_document_ref" class="text-[10px] font-black text-destructive uppercase px-1">{{ form.errors.gs1_document_ref }}</p>
            </div>
            <div class="space-y-3">
              <Label class="text-[10px] font-black uppercase tracking-[0.2em] text-muted-foreground/80 px-1">Ghi chú chi tiết</Label>
              <Textarea v-model="form.note" placeholder="Mô tả tình trạng hàng hóa khi nhập kho, kết quả kiểm tra cảm quan..." rows="4" class="rounded-2xl bg-background shadow-inner border-border/50 focus:border-emerald-500/50 focus:ring-emerald-500/20 font-medium  placeholder:opacity-40" />
              <p v-if="form.errors.note" class="text-[10px] font-black text-destructive uppercase px-1">{{ form.errors.note }}</p>
            </div>
          </CardContent>
        </Card>
      </div>

      <div class="lg:col-span-5 space-y-8">
        <!-- Section 3: Tạo lô hàng -->
        <Card class="border-emerald-500/30 bg-card/50 backdrop-blur-xl shadow-2xl shadow-emerald-500/5 rounded-[2.5rem] relative overflow-hidden sticky top-8">
          <div class="absolute right-0 top-0 w-40 h-40 bg-emerald-500/10 blur-3xl -z-10 rounded-full transition-opacity duration-1000"></div>
          <CardHeader class="border-b bg-emerald-500/5 py-5 px-8">
            <div class="flex items-center gap-4">
              <div class="p-2.5 rounded-xl bg-emerald-500/10 text-emerald-600 shadow-inner">
                <Archive class="w-5 h-5" />
              </div>
              <CardTitle class="text-xs font-black uppercase tracking-[0.3em] text-emerald-700 ">Khởi tạo lô hàng</CardTitle>
            </div>
          </CardHeader>
          <CardContent class="p-8 space-y-8">
            <div class="space-y-3">
              <Label class="text-[10px] font-black uppercase tracking-[0.2em] text-muted-foreground/80 px-1">Sản phẩm tiếp nhận *</Label>
              <Select v-model="form.output_product_id">
                <SelectTrigger class="h-12 font-black rounded-2xl bg-background shadow-inner focus:ring-emerald-500/20">
                  <SelectValue placeholder="— Chọn sản phẩm trong danh mục —" />
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
                <Label class="text-[10px] font-black uppercase tracking-[0.2em] text-muted-foreground/80 px-1">Số lượng *</Label>
                <Input v-model="form.output_quantity" type="number" step="any" placeholder="0.00" class="h-12 font-black text-lg tracking-tighter rounded-2xl bg-background shadow-inner focus:ring-emerald-500/20" required />
                <p v-if="form.errors.output_quantity" class="text-[10px] font-black text-destructive uppercase px-1">{{ form.errors.output_quantity }}</p>
              </div>
              <div class="space-y-3">
                <Label class="text-[10px] font-black uppercase tracking-[0.2em] text-muted-foreground/80 px-1">Đơn vị tính *</Label>
                <Input v-model="form.output_unit" placeholder="tấn, kg, bao..." class="h-12 font-black uppercase tracking-widest rounded-2xl bg-background shadow-inner focus:ring-emerald-500/20" required />
                <p v-if="form.errors.output_unit" class="text-[10px] font-black text-destructive uppercase px-1">{{ form.errors.output_unit }}</p>
              </div>
            </div>

            <div class="space-y-3">
              <Label class="text-[10px] font-black uppercase tracking-[0.2em] text-muted-foreground/80 px-1">Loại lô hàng</Label>
              <div class="grid grid-cols-3 gap-2">
                <Button v-for="t in [['raw_material','Nguyên liệu'], ['wip','Bán TP'], ['finished','Thành phẩm']]" 
                  :key="t[0]" type="button" variant="outline"
                  @click="form.output_batch_type = t[0]"
                  class="h-12 text-[9px] font-black uppercase tracking-widest transition-all rounded-xl"
                  :class="form.output_batch_type === t[0] ? 'bg-emerald-600 border-emerald-600 text-white hover:bg-emerald-700 shadow-lg shadow-emerald-500/20 scale-105' : 'bg-background text-muted-foreground border-border/50 hover:bg-muted opacity-60 hover:opacity-100 shadow-sm'">
                  {{ t[1] }}
                </Button>
              </div>
              <p v-if="form.errors.output_batch_type" class="text-[10px] font-black text-destructive uppercase px-1">{{ form.errors.output_batch_type }}</p>
            </div>

            <div class="grid grid-cols-2 gap-6 pt-2">
              <div class="space-y-3">
                <Label class="text-[10px] font-black uppercase tracking-[0.2em] text-muted-foreground/80 px-1">Ngày sản xuất</Label>
                <Input v-model="form.output_production_date" type="date" class="h-12 font-bold rounded-2xl bg-background shadow-inner focus:ring-emerald-500/20" />
                <p v-if="form.errors.output_production_date" class="text-[10px] font-black text-destructive uppercase px-1">{{ form.errors.output_production_date }}</p>
              </div>
              <div class="space-y-3">
                <Label class="text-[10px] font-black uppercase tracking-[0.2em] text-muted-foreground/80 px-1">Hạn sử dụng</Label>
                <Input v-model="form.output_expiry_date" type="date" class="h-12 font-bold rounded-2xl bg-background shadow-inner focus:ring-emerald-500/20" />
                <p v-if="form.errors.output_expiry_date" class="text-[10px] font-black text-destructive uppercase px-1">{{ form.errors.output_expiry_date }}</p>
              </div>
            </div>
          </CardContent>
          <CardFooter class="border-t bg-emerald-500/5 p-8 flex flex-col gap-4">
            <Button 
              type="submit" 
              class="w-full h-16 font-black uppercase tracking-[0.3em] text-[10px] shadow-2xl shadow-emerald-500/30 bg-emerald-600 hover:bg-emerald-700 hover:scale-[1.02] active:scale-95 transition-all duration-500 rounded-2xl group relative overflow-hidden" 
              :disabled="form.processing"
            >
              <div class="absolute inset-0 bg-white/20 translate-y-16 group-hover:translate-y-0 transition-transform duration-500 opacity-20"></div>
              <ArrowDownLeft class="w-5 h-5 mr-3 relative z-10 group-hover:-translate-y-1 transition-transform" />
              <span class="relative z-10">{{ form.processing ? 'ĐANG LƯU DỮ LIỆU...' : 'XÁC NHẬN NHẬP HÀNG' }}</span>
            </Button>
          </CardFooter>
        </Card>
      </div>

    </form>
  </div>
</template>
