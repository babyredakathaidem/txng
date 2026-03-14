<script setup>
import { Head, Link } from '@inertiajs/vue3'
import GuestLayout from '@/Layouts/GuestLayout.vue'
import { Card, CardHeader, CardTitle, CardDescription, CardContent, CardFooter } from '@/Components/ui/card/index.js'
import { Button } from '@/Components/ui/button/index.js'
import { Badge } from '@/Components/ui/badge/index.js'
import { Separator } from '@/Components/ui/separator/index.js'
import { 
  LayoutGrid, 
  ArrowRight, 
  CheckCircle,
  GraduationCap,
  Globe
} from 'lucide-vue-next'

defineOptions({ layout: GuestLayout })

defineProps({
  categories: { type: Array, default: () => [] },
})
</script>

<template>
  <Head title="Lĩnh vực hỗ trợ — AGU Traceability" />

  <div class="max-w-7xl mx-auto px-6 py-16 space-y-12">
    <!-- Header -->
    <div class="space-y-4 max-w-2xl" data-aos="fade-right">
      <nav class="flex items-center gap-2 text-[10px] font-black uppercase tracking-widest text-muted-foreground">
        <Link href="/" class="hover:text-primary transition-colors">Trang chủ</Link>
        <span>/</span>
        <span class="text-foreground ">Lĩnh vực hỗ trợ</span>
      </nav>
      <div class="flex items-center gap-4">
         <div class="p-3 rounded-2xl bg-primary/10 text-primary">
            <LayoutGrid class="w-8 h-8" />
         </div>
         <h1 class="text-4xl font-black  tracking-tighter text-foreground uppercase">Danh mục lĩnh vực</h1>
      </div>
      <p class="text-muted-foreground font-medium  leading-relaxed">
        Hệ thống AGU Traceability được thiết kế để chuẩn hóa dữ liệu cho các nhóm ngành nông nghiệp trọng điểm theo tiêu chuẩn <span class="text-foreground font-bold">TCVN 12850:2019</span> và định danh toàn cầu <span class="text-foreground font-bold">GS1</span>.
      </p>
    </div>

    <!-- Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
      <Card
        v-for="(cat, i) in categories"
        :key="cat.code"
        class="group rounded-[2rem] border-border/50 bg-card/50 backdrop-blur-sm hover:border-primary/30 transition-all duration-500 overflow-hidden shadow-lg hover:shadow-2xl"
        data-aos="fade-up" :data-aos-delay="i * 100"
      >
        <CardHeader class="pb-4">
          <div class="flex items-start justify-between">
            <div class="text-5xl group-hover:scale-110 transition-transform duration-500">{{ cat.icon }}</div>
            <Badge variant="outline" class="font-mono text-[9px] font-black uppercase border-primary/20 text-primary bg-primary/5 px-2">
               {{ cat.code }}
            </Badge>
          </div>
          <div class="pt-4 space-y-1">
             <CardTitle class="text-xl font-black text-foreground uppercase  tracking-tight group-hover:text-primary transition-colors">{{ cat.name_vi }}</CardTitle>
             <CardDescription class="font-bold text-[10px] text-muted-foreground flex items-center gap-1.5 uppercase tracking-widest">
                <GraduationCap class="w-3.5 h-3.5" /> {{ cat.tcvn_ref }}
             </CardDescription>
          </div>
        </CardHeader>

        <CardContent class="space-y-4">
          <Separator border-dashed />
          <div class="space-y-3">
             <div class="flex items-start gap-2">
                <CheckCircle class="w-4 h-4 text-emerald-500 shrink-0 mt-0.5" />
                <p class="text-xs text-muted-foreground leading-relaxed ">Tuân thủ đầy đủ ghi nhận 5W1H theo từng công đoạn sản xuất.</p>
             </div>
             <div class="flex items-start gap-2">
                <Globe class="w-4 h-4 text-primary shrink-0 mt-0.5" />
                <p class="text-xs text-muted-foreground leading-relaxed ">Hỗ trợ mã hóa Digital Link và lưu trữ IPFS phi tập trung.</p>
             </div>
          </div>
        </CardContent>
        
        <CardFooter class="bg-muted/5 p-4 border-t border-border/50">
           <Button variant="ghost" as-child class="w-full justify-between h-8 font-black uppercase tracking-[0.2em] text-[9px] group-hover:text-primary transition-all">
              <Link :href="`/san-pham?category_id=${cat.id}`">Khám phá sản phẩm</Link>
              <ArrowRight class="w-3 h-3 group-hover:translate-x-1 transition-transform" />
           </Button>
        </CardFooter>
      </Card>
    </div>

    <!-- CTA -->
    <Card class="rounded-[3rem] bg-gradient-to-br from-primary to-primary/80 text-primary-foreground border-none overflow-hidden relative shadow-2xl">
       <div class="absolute -right-20 -top-20 w-64 h-64 bg-white/10 blur-[100px] rounded-full"></div>
       <CardContent class="p-12 text-center space-y-6 relative z-10">
          <h2 class="text-3xl font-black uppercase  tracking-tighter">Doanh nghiệp của bạn chưa có định danh?</h2>
          <p class="text-primary-foreground/80 font-medium max-w-lg mx-auto">
             Tham gia ngay vào hệ sinh thái AGU Traceability để chuẩn hóa quy trình và khẳng định chất lượng sản phẩm trên thị trường.
          </p>
          <div class="pt-4">
             <Button as-child variant="secondary" class="h-14 px-12 rounded-2xl font-black uppercase tracking-[0.2em] text-xs shadow-xl shadow-black/10">
                <Link href="/onboarding/enterprise">Bắt đầu đăng ký miễn phí</Link>
             </Button>
          </div>
       </CardContent>
    </Card>
  </div>
</template>
