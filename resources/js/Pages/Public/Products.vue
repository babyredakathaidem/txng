<script setup>
import { Head, router, Link } from '@inertiajs/vue3'
import GuestLayout from '@/Layouts/GuestLayout.vue'
import { ref } from 'vue'
import { Card, CardHeader, CardTitle, CardDescription, CardContent, CardFooter } from '@/Components/ui/card/index.js'
import { Button } from '@/Components/ui/button/index.js'
import { Input } from '@/Components/ui/input/index.js'
import { Badge } from '@/Components/ui/badge/index.js'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/Components/ui/select/index.js'
import { Separator } from '@/Components/ui/separator/index.js'
import { Label } from '@/Components/ui/label/index.js'
import { 
  Search, 
  MapPin, 
  Archive, 
  Filter, 
  ArrowRight,
  X,
  ShoppingBag
} from 'lucide-vue-next'

defineOptions({ layout: GuestLayout })

const props = defineProps({
  products:   { type: Object, default: () => ({}) },
  categories: { type: Array,  default: () => [] },
  provinces:  { type: Array,  default: () => [] },
  filters:    { type: Object, default: () => ({}) },
})

const q          = ref(props.filters.q ?? '')
const categoryId = ref(props.filters.category_id ? String(props.filters.category_id) : 'all')
const province   = ref(props.filters.province ?? 'all')

function applyFilter() {
  router.get('/san-pham', {
    q:           q.value || undefined,
    category_id: categoryId.value === 'all' ? undefined : categoryId.value,
    province:    province.value === 'all' ? undefined : province.value,
  }, { preserveState: false, replace: true })
}

function reset() {
  q.value = ''
  categoryId.value = 'all'
  province.value = 'all'
  router.get('/san-pham', {}, { preserveState: false, replace: true })
}

function goToVerify(gtin) {
  router.get('/verify', { query: gtin })
}

const imgSrc = (path) => {
  if (!path) return null
  return path.startsWith('http') ? path : `/storage/${path}`
}

const list      = props.products?.data ?? []
const paginator = props.products
</script>

<template>
  <Head title="Danh mục sản phẩm — AGU Traceability" />

  <div class="max-w-7xl mx-auto px-6 py-12 space-y-10">

    <!-- Header -->
    <div class="space-y-4">
      <nav class="flex items-center gap-2 text-[10px] font-black uppercase tracking-widest text-muted-foreground">
        <Link href="/" class="hover:text-primary transition-colors">Trang chủ</Link>
        <span>/</span>
        <span class="text-foreground ">Danh mục sản phẩm</span>
      </nav>
      <div class="flex items-center gap-4">
         <div class="p-3 rounded-2xl bg-primary/10 text-primary">
            <ShoppingBag class="w-8 h-8" />
         </div>
         <div>
            <h1 class="text-4xl font-black  tracking-tighter text-foreground uppercase">Sản phẩm tiêu biểu</h1>
            <p class="text-muted-foreground font-medium  mt-1">Khám phá các sản phẩm đã được xác thực minh bạch thông tin.</p>
         </div>
      </div>
    </div>

    <!-- Filter Bar -->
    <Card class="border-border/50 bg-card/50 backdrop-blur-md shadow-xl overflow-hidden relative rounded-[2rem]">
      <div class="absolute left-0 top-0 bottom-0 w-1 bg-primary"></div>
      <CardContent class="p-6 space-y-6">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-end">
          
          <div class="lg:col-span-5 space-y-2">
            <Label class="text-[10px] font-black uppercase tracking-widest text-muted-foreground ml-1">Tìm kiếm</Label>
            <div class="relative">
               <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted-foreground opacity-50" />
               <Input 
                 v-model="q" 
                 @keyup.enter="applyFilter" 
                 placeholder="Tên sản phẩm, GTIN, doanh nghiệp..." 
                 class="h-11 pl-10 rounded-xl"
               />
            </div>
          </div>

          <div class="lg:col-span-3 space-y-2">
            <Label class="text-[10px] font-black uppercase tracking-widest text-muted-foreground ml-1">Lĩnh vực</Label>
            <Select v-model="categoryId">
              <SelectTrigger class="h-11 rounded-xl">
                <SelectValue placeholder="Tất cả lĩnh vực" />
              </SelectTrigger>
              <SelectContent class="rounded-xl">
                <SelectItem value="all">Tất cả lĩnh vực</SelectItem>
                <SelectItem v-for="c in categories" :key="c.id" :value="String(c.id)">
                  {{ c.icon }} {{ c.name_vi }}
                </SelectItem>
              </SelectContent>
            </Select>
          </div>

          <div class="lg:col-span-3 space-y-2">
            <Label class="text-[10px] font-black uppercase tracking-widest text-muted-foreground ml-1">Địa phương</Label>
            <Select v-model="province">
              <SelectTrigger class="h-11 rounded-xl">
                <SelectValue placeholder="Toàn quốc" />
              </SelectTrigger>
              <SelectContent class="rounded-xl">
                <SelectItem value="all">Toàn quốc</SelectItem>
                <SelectItem v-for="p in provinces" :key="p" :value="p">{{ p }}</SelectItem>
              </SelectContent>
            </Select>
          </div>

          <div class="lg:col-span-1">
             <Button @click="applyFilter" class="w-full h-11 rounded-xl font-black uppercase tracking-widest text-[10px] shadow-lg shadow-primary/20">
                Lọc
             </Button>
          </div>
        </div>

        <div class="flex items-center justify-between pt-4 border-t border-dashed border-border/50">
           <div class="flex items-center gap-2 text-[10px] font-bold text-muted-foreground uppercase tracking-widest">
              <Filter class="w-3.5 h-3.5" />
              Kết quả: <span class="text-foreground font-black">{{ paginator?.total ?? 0 }}</span> sản phẩm
           </div>
           <Button v-if="q || categoryId !== 'all' || province !== 'all'" variant="ghost" @click="reset" class="h-8 text-[10px] font-black uppercase tracking-widest text-primary hover:text-primary/80">
              <X class="w-3.5 h-3.5 mr-1.5" /> Xóa bộ lọc
           </Button>
        </div>
      </CardContent>
    </Card>

    <!-- Product Grid -->
    <div v-if="list.length === 0" class="flex flex-col items-center justify-center py-32 border-2 border-dashed border-border/50 rounded-[2.5rem] bg-muted/5 text-muted-foreground">
       <Archive class="w-16 h-16 opacity-10 mb-4 animate-pulse" />
       <p class="text-sm font-bold uppercase tracking-widest ">Không tìm thấy sản phẩm phù hợp</p>
       <Button variant="link" @click="reset" class="mt-2 text-primary font-black uppercase text-[10px]">Quay lại danh sách đầy đủ</Button>
    </div>

    <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-8">
      <Card
        v-for="p in list"
        :key="p.id"
        class="group rounded-[2rem] border-border/50 overflow-hidden hover:border-primary/40 hover:shadow-2xl transition-all duration-500 cursor-pointer flex flex-col bg-card/50 backdrop-blur-sm shadow-lg"
        @click="p.gtin && goToVerify(p.gtin)"
      >
        <!-- Image Container -->
        <div class="relative aspect-[4/3] bg-muted overflow-hidden">
          <img
            v-if="imgSrc(p.image_path)"
            :src="imgSrc(p.image_path)"
            :alt="p.name"
            class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-700"
          />
          <div v-else class="w-full h-full flex items-center justify-center bg-gradient-to-br from-muted to-background">
            <span class="text-6xl group-hover:scale-125 transition-transform duration-500">{{ p.category?.icon ?? '📦' }}</span>
          </div>
          
          <!-- Category Badge Overlay -->
          <div v-if="p.category" class="absolute top-4 left-4">
             <Badge variant="secondary" class="bg-background/80 backdrop-blur-md text-[8px] font-black uppercase tracking-widest h-6 border-none shadow-lg shadow-black/10">
                {{ p.category.name_vi }}
             </Badge>
          </div>
        </div>

        <CardContent class="p-6 flex-1 flex flex-col space-y-4">
          <div class="space-y-2">
             <div v-if="p.enterprise?.province" class="flex items-center gap-1.5 text-[9px] font-black text-primary uppercase tracking-widest  opacity-80">
                <MapPin class="w-3 h-3" /> {{ p.enterprise.province }}
             </div>
             <h3 class="text-base font-black text-foreground leading-snug line-clamp-2 uppercase  tracking-tight group-hover:text-primary transition-colors">
               {{ p.name }}
             </h3>
             <div v-if="p.enterprise?.name" class="text-[11px] font-bold text-muted-foreground  truncate">
               {{ p.enterprise.name }}
             </div>
          </div>

          <div class="mt-auto space-y-3 pt-2">
             <Separator border-dashed class="opacity-50" />
             <div v-if="p.gtin" class="flex items-center justify-between">
                <span class="text-[9px] font-black uppercase text-muted-foreground tracking-tighter ">GS1 GTIN:</span>
                <span class="text-[10px] font-mono font-black text-foreground">{{ p.gtin }}</span>
             </div>
             <div v-else class="text-right">
                <span class="text-[8px] font-black uppercase text-muted-foreground/40 ">Chưa đăng ký GTIN</span>
             </div>
          </div>
        </CardContent>
        
        <CardFooter class="bg-muted/5 p-4 border-t border-border/50 group-hover:bg-primary/5 transition-colors">
           <Button variant="ghost" class="w-full justify-between h-8 font-black uppercase tracking-[0.2em] text-[9px] group-hover:text-primary transition-all">
              Truy xuất chi tiết
              <ArrowRight class="w-3 h-3 group-hover:translate-x-1 transition-transform" />
           </Button>
        </CardFooter>
      </Card>
    </div>

    <!-- Pagination -->
    <div v-if="paginator?.last_page > 1" class="flex items-center justify-between pt-12 border-t border-dashed border-border/50">
      <div class="text-xs font-bold text-muted-foreground uppercase tracking-widest ">
        Trang <span class="text-foreground font-black">{{ paginator.current_page }}</span> / {{ paginator.last_page }}
      </div>
      <div class="flex gap-3">
        <Button 
          variant="outline" 
          size="sm" 
          as-child 
          :disabled="!paginator.prev_page_url"
          class="font-black uppercase text-[10px] tracking-widest h-10 px-6 rounded-xl border-2"
        >
          <a :href="paginator.prev_page_url || '#'">Trước</a>
        </Button>
        <Button 
          variant="outline" 
          size="sm" 
          as-child 
          :disabled="!paginator.next_page_url"
          class="font-black uppercase text-[10px] tracking-widest h-10 px-6 rounded-xl border-2"
        >
          <a :href="paginator.next_page_url || '#'">Sau</a>
        </Button>
      </div>
    </div>

  </div>
</template>
