<script setup>
/**
 * KdeFields.vue — Dynamic KDE input fields theo cte_code
 * Props:
 *   cteCode: string — mã CTE (planting, harvesting, inspection, ...)
 *   modelValue: object — kde_data hiện tại
 */
const props = defineProps({
  cteCode:    { type: String, default: '' },
  modelValue: { type: Object, default: () => ({}) },
})
const emit = defineEmits(['update:modelValue'])

// ── KDE schemas theo CTE ──────────────────────────────────────────
const KDE_SCHEMAS = {
  planting: [
    { key: 'seed_variety',   label: 'Giống cây trồng',        type: 'text',   required: true,  placeholder: 'VD: IR 504, Jasmine 85' },
    { key: 'planting_density', label: 'Mật độ gieo (cây/m²)', type: 'number', required: false },
    { key: 'method',         label: 'Phương pháp canh tác',   type: 'select', required: false, options: ['Cấy tay', 'Cấy máy', 'Gieo thẳng', 'Gieo mạ'] },
    { key: 'land_area_ha',   label: 'Diện tích (ha)',          type: 'number', required: false },
    { key: 'seed_source',    label: 'Nguồn giống',             type: 'text',   required: false, placeholder: 'VD: Trung tâm giống tỉnh' },
  ],
  growing: [
    { key: 'fertilizer_type',   label: 'Loại phân bón',        type: 'text',   required: true,  placeholder: 'VD: NPK 16-16-8' },
    { key: 'fertilizer_dose',   label: 'Liều lượng (kg/ha)',   type: 'number', required: false },
    { key: 'pesticide',         label: 'Thuốc BVTV',           type: 'text',   required: false, placeholder: 'Tên hoạt chất hoặc tên thương mại' },
    { key: 'pesticide_dose',    label: 'Liều lượng thuốc',     type: 'text',   required: false },
    { key: 'pesticide_phi',     label: 'PHI (ngày cách ly)',   type: 'number', required: false },
  ],
  harvesting: [
    { key: 'yield_kg_ha',    label: 'Năng suất (kg/ha)',       type: 'number', required: true },
    { key: 'moisture',       label: 'Độ ẩm (%)',               type: 'number', required: false },
    { key: 'impurity',       label: 'Tạp chất (%)',            type: 'number', required: false },
    { key: 'vehicle',        label: 'Phương tiện thu hoạch',   type: 'text',   required: false },
    { key: 'harvest_area_ha',label: 'Diện tích thu (ha)',      type: 'number', required: false },
  ],
  processing: [
    { key: 'output_ratio',   label: 'Tỉ lệ thành phẩm (%)',   type: 'number', required: true },
    { key: 'capacity_ton_h', label: 'Công suất (tấn/giờ)',     type: 'number', required: false },
    { key: 'process_method', label: 'Phương pháp chế biến',    type: 'text',   required: false, placeholder: 'VD: Sấy nhiệt, đông lạnh' },
    { key: 'temperature',    label: 'Nhiệt độ xử lý (°C)',     type: 'number', required: false },
  ],
  packaging: [
    { key: 'package_spec',   label: 'Quy cách bao bì',         type: 'text',   required: true,  placeholder: 'VD: Túi PE 5kg, thùng carton 20kg' },
    { key: 'package_qty',    label: 'Số lượng bao/thùng',      type: 'number', required: false },
    { key: 'package_material',label: 'Vật liệu bao bì',        type: 'text',   required: false },
    { key: 'label_printed',  label: 'Tem nhãn in',             type: 'select', required: false, options: ['Có', 'Không'] },
  ],
  inspection: [
    { key: 'cert_no',        label: 'Số phiếu kiểm',           type: 'text',   required: true },
    { key: 'criteria',       label: 'Chỉ tiêu kiểm tra',       type: 'textarea',required: false, placeholder: 'VD: Hàm lượng thuốc trừ sâu, vi sinh vật...' },
    { key: 'result',         label: 'Kết quả tổng thể',        type: 'select', required: true, options: ['Đạt', 'Không đạt', 'Có điều kiện'] },
    { key: 'lab_name',       label: 'Đơn vị kiểm nghiệm',      type: 'text',   required: false },
    { key: 'valid_until',    label: 'Hiệu lực đến',            type: 'date',   required: false },
  ],
  shipping: [
    { key: 'vehicle_plate',  label: 'Phương tiện / Biển số',   type: 'text',   required: true, placeholder: 'VD: 65C-123.45' },
    { key: 'driver_name',    label: 'Tài xế',                  type: 'text',   required: false },
    { key: 'route_from',     label: 'Điểm xuất phát',          type: 'text',   required: false },
    { key: 'route_to',       label: 'Điểm đến',                type: 'text',   required: false },
    { key: 'temp_condition', label: 'Điều kiện vận chuyển',    type: 'text',   required: false, placeholder: 'VD: Lạnh 2-8°C, nhiệt độ thường' },
    { key: 'distance_km',    label: 'Quãng đường (km)',         type: 'number', required: false },
  ],
  storage: [
    { key: 'warehouse_name', label: 'Tên / Địa chỉ kho',       type: 'text',   required: true },
    { key: 'keeper',         label: 'Người quản lý kho',        type: 'text',   required: false },
    { key: 'temp_celsius',   label: 'Nhiệt độ bảo quản (°C)',  type: 'number', required: false },
    { key: 'humidity_pct',   label: 'Độ ẩm (%)',               type: 'number', required: false },
    { key: 'condition',      label: 'Điều kiện bảo quản',      type: 'text',   required: false },
  ],
}

// Fallback generic
const GENERIC_FIELDS = [
  { key: 'what_result',  label: 'Kết quả thực hiện', type: 'text',     required: false },
  { key: 'why_reason',   label: 'Lý do / Ghi chú',   type: 'textarea', required: false },
]

import { computed } from 'vue'

const fields = computed(() => KDE_SCHEMAS[props.cteCode] ?? GENERIC_FIELDS)

const inputCls = 'w-full bg-black/20 border border-white/10 rounded-xl px-3 py-2.5 text-sm text-white/90 placeholder:text-white/30 outline-none focus:border-brand-500/60 transition'

function update(key, val) {
  emit('update:modelValue', { ...props.modelValue, [key]: val })
}
</script>

<template>
  <div v-if="fields.length" class="grid grid-cols-1 md:grid-cols-2 gap-3">
    <div
      v-for="field in fields"
      :key="field.key"
      :class="['textarea', 'text'].includes(field.type) && field.type === 'textarea' ? 'md:col-span-2' : ''"
    >
      <label class="block text-xs font-medium text-white/50 mb-1.5">
        {{ field.label }}
        <span v-if="field.required" class="text-red-400 ml-0.5">*</span>
      </label>

      <!-- Textarea -->
      <textarea
        v-if="field.type === 'textarea'"
        :value="modelValue[field.key] ?? ''"
        @input="update(field.key, $event.target.value)"
        :placeholder="field.placeholder ?? ''"
        :class="inputCls"
        rows="2"
      />

      <!-- Select -->
      <select
        v-else-if="field.type === 'select'"
        :value="modelValue[field.key] ?? ''"
        @change="update(field.key, $event.target.value)"
        :class="inputCls"
      >
        <option value="">— Chọn —</option>
        <option v-for="opt in field.options" :key="opt" :value="opt">{{ opt }}</option>
      </select>

      <!-- Number / Date / Text -->
      <input
        v-else
        :type="field.type ?? 'text'"
        :value="modelValue[field.key] ?? ''"
        @input="update(field.key, $event.target.value)"
        :placeholder="field.placeholder ?? ''"
        :class="inputCls"
      />
    </div>
  </div>

  <!-- Empty state khi không có schema -->
  <div v-else class="text-xs text-white/30  py-2">
    Không có trường dữ liệu chuẩn cho loại sự kiện này. Bạn có thể thêm ghi chú bên dưới.
  </div>
</template>
