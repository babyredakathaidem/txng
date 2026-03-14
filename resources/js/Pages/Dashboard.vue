<script setup>
import { computed } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/Components/ui/card/index.js'
import { Badge } from '@/Components/ui/badge/index.js'
import { Button } from '@/Components/ui/button/index.js'
import {
  Package, MapPin, Archive, Activity,
  QrCode, FlaskConical, ArrowRight,
  Truck, ArrowDownLeft
} from 'lucide-vue-next'

const page = usePage()
const user = computed(() => page.props?.auth?.user ?? null)

const props = defineProps({
  stats:         { type: Object, default: () => ({}) },
  scanTrend:     { type: Array,  default: () => [] },
  batchProgress: { type: Array,  default: () => [] },
  recentScans:   { type: Array,  default: () => [] },
  topBatches:    { type: Array,  default: () => [] },
})

const statCards = computed(() => [
  { label: 'Sản phẩm',    value: props.stats.products ?? 0,              href: '/products',        icon: Package,    sub: 'Danh mục' },
  { label: 'Địa điểm',    value: props.stats.locations_count ?? 0,       href: '/trace-locations', icon: MapPin,     sub: 'Ruộng & Kho' },
  { label: 'Lô hàng',     value: props.stats.batches?.total ?? 0,        href: '/batches',         icon: Archive, sub: `${props.stats.batches?.active ?? 0} active` },
  { label: 'Sự kiện',     value: props.stats.events?.total ?? 0,         href: '/events',          icon: Activity,   sub: `${props.stats.events?.published ?? 0} published` },
  { label: 'Lượt quét QR', value: props.stats.scan_count ?? 0,           href: '/qrcodes',         icon: QrCode,     sub: `${props.stats.scan_ok ?? 0} hợp lệ` },
])

const maxScan = computed(() => Math.max(...props.scanTrend.map(d => d.count), 1))
const barHeight = (count) => Math.max((count / maxScan.value) * 100, count > 0 ? 4 : 0)

function decisionVariant(d) {
  return { allowed: 'default', blocked: 'destructive', expired: 'secondary', invalid: 'outline' }[d] ?? 'outline'
}
function decisionLabel(d) {
  return { allowed: 'Cho phép', blocked: 'Chặn', expired: 'Hết hạn', invalid: 'Không hợp lệ' }[d] ?? d
}
</script>

<template>
  <div class="space-y-6">

    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-4">
      <div>
        <p class="text-[10px] font-black uppercase tracking-[0.3em] text-primary mb-1">Overview</p>
        <h1 class="text-4xl font-black tracking-tighter text-foreground uppercase ">Xin chào, {{ user?.name }} </h1>
        <p class="text-muted-foreground font-medium text-sm mt-1 opacity-70">Chào mừng bạn trở lại với hệ thống truy xuất nguồn gốc AGU.</p>
      </div>
      <div class="flex items-center gap-2 text-[10px] font-black uppercase tracking-widest text-muted-foreground/40 bg-muted/20 px-4 py-2 rounded-full border border-border/50">
         <span class="w-2 h-2 rounded-full bg-green-500 animate-pulse"></span>
         System Live
      </div>
    </div>

    <!-- Quick actions -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
      <!-- Nhập hàng -->
      <Link href="/events/create/transfer-in" class="group">
        <Card class="bg-card border-border shadow-xl transition-all duration-300 group-hover:scale-[1.02] group-hover:shadow-2xl overflow-hidden relative">
          <div class="absolute -right-10 -top-10 w-40 h-40 bg-emerald-500/5 blur-3xl rounded-full group-hover:scale-150 transition-transform duration-500"></div>
          <CardContent class="flex items-center gap-6 p-8 relative z-10">
            <div class="p-4 rounded-2xl bg-emerald-500/10 text-emerald-600">
              <ArrowDownLeft class="w-8 h-8" />
            </div>
            <div>
              <p class="font-black text-xl tracking-tight leading-tight uppercase  text-foreground">Nhập hàng mới</p>
              <p class="text-xs font-medium text-muted-foreground mt-1">Tiếp nhận lô hàng từ bên ngoài</p>
            </div>
          </CardContent>
        </Card>
      </Link>

      <!-- Chế biến -->
      <Link href="/batches/transform" class="group">
        <Card class="bg-primary text-primary-foreground border-none shadow-xl shadow-primary/20 transition-all duration-300 group-hover:scale-[1.02] group-hover:shadow-2xl group-hover:shadow-primary/30 overflow-hidden relative">
          <div class="absolute -right-10 -top-10 w-40 h-40 bg-white/10 blur-3xl rounded-full group-hover:scale-150 transition-transform duration-500"></div>
          <CardContent class="flex items-center gap-6 p-8 relative z-10">
            <div class="p-4 rounded-2xl bg-white/20 backdrop-blur-md shadow-inner text-white">
              <FlaskConical class="w-8 h-8" />
            </div>
            <div>
              <p class="font-black text-xl tracking-tight leading-tight uppercase ">Chế biến lô</p>
              <p class="text-xs font-medium opacity-80 mt-1">Sản xuất từ nguyên liệu hiện có</p>
            </div>
          </CardContent>
        </Card>
      </Link>

      <!-- Chuyển giao -->
      <Link href="/events/create/transfer-out" class="group">
        <Card class="bg-card border-border shadow-xl transition-all duration-300 group-hover:scale-[1.02] group-hover:shadow-2xl overflow-hidden relative">
          <div class="absolute -right-10 -top-10 w-40 h-40 bg-primary/5 blur-3xl rounded-full group-hover:scale-150 transition-transform duration-500"></div>
          <CardContent class="flex items-center gap-6 p-8 relative z-10">
            <div class="p-4 rounded-2xl bg-primary/10 text-primary">
              <Truck class="w-8 h-8" />
            </div>
            <div>
              <p class="font-black text-xl tracking-tight leading-tight uppercase  text-foreground">Chuyển giao đi</p>
              <p class="text-xs font-medium text-muted-foreground mt-1">Gửi lô hàng cho đối tác</p>
            </div>
          </CardContent>
        </Card>
      </Link>
    </div>

    <!-- Stat cards -->
    <div class="grid grid-cols-2 lg:grid-cols-5 gap-4">
      <Link v-for="s in statCards" :key="s.label" :href="s.href" class="group">
        <Card class="h-full border-border/50 bg-card/50 backdrop-blur-sm hover:bg-card hover:border-primary/50 transition-all duration-300 shadow-sm hover:shadow-xl hover:-translate-y-1 rounded-3xl">
          <CardContent class="p-6">
            <div class="flex items-center justify-between mb-4">
              <div class="p-2 rounded-xl bg-muted/50 group-hover:bg-primary/10 transition-colors">
                 <component :is="s.icon" class="w-4 h-4 text-muted-foreground group-hover:text-primary transition-colors" />
              </div>
              <span class="text-[9px] font-black uppercase tracking-[0.2em] text-muted-foreground/40">{{ s.sub }}</span>
            </div>
            <p class="text-4xl font-black tracking-tighter text-foreground group-hover:text-primary transition-colors">{{ s.value }}</p>
            <p class="text-[10px] font-bold text-muted-foreground mt-2 uppercase tracking-widest">{{ s.label }}</p>
          </CardContent>
        </Card>
      </Link>
    </div>

    <!-- Charts row -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">

      <!-- Scan trend chart -->
      <Card class="lg:col-span-2 border-border/50 bg-card/50 backdrop-blur-sm rounded-3xl">
        <CardHeader>
          <CardTitle class="text-[10px] font-black uppercase tracking-widest opacity-60">Lượt quét QR theo ngày</CardTitle>
        </CardHeader>
        <CardContent>
          <div v-if="scanTrend.length === 0" class="text-center py-8 text-muted-foreground text-sm">
            Chưa có dữ liệu
          </div>
          <div v-else class="flex items-end gap-1 h-32">
            <div v-for="d in scanTrend" :key="d.date"
              class="flex-1 flex flex-col items-center gap-1 group">
              <div class="w-full bg-primary/80 rounded-sm transition-all hover:bg-primary shadow-lg shadow-primary/20"
                :style="{ height: barHeight(d.count) + '%', minHeight: d.count > 0 ? '4px' : '2px' }" />
              <span class="text-[9px] text-muted-foreground rotate-45 origin-left hidden group-hover:block font-bold">
                {{ d.date?.slice(5) }}
              </span>
            </div>
          </div>
        </CardContent>
      </Card>

      <!-- Top batches -->
      <Card class="border-border/50 bg-card/50 backdrop-blur-sm rounded-3xl">
        <CardHeader>
          <CardTitle class="text-[10px] font-black uppercase tracking-widest opacity-60">Top lô được quét</CardTitle>
        </CardHeader>
        <CardContent class="space-y-3">
          <div v-if="topBatches.length === 0"
            class="text-center py-6 text-muted-foreground text-sm ">Chưa có dữ liệu</div>
          <div v-for="b in topBatches" :key="b.id"
            class="flex items-center justify-between gap-2 p-2 rounded-xl hover:bg-muted/50 transition-colors">
            <span class="font-mono text-xs font-black text-primary tracking-tighter truncate">{{ b.code }}</span>
            <Badge variant="secondary" class="font-black text-[9px] px-2 py-0.5">{{ b.scan_count }} scans</Badge>
          </div>
        </CardContent>
      </Card>
    </div>

    <!-- Bottom row -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">

      <!-- Batch progress -->
      <Card class="border-border/50 bg-card/50 backdrop-blur-sm overflow-hidden rounded-3xl">
        <CardHeader class="flex flex-row items-center justify-between border-b bg-muted/30 py-4 px-6">
          <CardTitle class="text-[10px] font-black uppercase tracking-widest opacity-60">Lô hàng gần đây</CardTitle>
          <Button variant="ghost" size="sm" as-child class="text-[9px] font-black uppercase tracking-widest">
            <Link href="/batches">Xem tất cả</Link>
          </Button>
        </CardHeader>
        <CardContent class="p-0">
          <div v-if="batchProgress.length === 0"
            class="text-center py-12 text-muted-foreground text-[10px] font-black uppercase tracking-widest opacity-40">Chưa có dữ liệu</div>
          <div v-for="b in batchProgress" :key="b.id"
            class="flex items-center justify-between gap-4 p-6 border-b last:border-0 hover:bg-muted/30 transition-colors group">
            <div class="flex-1 min-w-0">
              <p class="text-sm font-mono font-black text-primary tracking-tighter truncate">{{ b.code }}</p>
              <p class="text-[10px] font-bold text-muted-foreground truncate uppercase tracking-widest mt-0.5">{{ b.product_name }}</p>
            </div>
            <div class="text-right shrink-0">
              <p class="text-[9px] font-black uppercase tracking-widest text-muted-foreground/60 mb-1.5">{{ b.published }}/{{ b.total }} sự kiện</p>
              <div class="w-24 h-1.5 bg-muted rounded-full overflow-hidden shadow-inner">
                <div class="h-full bg-primary rounded-full transition-all duration-1000 shadow-[0_0_8px_rgba(var(--primary),0.5)]"
                  :style="{ width: b.total > 0 ? (b.published / b.total * 100) + '%' : '0%' }" />
              </div>
            </div>
          </div>
        </CardContent>
      </Card>

      <!-- Scan log -->
      <Card class="border-border/50 bg-card/50 backdrop-blur-sm overflow-hidden rounded-3xl">
        <CardHeader class="border-b bg-muted/30 py-4 px-6">
          <CardTitle class="text-[10px] font-black uppercase tracking-widest opacity-60">Scan log gần nhất</CardTitle>
        </CardHeader>
        <CardContent class="p-0">
          <div v-if="recentScans.length === 0"
            class="text-center py-12 text-muted-foreground text-[10px] font-black uppercase tracking-widest opacity-40">Chưa có lượt quét</div>
          <div v-for="s in recentScans" :key="s.id"
            class="flex items-center gap-4 p-6 border-b last:border-0 hover:bg-muted/30 transition-colors group">
            <Badge :variant="decisionVariant(s.decision)" class="w-24 justify-center shrink-0 font-black text-[9px] uppercase tracking-widest h-6 rounded-full shadow-sm">
              {{ decisionLabel(s.decision) }}
            </Badge>
            <div class="flex-1 min-w-0">
              <p class="text-xs font-black text-foreground tracking-tight truncate">
                {{ s.qr_type === 'public' ? 'Public QR' : 'Internal' }}
              </p>
              <p class="text-[10px] font-bold text-muted-foreground/60 uppercase tracking-widest mt-0.5">{{ s.scanned_at }}</p>
            </div>
            <QrCode class="w-4 h-4 text-muted-foreground/20 group-hover:text-primary transition-colors" />
          </div>
        </CardContent>
      </Card>
    </div>

  </div>
</template>
