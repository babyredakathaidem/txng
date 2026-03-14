<script setup>
import { Head, router } from '@inertiajs/vue3'
import { Card, CardHeader, CardTitle, CardDescription, CardContent, CardFooter } from '@/Components/ui/card/index.js'
import { Button } from '@/Components/ui/button/index.js'
import { Alert, AlertDescription } from '@/Components/ui/alert/index.js'
import { Lock, ShieldAlert, LogOut, Info } from 'lucide-vue-next'

defineProps({
  blocked_reason: { type: String, default: null },
  enterprise:     { type: Object, default: () => ({}) },
})

const logout = () => {
  router.post(route('logout'))
}
</script>

<template>
  <Head title="Tài khoản bị khóa — AGU" />

  <div class="max-w-lg mx-auto py-12">
    <Card class="border-border/50 shadow-2xl bg-card/50 backdrop-blur-xl relative overflow-hidden rounded-[2.5rem]">
      <div class="absolute inset-0 bg-gradient-to-br from-destructive/5 to-transparent pointer-events-none"></div>
      
      <CardHeader class="text-center pb-2 pt-10 px-8">
        <div class="w-20 h-20 rounded-full bg-muted flex items-center justify-center mx-auto mb-6 border-2 border-border shadow-inner animate-in zoom-in duration-500">
          <Lock class="w-10 h-10 text-muted-foreground" />
        </div>
        <CardTitle class="text-3xl font-black tracking-tighter text-foreground uppercase ">Tài khoản bị khóa</CardTitle>
        <CardDescription class="text-muted-foreground font-medium mt-2 leading-relaxed">
          Truy cập của doanh nghiệp <span class="font-bold text-foreground">{{ enterprise?.name }}</span> đã bị tạm ngưng.
        </CardDescription>
      </CardHeader>

      <CardContent class="space-y-6 pt-6 px-8 text-center">
        <div v-if="blocked_reason" class="p-5 rounded-3xl bg-muted/30 border border-dashed border-border text-left space-y-3 shadow-inner">
           <div class="flex items-center gap-2 text-muted-foreground">
              <ShieldAlert class="w-4 h-4" />
              <span class="text-[10px] font-black uppercase tracking-widest opacity-60">Lý do từ quản trị viên:</span>
           </div>
           <p class="text-sm font-medium  leading-relaxed">{{ blocked_reason }}</p>
        </div>

        <Alert class="bg-muted/20 border-border/50 rounded-2xl">
          <Info class="h-4 w-4" />
          <AlertDescription class="text-xs font-medium text-muted-foreground  px-1">
            Vui lòng liên hệ bộ phận quản lý hệ thống <span class="text-primary font-bold underline decoration-primary/20 underline-offset-4">AGU Traceability</span> để biết thêm chi tiết và yêu cầu mở khóa.
          </AlertDescription>
        </Alert>
      </CardContent>

      <CardFooter class="border-t bg-muted/5 p-8">
        <Button variant="ghost" @click="logout" class="w-full h-12 font-black uppercase tracking-widest text-[10px] text-muted-foreground hover:text-destructive transition-all rounded-xl">
           <LogOut class="w-4 h-4 mr-2" /> Đăng xuất tài khoản
        </Button>
      </CardFooter>
    </Card>
  </div>
</template>
