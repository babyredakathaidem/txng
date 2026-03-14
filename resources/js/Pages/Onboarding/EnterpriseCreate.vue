<script setup>
import { Head, useForm, Link } from '@inertiajs/vue3'
import { computed, onMounted, ref, watch } from 'vue'
import { Button } from '@/Components/ui/button/index.js'
import { Input } from '@/Components/ui/input/index.js'
import { Label } from '@/Components/ui/label/index.js'
import { Checkbox } from '@/Components/ui/checkbox/index.js'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/Components/ui/select/index.js'
import { Card, CardHeader, CardTitle, CardDescription, CardContent, CardFooter } from '@/Components/ui/card/index.js'
import AuthLayout from '@/Layouts/AuthLayout.vue'

const API_BASE = 'https://provinces.open-api.vn/api/v2'
const provinces = ref([])
const wards = ref([])
const loadingProvinces = ref(false)
const loadingWards = ref(false)

const form = useForm({
  name:'',
  business_code:'',
  business_code_issued_at:'',
  business_cert_no:'',
  business_cert_issued_place:'',
  business_license_no:'',
  business_license_issued_place:'',
  province_code:null,
  province:'',
  ward_code:null,
  ward:'',
  district:'', // map ward -> district
  address_detail:'',
  phone:'',
  email:'',
  representative_name:'',
  representative_id:'',
  business_cert_file:null,
  accept_terms:false,
})

async function loadProvinces(){
  loadingProvinces.value = true
  try{
    const res = await fetch(`${API_BASE}/p/`)
    provinces.value = await res.json()
  } finally { loadingProvinces.value = false }
}

async function loadWardsByProvinceCode(provinceCode){
  wards.value = []
  if(!provinceCode) return
  loadingWards.value = true
  try{
    const res = await fetch(`${API_BASE}/p/${provinceCode}?depth=2`)
    const json = await res.json()
    wards.value = Array.isArray(json?.wards) ? json.wards : []
  } finally { loadingWards.value = false }
}

watch(()=>form.province_code, async(code)=>{
  form.ward_code = null
  form.ward = ''
  form.district = ''
  wards.value = []
  const p = provinces.value.find(x=>x.code===Number(code))
  form.province = p?.name ?? ''
  if (code) await loadWardsByProvinceCode(code)
})

watch(()=>form.ward_code, (code)=>{
  const w = wards.value.find(x=>x.code===Number(code))
  form.ward = w?.name ?? ''
  form.district = form.ward
})

onMounted(loadProvinces)

const onFileChange = (e)=>{ form.business_cert_file = e.target.files?.[0] ?? null }

const canSubmit = computed(()=> Boolean(
  form.name && form.business_code && form.province && form.ward && form.phone && form.email && form.business_cert_file && form.accept_terms && !form.processing
))

const submit = ()=> form.post('/onboarding/enterprise', { forceFormData:true, preserveScroll:true })
</script>

<template>
  <Head title="Đăng ký doanh nghiệp" />
  
  <AuthLayout title="Đăng ký tổ chức" subtitle="Khai báo thông tin pháp nhân để tham gia chuỗi cung ứng.">
    <Card class="border-none bg-transparent shadow-none p-0">
      <form class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-6" @submit.prevent="submit">
        
        <div class="md:col-span-2 space-y-2">
          <Label class="text-xs font-bold uppercase tracking-widest">Tên đầy đủ của tổ chức / DN *</Label>
          <Input v-model="form.name" placeholder="Công ty TNHH Một Thành Viên..." class="h-11 bg-muted/20" />
          <p v-if="form.errors.name" class="text-xs font-medium text-destructive mt-1">{{ form.errors.name }}</p>
        </div>

        <div class="space-y-2">
          <Label class="text-xs font-bold uppercase tracking-widest">Mã số doanh nghiệp *</Label>
          <Input v-model="form.business_code" placeholder="Nhập mã số thuế / mã số DN" class="h-11 bg-muted/20" />
          <p v-if="form.errors.business_code" class="text-xs font-medium text-destructive mt-1">{{ form.errors.business_code }}</p>
        </div>

        <div class="space-y-2">
          <Label class="text-xs font-bold uppercase tracking-widest">Ngày cấp mã số</Label>
          <Input v-model="form.business_code_issued_at" type="date" class="h-11 bg-muted/20" />
        </div>

        <div class="space-y-2">
          <Label class="text-xs font-bold uppercase tracking-widest">Số GCN đăng ký DN</Label>
          <Input v-model="form.business_cert_no" placeholder="Số hiệu giấy chứng nhận" class="h-11 bg-muted/20" />
        </div>

        <div class="space-y-2">
          <Label class="text-xs font-bold uppercase tracking-widest">Nơi cấp GCN</Label>
          <Input v-model="form.business_cert_issued_place" placeholder="Sở Kế hoạch và Đầu tư..." class="h-11 bg-muted/20" />
        </div>

        <div class="space-y-2">
          <Label class="text-xs font-bold uppercase tracking-widest">Số giấy phép kinh doanh</Label>
          <Input v-model="form.business_license_no" placeholder="Nếu có" class="h-11 bg-muted/20" />
        </div>

        <div class="space-y-2">
          <Label class="text-xs font-bold uppercase tracking-widest">Nơi cấp giấy phép</Label>
          <Input v-model="form.business_license_issued_place" class="h-11 bg-muted/20" />
        </div>

        <div class="space-y-2">
          <Label class="text-xs font-bold uppercase tracking-widest">Tỉnh / Thành phố *</Label>
          <Select v-model="form.province_code">
            <SelectTrigger class="h-11 bg-muted/20">
              <SelectValue :placeholder="loadingProvinces ? 'Đang tải dữ liệu...' : 'Chọn tỉnh/thành'" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem v-for="p in provinces" :key="p.code" :value="String(p.code)">{{ p.name }}</SelectItem>
            </SelectContent>
          </Select>
        </div>

        <div class="space-y-2">
          <Label class="text-xs font-bold uppercase tracking-widest">Quận / Huyện / Xã *</Label>
          <Select v-model="form.ward_code" :disabled="!form.province_code">
            <SelectTrigger class="h-11 bg-muted/20">
              <SelectValue :placeholder="loadingWards ? 'Đang tải...' : 'Chọn khu vực'" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem v-for="w in wards" :key="w.code" :value="String(w.code)">{{ w.name }}</SelectItem>
            </SelectContent>
          </Select>
          <p v-if="form.errors.district" class="text-xs font-medium text-destructive mt-1">{{ form.errors.district }}</p>
        </div>

        <div class="md:col-span-2 space-y-2">
          <Label class="text-xs font-bold uppercase tracking-widest">Địa chỉ chi tiết</Label>
          <Input v-model="form.address_detail" placeholder="Số nhà, tên đường, tổ, ấp..." class="h-11 bg-muted/20" />
        </div>

        <div class="space-y-2">
          <Label class="text-xs font-bold uppercase tracking-widest">Số điện thoại *</Label>
          <Input v-model="form.phone" placeholder="09xx..." class="h-11 bg-muted/20" />
        </div>

        <div class="space-y-2">
          <Label class="text-xs font-bold uppercase tracking-widest">Email doanh nghiệp *</Label>
          <Input v-model="form.email" type="email" placeholder="contact@company.com" class="h-11 bg-muted/20" />
        </div>

        <div class="space-y-2">
          <Label class="text-xs font-bold uppercase tracking-widest">Người đại diện pháp luật</Label>
          <Input v-model="form.representative_name" placeholder="Nguyễn Văn A" class="h-11 bg-muted/20" />
        </div>

        <div class="space-y-2">
          <Label class="text-xs font-bold uppercase tracking-widest">Số CCCD / Hộ chiếu</Label>
          <Input v-model="form.representative_id" class="h-11 bg-muted/20" />
        </div>

        <div class="md:col-span-2 space-y-3">
          <Label class="text-xs font-bold uppercase tracking-widest">Đính kèm GCN đăng ký doanh nghiệp (PDF/Ảnh) *</Label>
          <div class="flex items-center gap-4">
             <Input type="file" accept=".pdf,.jpg,.jpeg,.png" @change="onFileChange" class="h-11 bg-muted/20 cursor-pointer pt-2" />
          </div>
          <p class="text-[10px] text-muted-foreground  uppercase font-bold tracking-tight">Tối đa 20MB. File này dùng để xác minh danh tính tổ chức.</p>
          <p v-if="form.errors.business_cert_file" class="text-xs font-medium text-destructive mt-1">{{ form.errors.business_cert_file }}</p>
        </div>

        <div class="md:col-span-2 flex items-start space-x-3 py-2">
          <Checkbox id="terms" :checked="form.accept_terms" @update:checked="(v) => (form.accept_terms = v)" class="mt-1" />
          <div class="grid gap-1.5 leading-none">
            <Label for="terms" class="text-sm font-medium leading-relaxed peer-disabled:cursor-not-allowed peer-disabled:opacity-70 cursor-pointer">
              Tôi cam đoan thông tin khai báo là chính xác và đồng ý với 
              <a href="#" class="text-primary hover:underline font-bold">Chính sách bảo mật</a> & 
              <a href="#" class="text-primary hover:underline font-bold">Điều khoản sử dụng</a> của hệ thống.
            </Label>
            <p v-if="form.errors.accept_terms" class="text-xs font-medium text-destructive">{{ form.errors.accept_terms }}</p>
          </div>
        </div>

        <div class="md:col-span-2 pt-4">
          <Button type="submit" :disabled="!canSubmit" class="w-full h-12 text-xs font-black uppercase tracking-widest shadow-xl shadow-primary/20">
            {{ form.processing ? 'ĐANG GỬI THÔNG TIN...' : 'HOÀN TẤT ĐĂNG KÝ' }}
          </Button>
          
          <div class="flex flex-col items-center gap-4 mt-8">
             <Link href="/" class="text-xs font-bold text-muted-foreground hover:text-foreground transition-colors uppercase tracking-widest">Để sau</Link>
             <div class="text-sm text-muted-foreground">
                Tổ chức đã có tài khoản? <Link href="/login" class="text-primary hover:underline font-black uppercase text-xs">Đăng nhập</Link>
             </div>
          </div>
        </div>
      </form>
    </Card>
  </AuthLayout>
</template>
