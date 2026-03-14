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
import { Switch } from '@/Components/ui/switch/index.js'
import { 
  Eye, 
  MapPin, 
  Archive, 
  GraduationCap, 
  X,
  CheckCircle,
  Info
} from 'lucide-vue-next'

const props = defineProps({
  batches: Array,
  locations: Array,
  certificates: Array,
})

const form = useForm({
  cte_code: '',
  event_time: new Date().toISOString().slice(0, 16),
  who_name: '',
  trace_location_id: '',
  kde_data: {},
  note: '',
  input_batch_ids: [],
  why_reason: '',
  has_certification: false,
  certification: {
    certificate_id: '',
    result: 'pass',
    reference_no: '',
    issued_date: '',
    expiry_date: '',
  }
})

function submit() {
  form.post(route('observation-events.store'))
}

function toggleBatch(id) {
  const idx = form.input_batch_ids.indexOf(id)
  if (idx >= 0) form.input_batch_ids.splice(idx, 1)
  else form.input_batch_ids.push(id)
}
</script>

<template>
  <Head title="Ghi nhận quan sát / Kiểm định" />

  <div class="max-w-5xl mx-auto space-y-8 pb-20">
    <!-- Header -->
    <div class="flex items-center justify-between gap-4">
      <div>
        <div class="text-[10px] font-black uppercase tracking-[0.3em] text-sky-600 mb-1">Monitoring / Quality Control</div>
        <h1 class="text-4xl font-black  tracking-tighter text-foreground uppercase">Ghi nhận Quan sát</h1>
        <p class="text-muted-foreground font-medium text-sm mt-1 opacity-70">Ghi nhật ký canh tác, chăm sóc hoặc kết quả kiểm định mẫu cho các lô hàng.</p>
      </div>
      <Button variant="ghost" size="icon" as-child class="h-12 w-12 rounded-2xl hover:bg-destructive/10 hover:text-destructive transition-colors">
         <Link :href="route('events.index')">
            <X class="w-6 h-6" />
         </Link>
      </Button>
    </div>

    <form @submit.prevent="submit" class="grid grid-cols-1 lg:grid-cols-12 gap-8">
      
      <div class="lg:col-span-7 space-y-8">
        <!-- Section 1: Thông tin sự kiện -->
        <Card class="border-sky-500/20 bg-card/50 backdrop-blur-sm shadow-xl shadow-sky-500/5 rounded-[2.5rem] overflow-hidden">
          <CardHeader class="border-b bg-sky-500/5 py-5 px-8">
            <div class="flex items-center gap-4">
              <div class="p-2.5 rounded-xl bg-sky-500/10 text-sky-600 shadow-inner">
                <Info class="w-5 h-5" />
              </div>
              <CardTitle class="text-xs font-black uppercase tracking-[0.3em] text-foreground ">Chi tiết sự kiện (5W)</CardTitle>
            </div>
          </CardHeader>
          <CardContent class="p-8 space-y-6">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
              <div class="space-y-3">
                <Label class="text-[10px] font-black uppercase tracking-[0.2em] text-muted-foreground/80 px-1">Loại sự kiện (CTE) *</Label>
                <Select v-model="form.cte_code">
                  <SelectTrigger class="h-12 font-black rounded-2xl bg-muted/30 shadow-inner focus:ring-sky-500/20">
                    <SelectValue placeholder="— Chọn công đoạn —" />
                  </SelectTrigger>
                  <SelectContent class="rounded-2xl">
                    <SelectItem value="planting" class="font-bold">🌱 Gieo hạt / Trồng</SelectItem>
                    <SelectItem value="growing" class="font-bold">🌿 Bón phân / Chăm sóc</SelectItem>
                    <SelectItem value="spraying" class="font-bold">💦 Phun thuốc</SelectItem>
                    <SelectItem value="storage" class="font-bold">🏭 Lưu kho</SelectItem>
                    <SelectItem value="inspection" class="font-bold">🔍 Kiểm định / Test mẫu</SelectItem>
                  </SelectContent>
                </Select>
                <p v-if="form.errors.cte_code" class="text-[10px] font-black text-destructive uppercase px-1">{{ form.errors.cte_code }}</p>
              </div>
              <div class="space-y-3">
                <Label class="text-[10px] font-black uppercase tracking-[0.2em] text-muted-foreground/80 px-1">Thời gian ghi nhận *</Label>
                <Input v-model="form.event_time" type="datetime-local" class="h-12 font-black rounded-2xl bg-muted/30 shadow-inner focus:ring-sky-500/20" required />
                <p v-if="form.errors.event_time" class="text-[10px] font-black text-destructive uppercase px-1">{{ form.errors.event_time }}</p>
              </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
              <div class="space-y-3">
                <Label class="text-[10px] font-black uppercase tracking-[0.2em] text-muted-foreground/80 px-1">Người thực hiện *</Label>
                <Input v-model="form.who_name" placeholder="Tên nhân viên / Cán bộ" class="h-12 font-bold rounded-2xl bg-muted/30 shadow-inner focus:ring-sky-500/20" required />
                <p v-if="form.errors.who_name" class="text-[10px] font-black text-destructive uppercase px-1">{{ form.errors.who_name }}</p>
              </div>
              <div class="space-y-3">
                <Label class="text-[10px] font-black uppercase tracking-[0.2em] text-muted-foreground/80 px-1">Địa điểm thực hiện</Label>
                <Select v-model="form.trace_location_id">
                  <SelectTrigger class="h-12 font-bold rounded-2xl bg-muted/30 shadow-inner focus:ring-sky-500/20">
                    <SelectValue placeholder="— Chọn địa điểm —" />
                  </SelectTrigger>
                  <SelectContent class="rounded-2xl">
                    <SelectItem v-for="loc in locations" :key="loc.id" :value="String(loc.id)">
                      {{ loc.name }} <span class="font-mono text-[10px] text-muted-foreground ml-1">({{ loc.gln }})</span>
                    </SelectItem>
                  </SelectContent>
                </Select>
                <p v-if="form.errors.trace_location_id" class="text-[10px] font-black text-destructive uppercase px-1">{{ form.errors.trace_location_id }}</p>
              </div>
            </div>

            <div class="space-y-3">
              <Label class="text-[10px] font-black uppercase tracking-[0.2em] text-muted-foreground/80 px-1">Mục đích / Lý do quan sát</Label>
              <Input v-model="form.why_reason" placeholder="Ví dụ: Kiểm tra chất lượng định kỳ, bón phân đợt 1..." class="h-12 font-medium  rounded-2xl bg-muted/30 shadow-inner focus:ring-sky-500/20 placeholder:opacity-50" />
              <p v-if="form.errors.why_reason" class="text-[10px] font-black text-destructive uppercase px-1">{{ form.errors.why_reason }}</p>
            </div>
          </CardContent>
        </Card>

        <!-- Section 2: Chứng nhận (Toggle) -->
        <Card class="border-emerald-500/20 bg-card/50 backdrop-blur-sm shadow-xl shadow-emerald-500/5 relative overflow-hidden rounded-[2.5rem]">
          <div v-if="form.has_certification" class="absolute right-0 top-0 w-40 h-40 bg-emerald-500/10 blur-3xl -z-10 rounded-full transition-opacity duration-1000"></div>
          <CardHeader class="border-b bg-muted/5 py-5 px-8">
            <div class="flex items-center justify-between w-full">
               <div class="flex items-center gap-4">
                  <div class="p-2.5 rounded-xl shadow-inner transition-colors duration-500" :class="form.has_certification ? 'bg-emerald-500/10 text-emerald-600' : 'bg-muted text-muted-foreground'">
                    <GraduationCap class="w-5 h-5" />
                  </div>
                  <CardTitle class="text-xs font-black uppercase tracking-[0.3em] " :class="form.has_certification ? 'text-emerald-600' : 'text-foreground'">Kèm theo chứng nhận</CardTitle>
               </div>
               <Switch :checked="form.has_certification" @update:checked="v => form.has_certification = v" class="data-[state=checked]:bg-emerald-500" />
            </div>
          </CardHeader>
          <CardContent v-if="form.has_certification" class="p-8 space-y-6 animate-in slide-in-from-top-4 duration-500">
            <div class="space-y-3">
              <Label class="text-[10px] font-black uppercase tracking-[0.2em] text-muted-foreground/80 px-1">Loại chứng chỉ / Giấy tờ *</Label>
              <Select v-model="form.certification.certificate_id">
                <SelectTrigger class="h-12 font-bold rounded-2xl bg-muted/30 shadow-inner focus:ring-emerald-500/20">
                  <SelectValue placeholder="— Chọn chứng chỉ hệ thống —" />
                </SelectTrigger>
                <SelectContent class="rounded-2xl">
                  <SelectItem v-for="c in certificates" :key="c.id" :value="String(c.id)">
                    {{ c.name }} <span class="text-[10px] text-muted-foreground ml-1">({{ c.organization }})</span>
                  </SelectItem>
                </SelectContent>
              </Select>
              <p v-if="form.errors['certification.certificate_id']" class="text-[10px] font-black text-destructive uppercase px-1">{{ form.errors['certification.certificate_id'] }}</p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
              <div class="space-y-3">
                <Label class="text-[10px] font-black uppercase tracking-[0.2em] text-muted-foreground/80 px-1">Kết quả đánh giá *</Label>
                <Select v-model="form.certification.result">
                  <SelectTrigger class="h-12 font-black rounded-2xl bg-muted/30 shadow-inner focus:ring-emerald-500/20">
                    <SelectValue />
                  </SelectTrigger>
                  <SelectContent class="rounded-2xl">
                    <SelectItem value="pass" class="text-emerald-600 font-bold">Đạt (Pass)</SelectItem>
                    <SelectItem value="fail" class="text-destructive font-bold">Không đạt (Fail)</SelectItem>
                    <SelectItem value="conditional" class="text-amber-600 font-bold">Có điều kiện</SelectItem>
                  </SelectContent>
                </Select>
              </div>
              <div class="space-y-3">
                <Label class="text-[10px] font-black uppercase tracking-[0.2em] text-muted-foreground/80 px-1">Số hiệu chứng từ</Label>
                <Input v-model="form.certification.reference_no" placeholder="Ví dụ: (400) KN-2026..." class="h-12 font-mono font-bold rounded-2xl bg-muted/30 shadow-inner focus:ring-emerald-500/20" />
              </div>
            </div>
          </CardContent>
          <CardContent v-else class="py-12 px-8 flex justify-center">
             <p class="text-[10px] font-bold text-muted-foreground  uppercase tracking-widest text-center opacity-60">Bật nút gạt phía trên nếu sự kiện này có kèm theo kết quả kiểm định hoặc chứng nhận chất lượng.</p>
          </CardContent>
        </Card>

        <div class="space-y-3">
          <Label class="text-[10px] font-black uppercase tracking-[0.2em] text-muted-foreground/80 px-1">Ghi chú bổ sung</Label>
          <Textarea v-model="form.note" placeholder="Mô tả chi tiết quan sát, tình trạng sâu bệnh, thông số môi trường..." rows="4" class="rounded-2xl bg-muted/20 shadow-inner border-border/50 focus:border-sky-500/50 focus:ring-sky-500/20 font-medium  placeholder:opacity-40" />
          <p v-if="form.errors.note" class="text-[10px] font-black text-destructive uppercase px-1">{{ form.errors.note }}</p>
        </div>
      </div>

      <div class="lg:col-span-5 space-y-8">
        <!-- Section 3: Chọn lô -->
        <Card class="border-primary/20 bg-card/50 backdrop-blur-xl shadow-2xl shadow-primary/5 rounded-[2.5rem] overflow-hidden sticky top-8">
          <CardHeader class="border-b bg-muted/10 py-5 px-6">
            <div class="flex items-center justify-between">
               <div class="flex items-center gap-3">
                  <div class="p-2.5 rounded-xl bg-primary/10 text-primary shadow-inner">
                    <Archive class="w-4 h-4" />
                  </div>
                  <CardTitle class="text-[10px] font-black uppercase tracking-[0.3em] text-foreground ">Lô hàng mục tiêu</CardTitle>
               </div>
               <Badge variant="secondary" class="font-black text-[9px] uppercase tracking-widest px-3 py-1 bg-background shadow-inner">{{ form.input_batch_ids.length }} Đã chọn</Badge>
            </div>
          </CardHeader>
          <CardContent class="p-0">
            <div class="overflow-y-auto max-h-[500px] p-6">
              <div class="space-y-4">
                <div v-for="b in batches" :key="b.id" 
                  @click="toggleBatch(b.id)"
                  class="flex items-center gap-5 p-5 rounded-3xl border transition-all duration-300 cursor-pointer group relative overflow-hidden"
                  :class="form.input_batch_ids.includes(b.id) ? 'border-sky-500 bg-sky-500/5 ring-4 ring-sky-500/10 shadow-xl' : 'border-border/50 bg-background hover:border-sky-500/40 hover:bg-muted/50 shadow-sm'">
                  
                  <div class="w-6 h-6 rounded-lg border-2 flex items-center justify-center transition-all duration-300 shadow-inner" :class="form.input_batch_ids.includes(b.id) ? 'bg-sky-500 border-sky-500 scale-110 rotate-12' : 'bg-transparent border-muted-foreground/30'">
                     <CheckCircle v-if="form.input_batch_ids.includes(b.id)" class="w-4 h-4 text-white" />
                  </div>
                  
                  <div class="flex-1 min-w-0">
                    <div class="font-mono text-base font-black tracking-tighter" :class="form.input_batch_ids.includes(b.id) ? 'text-sky-600' : 'text-foreground'">{{ b.code }}</div>
                    <div class="text-[10px] font-bold text-muted-foreground uppercase tracking-widest mt-1 truncate group-hover:text-foreground/60 transition-colors">{{ b.product_name }}</div>
                  </div>
                  
                  <div class="text-right shrink-0">
                     <span class="text-sm font-black text-foreground group-hover:text-primary transition-colors">{{ b.current_quantity }}</span>
                     <span class="text-[9px] uppercase tracking-widest opacity-40 ml-1">{{ b.unit }}</span>
                  </div>
                </div>

                <div v-if="!batches.length" class="flex flex-col items-center justify-center py-24 border-2 border-dashed border-border/50 rounded-[2rem] bg-muted/10 text-muted-foreground opacity-60">
                   <Archive class="w-12 h-12 opacity-20 mb-4" />
                   <p class="text-[10px] font-black uppercase tracking-[0.2em]">Không có lô hàng khả dụng</p>
                </div>
              </div>
            </div>
          </CardContent>
          <CardFooter class="border-t bg-sky-500/5 p-6 flex flex-col gap-4">
            <div v-if="form.input_batch_ids.length" class="flex items-start gap-3 p-4 rounded-2xl bg-sky-500/10 border border-sky-500/20 text-sky-700 animate-in zoom-in duration-500 shadow-inner w-full">
               <Eye class="w-5 h-5 shrink-0 mt-0.5" />
               <p class="text-[10px] font-black uppercase tracking-widest leading-relaxed">Sự kiện sẽ được ghi nhận đồng thời cho {{ form.input_batch_ids.length }} lô hàng đã chọn.</p>
            </div>
            <p v-if="form.errors.input_batch_ids" class="text-center text-[10px] font-black text-destructive uppercase tracking-widest">{{ form.errors.input_batch_ids }}</p>

            <Button 
               type="submit" 
               class="w-full h-16 font-black uppercase tracking-[0.2em] text-[10px] shadow-2xl shadow-sky-500/30 bg-sky-600 hover:bg-sky-700 hover:scale-[1.02] active:scale-95 transition-all duration-500 rounded-2xl group relative overflow-hidden" 
               :disabled="form.processing || !form.input_batch_ids.length || !form.cte_code"
            >
               <div class="absolute inset-0 bg-white/20 translate-y-16 group-hover:translate-y-0 transition-transform duration-500 opacity-20"></div>
               <Eye class="w-4 h-4 mr-3 relative z-10 group-hover:scale-110 transition-transform" />
               <span class="relative z-10">{{ form.processing ? 'ĐANG LƯU DỮ LIỆU...' : 'HOÀN TẤT GHI NHẬN' }}</span>
            </Button>
          </CardFooter>
        </Card>
      </div>

    </form>
  </div>
</template>
