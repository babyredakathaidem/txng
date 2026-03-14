<script setup>
import { Head, router, Link } from '@inertiajs/vue3'
import GuestLayout from '@/Layouts/GuestLayout.vue'
import { ref } from 'vue'
import { Card, CardHeader, CardTitle, CardDescription, CardContent, CardFooter } from '@/Components/ui/card/index.js'
import { Button } from '@/Components/ui/button/index.js'
import { Input } from '@/Components/ui/input/index.js'
import { Badge } from '@/Components/ui/badge/index.js'
import { Separator } from '@/Components/ui/separator/index.js'
import { Label } from '@/Components/ui/label/index.js'
import { Search, AlertTriangle, CheckCircle, ArrowRight } from 'lucide-vue-next'

defineOptions({ layout: GuestLayout })

const props = defineProps({
  query:   { type: String, default: '' },
  results: { type: Array,  default: () => [] },
  error:   { type: String, default: null },
})

const q = ref(props.query)
const search = () => {
  if (q.value.trim()) {
    router.get('/verify', { query: q.value.trim() }, { preserveState: false })
  }
}
</script>

<template>
  <Head :title="query ? `Tra cứu: ${query} — AGU Traceability` : 'Tra cứu nguồn gốc — AGU Traceability'" />

  <div class="max-w-4xl mx-auto px-6 py-12">
    <!-- Header -->
    <div class="mb-8 space-y-4">
      <nav class="flex items-center gap-2 text-xs font-medium text-muted-foreground uppercase tracking-widest">
        <Link href="/" class="hover:text-primary transition-colors">Trang chủ</Link>
        <span>/</span>
        <span class="text-foreground">Tra cứu</span>
      </nav>

      <h1 class="text-4xl font-black tracking-tighter text-foreground uppercase ">Tra cứu nguồn gốc</h1>
      <p class="text-muted-foreground font-medium">Nhập mã lô hàng (Batch Code) hoặc mã GTIN để kiểm tra thông tin.</p>

      <div class="flex gap-2 max-w-xl">
        <div class="relative flex-1">
          <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted-foreground" />
          <Input 
            v-model="q" 
            @keyup.enter="search" 
            placeholder="Nhập mã lô (VD: LG01001) hoặc mã GTIN..." 
            class="h-12 pl-10 bg-muted/20 border-border/50 focus:border-primary/50"
          />
        </div>
        <Button @click="search" class="h-12 px-8 font-bold uppercase tracking-widest">Tìm kiếm</Button>
      </div>
    </div>

    <!-- Empty/No Query -->
    <div v-if="!query" class="flex flex-col items-center justify-center py-24 border-2 border-dashed rounded-3xl bg-muted/10">
       <div class="p-4 rounded-full bg-muted mb-4">
          <Search class="w-12 h-12 text-muted-foreground/30" />
       </div>
       <p class="text-muted-foreground text-sm font-medium ">Sẵn sàng tra cứu. Vui lòng nhập thông tin vào ô tìm kiếm phía trên.</p>
    </div>

    <!-- Error/Not Found -->
    <Card v-else-if="error" class="border-destructive/20 bg-destructive/5">
      <CardContent class="flex flex-col items-center py-12 text-center">
        <AlertTriangle class="w-12 h-12 text-destructive mb-4" />
        <CardTitle class="text-destructive font-black uppercase text-lg">{{ error }}</CardTitle>
        <CardDescription class="mt-2 font-medium">Chúng tôi không tìm thấy thông tin phù hợp với mã bạn vừa cung cấp.</CardDescription>
        <p class="text-xs text-muted-foreground mt-6 ">Gợi ý: Kiểm tra lại mã lô trên bao bì sản phẩm hoặc liên hệ nhà sản xuất để được hỗ trợ.</p>
      </CardContent>
    </Card>

    <!-- Results -->
    <div v-else class="space-y-6">
      <div class="flex items-center gap-2 px-1">
         <Badge variant="outline" class="font-mono text-[10px]">{{ results.length }} kết quả</Badge>
         <p class="text-xs text-muted-foreground">cho từ khóa "<span class="text-primary font-bold">{{ query }}</span>"</p>
      </div>

      <Card v-for="b in results" :key="b.id" class="group hover:border-primary/30 transition-all duration-300">
        <CardHeader class="pb-4">
          <div class="flex items-start gap-4">
            <div v-if="b.product?.category" class="w-14 h-14 rounded-2xl bg-primary/10 flex items-center justify-center text-3xl group-hover:scale-110 transition-transform">
               {{ b.product.category.icon }}
            </div>
            <div class="flex-1 space-y-1">
               <div class="flex items-center gap-3 flex-wrap">
                  <h3 class="text-xl font-black text-foreground uppercase tracking-tight group-hover:text-primary transition-colors">
                    {{ b.product?.name ?? 'Sản phẩm không định danh' }}
                  </h3>
                  <Badge :variant="b.status === 'recalled' ? 'destructive' : 'default'" class="font-bold text-[10px] uppercase">
                     <CheckCircle v-if="b.status !== 'recalled'" class="w-3 h-3 mr-1" />
                     {{ b.status === 'recalled' ? 'Đã thu hồi' : 'Hợp lệ' }}
                  </Badge>
               </div>
               <p v-if="b.product?.category" class="text-sm font-bold text-muted-foreground uppercase tracking-widest text-[10px]">
                  {{ b.product.category.name_vi }}
               </p>
            </div>
          </div>
        </CardHeader>

        <CardContent>
          <div class="grid grid-cols-2 md:grid-cols-4 gap-6 p-4 rounded-2xl bg-muted/30 border border-border/50">
            <div class="space-y-1">
              <Label class="text-[10px] font-black uppercase text-muted-foreground tracking-widest">Mã lô hàng</Label>
              <p class="font-mono text-sm font-bold text-primary">{{ b.code }}</p>
            </div>
            <div v-if="b.product?.gtin" class="space-y-1">
              <Label class="text-[10px] font-black uppercase text-muted-foreground tracking-widest">Mã GTIN</Label>
              <p class="font-mono text-sm font-bold">{{ b.product.gtin }}</p>
            </div>
            <div v-if="b.production_date" class="space-y-1">
              <Label class="text-[10px] font-black uppercase text-muted-foreground tracking-widest">Ngày sản xuất</Label>
              <p class="text-sm font-bold">{{ b.production_date }}</p>
            </div>
            <div v-if="b.expiry_date" class="space-y-1">
              <Label class="text-[10px] font-black uppercase text-muted-foreground tracking-widest">Hạn sử dụng</Label>
              <p class="text-sm font-bold">{{ b.expiry_date }}</p>
            </div>
            <div v-if="b.quantity" class="space-y-1">
              <Label class="text-[10px] font-black uppercase text-muted-foreground tracking-widest">Số lượng lô</Label>
              <p class="text-sm font-bold">{{ b.quantity }} <span class="text-[10px] text-muted-foreground uppercase">{{ b.unit }}</span></p>
            </div>
            <div v-if="b.enterprise" class="col-span-2 space-y-1">
              <Label class="text-[10px] font-black uppercase text-muted-foreground tracking-widest">Nhà sản xuất</Label>
              <p class="text-sm font-bold truncate">{{ b.enterprise.name }}</p>
              <p v-if="b.enterprise.province" class="text-[10px] font-medium text-muted-foreground ">{{ b.enterprise.province }}</p>
            </div>
            <div class="space-y-1">
              <Label class="text-[10px] font-black uppercase text-muted-foreground tracking-widest">Minh bạch</Label>
              <p class="text-sm font-black text-primary">{{ b.event_count }} Sự kiện</p>
            </div>
          </div>

          <!-- Alert for recall -->
          <div v-if="b.status === 'recalled'" class="mt-6 flex gap-3 p-4 rounded-xl bg-destructive/10 border border-destructive/20 text-destructive">
             <AlertTriangle class="w-5 h-5 shrink-0" />
             <div class="text-xs font-bold leading-relaxed ">
                CẢNH BÁO: Lô hàng này đã được nhà sản xuất phát lệnh thu hồi. Vui lòng KHÔNG SỬ DỤNG và liên hệ trực tiếp với điểm bán lẻ hoặc nhà sản xuất để được giải quyết.
             </div>
          </div>
        </CardContent>

        <CardFooter class="border-t bg-muted/5 py-4 flex items-center justify-between">
           <p class="text-[10px] text-muted-foreground font-medium  uppercase tracking-tighter">
              Để xem chi tiết chuỗi cung ứng, vui lòng quét mã QR trên bao bì.
           </p>
           <Button variant="ghost" size="sm" class="text-xs font-black uppercase tracking-tighter group-hover:text-primary">
              Tìm hiểu thêm <ArrowRight class="w-3 h-3 ml-1.5 group-hover:translate-x-1 transition-transform" />
           </Button>
        </CardFooter>
      </Card>
    </div>
  </div>
</template>
