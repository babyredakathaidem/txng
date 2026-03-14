<script setup>
import { Head, Link, router } from '@inertiajs/vue3'
import { Card, CardHeader, CardTitle, CardDescription, CardContent, CardFooter } from '@/Components/ui/card/index.js'
import { Button } from '@/Components/ui/button/index.js'
import { Alert, AlertDescription, AlertTitle } from '@/Components/ui/alert/index.js'
import { XCircle, ArrowLeft, AlertTriangle } from 'lucide-vue-next'

const props = defineProps({
  rejection_reason: String,
})

const logout = () => {
  router.post(route('logout'))
}
</script>

<template>
  <Head title="Hồ sơ bị từ chối — AGU" />

  <div class="max-w-lg mx-auto py-12">
    <Card class="border-destructive/20 shadow-2xl shadow-destructive/5 relative overflow-hidden rounded-[2.5rem]">
      <div class="absolute right-0 top-0 w-32 h-32 bg-destructive/5 blur-3xl -z-10 rounded-full"></div>
      
      <CardHeader class="text-center pb-2 px-8 pt-10">
        <div class="w-20 h-20 rounded-full bg-destructive/10 flex items-center justify-center mx-auto mb-6 border-2 border-destructive/20 shadow-lg shadow-destructive/5 animate-in zoom-in duration-500">
          <XCircle class="w-10 h-10 text-destructive" />
        </div>
        <CardTitle class="text-3xl font-black tracking-tighter text-foreground uppercase ">Hồ sơ bị từ chối</CardTitle>
        <CardDescription class="text-muted-foreground font-medium mt-2 leading-relaxed">
          Rất tiếc, yêu cầu đăng ký doanh nghiệp của bạn không được phê duyệt.
        </CardDescription>
      </CardHeader>

      <CardContent class="space-y-6 pt-6 px-8">
        <Alert variant="destructive" class="bg-destructive/5 border-destructive/20 rounded-2xl">
          <AlertTriangle class="h-4 w-4" />
          <AlertTitle class="font-black uppercase text-xs tracking-widest mb-1">Lý do từ chối:</AlertTitle>
          <AlertDescription class="font-medium  text-sm">
            {{ rejection_reason || 'Thông tin cung cấp chưa chính xác hoặc thiếu các giấy tờ chứng minh pháp nhân cần thiết.' }}
          </AlertDescription>
        </Alert>

        <p class="text-xs text-muted-foreground leading-relaxed text-center px-4 ">
          Bạn có thể thực hiện đăng ký lại bằng cách cung cấp thông tin chính xác hơn hoặc liên hệ với bộ phận hỗ trợ kỹ thuật của <span class="font-bold text-foreground underline decoration-primary/30 decoration-2 underline-offset-4">AGU Traceability</span> để được hướng dẫn chi tiết.
        </p>
      </CardContent>

      <CardFooter class="flex flex-col gap-3 border-t bg-muted/5 p-8">
        <Button class="w-full h-12 font-black uppercase tracking-[0.2em] text-[10px] rounded-xl shadow-xl shadow-primary/20 active:scale-95 transition-all" as-child>
           <Link :href="route('onboarding.enterprise.create')">Cập nhật hồ sơ & Gửi lại</Link>
        </Button>
        <Button variant="ghost" @click="logout" class="w-full h-12 font-black uppercase tracking-widest text-[10px] text-muted-foreground hover:text-destructive rounded-xl transition-colors">
           <ArrowLeft class="w-4 h-4 mr-2" /> Đăng xuất tài khoản
        </Button>
      </CardFooter>
    </Card>
  </div>
</template>
