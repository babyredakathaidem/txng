<script setup>
import { ref } from 'vue'
import { 
  ShieldCheck, 
  ShieldAlert, 
  Loader2, 
  CheckCircle2, 
  AlertTriangle, 
  X,
  Database,
  Globe,
  Cpu,
  Fingerprint
} from 'lucide-vue-next'

const props = defineProps({
  eventId:     { type: Number, required: true },
  ipfsCid:     { type: String, default: null },
  shortCidFn:  { type: Function, default: (c) => c?.slice(0,8) + '…' + c?.slice(-6) },
})

// ── State machine per button ──────────────────────────────────────
const state   = ref('idle')
const details = ref(null)
const showTip = ref(false)

async function verify() {
  if (state.value === 'loading') return
  state.value   = 'loading'
  details.value = null
  showTip.value = false

  try {
    const res  = await fetch(`/verify/integrity/${props.eventId}`, {
      headers: {
        'Accept':           'application/json',
        'X-Requested-With': 'XMLHttpRequest',
      },
    })
    const data = await res.json()
    state.value   = data.verdict ?? 'error'
    details.value = data
    showTip.value = true
  } catch {
    state.value = 'error'
  }
}

// ── Button visual config per state ───────────────────────────────
const BTN = {
  idle: {
    cls:   'bg-emerald-600 text-white hover:bg-emerald-500 shadow-emerald-500/20',
    label: 'XÁC THỰC',
    icon:  'shield',
  },
  loading: {
    cls:   'bg-muted text-muted-foreground cursor-not-allowed',
    label: 'CHECKING...',
    icon:  'spinner',
  },
  valid: {
    cls:   'bg-emerald-600 text-white hover:bg-emerald-500 shadow-emerald-500/30',
    label: 'TOÀN VẸN',
    icon:  'check',
  },
  valid_no_fabric: {
    cls:   'bg-amber-500 text-white hover:bg-amber-400 shadow-amber-500/20',
    label: 'HỢP LỆ*',
    icon:  'check',
  },
  tampered_db: {
    cls:   'bg-destructive text-destructive-foreground hover:bg-destructive shadow-destructive/20 animate-pulse',
    label: 'BỊ SỬA ĐỔI!',
    icon:  'warn',
  },
  tampered_ipfs: {
    cls:   'bg-destructive text-destructive-foreground hover:bg-destructive shadow-destructive/20 animate-pulse',
    label: 'BỊ SỬA ĐỔI!',
    icon:  'warn',
  },
  ipfs_unavailable: {
    cls:   'bg-muted text-muted-foreground border-border/50 hover:bg-muted/80',
    label: 'THỬ LẠI',
    icon:  'warn-amber',
  },
  error: {
    cls:   'bg-muted text-muted-foreground border-border/50 hover:bg-muted/80',
    label: 'LỖI KẾT NỐI',
    icon:  'warn-amber',
  },
}

// ── Tooltip color per state ───────────────────────────────────────
const TIP_CLS = {
  valid:            'border-emerald-500/30 bg-emerald-950/90',
  valid_no_fabric:  'border-amber-500/30 bg-amber-950/90',
  tampered_db:      'border-destructive/40 bg-destructive/10',
  tampered_ipfs:    'border-destructive/40 bg-destructive/10',
  ipfs_unavailable: 'border-border bg-card',
  error:            'border-border bg-card',
}
</script>

<template>
  <div class="relative inline-flex items-center gap-3">

    <!-- IPFS CID label -->
    <div v-if="ipfsCid" class="flex flex-col items-end opacity-40 hover:opacity-100 transition-opacity">
      <span class="text-[7px] text-foreground font-black uppercase leading-none tracking-widest">Digital Link</span>
      <code class="text-[9px] font-mono text-primary font-black">
        #{{ shortCidFn(ipfsCid) }}
      </code>
    </div>

    <!-- Button -->
    <button
      @click="verify"
      :disabled="state === 'loading'"
      :class="[
        'inline-flex items-center gap-2 text-[9px] font-black px-4 py-2 rounded-xl',
        'shadow-xl active:scale-95 transition-all uppercase tracking-widest select-none border border-white/10',
        BTN[state]?.cls ?? BTN.idle.cls,
      ]"
    >
      <Loader2 v-if="BTN[state]?.icon === 'spinner'" class="w-3.5 h-3.5 animate-spin shrink-0" />
      <CheckCircle2 v-else-if="BTN[state]?.icon === 'check'" class="w-3.5 h-3.5 shrink-0" />
      <ShieldAlert v-else-if="BTN[state]?.icon === 'warn'" class="w-3.5 h-3.5 shrink-0" />
      <AlertTriangle v-else-if="BTN[state]?.icon === 'warn-amber'" class="w-3.5 h-3.5 shrink-0" />
      <ShieldCheck v-else class="w-3.5 h-3.5 shrink-0" />

      {{ BTN[state]?.label ?? 'Xác thực' }}
    </button>

    <!-- Tooltip dropdown -->
    <transition
      enter-active-class="transition-all duration-300 ease-out"
      enter-from-class="opacity-0 scale-95 -translate-y-2"
      enter-to-class="opacity-100 scale-100 translate-y-0"
      leave-active-class="transition-all duration-200 ease-in"
      leave-from-class="opacity-100 scale-100 translate-y-0"
      leave-to-class="opacity-0 scale-95 -translate-y-2"
    >
      <div
        v-if="showTip && details && state !== 'loading'"
        class="absolute right-0 top-full mt-3 z-[100] w-72 rounded-[2rem] border p-5 shadow-2xl backdrop-blur-2xl animate-in zoom-in duration-300"
        :class="TIP_CLS[state] ?? 'border-border bg-card/95'"
      >
        <!-- Close -->
        <button
          @click.stop="showTip = false"
          class="absolute top-4 right-4 text-foreground/20 hover:text-foreground/60 transition-colors"
        >
          <X class="w-4 h-4" />
        </button>

        <div class="space-y-5">
          <div class="px-1">
             <div class="text-[9px] font-black uppercase tracking-[0.3em] text-foreground/40 mb-4 ">Blockchain Integrity Report</div>
             
             <!-- 3 Layer rows -->
             <div class="space-y-4">
                <!-- Layer 1: Fabric -->
                <div class="flex items-start gap-4 group/item">
                  <div class="p-2 rounded-xl bg-background/50 border border-border shadow-inner group-hover/item:border-primary/30 transition-colors">
                    <Cpu class="w-4 h-4" :class="details.fabric?.found ? 'text-purple-500' : 'text-muted-foreground/40'" />
                  </div>
                  <div class="flex-1 min-w-0">
                    <div class="text-foreground font-black text-[9px] uppercase tracking-widest flex items-center gap-2">
                       Hyperledger Fabric 
                       <CheckCircle2 v-if="details.fabric?.found" class="w-3 h-3 text-emerald-500" />
                    </div>
                    <div class="text-[10px] font-bold  mt-0.5" :class="details.fabric?.found ? 'text-foreground/70' : 'text-muted-foreground/40'">
                      {{ details.fabric?.found ? 'Record verified on ledger' : details.fabric?.mock ? 'Mock Mode — No Fabric' : 'Hash not found' }}
                    </div>
                  </div>
                </div>

                <!-- Layer 2: IPFS -->
                <div class="flex items-start gap-4 group/item">
                  <div class="p-2 rounded-xl bg-background/50 border border-border shadow-inner group-hover/item:border-primary/30 transition-colors">
                    <Globe class="w-4 h-4" :class="details.ipfs?.valid ? 'text-sky-500' : 'text-muted-foreground/40'" />
                  </div>
                  <div class="flex-1 min-w-0">
                    <div class="text-foreground font-black text-[9px] uppercase tracking-widest flex items-center gap-2">
                       IPFS Protocol
                       <CheckCircle2 v-if="details.ipfs?.valid" class="w-3 h-3 text-emerald-500" />
                    </div>
                    <div class="text-[10px] font-bold  mt-0.5" :class="details.ipfs?.valid ? 'text-foreground/70' : 'text-muted-foreground/40'">
                      {{ details.ipfs?.valid ? 'Content hash matches ✓' : details.ipfs?.fetched === false ? 'Gateway Unavailable' : 'Hash MISMATCH ✗' }}
                    </div>
                  </div>
                </div>

                <!-- Layer 3: DB -->
                <div class="flex items-start gap-4 group/item">
                  <div class="p-2 rounded-xl bg-background/50 border border-border shadow-inner group-hover/item:border-primary/30 transition-colors">
                    <Database class="w-4 h-4" :class="details.db?.valid ? 'text-emerald-500' : 'text-muted-foreground/40'" />
                  </div>
                  <div class="flex-1 min-w-0">
                    <div class="text-foreground font-black text-[9px] uppercase tracking-widest flex items-center gap-2">
                       Local Database
                       <CheckCircle2 v-if="details.db?.valid" class="w-3 h-3 text-emerald-500" />
                    </div>
                    <div class="text-[10px] font-bold  mt-0.5" :class="details.db?.valid ? 'text-foreground/70' : 'text-muted-foreground/40'">
                      {{ details.db?.valid ? 'Local data is pristine ✓' : 'TAMPERED DETECTED ✗' }}
                    </div>
                  </div>
                </div>
             </div>
          </div>

          <!-- Bottom Meta -->
          <div class="mt-4 pt-4 border-t border-dashed border-border/50">
             <div class="flex items-center justify-between text-[8px] font-black uppercase tracking-widest text-foreground/30">
                <span>Trust Source:</span>
                <span class="text-primary ">{{ details.ground_truth_source }}</span>
             </div>
          </div>
        </div>
      </div>
    </transition>

  </div>
</template>
