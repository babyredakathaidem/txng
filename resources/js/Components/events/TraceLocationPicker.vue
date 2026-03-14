<script setup>
/**
 * TraceLocationPicker
 *
 * v-model: trace_location_id (Number | null)
 * Fetch locations từ /api/trace-locations (auth + tenant).
 * Dùng Teleport + getBoundingClientRect để dropdown không bị
 * overflow:hidden/auto của modal/scrollable container clip.
 */
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'
import axios from 'axios'

const props = defineProps({
  modelValue: { type: [Number, String, null], default: null },
  label:      { type: String, default: 'Kho / Địa điểm tiếp nhận' },
  aiType:     { type: String, default: '' },   // '' = tất cả | '416' | '414' ...
  required:   { type: Boolean, default: false },
})
const emit = defineEmits(['update:modelValue'])

// ── Data ────────────────────────────────────────────────────────
const locations = ref([])
const loading   = ref(false)

async function fetchLocations() {
  loading.value = true
  try {
    const { data } = await axios.get(route('api.trace-locations.list'), {
      params: { ai_type: props.aiType || undefined },
    })
    locations.value = Array.isArray(data) ? data : []
  } catch {
    locations.value = []
  } finally {
    loading.value = false
  }
}

onMounted(fetchLocations)

// ── Selected ─────────────────────────────────────────────────────
const selected = computed(() =>
  locations.value.find(l => l.id === Number(props.modelValue)) ?? null
)

// ── Dropdown state ────────────────────────────────────────────────
const open          = ref(false)
const search        = ref('')
const triggerRef    = ref(null)
const dropdownStyle = ref({})

const filtered = computed(() => {
  const q = search.value.toLowerCase().trim()
  if (!q) return locations.value
  return locations.value.filter(l =>
    (l.name     ?? '').toLowerCase().includes(q) ||
    (l.gln      ?? '').toLowerCase().includes(q) ||
    (l.province ?? '').toLowerCase().includes(q) ||
    (l.district ?? '').toLowerCase().includes(q)
  )
})

function openDropdown() {
  open.value  = true
  search.value = ''
  recalcPos()
}

function recalcPos() {
  if (!triggerRef.value) return
  const r = triggerRef.value.getBoundingClientRect()
  dropdownStyle.value = {
    position : 'fixed',
    top      : `${r.bottom + 4}px`,
    left     : `${r.left}px`,
    width    : `${r.width}px`,
    zIndex   : 9999,
  }
}

function selectItem(loc) {
  emit('update:modelValue', loc.id)
  open.value = false
}

function clear(e) {
  e.stopPropagation()
  emit('update:modelValue', null)
}

// ── Đóng khi click ngoài ─────────────────────────────────────────
function onMousedown(e) {
  if (
    triggerRef.value?.contains(e.target) ||
    e.target.closest('[data-tlp-dd]')
  ) return
  open.value = false
}

// ── Re-position khi scroll / resize ──────────────────────────────
function onScrollResize() { if (open.value) recalcPos() }

onMounted(() => {
  document.addEventListener('mousedown', onMousedown)
  window.addEventListener('scroll', onScrollResize, true)
  window.addEventListener('resize', onScrollResize)
})
onUnmounted(() => {
  document.removeEventListener('mousedown', onMousedown)
  window.removeEventListener('scroll', onScrollResize, true)
  window.removeEventListener('resize', onScrollResize)
})

// ── AI type icon ──────────────────────────────────────────────────
const AI_ICON = { '416': '🌱', '414': '🏭', '410': '📥', '412': '🤝', '417': '🔄' }
function aiIcon(t) { return AI_ICON[t] ?? '📍' }

// ── Style ─────────────────────────────────────────────────────────
const baseCls = 'w-full bg-[#0d1117] border border-white/10 rounded-xl px-4 py-3 text-sm text-white/90 outline-none transition-all shadow-inner'
</script>

<template>
  <div class="space-y-1">
    <!-- Label -->
    <label class="block text-xs font-bold text-white/40 uppercase tracking-widest mb-2">
      {{ label }}<span v-if="required" class="text-red-400 ml-1">*</span>
    </label>

    <!-- Trigger ─────────────────────────────────────────────────── -->
    <button
      ref="triggerRef"
      type="button"
      @click="openDropdown"
      :class="[
        baseCls,
        'flex items-center justify-between gap-2 text-left cursor-pointer',
        open ? 'border-emerald-500/50 ring-1 ring-emerald-500/20' : 'hover:border-white/20',
      ]"
    >
      <!-- Selected value -->
      <div v-if="selected" class="flex items-center gap-2 min-w-0">
        <span class="text-base leading-none shrink-0">{{ aiIcon(selected.ai_type) }}</span>
        <span class="text-sm font-semibold text-white/85 truncate">{{ selected.name }}</span>
        <span class="text-xs font-mono text-white/25 shrink-0">{{ selected.gln }}</span>
        <span class="text-[10px] text-emerald-400/50 shrink-0">· {{ [selected.district, selected.province].filter(Boolean).join(', ') }}</span>
      </div>
      <span v-else class="text-white/25 text-sm">
        /— Chọn hoặc tìm kiếm địa điểm —
      </span>

      <!-- Right icons -->
      <div class="flex items-center gap-1 shrink-0 ml-auto">
        <span v-if="loading" class="text-[10px] text-white/25 animate-pulse">...</span>
        <button
          v-if="selected"
          type="button"
          @click="clear"
          class="text-white/20 hover:text-red-400 text-lg leading-none transition-colors"
        >×</button>
        <svg
          class="w-4 h-4 text-white/20 transition-transform duration-150"
          :class="open ? 'rotate-180' : ''"
          fill="none" viewBox="0 0 24 24" stroke="currentColor"
        >
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
        </svg>
      </div>
    </button>

    <!-- ── DROPDOWN — Teleport ra body, không bị overflow clip ──── -->
    <Teleport to="body">
      <Transition
        enter-active-class="transition-all duration-150 ease-out origin-top"
        enter-from-class="opacity-0 scale-y-95 -translate-y-1"
        enter-to-class="opacity-100 scale-y-100 translate-y-0"
        leave-active-class="transition-all duration-100 ease-in origin-top"
        leave-from-class="opacity-100 scale-y-100 translate-y-0"
        leave-to-class="opacity-0 scale-y-95 -translate-y-1"
      >
        <div
          v-if="open"
          data-tlp-dd
          :style="dropdownStyle"
          class="rounded-2xl border border-white/10 bg-[#0c1014] shadow-2xl shadow-black/70 overflow-hidden"
        >
          <!-- Search bar -->
          <div class="p-2 border-b border-white/6">
            <div class="relative">
              <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-white/20 pointer-events-none"
                fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0"/>
              </svg>
              <input
                v-model="search"
                type="text"
                autofocus
                class="w-full bg-white/5 border border-white/8 rounded-xl pl-8 pr-3 py-2
                       text-xs text-white/80 placeholder:text-white/25
                       outline-none focus:border-emerald-500/40 transition"
                placeholder="Tìm tên ruộng, mã GLN..."
                @keydown.escape="open = false"
              />
            </div>
          </div>

          <!-- Location list -->
          <div class="max-h-56 overflow-y-auto overscroll-contain">

            <!-- Loading -->
            <div v-if="loading" class="py-6 text-center text-xs text-white/20 animate-pulse">
              Đang tải địa điểm...
            </div>

            <!-- Empty -->
            <div v-else-if="filtered.length === 0" class="py-6 text-center text-xs text-white/20">
              {{ search ? 'Không tìm thấy địa điểm' : 'Chưa có địa điểm nào' }}
            </div>

            <!-- Items -->
            <template v-else>
              <button
                v-for="loc in filtered.slice(0, 40)"
                :key="loc.id"
                type="button"
                @click="selectItem(loc)"
                class="w-full flex items-center gap-3 px-4 py-2.5 text-left
                       border-b border-white/4 last:border-0
                       hover:bg-emerald-500/6 transition"
                :class="modelValue === loc.id ? 'bg-emerald-500/8 border-l-2 border-l-emerald-500' : ''"
              >
                <!-- Icon -->
                <div class="w-7 h-7 rounded-lg bg-white/5 border border-white/8
                            flex items-center justify-center text-sm shrink-0">
                  {{ aiIcon(loc.ai_type) }}
                </div>

                <!-- Info -->
                <div class="flex-1 min-w-0">
                  <div class="text-xs font-semibold text-white/80 truncate">{{ loc.name }}</div>
                  <div class="text-[10px] text-white/30 truncate">
                    <span v-if="loc.gln" class="font-mono mr-1">{{ loc.gln }}</span>
                    <span>{{ [loc.district, loc.province].filter(Boolean).join(', ') }}</span>
                  </div>
                </div>

                <!-- AI type badge -->
                <span class="text-[9px] font-mono text-white/15 shrink-0">({{ loc.ai_type }})</span>

                <!-- Check -->
                <svg v-if="modelValue === loc.id"
                  class="w-4 h-4 text-emerald-400 shrink-0" viewBox="0 0 20 20" fill="currentColor">
                  <path fill-rule="evenodd"
                    d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                    clip-rule="evenodd"/>
                </svg>
              </button>
            </template>
          </div>

          <!-- Footer -->
          <div class="px-4 py-2 border-t border-white/6 flex items-center justify-between">
            <span class="text-[9px] text-white/15">{{ filtered.length }} địa điểm</span>
            <a
              :href="route('trace-locations.index')"
              target="_blank"
              class="text-[9px] text-emerald-400/40 hover:text-emerald-400/70 transition"
            >Quản lý địa điểm →</a>
          </div>
        </div>
      </Transition>
    </Teleport>
  </div>
</template>
