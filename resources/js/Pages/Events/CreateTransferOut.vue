<script setup>
import { ref } from 'vue'
import { Head, useForm, Link } from '@inertiajs/vue3'
import { Card, CardHeader, CardTitle, CardDescription, CardContent, CardFooter } from '@/Components/ui/card/index.js'
import { Button } from '@/Components/ui/button/index.js'
import { Input } from '@/Components/ui/input/index.js'
import { Label } from '@/Components/ui/label/index.js'
import { Checkbox } from '@/Components/ui/checkbox/index.js'
import { Badge } from '@/Components/ui/badge/index.js'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/Components/ui/select/index.js'
import { Textarea } from '@/Components/ui/textarea/index.js'
import { 
  Truck, 
  Users, 
  Archive, 
  FileText, 
  X,
  CheckCircle,
  ArchiveRestore
} from 'lucide-vue-next'

const props = defineProps({
  enterprises: Array,
  batches: Array,
})

const form = useForm({
  input_batch_ids: [],
  to_enterprise_id: '',
  event_time: new Date().toISOString().slice(0, 16),
  who_name: '',
  kde_data: {},
  note: '',
  gs1_document_ref: '',
})

function submit() {
  form.post(route('events.transfer.out'))
}

function toggleBatch(id) {
  const idx = form.input_batch_ids.indexOf(id)
  if (idx >= 0) form.input_batch_ids.splice(idx, 1)
  else form.input_batch_ids.push(id)
}
</script>

<template>
  <Head title="Nghiệp vụ chuyển giao hàng hóa" />

  <div class="max-w-6xl mx-auto space-y-8 pb-20">
    <!-- Header -->
    <div class="flex items-center justify-between gap-4">
      <div>
        <div class="text-[10px] font-black uppercase tracking-[0.3em] text-primary mb-1">Logistics / Supply Chain</div>
        <h1 class="text-4xl font-black  tracking-tighter text-foreground uppercase">Chuyển giao hàng hóa</h1>
        <p class="text-muted-foreground font-medium text-sm mt-1 opacity-70">Khởi tạo sự kiện luân chuyển lô hàng sang đối tác trong chuỗi cung ứng.</p>
      </div>
      <Button variant="ghost" size="icon" as-child class="h-12 w-12 rounded-2xl hover:bg-destructive/10 hover:text-destructive transition-colors">
         <Link :href="route('dashboard')">
            <X class="w-6 h-6" />
         </Link>
      </Button>
    </div>

    <form @submit.prevent="submit" class="grid grid-cols-1 lg:grid-cols-12 gap-8">
      
      <div class="lg:col-span-7 space-y-8">
        <!-- Section 1: Đối tác -->
        <Card class="border-purple-500/20 bg-card/50 backdrop-blur-sm shadow-xl shadow-purple-500/5 rounded-[2.5rem] overflow-hidden">
          <CardHeader class="border-b bg-purple-500/5 py-5 px-8">
            <div class="flex items-center gap-4">
              <div class="p-2.5 rounded-xl bg-purple-500/10 text-purple-600 shadow-inner">
                <Users class="w-5 h-5" />
              </div>
              <CardTitle class="text-xs font-black uppercase tracking-[0.3em] text-purple-700 ">Thông tin bên nhận</CardTitle>
            </div>
          </CardHeader>
          <CardContent class="p-8 space-y-6">
            <div class="space-y-3">
              <Label class="text-[10px] font-black uppercase tracking-[0.2em] text-muted-foreground/80 px-1">Doanh nghiệp nhận hàng *</Label>
              <Select v-model="form.to_enterprise_id">
                <SelectTrigger class="h-12 font-black rounded-2xl bg-muted/30 shadow-inner focus:ring-purple-500/20">
                  <SelectValue placeholder="— Chọn doanh nghiệp đối tác —" />
                </SelectTrigger>
                <SelectContent class="rounded-2xl">
                  <SelectItem v-for="ent in enterprises" :key="ent.id" :value="String(ent.id)" class="font-bold">
                    {{ ent.name }} <span class="text-[10px] text-muted-foreground ml-1">({{ ent.code }})</span>
                  </SelectItem>
                </SelectContent>
              </Select>
              <p v-if="form.errors.to_enterprise_id" class="text-[10px] font-black text-destructive uppercase px-1">{{ form.errors.to_enterprise_id }}</p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
              <div class="space-y-3">
                <Label class="text-[10px] font-black uppercase tracking-[0.2em] text-muted-foreground/80 px-1">Thời gian bàn giao *</Label>
                <Input v-model="form.event_time" type="datetime-local" class="h-12 font-black rounded-2xl bg-muted/30 shadow-inner focus:ring-purple-500/20" required />
                <p v-if="form.errors.event_time" class="text-[10px] font-black text-destructive uppercase px-1">{{ form.errors.event_time }}</p>
              </div>
              <div class="space-y-3">
                <Label class="text-[10px] font-black uppercase tracking-[0.2em] text-muted-foreground/80 px-1">Nhân viên thực hiện *</Label>
                <Input v-model="form.who_name" placeholder="Tên người bàn giao" class="h-12 font-bold rounded-2xl bg-muted/30 shadow-inner focus:ring-purple-500/20" required />
                <p v-if="form.errors.who_name" class="text-[10px] font-black text-destructive uppercase px-1">{{ form.errors.who_name }}</p>
              </div>
            </div>
          </CardContent>
        </Card>

        <!-- Section 2: Vận chuyển -->
        <Card class="border-dashed border-2 border-border/50 bg-muted/5 rounded-[2.5rem] overflow-hidden">
          <CardHeader class="border-b border-border/30 bg-muted/20 py-5 px-8">
            <div class="flex items-center gap-4">
              <div class="p-2.5 rounded-xl bg-background border border-border/50 text-muted-foreground shadow-sm">
                <Truck class="w-5 h-5" />
              </div>
              <CardTitle class="text-xs font-black uppercase tracking-[0.3em] text-foreground ">Vận chuyển & Chứng từ</CardTitle>
            </div>
          </CardHeader>
          <CardContent class="p-8 space-y-6">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
              <div class="space-y-3">
                <Label class="text-[10px] font-black uppercase tracking-[0.2em] text-muted-foreground/80 px-1">Số vận đơn / Hợp đồng</Label>
                <Input v-model="form.gs1_document_ref" placeholder="(400) SHIP-2026..." class="h-12 font-mono font-bold rounded-2xl bg-background shadow-inner focus:ring-primary/20" />
                <p v-if="form.errors.gs1_document_ref" class="text-[10px] font-black text-destructive uppercase px-1">{{ form.errors.gs1_document_ref }}</p>
              </div>
              <div class="space-y-3">
                <Label class="text-[10px] font-black uppercase tracking-[0.2em] text-muted-foreground/80 px-1">Phương tiện vận chuyển</Label>
                <Input v-model="form.kde_data.vehicle" placeholder="Số xe, loại xe..." class="h-12 font-bold rounded-2xl bg-background shadow-inner focus:ring-primary/20 uppercase" />
              </div>
            </div>

            <div class="space-y-3">
              <Label class="text-[10px] font-black uppercase tracking-[0.2em] text-muted-foreground/80 px-1">Ghi chú bàn giao</Label>
              <Textarea v-model="form.note" placeholder="Mô tả chi tiết tình trạng hàng hóa hoặc điều kiện bảo quản khi di chuyển..." rows="4" class="rounded-2xl bg-background shadow-inner border-border/50 focus:border-primary/50 focus:ring-primary/20 font-medium  placeholder:opacity-40" />
              <p v-if="form.errors.note" class="text-[10px] font-black text-destructive uppercase px-1">{{ form.errors.note }}</p>
            </div>
          </CardContent>
        </Card>
      </div>

      <div class="lg:col-span-5 space-y-8">
        <!-- Section 3: Chọn lô -->
        <Card class="border-primary/20 bg-card/50 backdrop-blur-xl shadow-2xl shadow-primary/5 rounded-[2.5rem] overflow-hidden sticky top-8">
          <CardHeader class="border-b bg-muted/10 py-5 px-6">
            <div class="flex items-center justify-between">
               <div class="flex items-center gap-4">
                  <div class="p-2.5 rounded-xl bg-primary/10 text-primary shadow-inner">
                    <ArchiveRestore class="w-5 h-5" />
                  </div>
                  <CardTitle class="text-[10px] font-black uppercase tracking-[0.3em] text-foreground ">Kiểm kê lô gửi đi</CardTitle>
               </div>
               <Badge variant="secondary" class="font-black text-[9px] uppercase tracking-widest px-3 py-1 bg-background shadow-inner">{{ form.input_batch_ids.length }} Lô</Badge>
            </div>
          </CardHeader>
          <CardContent class="p-0">
            <div class="overflow-y-auto max-h-[500px] p-6">
              <div class="space-y-4">
                <div v-for="b in batches" :key="b.id" 
                  @click="toggleBatch(b.id)"
                  class="flex items-center gap-5 p-5 rounded-3xl border transition-all duration-300 cursor-pointer group relative overflow-hidden"
                  :class="form.input_batch_ids.includes(b.id) ? 'border-primary bg-primary/5 ring-4 ring-primary/10 shadow-xl' : 'border-border/50 bg-background hover:border-primary/40 hover:bg-muted/50 shadow-sm'">
                  
                  <div class="w-6 h-6 rounded-lg border-2 flex items-center justify-center transition-all duration-300 shadow-inner" :class="form.input_batch_ids.includes(b.id) ? 'bg-primary border-primary scale-110 rotate-12' : 'bg-transparent border-muted-foreground/30'">
                     <CheckCircle v-if="form.input_batch_ids.includes(b.id)" class="w-4 h-4 text-primary-foreground" />
                  </div>
                  
                  <div class="flex-1 min-w-0">
                    <div class="font-mono text-base font-black tracking-tighter uppercase" :class="form.input_batch_ids.includes(b.id) ? 'text-primary' : 'text-foreground'">{{ b.code }}</div>
                    <div class="text-[10px] uppercase font-bold text-muted-foreground mt-0.5 truncate group-hover:text-foreground/60 transition-colors">{{ b.product_name }}</div>
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
          <CardFooter class="border-t bg-primary/5 p-6 flex flex-col gap-4">
            <div class="w-full space-y-4">
               <div v-if="form.input_batch_ids.length" class="flex items-start gap-3 p-4 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-700 animate-in zoom-in duration-500 shadow-inner w-full">
                  <CheckCircle class="w-5 h-5 shrink-0 mt-0.5 animate-pulse shadow-[0_0_12px_rgba(16,185,129,0.8)] rounded-full" />
                  <p class="text-[10px] font-black uppercase tracking-widest leading-relaxed">Sẵn sàng điều phối {{ form.input_batch_ids.length }} lô hàng sang đối tác.</p>
               </div>
               <p v-if="form.errors.input_batch_ids" class="text-center text-[10px] font-black text-destructive uppercase tracking-widest">{{ form.errors.input_batch_ids }}</p>

               <Button 
                  type="submit" 
                  class="w-full h-16 font-black uppercase tracking-[0.2em] text-[10px] shadow-2xl shadow-primary/30 rounded-2xl group relative overflow-hidden transition-all duration-500 hover:scale-[1.02] active:scale-95" 
                  :disabled="form.processing || !form.input_batch_ids.length || !form.to_enterprise_id"
               >
                  <div class="absolute inset-0 bg-white/20 translate-y-16 group-hover:translate-y-0 transition-transform duration-500 opacity-20"></div>
                  <Truck class="w-5 h-5 mr-3 relative z-10 group-hover:translate-x-2 transition-transform" />
                  <span class="relative z-10">{{ form.processing ? 'ĐANG ĐIỀU PHỐI...' : 'HOÀN TẤT CHUYỂN GIAO' }}</span>
               </Button>
            </div>
          </CardFooter>
        </Card>
      </div>

    </form>
  </div>
</template>
