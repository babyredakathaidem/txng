<script setup>
import { Head } from '@inertiajs/vue3'
import { Card, CardHeader, CardTitle, CardDescription, CardContent } from '@/Components/ui/card/index.js'
import { Badge } from '@/Components/ui/badge/index.js'
import { 
  Building2, 
  Archive, 
  History, 
  AlertTriangle,
  Activity,
  ArrowRight,
  ShieldCheck,
  Globe
} from 'lucide-vue-next'

const props = defineProps({
    total_enterprises: Number,
    total_batches: Number,
    total_events: Number,
    recent_recalls: Array,
})

const stats = [
    {
        label: 'Doanh nghiệp',
        value: props.total_enterprises,
        icon: Building2,
        color: 'text-primary',
        bg: 'bg-primary/10',
        sub: 'Đối tác hệ thống'
    },
    {
        label: 'Lô hàng',
        value: props.total_batches,
        icon: Archive,
        color: 'text-emerald-500',
        bg: 'bg-emerald-500/10',
        sub: 'Đang lưu hành'
    },
    {
        label: 'Sự kiện TXNG',
        value: props.total_events,
        icon: History,
        color: 'text-sky-500',
        bg: 'bg-sky-500/10',
        sub: 'Dữ liệu IPFS'
    },
    {
        label: 'Thu hồi gần đây',
        value: props.recent_recalls?.length ?? 0,
        icon: AlertTriangle,
        color: 'text-destructive',
        bg: 'bg-destructive/10',
        sub: 'Cảnh báo an toàn'
    },
]

const recallBadge = (s) => {
    if (s === 'active') return 'destructive'
    if (s === 'resolved') return 'default'
    return 'secondary'
}
</script>

<template>
    <Head title="Thống kê hệ thống" />

    <div class="space-y-6">
        <!-- Header -->
        <div class="flex flex-col md:flex-row md:items-end justify-between gap-4">
          <div>
            <p class="text-[10px] font-black uppercase tracking-[0.3em] text-primary mb-1">System Intelligence</p>
            <h1 class="text-4xl font-black tracking-tighter text-foreground">Thống kê tổng quát</h1>
            <p class="text-muted-foreground font-medium text-sm mt-1 opacity-70">Tổng quan toàn bộ hoạt động trên nền tảng AGU Traceability.</p>
          </div>
          <div class="flex items-center gap-2 text-[10px] font-black uppercase tracking-widest text-muted-foreground/40 bg-muted/20 px-4 py-2 rounded-full border border-border/50">
             <Activity class="w-3 h-3 mr-1 text-primary animate-pulse" />
             Global Activity Live
          </div>
        </div>

        <!-- Stat cards -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <Card v-for="s in stats" :key="s.label" class="border-border/50 bg-card/50 backdrop-blur-sm hover:bg-card hover:border-primary/50 transition-all duration-300 shadow-sm hover:shadow-xl hover:-translate-y-1 overflow-hidden group">
              <CardContent class="p-6">
                <div class="flex items-center justify-between mb-4">
                  <div :class="[s.bg, 'p-3 rounded-2xl group-hover:scale-110 transition-transform duration-500 shadow-inner']">
                      <component :is="s.icon" :class="[s.color, 'w-6 h-6']" />
                  </div>
                  <span class="text-[9px] font-black uppercase tracking-[0.2em] text-muted-foreground/40">{{ s.sub }}</span>
                </div>
                <div class="text-4xl font-black tracking-tighter text-foreground group-hover:text-primary transition-colors">{{ s.value?.toLocaleString() }}</div>
                <div class="text-[10px] font-bold text-muted-foreground mt-2 uppercase tracking-widest">{{ s.label }}</div>
              </CardContent>
            </Card>
        </div>

        <!-- Recent recalls -->
        <Card class="border-border/50 bg-card/50 backdrop-blur-sm overflow-hidden">
          <CardHeader class="flex flex-row items-center justify-between border-b bg-muted/30 py-4 px-6">
            <div>
              <CardTitle class="text-sm font-black uppercase tracking-widest">Lệnh thu hồi gần đây</CardTitle>
              <CardDescription class="text-[10px] font-medium mt-0.5 uppercase tracking-widest opacity-60">5 lệnh thu hồi mới nhất trong hệ thống</CardDescription>
            </div>
            <div class="p-2 rounded-xl bg-destructive/10 text-destructive">
               <AlertTriangle class="w-4 h-4" />
            </div>
          </CardHeader>
          <CardContent class="p-0">
            <div v-if="recent_recalls?.length" class="divide-y divide-border/30">
                <div v-for="r in recent_recalls" :key="r.id" class="flex items-center justify-between p-6 hover:bg-muted/30 transition-all group">
                    <div class="space-y-2">
                        <div class="flex items-center gap-3">
                            <span class="text-[10px] font-black uppercase tracking-widest text-muted-foreground/40">Lô hàng:</span>
                            <span class="text-sm font-black tracking-tight text-primary bg-primary/5 px-3 py-1 rounded-full border border-primary/20">{{ r.batch?.code ?? '—' }}</span>
                        </div>
                        <div class="text-xs font-bold text-foreground line-clamp-1 max-w-md  opacity-80 group-hover:opacity-100 transition-opacity">"{{ r.reason }}"</div>
                    </div>
                    <div class="flex items-center gap-6 shrink-0">
                        <div class="flex flex-col items-end">
                          <span class="text-[10px] font-black uppercase tracking-widest text-muted-foreground/40 mb-1">Thời gian</span>
                          <span class="text-xs font-black tracking-tighter text-foreground bg-muted px-3 py-1 rounded-full border border-border/50">
                              {{ r.recalled_at ? new Date(r.recalled_at).toLocaleDateString('vi-VN') : '—' }}
                          </span>
                        </div>
                        <div class="flex flex-col items-end">
                          <span class="text-[10px] font-black uppercase tracking-widest text-muted-foreground/40 mb-1">Trạng thái</span>
                          <Badge :variant="recallBadge(r.status)" class="font-black text-[9px] uppercase tracking-widest px-3 py-0.5">{{ r.status }}</Badge>
                        </div>
                    </div>
                </div>
            </div>
            <div v-else class="flex flex-col items-center justify-center py-24 text-muted-foreground">
                <div class="p-4 rounded-full bg-muted mb-4">
                  <ShieldCheck class="w-10 h-10 opacity-20" />
                </div>
                <div class="text-[10px] font-black uppercase tracking-widest opacity-40">
                    Hệ thống an toàn • Chưa có lệnh thu hồi nào.
                </div>
            </div>
          </CardContent>
        </Card>
    </div>
</template>
