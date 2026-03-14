<script setup>
import { ref } from "vue";
import axios from "axios";

const props = defineProps({
  modelValue: { type: Object, default: () => ({ place_name: "", lat: "", lng: "", refid: "" }) },
});
const emit = defineEmits(["update:modelValue"]);

const q = ref(props.modelValue.place_name || "");
const suggestions = ref([]);
const loading = ref(false);

function update(patch) {
  emit("update:modelValue", { ...props.modelValue, ...patch });
}

let timer = null;

async function onInput() {
  update({ place_name: q.value });

  clearTimeout(timer);
  timer = setTimeout(async () => {
    const text = q.value.trim();
    if (text.length < 2) {
      suggestions.value = [];
      return;
    }

    loading.value = true;
    try {
      const { data } = await axios.post(route("vietmap.autocomplete"), { text });
      suggestions.value = (Array.isArray(data) ? data : []).map((x) => ({
        refid: x.ref_id,
        display: x.display || x.address || x.name || "",
      }));
    } finally {
      loading.value = false;
    }
  }, 300);
}

async function pick(item) {
  loading.value = true;
  try {
    const { data } = await axios.post(route("vietmap.place"), { refid: item.refid });

    const label = data.display || item.display;

    q.value = label;
    update({
      place_name: label,
      lat: data.lat ?? "",
      lng: data.lng ?? "",
      refid: item.refid,
    });

    suggestions.value = [];
  } finally {
    loading.value = false;
  }
}
</script>

<template>
  <div class="space-y-2">
    <label class="text-sm font-medium">Tên địa điểm</label>
    <input
    class="w-full border border-glass rounded-xl px-3 py-2 bg-black/20 text-white/90 placeholder:text-white/30 outline-none focus:border-brand-500"
    v-model="q"
      @input="onInput"
      placeholder="Gõ địa điểm (VD: Co.opmart Long Xuyên...)"
    />
    <div v-if="suggestions.length" class="border border-glass rounded-xl overflow-hidden bg-cosmic-900">
      <button
        v-for="s in suggestions"
        :key="s.refid"
        type="button"
        class="w-full text-left px-3 py-2 hover:bg-orange-400"
        @click="pick(s)"
      >
        {{ s.display }}
      </button>
    </div>

    <div class="grid grid-cols-2 gap-3">
      <div>
        <label class="text-sm font-medium">Tọa độ X (lat)</label>
        <input class="w-full border border-glass rounded-xl px-3 py-2 bg-black/20 text-white/70 outline-none" :value="props.modelValue.lat" readonly />
      </div>
      <div>
        <label class="text-sm font-medium">Tọa độ Y (lng)</label>
        <input class="w-full border border-glass rounded-xl px-3 py-2 bg-black/20 text-white/70 outline-none" :value="props.modelValue.lng" readonly />
      </div>
    </div>

    <p v-if="loading" class="text-xs text-gray-500">Đang tìm…</p>
  </div>
</template>
