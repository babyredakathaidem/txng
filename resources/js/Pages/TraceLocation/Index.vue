<script setup>
import { ref, computed } from 'vue'
import { Head, useForm, Link } from '@inertiajs/vue3'
import { Card, CardHeader, CardTitle, CardDescription, CardContent, CardFooter } from '@/Components/ui/card/index.js'
import { Button } from '@/Components/ui/button/index.js'
import { Badge } from '@/Components/ui/badge/index.js'
import { Input } from '@/Components/ui/input/index.js'
import { Label } from '@/Components/ui/label/index.js'
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/Components/ui/dialog/index.js'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/Components/ui/select/index.js'
import { Textarea } from '@/Components/ui/textarea/index.js'
import { 
  MapPin, 
  Plus, 
  Pencil, 
  Trash2, 
  Map as MapIcon,
  Globe,
  Fingerprint,
  Info,
  Navigation,
  Factory,
  Warehouse,
  Home,
  Sprout,
  ChevronRight
} from 'lucide-vue-next'

const props = defineProps({
  locations: Object,
  ai_labels: Object,
})

const showAddForm = ref(false)
const editingId  = ref(null)

const form = useForm({
  name: '',
  ai_type: '416',
  gln: '',
  code: '',
  province: '',
  district: '',
  address_detail: '',
  lat: null,
  lng: null,
  area_ha: null,
  farm_code: '',
  status: 'active',
  note: '',
})

function openAdd() {
  editingId.value = null
  form.reset()
  showAddForm.value = true
}

function openEdit(loc) {
  editingId.value = loc.id
  form.name           = loc.name
  form.ai_type        = String(loc.ai_type)
  form.gln            = loc.gln
  form.code           = loc.code
  form.province       = loc.province
  form.district       = loc.district
  form.address_detail = loc.address_detail
  form.lat            = loc.lat
  form.lng            = loc.lng
  form.area_ha        = loc.area_ha
  form.farm_code      = loc.farm_code
  form.status         = loc.status
  form.note           = loc.note
  showAddForm.value   = true
}

function submit() {
  if (editingId.value) {
    form.put(route('trace-locations.update', editingId.value), {
      onSuccess: () => { showAddForm.value = false }
    })
  } else {
    form.post(route('trace-locations.store'), {
      onSuccess: () => { showAddForm.value = false }
    })
  }
}

function deleteLocation(id) {
  if (confirm('Bạn có chắc muốn xóa địa điểm này?')) {
    form.delete(route('trace-locations.destroy', id))
  }
}

// ── GPS ───────────────────────────────────────────────────
const gpsLoading = ref(false)
function getGps() {
  gpsLoading.value = true
  navigator.geolocation.getCurrentPosition(
    (pos) => {
      form.lat = pos.coords.latitude
      form.lng = pos.coords.longitude
      gpsLoading.value = false
    },
    (err) => { alert('Lỗi GPS: ' + err.message); gpsLoading.value = false },
    { enableHighAccuracy: true }
  )
}
</script>

<template>
  <Head title="Quản lý địa điểm truy vết" />

  <div class="space-y-8 animate-in fade-in duration-700">
    <!-- Header chuẩn Dashboard -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
      <div class="space-y-3">
        <nav class="flex items-center gap-2 text-[10px] font-black uppercase tracking-[0.3em] text-muted-foreground/60 bg-muted/20 w-fit px-4 py-1.5 rounded-full border border-border/50">
          <Link href="/dashboard" class="hover:text-primary transition-colors">Home</Link>
          <ChevronRight class="w-3 h-3 opacity-20" />
          <span class="text-foreground ">Trace Locations</span>
        </nav>
        <div>
          <h1 class="text-4xl font-black tracking-tighter text-foreground uppercase ">Địa điểm truy vết</h1>
          <p class="text-muted-foreground font-medium text-sm mt-1 opacity-70">Quản lý RUỘNG LÚA, KHO BÃI, NHÀ MÁY theo chuẩn định danh GS1 (GLN).</p>
        </div>
      </div>
      
      <div class="flex items-center gap-3 pt-4">
        <Button @click="openAdd" class="h-12 px-8 font-black uppercase tracking-widest text-[10px] shadow-xl shadow-primary/20 rounded-2xl group active:scale-95 transition-all">
           <Plus class="w-5 h-5 mr-3 group-hover:scale-110 transition-transform" /> Khai báo địa điểm
        </Button>
      </div>
    </div>

    <!-- Empty state -->
    <div v-if="locations.data.length === 0 && !showAddForm" class="flex flex-col items-center justify-center py-48 border-2 border-dashed border-border/30 rounded-[3rem] bg-card/20 text-muted-foreground shadow-inner">
      <div class="p-12 rounded-full bg-muted/20 mb-8 group hover:scale-110 transition-transform duration-700 shadow-inner">
        <MapPin class="w-24 h-24 opacity-10 group-hover:opacity-30 transition-opacity" />
      </div>
      <h3 class="text-2xl font-black uppercase tracking-[0.2em] text-foreground ">Chưa có địa điểm</h3>
      <p class="text-[10px] font-bold mt-4 max-w-xs text-center uppercase tracking-widest opacity-40 leading-relaxed ">Hãy bắt đầu bằng việc khai báo ruộng lúa hoặc cơ sở chế biến đầu tiên của bạn.</p>
      <Button variant="outline" class="mt-10 font-black text-[10px] uppercase tracking-widest h-12 px-10 rounded-2xl border-2 hover:bg-muted transition-all" @click="openAdd">Khai báo ngay</Button>
    </div>

    <!-- Grid danh sách -->
    <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8 pb-10">
      <Card v-for="loc in locations.data" :key="loc.id" class="group border-border/50 bg-card/50 backdrop-blur-sm hover:bg-card hover:border-primary/50 transition-all duration-500 shadow-sm hover:shadow-2xl hover:-translate-y-2 overflow-hidden rounded-[2.5rem]">
        <CardHeader class="p-8 pb-4">
          <div class="flex items-start justify-between">
            <div class="p-4 rounded-2xl bg-primary/10 text-primary group-hover:bg-primary group-hover:text-white transition-all duration-500 shadow-inner">
              <Sprout v-if="loc.ai_type === '416'" class="w-8 h-8" />
              <Factory v-else-if="loc.ai_type === '414'" class="w-8 h-8" />
              <Warehouse v-else class="w-8 h-8" />
            </div>
            <div class="flex gap-1.5 opacity-0 group-hover:opacity-100 transition-all duration-300 translate-x-4 group-hover:translate-x-0">
              <Button variant="ghost" size="icon" @click="openEdit(loc)" class="h-10 w-10 rounded-xl hover:bg-blue-500/10 hover:text-blue-500 shadow-sm">
                <Pencil class="w-5 h-5" />
              </Button>
              <Button variant="ghost" size="icon" @click="deleteLocation(loc.id)" class="h-10 w-10 rounded-xl hover:bg-destructive/10 hover:text-destructive shadow-sm">
                <Trash2 class="w-5 h-5" />
              </Button>
            </div>
          </div>
          <div class="mt-8 space-y-3">
             <CardTitle class="text-2xl font-black tracking-tight text-foreground group-hover:text-primary transition-colors line-clamp-1  uppercase">{{ loc.name }}</CardTitle>
             <div class="flex flex-wrap items-center gap-2">
                <Badge variant="outline" class="font-mono text-[10px] font-black uppercase tracking-widest bg-background/50 border-primary/20 text-primary px-3 py-1 rounded-lg">GLN: {{ loc.gln }}</Badge>
                <Badge variant="secondary" class="text-[9px] font-black uppercase tracking-widest px-3 py-1 bg-muted rounded-lg">{{ ai_labels[loc.ai_type] }}</Badge>
             </div>
          </div>
        </CardHeader>
        <CardContent class="px-8 py-4">
           <div class="flex items-start gap-4 text-muted-foreground bg-muted/30 p-5 rounded-3xl border border-border/30 group-hover:bg-background/50 transition-colors shadow-inner">
             <MapIcon class="w-5 h-5 shrink-0 mt-0.5 opacity-40 text-primary" />
             <span class="text-[11px] font-bold leading-relaxed tracking-tight line-clamp-2 uppercase  opacity-70">{{ [loc.address_detail, loc.district, loc.province].filter(Boolean).join(', ') || 'Chưa có thông tin địa chỉ' }}</span>
           </div>
        </CardContent>
        <CardFooter class="px-8 pt-2 pb-8 flex items-center justify-between">
           <div v-if="loc.lat" class="flex items-center gap-2 text-[10px] font-black tracking-widest text-muted-foreground/40 uppercase bg-muted/50 px-4 py-1.5 rounded-full group-hover:text-primary/60 transition-colors border border-border/50">
              <Globe class="w-3.5 h-3.5" />
              {{ loc.lat.toFixed(5) }}, {{ loc.lng.toFixed(5) }}
           </div>
           <div v-if="loc.code" class="text-[10px] font-black text-muted-foreground/20 uppercase tracking-[0.3em] group-hover:text-primary/20 transition-colors">
             #{{ loc.code }}
           </div>
        </CardFooter>
      </Card>
    </div>


    <!-- Dialog Form -->
    <Dialog :open="showAddForm" @update:open="(v) => (!v && (showAddForm = false))">
      <DialogContent class="sm:max-w-[650px] max-h-[90vh] overflow-y-auto rounded-[3rem] p-0 border-border/50 bg-card/95 backdrop-blur-2xl shadow-2xl">
        <DialogHeader class="p-10 pb-0">
          <div class="flex items-center gap-6 mb-2">
             <div class="p-4 rounded-[1.5rem] bg-primary text-white shadow-xl shadow-primary/20">
                <MapPin class="size-8" />
             </div>
             <div>
                <DialogTitle class="text-3xl font-black  tracking-tighter uppercase">{{ editingId ? 'Cập nhật địa điểm' : 'Khai báo địa điểm' }}</DialogTitle>
                <DialogDescription class="text-[10px] font-bold uppercase tracking-widest text-muted-foreground/60 mt-1">Chuẩn định danh vị trí toàn cầu GLN</DialogDescription>
             </div>
          </div>
        </DialogHeader>

        <div class="p-10 pt-6 space-y-10">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <div class="space-y-3">
              <Label class="text-[10px] font-black uppercase tracking-[0.2em] text-primary/60 px-1 ">Tên gợi nhớ *</Label>
              <Input v-model="form.name" placeholder="Ví dụ: Vùng trồng lúa 01..." class="h-12 font-black rounded-xl bg-muted/30 border-border/50 shadow-inner focus:bg-background" />
              <p v-if="form.errors.name" class="text-xs font-black text-destructive uppercase px-1 ">{{ form.errors.name }}</p>
            </div>
            <div class="space-y-3">
              <Label class="text-[10px] font-black uppercase tracking-[0.2em] text-muted-foreground/60 px-1">Phân loại chuẩn *</Label>
              <Select v-model="form.ai_type">
                <SelectTrigger class="h-12 font-bold rounded-xl bg-muted/30 border-border/50 shadow-inner">
                  <SelectValue placeholder="Chọn loại" />
                </SelectTrigger>
                <SelectContent class="rounded-xl">
                  <SelectItem v-for="(label, ai) in ai_labels" :key="ai" :value="String(ai)" class="font-bold">
                    ({{ ai }}) {{ label }}
                  </SelectItem>
                </SelectContent>
              </Select>
            </div>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <div class="space-y-3">
              <Label class="text-[10px] font-black uppercase tracking-[0.2em] text-primary/60 px-1 ">Mã GLN (13 số) *</Label>
              <Input v-model="form.gln" placeholder="893..." class="h-12 font-mono font-black text-lg tracking-widest rounded-xl bg-muted/30 border-border/50 shadow-inner focus:bg-background" />
              <p v-if="form.errors.gln" class="text-xs font-black text-destructive uppercase px-1 ">{{ form.errors.gln }}</p>
            </div>
            <div class="space-y-3">
              <Label class="text-[10px] font-black uppercase tracking-[0.2em] text-muted-foreground/60 px-1">Mã quản lý nội bộ</Label>
              <Input v-model="form.code" placeholder="VD: FAR-001" class="h-12 font-mono font-bold rounded-xl bg-muted/30 border-border/50 shadow-inner focus:bg-background" />
            </div>
          </div>

          <Separator border-dashed class="opacity-50" />

          <div class="space-y-8">
            <div class="flex items-center gap-3 px-1">
               <Navigation class="size-4 text-primary" />
               <span class="text-[10px] font-black uppercase tracking-[0.3em] text-foreground">Vị trí địa lý & Hành chính</span>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
              <div class="space-y-3">
                <Label class="text-[10px] font-black uppercase tracking-[0.2em] text-muted-foreground/60 px-1">Tỉnh / Thành phố</Label>
                <Input v-model="form.province" class="h-12 font-bold rounded-xl bg-muted/30 border-border/50 shadow-inner focus:bg-background" />
              </div>
              <div class="space-y-3">
                <Label class="text-[10px] font-black uppercase tracking-[0.2em] text-muted-foreground/60 px-1">Quận / Huyện</Label>
                <Input v-model="form.district" class="h-12 font-bold rounded-xl bg-muted/30 border-border/50 shadow-inner focus:bg-background" />
              </div>
              <div class="md:col-span-2 space-y-3">
                <Label class="text-[10px] font-black uppercase tracking-[0.2em] text-muted-foreground/60 px-1">Địa chỉ chi tiết (Thôn, ấp, số nhà...)</Label>
                <Input v-model="form.address_detail" class="h-12 font-bold rounded-xl bg-muted/30 border-border/50 shadow-inner focus:bg-background " />
              </div>
            </div>

            <!-- GPS BOX -->
            <Card class="bg-primary/5 border-primary/20 rounded-[2rem] overflow-hidden shadow-inner relative">
               <div class="absolute right-0 top-0 w-24 h-24 bg-primary/5 blur-2xl rounded-full"></div>
               <CardHeader class="p-6 pb-2">
                  <div class="flex items-center justify-between">
                     <div class="flex items-center gap-3 text-primary">
                        <div class="p-2 rounded-lg bg-primary/10 shadow-sm"><Globe class="size-4" /></div>
                        <span class="text-[10px] font-black uppercase tracking-widest">Tọa độ GPS chuẩn xác</span>
                     </div>
                     <Button variant="ghost" size="sm" class="h-9 px-4 text-[9px] font-black uppercase tracking-widest text-primary hover:bg-primary/10 rounded-xl" @click="getGps" :disabled="gpsLoading">
                        <RefreshCw v-if="gpsLoading" class="size-3 mr-2 animate-spin" />
                        <Navigation v-else class="size-3 mr-2" />
                        Lấy vị trí hiện tại
                     </Button>
                  </div>
               </CardHeader>
               <CardContent class="p-6 pt-2 grid grid-cols-2 gap-6 relative z-10">
                  <div class="space-y-2">
                     <Label class="text-[8px] font-black uppercase text-muted-foreground/60 tracking-[0.2em] px-1">Vĩ độ (Latitude)</Label>
                     <Input v-model="form.lat" type="number" step="any" class="h-10 text-xs font-mono font-bold bg-background/50 rounded-xl border-primary/10" />
                  </div>
                  <div class="space-y-2">
                     <Label class="text-[8px] font-black uppercase text-muted-foreground/60 tracking-[0.2em] px-1">Kinh độ (Longitude)</Label>
                     <Input v-model="form.lng" type="number" step="any" class="h-10 text-xs font-mono font-bold bg-background/50 rounded-xl border-primary/10" />
                  </div>
               </CardContent>
            </Card>
          </div>

          <div class="space-y-3">
            <Label class="text-[10px] font-black uppercase tracking-[0.2em] text-muted-foreground/60 px-1 ">Ghi chú & Đặc điểm địa danh</Label>
            <Textarea v-model="form.note" rows="3" class="rounded-xl bg-muted/30 border-border/50 shadow-inner focus:bg-background font-medium " />
          </div>
        </div>

        <DialogFooter class="p-10 bg-muted/30 border-t border-border/50 gap-4">
          <Button variant="ghost" @click="showAddForm = false" class="h-14 px-8 rounded-2xl font-black uppercase text-[10px] tracking-widest">Hủy bỏ</Button>
          <Button @click="submit" :disabled="form.processing" class="h-14 px-12 rounded-2xl font-black uppercase text-[10px] tracking-[0.2em] shadow-xl shadow-primary/20 active:scale-95 transition-all">
             <CheckCircle class="size-4 mr-2" /> {{ form.processing ? 'ĐANG LƯU...' : 'XÁC NHẬN KHAI BÁO' }}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  </div>
</template>
