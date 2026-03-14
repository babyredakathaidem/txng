<script setup>
import { Head, router } from '@inertiajs/vue3'
import { onMounted, onBeforeUnmount, ref } from 'vue'
import { Card, CardHeader, CardTitle, CardDescription, CardContent, CardFooter } from '@/Components/ui/card/index.js'
import { Button } from '@/Components/ui/button/index.js'
import { Badge } from '@/Components/ui/badge/index.js'
import { RefreshCw, Clock, LogOut } from 'lucide-vue-next'

const props = defineProps({
  enterprise: { type: Object, default: () => ({}) },
})

const checking = ref(false)
const errorMsg  = ref(null)
let   timer     = null

async function checkStatus() {
  try {
    checking.value = true
    errorMsg.value = null
    const res  = await fetch(route('onboarding.enterprise.status'), {
      headers: { Accept: 'application/json' }, credentials: 'same-origin',
    })
    if (!res.ok) throw new Error()
    const data = await res.json()

    if (!data.has_enterprise) { window.location.href = route('onboarding.enterprise.create'); return }
    if (data.status === 'approved') { window.location.href = route('dashboard'); return }
    if (data.status === 'rejected') { window.location.href = route('onboarding.enterprise.rejected'); return }
  } catch {
    errorMsg.value = 'Không thể kiểm tra trạng thái. Vui lòng thử lại.'
  } finally {
    checking.value = false
  }
}

onMounted(async () => {
  await checkStatus()
  timer = setInterval(checkStatus, 3000)
})
onBeforeUnmount(() => clearInterval(timer))

const logout = () => {
  router.post(route('logout'))
}
</script>

<template>
  <Head title="Đang chờ duyệt doanh nghiệp — AGU" />

  <Card class="border-none bg-transparent shadow-none p-0 text-center max-w-lg mx-auto">
    <CardContent class="px-0 py-8 space-y-6">
      <!-- Animated Status Icon -->
      <div class="relative w-24 h-24 mx-auto mb-4">
         <div class="absolute inset-0 rounded-full border-4 border-primary/10 animate-ping opacity-20"></div>
         <div class="absolute inset-0 rounded-full border-4 border-primary/20 animate-pulse"></div>
         <div class="relative w-full h-full rounded-full bg-primary/5 flex items-center justify-center">
            <Clock class="w-12 h-12 text-primary" />
         </div>
      </div>

      <div class="space-y-2">
        <CardTitle class="text-3xl font-black tracking-tighter text-foreground uppercase ">Đang chờ phê duyệt</CardTitle>
        <CardDescription class="text-muted-foreground font-medium text-base">
          Hồ sơ doanh nghiệp <span class="text-primary font-bold">{{ enterprise?.name }}</span> đang được xem xét.
        </CardDescription>
      </div>

      <div class="space-y-4">
         <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full border border-primary/20 bg-primary/5 text-primary text-[10px] font-black uppercase tracking-widest ">
            <div class="w-2 h-2 rounded-full bg-primary animate-pulse"></div>
            Hệ thống đang tự động kiểm tra trạng thái...
         </div>

         <p class="text-sm text-muted-foreground leading-relaxed  max-w-sm mx-auto">
            Quản trị viên hệ thống sẽ kiểm tra các giấy tờ đính kèm của bạn. Kết quả sẽ được gửi qua email trong vòng 24h làm việc.
         </p>
      </div>

      <div v-if="errorMsg" class="p-3 rounded-xl bg-destructive/10 border border-destructive/20 text-destructive text-xs font-bold animate-in zoom-in">
         {{ errorMsg }}
      </div>
    </CardContent>

    <CardFooter class="px-0 pt-6 border-t border-border/50 flex flex-col sm:flex-row gap-3 justify-center">
      <Button variant="outline" @click="checkStatus" :disabled="checking" class="h-11 px-8 rounded-xl font-bold uppercase tracking-widest text-[10px] border-2">
         <RefreshCw class="w-4 h-4 mr-2" :class="{ 'animate-spin': checking }" />
         {{ checking ? 'Đang cập nhật...' : 'Kiểm tra ngay' }}
      </Button>
      
      <Button variant="ghost" @click="logout" class="h-11 px-8 rounded-xl font-bold uppercase tracking-widest text-[10px] text-muted-foreground hover:text-destructive">
         <LogOut class="w-4 h-4 mr-2" />
         Đăng xuất tài khoản
      </Button>
    </CardFooter>
  </Card>
</template>
