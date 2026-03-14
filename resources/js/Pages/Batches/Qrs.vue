<script setup>
import { Head, useForm, Link } from '@inertiajs/vue3'
import { computed, onMounted, onUnmounted, ref, watch, nextTick } from 'vue'
import VietmapPlacePicker from '@/Components/VietmapPlacePicker.vue'
import QRCode from 'qrcode'
import { Card, CardContent, CardHeader, CardTitle, CardDescription, CardFooter } from '@/Components/ui/card/index.js'
import { Button } from '@/Components/ui/button/index.js'
import { Badge } from '@/Components/ui/badge/index.js'
import { Input } from '@/Components/ui/input/index.js'
import { Label } from '@/Components/ui/label/index.js'
import { 
  ArrowLeft,
  QrCode,
  Download,
  Copy,
  CheckCircle,
  MapPin,
  RefreshCw,
  Eye,
  Globe,
  Lock
} from 'lucide-vue-next'

const props = defineProps({
  batch:          Object,
  qrs:            Array,
  publicUrlBase:  String,
  privateUrlBase: String,
})

const publicQr  = computed(() => props.qrs?.find(q => q.type === 'public'))
const privateQr = computed(() => props.qrs?.find(q => q.type === 'private'))

const publicLink  = computed(() => {
  if (!publicQr.value) return ''
  return publicQr.value.gs1_digital_link || `${props.publicUrlBase}/${publicQr.value.token}`
})
const privateLink = computed(() => {
  if (!privateQr.value) return ''
  return privateQr.value.gs1_digital_link || `${props.privateUrlBase}/${privateQr.value.token}`
})

// ── Vietmap picker ────────────────────────────────────────
const place = ref({
  place_name: publicQr.value?.place_name  || '',
  lat:        publicQr.value?.allowed_lat || '',
  lng:        publicQr.value?.allowed_lng || '',
  refid:      '',
})

// ── Forms ─────────────────────────────────────────────────
const formEnsure = useForm({})
const formPublic = useForm({
  place_name:       place.value.place_name,
  allowed_lat:      place.value.lat,
  allowed_lng:      place.value.lng,
  allowed_radius_m: publicQr.value?.allowed_radius_m ?? 50,
})

// Đã lưu cấu hình chưa (dùng để ẩn/hiện form)
const configSaved = ref(
  !!(publicQr.value?.place_name && publicQr.value?.allowed_lat && publicQr.value?.allowed_radius_m)
)

function ensureQrs() {
  formEnsure.post(route('batches.qrs.ensure', props.batch.id))
}

function syncFromPicker(val) {
  place.value            = val
  formPublic.place_name  = val.place_name
  formPublic.allowed_lat = val.lat
  formPublic.allowed_lng = val.lng
}

function savePublic() {
  if (!publicQr.value) return
  formPublic.post(route('qrcodes.configurePublic', publicQr.value.id), {
    onSuccess: () => {
      configSaved.value    = true
      showMapPreview.value = false
      destroyMap('map-preview')
      // Render map xác nhận sau khi lưu
      nextTick(() => renderMap(
        formPublic.allowed_lat,
        formPublic.allowed_lng,
        formPublic.allowed_radius_m,
        'map-saved'
      ))
    },
  })
}

function editConfig() {
  configSaved.value = false
  nextTick(() => destroyMap('map-saved'))
}

// ── QR PNG — live update ──────────────────────────────────
const publicQrPng  = ref('')
const privateQrPng = ref('')

async function genQrPng(text) {
  if (!text) return ''
  return QRCode.toDataURL(text, {
    errorCorrectionLevel: 'M',
    width: 800,
    margin: 2,
    color: { dark: '#000000', light: '#ffffff' },
  })
}

async function refreshQrImages() {
  publicQrPng.value  = await genQrPng(publicLink.value)
  privateQrPng.value = await genQrPng(privateLink.value)
}

onMounted(refreshQrImages)
watch([publicLink, privateLink], refreshQrImages)

function downloadQr(dataUrl, filename) {
  if (!dataUrl) return
  const a = document.createElement('a')
  a.href = dataUrl; a.download = filename
  document.body.appendChild(a); a.click(); a.remove()
}

// ── Copy link ─────────────────────────────────────────────
const copied = ref(null)
async function copyLink(text, key) {
  await navigator.clipboard.writeText(text)
  copied.value = key
  setTimeout(() => { copied.value = null }, 2000)
}

// ── Leaflet map ───────────────────────────────────────────
const maps = {}

function loadLeaflet() {
  return new Promise((resolve) => {
    if (window.L) { resolve(); return }
    const css = document.createElement('link')
    css.rel = 'stylesheet'
    css.href = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css'
    document.head.appendChild(css)
    const script = document.createElement('script')
    script.src = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js'
    script.onload = resolve
    document.head.appendChild(script)
  })
}

async function renderMap(lat, lng, radiusM, mapId) {
  if (!lat || !lng || !radiusM) return
  await loadLeaflet()
  await nextTick()
  const el = document.getElementById(mapId)
  if (!el) return
  if (maps[mapId]) { maps[mapId].remove(); delete maps[mapId] }
  const L   = window.L
  const map = L.map(el, { zoomControl: true, scrollWheelZoom: false })
  maps[mapId] = map
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '© OpenStreetMap', maxZoom: 19,
  }).addTo(map)
  const center = [parseFloat(lat), parseFloat(lng)]
  const r = parseInt(radiusM)
  L.marker(center).addTo(map)
  L.circle(center, {
    radius: r, color: '#f97316', fillColor: '#f97316',
    fillOpacity: 0.15, weight: 2,
  }).addTo(map)
  map.setView(center, r < 100 ? 18 : r < 500 ? 16 : 14)
}

function destroyMap(mapId) {
  if (maps[mapId]) { maps[mapId].remove(); delete maps[mapId] }
}

// Hiện map preview tự động khi đủ lat + lng + radius
const showMapPreview = ref(false)

watch(
  () => [formPublic.allowed_radius_m, formPublic.allowed_lat, formPublic.allowed_lng],
  ([r, lat, lng]) => {
    if (r && lat && lng) {
      showMapPreview.value = true
      nextTick(() => renderMap(lat, lng, r, 'map-preview'))
    } else {
      showMapPreview.value = false
      destroyMap('map-preview')
    }
  }
)

// Render map saved khi mount nếu đã có config
onMounted(() => {
  if (configSaved.value && publicQr.value?.allowed_lat) {
    nextTick(() => renderMap(
      publicQr.value.allowed_lat,
      publicQr.value.allowed_lng,
      publicQr.value.allowed_radius_m,
      'map-saved'
    ))
  }
})

onUnmounted(() => {
  Object.keys(maps).forEach(id => { maps[id]?.remove(); delete maps[id] })
})

function confirmMapPreview() {
  showMapPreview.value = false
  destroyMap('map-preview')
}

</script>

<template>
  <Head :title="`QR — Lô ${batch?.code}`" />

  <div class="max-w-6xl mx-auto space-y-8 pb-20">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-6">
      <div class="space-y-3">
        <nav class="flex items-center gap-2 text-[10px] font-black uppercase tracking-[0.3em] text-muted-foreground/60 bg-muted/20 w-fit px-4 py-1.5 rounded-full border border-border/50">
          <Link :href="route('batches.index')" class="hover:text-primary transition-colors">Quản lý lô hàng</Link>
          <span>/</span>
          <span class="text-foreground ">Phát hành QR</span>
        </nav>
        <div class="flex items-center gap-4">
           <div class="p-3 rounded-[1.5rem] bg-primary/10 text-primary shadow-inner">
              <QrCode class="w-8 h-8" />
           </div>
           <div>
              <h1 class="text-4xl font-black tracking-tighter text-foreground uppercase ">Lô {{ batch?.code }}</h1>
              <p class="text-[10px] font-bold text-muted-foreground uppercase tracking-[0.2em] mt-1">{{ batch?.product_name }}</p>
           </div>
        </div>
      </div>

      <div class="flex items-center gap-3">
        <Button variant="outline" size="sm" as-child class="font-black uppercase text-[9px] tracking-widest h-10 px-5 rounded-xl hover:bg-muted transition-all">
           <Link :href="route('batches.index')">
              <ArrowLeft class="w-4 h-4 mr-2" /> Quay lại
           </Link>
        </Button>
        <Button 
          size="sm" 
          @click="ensureQrs" 
          :disabled="formEnsure.processing"
          class="font-black uppercase text-[9px] tracking-[0.2em] h-10 px-6 rounded-xl transition-all"
          :class="(publicQr && privateQr) ? 'bg-background text-muted-foreground border border-border/50 hover:bg-muted shadow-sm' : 'shadow-xl shadow-primary/20 text-primary-foreground hover:scale-105'"
        >
           <RefreshCw v-if="formEnsure.processing" class="w-4 h-4 mr-2 animate-spin" />
           <QrCode v-else class="w-4 h-4 mr-2" />
           {{ (publicQr && privateQr) ? 'Khởi tạo lại QR' : 'Khởi tạo 2 mã QR' }}
        </Button>
      </div>
    </div>

    <!-- Empty State -->
    <div v-if="!publicQr && !privateQr" class="flex flex-col items-center justify-center py-40 border-2 border-dashed rounded-[3rem] bg-card/20 text-muted-foreground border-border/30 shadow-inner">
      <div class="p-10 rounded-full bg-muted/20 mb-8 group hover:scale-110 transition-transform duration-500 shadow-inner">
        <QrCode class="w-20 h-20 opacity-10 group-hover:opacity-30 transition-opacity" />
      </div>
      <h3 class="text-xl font-black uppercase tracking-[0.2em] text-foreground">Chưa phát hành QR</h3>
      <p class="text-[10px] font-bold mt-3 max-w-sm text-center uppercase tracking-widest opacity-40 ">Nhấn "Khởi tạo 2 mã QR" ở góc trên để tạo mã Public (trưng bày) và Private (in trên sản phẩm) cho lô hàng này.</p>
    </div>

    <!-- QR Cards -->
    <div v-else class="grid grid-cols-1 lg:grid-cols-2 gap-8">

      <!-- PUBLIC QR -->
      <Card class="border-border/50 bg-card/50 backdrop-blur-sm shadow-xl rounded-[2.5rem] overflow-hidden group">
        <CardHeader class="border-b bg-muted/30 py-5 px-8 flex flex-row items-center justify-between space-y-0">
          <div class="flex items-center gap-4">
             <div class="p-2.5 rounded-xl bg-background border border-border/50 text-muted-foreground shadow-sm group-hover:text-primary transition-colors">
               <Globe class="w-5 h-5" />
             </div>
             <div>
                <CardTitle class="text-xs font-black uppercase tracking-[0.3em] text-foreground  group-hover:text-primary transition-colors">Mã QR Public</CardTitle>
                <CardDescription class="text-[9px] font-black uppercase tracking-[0.2em] opacity-50 mt-0.5">Đặt tại quầy / điểm bán</CardDescription>
             </div>
          </div>
          <Badge variant="outline" class="font-black text-[9px] uppercase tracking-widest bg-primary/5 text-primary border-primary/20 shadow-sm px-3 py-1">PUBLIC</Badge>
        </CardHeader>
        
        <CardContent class="p-8 space-y-8">
          <!-- Info / Image -->
          <div class="flex flex-col sm:flex-row gap-8 items-center sm:items-start">
            <div class="shrink-0 space-y-4">
              <div class="w-48 h-48 rounded-3xl overflow-hidden bg-white p-2 shadow-inner border border-border/50 flex items-center justify-center group-hover:scale-105 transition-transform duration-500">
                <img v-if="publicQrPng" :src="publicQrPng" alt="QR Public" class="w-full h-full object-contain mix-blend-multiply" />
                <div v-else class="text-[9px] font-black uppercase text-muted-foreground/30 animate-pulse">Đang tải...</div>
              </div>
              <Button @click="downloadQr(publicQrPng, `QR_PUBLIC_${batch.code}.png`)" :disabled="!publicQrPng" variant="outline" class="w-full font-black uppercase text-[9px] tracking-widest h-10 rounded-xl hover:bg-primary/10 hover:text-primary transition-all shadow-sm">
                 <Download class="w-3.5 h-3.5 mr-2" /> Tải ảnh PNG
              </Button>
            </div>

            <div class="flex-1 w-full space-y-4">
              <div class="space-y-2">
                 <Label class="text-[9px] font-black uppercase tracking-[0.2em] text-muted-foreground/60 px-1">Link truy xuất</Label>
                 <div class="relative group/link">
                   <div class="p-4 rounded-2xl bg-muted/30 border border-border/50 font-mono text-[10px] font-bold text-foreground break-all shadow-inner leading-relaxed pr-12 group-hover/link:bg-background transition-colors">
                     {{ publicLink }}
                   </div>
                   <Button variant="ghost" size="icon" @click="copyLink(publicLink, 'public')" class="absolute right-2 top-1/2 -translate-y-1/2 w-8 h-8 rounded-xl hover:bg-primary/10 hover:text-primary text-muted-foreground transition-all">
                     <CheckCircle v-if="copied === 'public'" class="w-4 h-4 text-emerald-500" />
                     <Copy v-else class="w-4 h-4" />
                   </Button>
                 </div>
              </div>

              <div v-if="publicQr.place_name" class="p-4 rounded-2xl bg-primary/5 border border-primary/10 shadow-inner space-y-3 relative overflow-hidden group-hover:bg-primary/10 transition-colors">
                 <div class="absolute -right-4 -bottom-4 opacity-5 group-hover:scale-150 transition-transform duration-700 text-primary">
                    <MapPin class="w-20 h-20" />
                 </div>
                 <div class="relative z-10 space-y-1">
                    <Label class="text-[9px] font-black uppercase tracking-[0.2em] text-primary/60">Vị trí phát hành</Label>
                    <p class="text-xs font-bold text-foreground">{{ publicQr.place_name }}</p>
                 </div>
                 <div class="relative z-10 space-y-1">
                    <Label class="text-[9px] font-black uppercase tracking-[0.2em] text-primary/60">Bán kính cho phép</Label>
                    <p class="text-xs font-black text-primary">{{ publicQr.allowed_radius_m }} mét</p>
                 </div>
              </div>
              <div v-else class="p-4 rounded-2xl bg-amber-500/5 border border-amber-500/20 text-amber-600 flex gap-3 shadow-inner">
                 <AlertTriangle class="w-4 h-4 shrink-0 mt-0.5" />
                 <p class="text-[10px] font-black uppercase tracking-widest leading-relaxed">Chưa cấu hình tọa độ giới hạn quét mã.</p>
              </div>
            </div>
          </div>

          <Separator border-dashed class="opacity-50" />

          <!-- Config Area -->
          <div v-if="configSaved && publicQr.allowed_lat" class="space-y-4 animate-in fade-in slide-in-from-bottom-4 duration-500">
            <div id="map-saved" class="w-full h-56 rounded-[2rem] overflow-hidden border-2 border-emerald-500/20 shadow-inner z-0"></div>
            <p class="text-[9px] font-black text-muted-foreground uppercase tracking-widest text-center ">
              Vùng hợp lệ quét QR — bán kính {{ publicQr.allowed_radius_m }}m
            </p>
            <Button variant="outline" @click="editConfig" class="w-full font-black uppercase text-[10px] tracking-widest h-12 rounded-2xl border-dashed hover:bg-muted transition-all shadow-sm">
              <Pencil class="w-4 h-4 mr-2" /> Thay đổi cấu hình
            </Button>
          </div>

          <div v-if="!configSaved" class="space-y-6 animate-in fade-in slide-in-from-top-4 duration-500">
            <div class="flex items-center gap-2 text-[10px] font-black uppercase tracking-[0.2em] text-foreground px-1">
               <MapPin class="w-3.5 h-3.5 text-primary" /> Cấu hình vị trí phát hành
            </div>
            
            <div class="space-y-2">
               <Label class="text-[9px] font-black uppercase tracking-[0.2em] text-muted-foreground/60 px-1">Tìm kiếm địa điểm</Label>
               <VietmapPlacePicker :modelValue="place" @update:modelValue="syncFromPicker" />
            </div>
            
            <div class="space-y-2">
              <Label class="text-[9px] font-black uppercase tracking-[0.2em] text-muted-foreground/60 px-1">Bán kính hợp lệ (mét)</Label>
              <Input type="number" v-model="formPublic.allowed_radius_m" min="1" max="5000" placeholder="VD: 100" class="h-12 font-black rounded-xl shadow-inner bg-muted/30 focus:bg-background" />
              <p v-if="formPublic.errors.allowed_radius_m" class="text-xs text-destructive mt-1">{{ formPublic.errors.allowed_radius_m }}</p>
            </div>

            <!-- Preview Map -->
            <div v-if="showMapPreview" class="p-4 rounded-[2rem] bg-primary/5 border border-primary/20 space-y-4 shadow-inner animate-in zoom-in duration-500">
              <div class="flex items-center gap-2 px-2">
                 <Eye class="w-4 h-4 text-primary" />
                 <Label class="text-[9px] font-black uppercase tracking-[0.2em] text-primary">Xem trước vùng hợp lệ</Label>
              </div>
              <div id="map-preview" class="w-full h-48 rounded-[1.5rem] overflow-hidden border border-primary/10 z-0"></div>
              <Button @click="confirmMapPreview" variant="secondary" class="w-full h-10 rounded-xl font-black uppercase text-[10px] tracking-widest shadow-sm bg-primary/10 text-primary hover:bg-primary hover:text-white transition-colors">
                OK — Xác nhận tọa độ
              </Button>
            </div>

            <Button @click="savePublic" :disabled="formPublic.processing" class="w-full h-14 font-black uppercase tracking-[0.3em] text-[10px] shadow-xl shadow-primary/20 rounded-2xl transition-all hover:scale-[1.02] active:scale-95">
              {{ formPublic.processing ? 'ĐANG LƯU...' : 'LƯU CẤU HÌNH' }}
            </Button>
            <p class="text-[9px] text-muted-foreground  font-bold leading-relaxed px-4 text-center">
              * Mã QR public chỉ có thể được quét thành công nếu thiết bị của người dùng nằm trong khu vực hình tròn được chỉ định trên bản đồ.
            </p>
          </div>
        </CardContent>
      </Card>

      <!-- PRIVATE QR -->
      <Card class="border-border/50 bg-card/50 backdrop-blur-sm shadow-xl rounded-[2.5rem] overflow-hidden group">
        <CardHeader class="border-b bg-muted/30 py-5 px-8 flex flex-row items-center justify-between space-y-0">
          <div class="flex items-center gap-4">
             <div class="p-2.5 rounded-xl bg-background border border-border/50 text-muted-foreground shadow-sm group-hover:text-primary transition-colors">
               <Lock class="w-5 h-5" />
             </div>
             <div>
                <CardTitle class="text-xs font-black uppercase tracking-[0.3em] text-foreground  group-hover:text-primary transition-colors">Mã QR Private</CardTitle>
                <CardDescription class="text-[9px] font-black uppercase tracking-[0.2em] opacity-50 mt-0.5">In trên bao bì sản phẩm</CardDescription>
             </div>
          </div>
          <Badge variant="secondary" class="font-black text-[9px] uppercase tracking-widest bg-muted shadow-sm px-3 py-1">PRIVATE</Badge>
        </CardHeader>
        
        <CardContent class="p-8 space-y-8">
          <!-- Info / Image -->
          <div class="flex flex-col sm:flex-row gap-8 items-center sm:items-start">
            <div class="shrink-0 space-y-4">
              <div class="w-48 h-48 rounded-3xl overflow-hidden bg-white p-2 shadow-inner border border-border/50 flex items-center justify-center group-hover:scale-105 transition-transform duration-500">
                <img v-if="privateQrPng" :src="privateQrPng" alt="QR Private" class="w-full h-full object-contain mix-blend-multiply" />
                <div v-else class="text-[9px] font-black uppercase text-muted-foreground/30 animate-pulse">Đang tải...</div>
              </div>
              <Button @click="downloadQr(privateQrPng, `QR_PRIVATE_${batch.code}.png`)" :disabled="!privateQrPng" variant="outline" class="w-full font-black uppercase text-[9px] tracking-widest h-10 rounded-xl hover:bg-primary/10 hover:text-primary transition-all shadow-sm">
                 <Download class="w-3.5 h-3.5 mr-2" /> Tải ảnh PNG
              </Button>
            </div>

            <div class="flex-1 w-full space-y-4">
              <div class="space-y-2">
                 <Label class="text-[9px] font-black uppercase tracking-[0.2em] text-muted-foreground/60 px-1">Link truy xuất</Label>
                 <div class="relative group/link">
                   <div class="p-4 rounded-2xl bg-muted/30 border border-border/50 font-mono text-[10px] font-bold text-foreground break-all shadow-inner leading-relaxed pr-12 group-hover/link:bg-background transition-colors">
                     {{ privateLink }}
                   </div>
                   <Button variant="ghost" size="icon" @click="copyLink(privateLink, 'private')" class="absolute right-2 top-1/2 -translate-y-1/2 w-8 h-8 rounded-xl hover:bg-primary/10 hover:text-primary text-muted-foreground transition-all">
                     <CheckCircle v-if="copied === 'private'" class="w-4 h-4 text-emerald-500" />
                     <Copy v-else class="w-4 h-4" />
                   </Button>
                 </div>
              </div>

              <div class="p-4 rounded-2xl bg-muted/20 border border-dashed border-border/50 space-y-2 shadow-inner group-hover:bg-background transition-colors">
                 <div class="flex items-center gap-2 text-[10px] font-black uppercase tracking-widest text-muted-foreground/80">
                    <CheckCircle class="w-3.5 h-3.5 text-emerald-500" /> Không giới hạn vị trí quét
                 </div>
                 <div class="flex items-start gap-2 text-[10px] font-black uppercase tracking-widest text-muted-foreground/80 leading-relaxed">
                    <AlertTriangle class="w-3.5 h-3.5 text-amber-500 shrink-0 mt-0.5" /> 
                    <span>Hết hiệu lực sau <span class="text-foreground bg-muted px-1.5 py-0.5 rounded mx-0.5">48 GIỜ</span> kể từ lần quét đầu tiên</span>
                 </div>
              </div>

              <div v-if="privateQr.first_scanned_at" class="p-4 rounded-2xl bg-destructive/5 border border-destructive/20 shadow-inner space-y-3 relative overflow-hidden">
                 <div class="relative z-10 space-y-1">
                    <Label class="text-[8px] font-black uppercase tracking-[0.3em] text-destructive/60">Lần quét đầu tiên</Label>
                    <p class="text-xs font-mono font-black text-destructive">{{ privateQr.first_scanned_at }}</p>
                 </div>
                 <div class="relative z-10 space-y-1">
                    <Label class="text-[8px] font-black uppercase tracking-[0.3em] text-destructive/60">Hạn chót quét mã</Label>
                    <p class="text-xs font-mono font-black text-destructive">{{ privateQr.expires_at }}</p>
                 </div>
              </div>
              <div v-else class="p-4 rounded-2xl bg-muted/10 border border-border/30 text-center shadow-inner">
                 <p class="text-[10px] font-bold  uppercase tracking-widest text-muted-foreground/40">Chưa được quét lần nào</p>
              </div>
            </div>
          </div>

          <Separator border-dashed class="opacity-50" />
          
          <div class="flex items-start gap-3 text-[9px] text-muted-foreground/60  font-bold leading-relaxed px-2">
             <Info class="w-4 h-4 shrink-0 text-primary opacity-50" />
             Hệ thống sẽ tự động ghi log thời gian, thông tin thiết bị và tọa độ GPS (nếu được phép) trong mỗi lượt người dùng quét mã.
          </div>

        </CardContent>
      </Card>
    </div>

  </div>
</template>
