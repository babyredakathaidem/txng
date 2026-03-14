<script setup>
import { Head, Link } from '@inertiajs/vue3'
import { Card, CardHeader, CardTitle, CardDescription, CardContent, CardFooter } from '@/Components/ui/card/index.js'
import { Button } from '@/Components/ui/button/index.js'
import { CheckCircle, ArrowRight } from 'lucide-vue-next'
</script>

<template>
  <Head title="Xác minh thành công — AGU" />

  <Card class="border-none bg-transparent shadow-none p-0 text-center rounded-[2.5rem]">
    <CardContent class="px-0 py-8 space-y-6">
      <!-- Success Icon -->
      <div class="relative w-24 h-24 mx-auto mb-4">
         <div class="absolute inset-0 rounded-full bg-emerald-500/20 blur-2xl animate-pulse"></div>
         <div class="relative w-full h-full rounded-full bg-emerald-500/10 border-2 border-emerald-500/40 flex items-center justify-center shadow-xl shadow-emerald-500/10 animate-in zoom-in duration-500">
            <CheckCircle class="w-12 h-12 text-emerald-600" />
         </div>
      </div>

      <div class="space-y-2">
        <CardTitle class="text-3xl font-black tracking-tighter text-foreground uppercase ">Email đã xác minh!</CardTitle>
        <CardDescription class="text-muted-foreground font-medium text-base px-4  leading-relaxed">
          Tuyệt vời! Địa chỉ email của bạn đã được hệ thống xác thực thành công.
        </CardDescription>
      </div>

      <div class="pt-4">
        <Button as-child class="h-12 px-10 font-black uppercase tracking-widest text-xs shadow-2xl shadow-primary/20 group rounded-xl active:scale-95 transition-all">
           <Link href="/login">
              Đăng nhập ngay
              <ArrowRight class="w-4 h-4 ml-2 group-hover:translate-x-1 transition-transform" />
           </Link>
        </Button>
      </div>
    </CardContent>

    <CardFooter class="px-0 pt-6 border-t border-border/50 flex justify-center">
       <p class="text-[10px] text-muted-foreground/40 font-black uppercase tracking-[0.3em]">AGU Traceability System</p>
    </CardFooter>
  </Card>
</template>
