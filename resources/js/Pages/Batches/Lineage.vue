<script setup>
import { ref, computed } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import { Card, CardHeader, CardTitle, CardDescription, CardContent, CardFooter } from '@/Components/ui/card/index.js'
import { Button } from '@/Components/ui/button/index.js'
import { Badge } from '@/Components/ui/badge/index.js'
import { Progress } from '@/Components/ui/progress/index.js'
import { Separator } from '@/Components/ui/separator/index.js'
import { 
  Archive, 
  ArrowsUpFromLine, 
  Scissors, 
  Truck, 
  X,
  ClipboardList,
  Globe,
  ArrowRight,
  ChevronsRight,
  ArrowLeft
} from 'lucide-vue-next'

const props = defineProps({
  batch: { type: Object, required: true },
  nodes: { type: Array,  default: () => [] },
  edges: { type: Array,  default: () => [] },
})

// ── Layout tự động ───────────────────
const nodeDepths = computed(() => {
  const depths = {}
  const edgeMap = {}
  for (const e of props.edges) {
    if (!edgeMap[e.from]) edgeMap[e.from] = []
    edgeMap[e.from].push(e.to)
  }
  const incomingCount = {}
  for (const n of props.nodes) incomingCount[n.id] = 0
  for (const e of props.edges) {
    incomingCount[e.to] = (incomingCount[e.to] ?? 0) + 1
  }
  const queue = props.nodes.filter(n => (incomingCount[n.id] ?? 0) === 0).map(n => n.id)
  queue.forEach(id => depths[id] = 0)
  while (queue.length) {
    const cur = queue.shift()
    const children = edgeMap[cur] ?? []
    for (const child of children) {
      depths[child] = Math.max(depths[child] ?? 0, (depths[cur] ?? 0) + 1)
      queue.push(child)
    }
  }
  return depths
})

const columns = computed(() => {
  const cols = {}
  for (const node of props.nodes) {
    const d = nodeDepths.value[node.id] ?? 0
    if (!cols[d]) cols[d] = []
    cols[d].push(node)
  }
  return Object.values(cols)
})

const selectedNode = ref(null)
function selectNode(node) {
  selectedNode.value = selectedNode.value?.id === node.id ? null : node
}

// ── Màu / style theo type ─────────────────────────────────
function nodeVariant(node) {
  if (node.batch_id === props.batch.id) return 'default'
  if (node.type === 'split_child') return 'secondary'
  if (node.type === 'merge_input' || node.type === 'merge_output') return 'outline'
  if (node.type === 'enterprise') return 'outline'
  return 'secondary'
}

function edgeStyle(edge) {
  return {
    split:    { color: 'text-amber-500', icon: Scissors, label: 'Tách lô' },
    merge:    { color: 'text-purple-500', icon: ArrowsUpFromLine, label: 'Gộp lô' },
    transfer: { color: 'text-sky-500', icon: Truck, label: 'Chuyển giao' },
  }[edge.type] ?? { color: 'text-muted-foreground', icon: ArrowRight, label: edge.label }
}

function batchTypeLabel(t) {
  return ({ original: 'Gốc', split: 'Đã tách', merged: 'Đã gộp', received: 'Đã nhận' })[t] ?? t
}

function statusLabel(s) {
  return ({ active: 'Hoạt động', recalled: 'Thu hồi', split: 'Đã tách', consumed: 'Đã dùng', received: 'Đã nhận', inactive: 'Không hoạt động' })[s] ?? s
}

function statusVariant(s) {
  if (s === 'active' || s === 'received') return 'default'
  if (s === 'recalled') return 'destructive'
  return 'secondary'
}

function edgesFor(nodeId) {
  return props.edges.filter(e => e.from === nodeId || e.to === nodeId)
}
</script>

<template>
  <Head :title="`Phả hệ lô ${batch.code}`" />

  <div class="max-w-7xl mx-auto space-y-8 pb-20">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-6">
      <div class="space-y-3">
        <nav class="flex items-center gap-2 text-[10px] font-black uppercase tracking-[0.3em] text-muted-foreground/60 bg-muted/20 w-fit px-4 py-1.5 rounded-full border border-border/50">
          <Link :href="route('batches.index')" class="hover:text-primary transition-colors">Quản lý lô hàng</Link>
          <ChevronRight class="w-3 h-3 opacity-20" />
          <span class="text-foreground ">Lineage</span>
        </nav>
        <div class="flex items-center gap-4">
           <div class="p-3 rounded-[1.5rem] bg-primary/10 text-primary shadow-inner">
              <Archive class="w-8 h-8" />
           </div>
           <div>
              <h1 class="text-4xl font-black tracking-tighter text-foreground uppercase ">Phả hệ lô {{ batch.code }}</h1>
              <p class="text-[10px] font-bold text-muted-foreground uppercase tracking-[0.2em] mt-1">Truy vết gốc: Tách lô, gộp lô, chuyển giao</p>
           </div>
        </div>
      </div>

      <div class="flex items-center gap-4">
        <Badge variant="outline" class="h-14 px-6 flex flex-col items-center justify-center gap-1 border-primary/20 bg-primary/5 rounded-[1rem] shadow-inner">
           <span class="text-xl font-black text-primary leading-none">{{ batch.completeness?.score ?? 0 }}%</span>
           <span class="text-[8px] uppercase tracking-[0.2em] font-black opacity-60">CTE Completeness</span>
        </Badge>
        <Button variant="outline" size="sm" as-child class="font-black uppercase text-[9px] tracking-widest h-14 px-5 rounded-2xl hover:bg-muted transition-all">
           <Link :href="route('batches.index')">
              <ArrowLeft class="w-4 h-4 mr-2" /> Quay lại
           </Link>
        </Button>
      </div>
    </div>

    <!-- Legend -->
    <div class="flex flex-wrap gap-4 px-4 py-2 bg-muted/20 rounded-full w-fit border border-border/50 shadow-inner">
      <div class="flex items-center gap-2">
         <div class="w-3 h-3 rounded-full bg-primary shadow-lg shadow-primary/40 animate-pulse"></div>
         <span class="text-[9px] font-black uppercase tracking-[0.2em] text-foreground">Lô hiện tại</span>
      </div>
      <div class="flex items-center gap-2 opacity-60">
         <div class="w-3 h-3 rounded-full bg-muted border border-foreground/20"></div>
         <span class="text-[9px] font-black uppercase tracking-[0.2em] text-foreground">Lô tổ tiên</span>
      </div>
      <div class="flex items-center gap-2">
         <Scissors class="w-3.5 h-3.5 text-amber-500" />
         <span class="text-[9px] font-black uppercase tracking-[0.2em] text-amber-600">Tách lô</span>
      </div>
      <div class="flex items-center gap-2">
         <ArrowsUpFromLine class="w-3.5 h-3.5 text-purple-500" />
         <span class="text-[9px] font-black uppercase tracking-[0.2em] text-purple-600">Gộp lô</span>
      </div>
      <div class="flex items-center gap-2">
         <Truck class="w-3.5 h-3.5 text-sky-500" />
         <span class="text-[9px] font-black uppercase tracking-[0.2em] text-sky-600">Chuyển giao</span>
      </div>
    </div>

    <!-- Flow Diagram -->
    <Card class="bg-card/50 overflow-hidden relative border-border/50 backdrop-blur-sm rounded-[2.5rem] shadow-xl">
      <div class="absolute inset-0 opacity-[0.03] pointer-events-none" 
           style="background-image: radial-gradient(circle at 2px 2px, currentColor 1px, transparent 0); background-size: 24px 24px;"></div>
      
      <CardContent class="p-12 overflow-x-auto min-h-[500px]">
        <!-- Empty state -->
        <div v-if="nodes.length === 0" class="flex flex-col items-center justify-center py-32 text-muted-foreground border-2 border-dashed border-border/30 rounded-[2rem] bg-muted/10">
          <div class="p-8 rounded-full bg-background mb-4 shadow-sm">
             <Globe class="w-12 h-12 opacity-20 animate-pulse" />
          </div>
          <p class="font-black uppercase tracking-[0.2em] text-foreground">Chưa có liên kết phả hệ</p>
          <p class="text-[10px] uppercase mt-2 tracking-widest opacity-40">Thực hiện các nghiệp vụ kho để tạo mạng lưới.</p>
        </div>

        <div v-else class="flex gap-16 items-start min-w-max relative z-10">
          <template v-for="(col, colIdx) in columns" :key="colIdx">
            <!-- Column of nodes -->
            <div class="flex flex-col gap-10 items-center py-6">
              <div
                v-for="node in col"
                :key="node.id"
                @click="selectNode(node)"
                class="w-64 rounded-3xl border-2 p-5 cursor-pointer transition-all duration-500 relative group overflow-hidden"
                :class="[
                  node.batch_id === batch.id ? 'border-primary bg-primary/5 ring-4 ring-primary/10 shadow-2xl' : 'border-border/50 bg-background hover:border-primary/40 hover:bg-muted/50 shadow-sm',
                  selectedNode?.id === node.id ? 'border-primary shadow-2xl scale-105 z-20' : ''
                ]"
              >
                <!-- Badge corner -->
                <div class="absolute -top-1 -right-1 bg-background rounded-bl-2xl border-l-2 border-b-2 pl-2 pb-2" :class="node.batch_id === batch.id ? 'border-primary' : 'border-border/50'">
                   <Badge :variant="node.type === 'enterprise' ? 'outline' : 'secondary'" class="text-[8px] font-black uppercase tracking-widest shadow-sm">
                      {{ node.type === 'enterprise' ? 'Partner' : batchTypeLabel(node.batch_type) }}
                   </Badge>
                </div>

                <!-- Node content -->
                <div v-if="node.type === 'enterprise'" class="space-y-2 mt-2">
                  <div class="text-sm font-black uppercase tracking-tight text-foreground line-clamp-2 leading-tight group-hover:text-primary transition-colors">{{ node.label }}</div>
                  <div v-if="node.code" class="text-[9px] font-mono font-bold text-muted-foreground bg-muted/50 w-fit px-2 py-0.5 rounded">{{ node.code }}</div>
                </div>
                <div v-else class="space-y-3">
                  <div>
                    <div class="font-mono text-base font-black tracking-tighter" :class="node.batch_id === batch.id ? 'text-primary' : 'text-foreground'">
                      {{ node.code }}
                    </div>
                    <div class="text-[10px] font-bold text-muted-foreground truncate uppercase opacity-60 mt-0.5 group-hover:text-foreground/60 transition-colors">{{ node.product_name }}</div>
                  </div>
                  
                  <div class="flex items-center justify-between pt-3 border-t border-dashed border-border/50">
                    <Badge :variant="statusVariant(node.status)" class="text-[8px] font-black uppercase tracking-widest px-2 py-0.5 shadow-sm">
                      {{ statusLabel(node.status) }}
                    </Badge>
                    <span v-if="node.quantity" class="text-[11px] font-black text-muted-foreground group-hover:text-primary transition-colors">
                      {{ node.quantity }} <span class="uppercase text-[8px] tracking-widest opacity-50 ml-0.5">{{ node.unit }}</span>
                    </span>
                  </div>
                </div>

                <!-- Hover action hint -->
                <div class="absolute -bottom-8 left-0 right-0 text-center opacity-0 group-hover:opacity-100 group-hover:bottom-2 transition-all duration-300">
                   <span class="text-[8px] font-black text-primary uppercase tracking-[0.3em] bg-background/80 px-3 py-1 rounded-full backdrop-blur shadow-sm">Chi tiết</span>
                </div>
              </div>
            </div>

            <!-- Connectors -->
            <div v-if="colIdx < columns.length - 1" class="flex flex-col justify-center items-center gap-16 self-stretch min-w-[5rem]">
               <template v-for="edge in edges.filter(e => col.some(n => n.id === e.from) && (columns[colIdx+1] ?? []).some(n => n.id === e.to))" :key="edge.from + edge.to">
                  <div class="flex flex-col items-center gap-1.5 group/edge z-0">
                     <div class="p-2 rounded-xl bg-background border border-border/50 shadow-sm relative z-10 transition-transform duration-500 group-hover/edge:scale-125 group-hover/edge:border-primary/40">
                        <component :is="edgeStyle(edge).icon" class="w-4 h-4" :class="edgeStyle(edge).color" />
                     </div>
                     <div class="w-24 h-[2px] rounded-full bg-gradient-to-r from-transparent via-border to-transparent absolute -z-10 group-hover/edge:via-primary/40 transition-colors duration-500"></div>
                     <span class="text-[8px] font-black uppercase tracking-[0.2em] text-center max-w-[90px] bg-background/80 px-2 py-0.5 rounded-full backdrop-blur mt-1" :class="edgeStyle(edge).color">
                        {{ edge.label }}
                     </span>
                  </div>
               </template>
               <ChevronsRight v-if="!edges.filter(e => col.some(n => n.id === e.from) && (columns[colIdx+1] ?? []).some(n => n.id === e.to)).length" class="w-5 h-5 text-muted-foreground/20 animate-pulse" />
            </div>
          </template>
        </div>
      </CardContent>
    </Card>

    <!-- Detailed Panel -->
    <Transition
      enter-active-class="transition duration-500 ease-out"
      enter-from-class="opacity-0 translate-y-8 scale-95"
      enter-to-class="opacity-100 translate-y-0 scale-100"
      leave-active-class="transition duration-300 ease-in"
      leave-from-class="opacity-100 translate-y-0 scale-100"
      leave-to-class="opacity-0 translate-y-8 scale-95"
    >
      <Card v-if="selectedNode && selectedNode.type !== 'enterprise'" class="border-primary/20 shadow-2xl shadow-primary/10 rounded-[2.5rem] overflow-hidden fixed bottom-8 right-8 w-[400px] z-50 bg-card/95 backdrop-blur-xl">
        <CardHeader class="flex flex-row items-start justify-between pb-6 border-b bg-muted/30">
          <div class="space-y-1.5">
            <Badge variant="outline" class="font-black text-[8px] uppercase tracking-[0.3em] text-primary border-primary/20 bg-background">Node Metadata</Badge>
            <CardTitle class="text-2xl font-mono font-black tracking-tighter">{{ selectedNode.code }}</CardTitle>
            <CardDescription class="font-bold uppercase tracking-widest text-[9px]">{{ selectedNode.product_name }}</CardDescription>
          </div>
          <Button variant="ghost" size="icon" @click="selectedNode = null" class="h-8 w-8 rounded-xl hover:bg-destructive/10 hover:text-destructive transition-colors">
             <X class="w-4 h-4" />
          </Button>
        </CardHeader>
        <CardContent class="p-6">
          <div class="grid grid-cols-2 gap-4">
            <div class="p-4 rounded-2xl bg-background border border-border/50 shadow-inner">
               <Label class="text-[8px] font-black uppercase text-muted-foreground/60 mb-1.5 block tracking-[0.2em]">Origin Type</Label>
               <p class="text-sm font-black">{{ batchTypeLabel(selectedNode.batch_type) }}</p>
            </div>
            <div class="p-4 rounded-2xl bg-background border border-border/50 shadow-inner">
               <Label class="text-[8px] font-black uppercase text-muted-foreground/60 mb-1.5 block tracking-[0.2em]">Status</Label>
               <p class="text-sm font-black" :class="selectedNode.status === 'active' ? 'text-emerald-500' : 'text-foreground'">
                  {{ statusLabel(selectedNode.status) }}
               </p>
            </div>
            <div class="p-4 rounded-2xl bg-background border border-border/50 shadow-inner">
               <Label class="text-[8px] font-black uppercase text-muted-foreground/60 mb-1.5 block tracking-[0.2em]">Current Stock</Label>
               <p class="text-sm font-black">{{ selectedNode.quantity }} <span class="text-[9px] uppercase tracking-widest opacity-40">{{ selectedNode.unit }}</span></p>
            </div>
            <div class="p-4 rounded-2xl bg-background border border-border/50 shadow-inner">
               <Label class="text-[8px] font-black uppercase text-muted-foreground/60 mb-1.5 block tracking-[0.2em]">Published Events</Label>
               <div class="flex items-center gap-2">
                  <span class="text-sm font-black">{{ selectedNode.event_count }}</span>
                  <div v-if="selectedNode.event_count > 0" class="w-2 h-2 rounded-full bg-emerald-500 shadow-[0_0_8px_rgba(16,185,129,0.8)] animate-pulse"></div>
               </div>
            </div>
          </div>

          <div class="mt-6 space-y-3">
             <Label class="text-[9px] font-black uppercase tracking-[0.2em] text-muted-foreground px-1">Network Connections</Label>
             <div class="space-y-2">
                <div v-for="e in edgesFor(selectedNode.id)" :key="e.from + e.to" class="flex items-center gap-3 p-3 rounded-xl border border-border/50 bg-muted/30 text-xs font-bold shadow-sm">
                   <div class="p-1.5 rounded-lg bg-background shadow-inner">
                     <component :is="edgeStyle(e).icon" class="w-3.5 h-3.5" :class="edgeStyle(e).color" />
                   </div>
                   <span :class="edgeStyle(e).color" class="uppercase tracking-widest text-[9px] font-black w-20">{{ e.label }}</span>
                   <Separator orientation="vertical" class="h-4" />
                   <span class="font-mono text-[10px] opacity-60 truncate">{{ e.from === selectedNode.id ? 'Target: ' + e.to : 'Source: ' + e.from }}</span>
                </div>
             </div>
          </div>
        </CardContent>
        <CardFooter class="border-t bg-muted/10 p-6 gap-3 flex-col sm:flex-row">
           <Button v-if="selectedNode.batch_id" class="w-full font-black uppercase text-[9px] tracking-widest h-10 rounded-xl shadow-lg shadow-primary/20" as-child>
              <Link :href="route('events.index', { batch_id: selectedNode.batch_id })">
                 <ClipboardList class="w-3.5 h-3.5 mr-2" /> Ghi dữ liệu
              </Link>
           </Button>
           <Button v-if="selectedNode.batch_id" variant="outline" class="w-full font-black uppercase text-[9px] tracking-widest h-10 rounded-xl" as-child>
              <Link :href="route('batches.lineage', selectedNode.batch_id)">
                 <Globe class="w-3.5 h-3.5 mr-2" /> Dịch chuyển gốc
              </Link>
           </Button>
        </CardFooter>
      </Card>
    </Transition>

    <!-- Current Batch Summary -->
    <Card class="bg-muted/10 border-dashed border-2 rounded-[2.5rem] overflow-hidden">
      <CardHeader class="py-5 px-8 border-b border-border/30 bg-muted/30">
         <div class="flex items-center gap-3">
            <div class="w-2 h-2 rounded-full bg-primary animate-pulse shadow-[0_0_8px_rgba(var(--primary),0.8)]"></div>
            <CardTitle class="text-[10px] font-black uppercase tracking-[0.3em] text-foreground">Focus: Active Context</CardTitle>
         </div>
      </CardHeader>
      <CardContent class="grid grid-cols-2 md:grid-cols-4 gap-8 p-8">
         <div class="space-y-1.5">
            <Label class="text-[9px] uppercase font-black text-muted-foreground/60 tracking-[0.2em]">Batch Identity</Label>
            <p class="font-mono text-base font-black text-primary tracking-tighter">{{ batch.code }}</p>
         </div>
         <div class="space-y-1.5">
            <Label class="text-[9px] uppercase font-black text-muted-foreground/60 tracking-[0.2em]">Provenance</Label>
            <p class="text-sm font-black uppercase tracking-widest">{{ batchTypeLabel(batch.batch_type) }}</p>
         </div>
         <div class="space-y-1.5">
            <Label class="text-[9px] uppercase font-black text-muted-foreground/60 tracking-[0.2em]">Status</Label>
            <div>
               <Badge :variant="statusVariant(batch.status)" class="text-[9px] h-6 font-black uppercase tracking-widest px-3 rounded-full shadow-sm">{{ statusLabel(batch.status) }}</Badge>
            </div>
         </div>
         <div class="space-y-1.5">
            <Label class="text-[9px] uppercase font-black text-muted-foreground/60 tracking-[0.2em]">Legal Owner</Label>
            <p class="text-sm font-black truncate  text-foreground">{{ batch.enterprise }}</p>
         </div>
      </CardContent>
      
      <CardFooter v-if="batch.completeness?.required_total > 0" class="flex-col items-start gap-4 border-t border-dashed border-border/50 bg-background/50 p-8">
         <div class="w-full flex items-center justify-between text-[10px] font-black uppercase tracking-[0.2em]">
            <span class="text-muted-foreground">Chuẩn TCVN 12850:2019</span>
            <span :class="batch.completeness.score === 100 ? 'text-emerald-500' : 'text-primary'">
               {{ batch.completeness.required_done }}/{{ batch.completeness.required_total }} Steps
            </span>
         </div>
         <Progress :model-value="batch.completeness.score" class="h-3 rounded-full bg-muted shadow-inner" />
         <div v-if="batch.completeness.missing?.length" class="flex flex-wrap items-center gap-2 pt-2">
            <span class="text-[9px] font-black uppercase text-muted-foreground/60 tracking-widest mr-2">Missing:</span>
            <Badge v-for="m in batch.completeness.missing" :key="m.code" variant="destructive" class="text-[8px] h-5 font-black uppercase tracking-widest bg-destructive/10 text-destructive border-destructive/20 shadow-sm">
               {{ m.name_vi }}
            </Badge>
         </div>
      </CardFooter>
    </Card>
  </div>
</template>
