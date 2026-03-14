<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3'
import { computed, onMounted, onBeforeUnmount, ref } from 'vue'
import { Card, CardHeader, CardTitle, CardDescription, CardContent, CardFooter } from '@/Components/ui/card/index.js'
import { Button } from '@/Components/ui/button/index.js'
import { Alert, AlertDescription } from '@/Components/ui/alert/index.js'
import { 
  CheckCircle, 
  Mail, 
  RefreshCw, 
  LogOut 
} from 'lucide-vue-next'

const props = defineProps({
  status: { type: String, default: null },
})

const form       = useForm({})
const justSent   = computed(() => props.status === 'verification-link-sent')
const verified   = ref(false)
let   pollTimer  = null

async function checkVerified() {
  try {
    const res  = await fetch('/auth/email-status', { headers: { Accept: 'application/json' }, credentials: 'same-origin' })
    const data = await res.json()
    if (data.verified) {
      verified.value = true
      clearInterval(pollTimer)
      setTimeout(() => { window.location.href = '/login' }, 2000)
    }
  } catch {}
}

onMounted(() => {
  pollTimer = setInterval(checkVerified, 3000)
})
onBeforeUnmount(() => clearInterval(pollTimer))

const submit = () => form.post(route('verification.send'))
</script>

<template>
  <Head title="Xác minh tài khoản — AGU" />

  <Card class="border-none bg-transparent shadow-none p-0">
    <!-- Trạng thái đã verify (polling phát hiện) -->
    <div v-if="verified" class="py-12 animate-in zoom-in duration-500">
      <CardContent class="flex flex-col items-center text-center space-y-4">
        <div class="w-20 h-20 rounded-full bg-emerald-500/10 border-2 border-emerald-500/40 flex items-center justify-center shadow-lg shadow-emerald-500/10">
          <CheckCircle class="w-10 h-10 text-emerald-600" />
        </div>
        <CardTitle class="text-2xl font-black  tracking-tighter uppercase">Xác minh thành công!</CardTitle>
        <CardDescription class="font-medium text-muted-foreground ">
          Email của bạn đã được xác thực hệ thống. Đang chuyển hướng về trang đăng nhập...
        </CardDescription>
        <div class="pt-4">
           <RefreshCw class="w-6 h-6 text-primary animate-spin" />
        </div>
      </CardContent>
    </div>

    <!-- Trạng thái chờ verify -->
    <div v-else>
      <CardHeader class="px-0 pt-0 pb-6">
        <CardTitle class="text-3xl font-black tracking-tighter text-foreground uppercase  flex items-center gap-2">
           <Mail class="h-8 w-8 text-primary" />
           Xác minh Email
        </CardTitle>
        <CardDescription class="text-muted-foreground font-medium mt-1">
          Chúng tôi đã gửi một liên kết kích hoạt đến hòm thư của bạn. Vui lòng kiểm tra và xác nhận để tiếp tục.
        </CardDescription>
      </CardHeader>
      
      <CardContent class="px-0 pb-6 space-y-6">
        <div v-if="justSent">
           <Alert class="bg-emerald-500/10 border-emerald-500/20 text-emerald-600">
              <CheckCircle class="h-4 w-4" />
              <AlertDescription class="font-bold text-xs uppercase tracking-tight">
                 Một liên kết xác minh mới đã được gửi thành công.
              </AlertDescription>
           </Alert>
        </div>

        <form @submit.prevent="submit" class="space-y-4">
          <Button 
            type="submit" 
            :disabled="form.processing" 
            class="w-full h-11 text-xs font-black uppercase tracking-widest shadow-lg shadow-primary/20"
          >
            <Mail class="w-4 h-4 mr-2" />
            {{ form.processing ? 'ĐANG GỬI...' : 'GỬI LẠI EMAIL XÁC MINH' }}
          </Button>

          <div class="flex items-center justify-center gap-2 py-2">
             <div class="w-2 h-2 rounded-full bg-primary animate-pulse"></div>
             <span class="text-[10px] font-black uppercase text-muted-foreground tracking-widest  opacity-60">
                Tự động phát hiện trạng thái xác minh...
             </span>
          </div>
        </form>
      </CardContent>

      <CardFooter class="px-0 border-t pt-6 flex flex-col gap-4">
        <div class="text-center w-full">
          <Link :href="route('logout')" method="post" as="button" class="text-xs font-bold uppercase tracking-widest text-muted-foreground hover:text-destructive transition-colors flex items-center justify-center gap-2 mx-auto">
             <LogOut class="w-4 h-4" /> Đăng xuất & Đổi tài khoản
          </Link>
        </div>
      </CardFooter>
    </div>
  </Card>
</template>
