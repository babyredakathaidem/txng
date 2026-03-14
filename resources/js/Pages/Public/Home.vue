<script setup>
import { Head, Link } from '@inertiajs/vue3'
import GuestLayout from '@/Layouts/GuestLayout.vue'
import { ref } from 'vue'
import { Card, CardHeader, CardTitle, CardDescription, CardContent, CardFooter } from '@/Components/ui/card/index.js'
import { Button } from '@/Components/ui/button/index.js'
import { Input } from '@/Components/ui/input/index.js'
import { Badge } from '@/Components/ui/badge/index.js'
import { Separator } from '@/Components/ui/separator/index.js'
import { 
  Search, 
  ArrowRight, 
  Building2, 
  Archive, 
  ClipboardCheck, 
  QrCode,
  ShieldCheck,
  Globe
} from 'lucide-vue-next'

defineOptions({ layout: GuestLayout })

const props = defineProps({
  categories: { type: Array, default: () => [] },
})

const q = ref('')
const go = () => {
  if (q.value.trim()) window.location.href = `/verify?query=${encodeURIComponent(q.value.trim())}`
}
const onEnter = (e) => { if (e.key === 'Enter') go() }
</script>

<template>
  <Head title="Nền tảng Truy xuất nguồn gốc chuẩn quốc gia — AGU" />

  <!-- HERO SECTION -->
  <section class="relative pt-20 pb-32 overflow-hidden">
    <!-- Animated background patterns -->
    <div class="absolute inset-0 -z-10 opacity-20 pointer-events-none">
       <div class="absolute top-0 left-1/4 w-[500px] h-[500px] bg-primary/20 blur-[120px] rounded-full animate-pulse"></div>
       <div class="absolute bottom-0 right-1/4 w-[400px] h-[400px] bg-emerald-500/10 blur-[100px] rounded-full"></div>
    </div>

    <div class="max-w-7xl mx-auto px-6 grid grid-cols-1 lg:grid-cols-2 gap-16 items-center">
      <div class="space-y-8" data-aos="fade-right">
        <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full border border-primary/20 bg-primary/5 text-primary text-xs font-black uppercase tracking-widest shadow-inner">
           <ShieldCheck class="w-4 h-4" /> Trusted Supply Chain Platform
        </div>
        
        <h1 class="text-5xl lg:text-7xl font-black leading-none text-foreground tracking-tighter uppercase ">
          Minh bạch <span class="text-primary underline decoration-primary/20 decoration-8 underline-offset-8">nguồn gốc</span> sản phẩm Việt.
        </h1>
        
        <p class="text-lg font-medium text-muted-foreground leading-relaxed max-w-lg">
          Nền tảng xác thực dữ liệu chuỗi cung ứng theo tiêu chuẩn <span class="text-foreground font-bold ">TCVN 12850:2019</span>, tích hợp công nghệ IPFS bảo mật dữ liệu vĩnh viễn.
        </p>

        <!-- Search bar -->
        <div class="flex flex-col sm:flex-row gap-3 max-w-xl">
          <div class="relative flex-1">
             <Search class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-muted-foreground opacity-50" />
             <Input 
               v-model="q"
               @keyup="onEnter"
               placeholder="Nhập mã lô hoặc mã GTIN sản phẩm..." 
               class="h-14 pl-12 rounded-2xl bg-card/50 backdrop-blur-sm border-border/50 text-base shadow-xl"
             />
          </div>
          <Button 
            @click="go"
            class="h-14 px-10 rounded-2xl font-black uppercase tracking-widest text-xs shadow-2xl shadow-primary/20 group"
          >
            Tra cứu ngay
            <ArrowRight class="w-4 h-4 ml-2 group-hover:translate-x-1 transition-transform" />
          </Button>
        </div>
        
        <div class="flex items-center gap-4 text-[10px] font-black uppercase tracking-widest text-muted-foreground  opacity-60">
           <span>Gợi ý:</span>
           <button @click="q='LG01001'; go()" class="hover:text-primary transition-colors border-b border-muted-foreground/30">LG01001</button>
           <button @click="q='8934563000012'; go()" class="hover:text-primary transition-colors border-b border-muted-foreground/30">8934563000012</button>
        </div>
      </div>

      <div class="hidden lg:block relative" data-aos="zoom-in">
         <div class="relative w-full aspect-square max-w-[500px] mx-auto">
            <div class="absolute inset-0 rounded-[3rem] border-2 border-primary/20 rotate-6 animate-pulse"></div>
            <div class="absolute inset-0 rounded-[3rem] border-2 border-emerald-500/10 -rotate-3"></div>
            <Card class="absolute inset-0 rounded-[3rem] bg-card/40 backdrop-blur-xl border-border/50 shadow-2xl flex items-center justify-center overflow-hidden">
               <div class="text-center space-y-2">
                  <div class="text-8xl font-black text-primary tracking-tighter opacity-80">AGU</div>
                  <div class="text-sm font-black uppercase tracking-[0.5em] text-muted-foreground opacity-40">Traceability</div>
               </div>
               
               <!-- Floating badges -->
               <div class="absolute top-12 left-12 animate-bounce duration-[3s]">
                  <Badge class="h-10 px-4 bg-emerald-500 text-white border-none shadow-xl shadow-emerald-500/20 font-black">✓ VERIFIED</Badge>
               </div>
               <div class="absolute bottom-16 right-8 animate-bounce duration-[2.5s]">
                  <Badge variant="outline" class="h-10 px-4 border-primary/40 text-primary shadow-xl shadow-primary/10 bg-background font-black uppercase ">GS1 Compliant</Badge>
               </div>
            </Card>
         </div>
      </div>
    </div>
  </section>

  <!-- FEATURES / STEPS -->
  <section class="py-24 bg-muted/20 border-y border-border/50 relative overflow-hidden">
    <div class="max-w-7xl mx-auto px-6">
      <div class="text-center space-y-4 mb-16" data-aos="fade-up">
         <Badge variant="secondary" class="uppercase font-black text-[10px] tracking-[0.3em] px-4">Quy chuẩn vận hành</Badge>
         <h2 class="text-4xl font-black text-foreground uppercase tracking-tight ">Minh bạch theo từng điểm chạm</h2>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
        <div v-for="(step, i) in [
          { num: '01', title: 'Đăng ký tổ chức', desc: 'Khai báo pháp nhân, MST và hồ sơ năng lực VietGAP/ISO.', icon: Building2, color: 'text-sky-500' },
          { num: '02', title: 'Định danh sản phẩm', desc: 'Thiết lập danh mục GS1, tự động cấp mã lô truy vết.', icon: Archive, color: 'text-amber-500' },
          { num: '03', title: 'Ghi nhận 5W1H', desc: 'Lưu trữ sự kiện chuỗi cung ứng theo chuẩn TCVN 12850.', icon: ClipboardCheck, color: 'text-primary' },
          { num: '04', title: 'Phát hành QR', desc: 'In mã QR minh bạch, cho phép khách hàng quét tra cứu tức thì.', icon: QrCode, color: 'text-emerald-500' },
        ]" :key="i"
          class="group p-8 rounded-[2rem] bg-card border border-border shadow-lg hover:border-primary/30 transition-all duration-500 hover:-translate-y-2"
          data-aos="fade-up" :data-aos-delay="i * 150">
          
          <div class="flex items-center justify-between mb-6">
             <div class="text-4xl font-black text-muted-foreground/10 group-hover:text-primary/20 transition-colors">{{ step.num }}</div>
             <component :is="step.icon" class="w-8 h-8 opacity-20 group-hover:opacity-100 transition-opacity" :class="step.color" />
          </div>
          
          <h3 class="text-lg font-black text-foreground uppercase  tracking-tight group-hover:text-primary transition-colors">{{ step.title }}</h3>
          <p class="text-sm font-medium text-muted-foreground mt-3 leading-relaxed opacity-70 group-hover:opacity-100">{{ step.desc }}</p>
        </div>
      </div>
    </div>
  </section>

  <!-- CATEGORIES PREVIEW -->
  <section class="py-24">
    <div class="max-w-7xl mx-auto px-6">
      <div class="flex flex-col md:flex-row md:items-end justify-between gap-8 mb-12" data-aos="fade-up">
        <div class="space-y-2">
          <Badge variant="outline" class="text-primary border-primary/20 uppercase font-black text-[9px] tracking-widest px-3 ">Supported Sectors</Badge>
          <h2 class="text-4xl font-black text-foreground uppercase tracking-tight ">Danh mục được hỗ trợ</h2>
          <p class="text-muted-foreground font-medium ">Hệ thống chuẩn hóa theo các nhóm ngành nông nghiệp trọng điểm.</p>
        </div>
        <Button variant="ghost" as-child class="font-black uppercase tracking-widest text-[10px] hover:text-primary">
           <Link href="/san-pham">Xem tất cả lĩnh vực <ArrowRight class="w-3.5 h-3.5 ml-2" /></Link>
        </Button>
      </div>

      <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4">
        <Link
          v-for="(cat, i) in categories"
          :key="cat.code"
          href="/san-pham"
          class="group p-6 rounded-[2.5rem] bg-muted/30 border border-border/50 hover:bg-card hover:border-primary/30 hover:shadow-2xl transition-all duration-500 text-center relative overflow-hidden"
          data-aos="zoom-in" :data-aos-delay="i * 100"
        >
          <div class="text-5xl mb-4 group-hover:scale-125 transition-transform duration-500">{{ cat.icon }}</div>
          <h4 class="text-sm font-black text-foreground uppercase tracking-tight leading-snug group-hover:text-primary transition-colors">{{ cat.name_vi }}</h4>
          <div class="text-[9px] font-bold text-muted-foreground mt-2 uppercase tracking-tighter opacity-40 group-hover:opacity-80 transition-opacity">{{ cat.tcvn_ref }}</div>
          
          <!-- Decorative element -->
          <div class="absolute -bottom-2 -right-2 w-8 h-8 rounded-full bg-primary/5 group-hover:scale-[4] transition-transform duration-700 pointer-events-none"></div>
        </Link>
      </div>
    </div>
  </section>

  <!-- CTA / TRUST SECTION -->
  <section class="pb-32 px-6">
     <Card class="max-w-5xl mx-auto rounded-[3rem] bg-primary text-primary-foreground border-none overflow-hidden relative shadow-[0_40px_80px_-20px_rgba(var(--primary),0.4)]">
        <div class="absolute top-0 right-0 w-96 h-96 bg-white/10 blur-[100px] rounded-full -translate-y-1/2 translate-x-1/2"></div>
        <CardContent class="p-12 md:p-20 relative z-10 flex flex-col md:flex-row items-center justify-between gap-12">
           <div class="space-y-6 text-center md:text-left">
              <h2 class="text-4xl md:text-5xl font-black uppercase tracking-tighter leading-none ">
                 Sẵn sàng minh bạch<br>Chuỗi giá trị của bạn?
              </h2>
              <p class="text-primary-foreground/80 font-medium max-w-md mx-auto md:mx-0">
                 Đăng ký tài khoản doanh nghiệp để bắt đầu chuẩn hóa dữ liệu truy xuất và kết nối với mạng lưới cung ứng toàn cầu.
              </p>
           </div>
           <div class="flex flex-col gap-4 shrink-0 w-full sm:w-auto">
              <Button as-child variant="secondary" class="h-16 px-12 rounded-2xl font-black uppercase tracking-[0.2em] text-xs shadow-xl shadow-black/10">
                 <Link href="/register">Đăng ký doanh nghiệp</Link>
              </Button>
              <Button as-child variant="ghost" class="h-16 px-12 rounded-2xl font-black uppercase tracking-[0.2em] text-xs text-primary-foreground hover:bg-white/10 border border-white/20">
                 <Link href="/verify">Tra cứu dữ liệu</Link>
              </Button>
           </div>
        </CardContent>
     </Card>
  </section>
</template>
