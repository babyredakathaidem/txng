<script setup>
import { Head } from '@inertiajs/vue3'
import { usePage } from '@inertiajs/vue3'

const page = usePage()

const props = defineProps({
  title: { type: String, default: '' },
  subtitle: { type: String, default: '' },
  bannerUrl: { type: String, default: '/brand/auth-banner.jpg' },
})
</script>

<template>
  <Head :title="props.title || page.props?.title || 'AGU Traceability'" />

  <div class="min-h-screen bg-background text-foreground transition-colors duration-500">
    <div class="min-h-screen grid grid-cols-1 lg:grid-cols-3">
      <!-- LEFT -->
      <aside class="relative hidden lg:block border-r border-border overflow-hidden bg-muted/30">
        <div class="absolute inset-0 bg-cover bg-center opacity-40 mix-blend-overlay dark:opacity-20" :style="`background-image:url('${props.bannerUrl}')`" />
        <div class="absolute inset-0 bg-gradient-to-br from-primary/10 via-transparent to-primary/5" />
        <div class="absolute -top-24 -left-24 h-80 w-80 rounded-full bg-primary/10 blur-3xl" />
        <div class="absolute -bottom-32 -right-32 h-96 w-96 rounded-full bg-primary/5 blur-3xl" />
        
        <div class="relative h-full p-12 flex flex-col justify-between">
          <div class="flex items-center gap-4">
            <div class="h-12 w-12 rounded-2xl bg-primary text-primary-foreground flex items-center justify-center font-black shadow-xl shadow-primary/20">
              <span class="text-xl">A</span>
            </div>
            <div>
              <div class="font-black text-xl tracking-tighter uppercase ">AGU <span class="text-primary not- lowercase">Trace</span></div>
              <div class="text-[10px] font-bold uppercase tracking-[0.2em] text-muted-foreground">Truy xuất & Chống giả</div>
            </div>
          </div>

          <div class="max-w-xs">
            <div class="text-4xl font-black leading-tight tracking-tighter text-foreground mb-6">
              QUẢN LÝ <br/> 
              <span class="text-primary ">CHUỖI CUNG ỨNG</span> <br/>
              THÔNG MINH.
            </div>
            <div class="text-sm font-medium text-muted-foreground leading-relaxed">
              Giải pháp toàn diện cho doanh nghiệp trong việc minh bạch hóa nguồn gốc sản phẩm và bảo vệ thương hiệu.
            </div>
          </div>

          <div class="text-[10px] font-black uppercase tracking-[0.3em] text-muted-foreground/40">
            © {{ new Date().getFullYear() }} AGU Ecosystem
          </div>
        </div>
      </aside>

      <!-- RIGHT -->
      <main class="lg:col-span-2 bg-background flex items-center justify-center p-8 relative overflow-hidden">
        <div class="absolute top-0 right-0 w-full h-full pointer-events-none opacity-50">
           <div class="absolute top-[-10%] right-[-10%] w-[40%] h-[40%] bg-primary/5 blur-[120px] rounded-full"></div>
           <div class="absolute bottom-[-10%] left-[-10%] w-[30%] h-[30%] bg-primary/5 blur-[100px] rounded-full"></div>
        </div>

        <div class="w-full max-w-md relative z-10">
          <div class="mb-10 text-center lg:text-left">
            <h1 class="text-4xl font-black tracking-tighter text-foreground mb-2">
              {{ props.title || 'Chào mừng trở lại' }}
            </h1>
            <p v-if="props.subtitle" class="text-muted-foreground font-medium">
              {{ props.subtitle }}
            </p>
          </div>

          <div class="bg-card/50 backdrop-blur-xl border border-border rounded-3xl p-8 shadow-2xl shadow-black/5 dark:shadow-primary/5">
            <slot />
          </div>

          <div class="mt-8 text-center text-[10px] font-bold uppercase tracking-[0.2em] text-muted-foreground/40">
             Reliable Traceability System
          </div>
        </div>
      </main>
    </div>
  </div>
</template>
