<script setup>
import { Head, useForm, Link } from '@inertiajs/vue3'
import { Card, CardHeader, CardTitle, CardDescription, CardContent, CardFooter } from '@/Components/ui/card/index.js'
import { Button } from '@/Components/ui/button/index.js'
import { Input } from '@/Components/ui/input/index.js'
import { Label } from '@/Components/ui/label/index.js'
import { Alert, AlertDescription } from '@/Components/ui/alert/index.js'
import { 
  CheckCircle, 
  ArrowLeft, 
  Mail 
} from 'lucide-vue-next'

defineProps({
  status: { type: String, default: null },
})

const form = useForm({ email: '' })
const submit = () => form.post(route('password.email'))
</script>

<template>
  <Head title="Quên mật khẩu — AGU" />

  <Card class="border-none bg-transparent shadow-none p-0">
    <CardHeader class="px-0 pt-0 pb-6 text-center sm:text-left">
      <CardTitle class="text-3xl font-black tracking-tighter text-foreground uppercase ">Quên mật khẩu?</CardTitle>
      <CardDescription class="text-muted-foreground font-medium mt-1">
        Đừng lo lắng, hãy cung cấp email của bạn để chúng tôi gửi liên kết khôi phục.
      </CardDescription>
    </CardHeader>
    
    <CardContent class="px-0 pb-6">
      <div v-if="status" class="mb-6">
         <Alert class="bg-emerald-500/10 border-emerald-500/20 text-emerald-600">
            <CheckCircle class="h-4 w-4" />
            <AlertDescription class="font-bold text-xs uppercase tracking-tight">
               {{ status }}
            </AlertDescription>
         </Alert>
      </div>

      <form class="space-y-5" @submit.prevent="submit">
        <div class="space-y-2">
          <Label for="email" class="text-xs font-bold uppercase tracking-widest text-muted-foreground">Email đăng ký tài khoản</Label>
          <div class="relative">
             <Mail class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted-foreground" />
             <Input 
               id="email"
               v-model="form.email" 
               type="email" 
               autocomplete="email" 
               placeholder="name@company.com" 
               class="h-11 pl-10 bg-muted/20 border-border/50 focus:border-primary/50"
               required
               autofocus
             />
          </div>
          <p v-if="form.errors.email" class="text-xs font-bold text-destructive mt-1 ">{{ form.errors.email }}</p>
        </div>

        <Button 
          type="submit" 
          :disabled="form.processing" 
          class="w-full h-11 text-xs font-black uppercase tracking-widest shadow-lg shadow-primary/20"
        >
          {{ form.processing ? 'ĐANG XỬ LÝ...' : 'GỬI LINK KHÔI PHỤC' }}
        </Button>
      </form>
    </CardContent>

    <CardFooter class="px-0 border-t pt-6">
      <div class="text-center w-full">
        <Button variant="link" as-child class="text-muted-foreground hover:text-primary transition-colors text-xs font-bold uppercase tracking-widest">
           <Link :href="route('login')">
              <ArrowLeft class="w-3.5 h-3.5 mr-2" /> Quay lại Đăng nhập
           </Link>
        </Button>
      </div>
    </CardFooter>
  </Card>
</template>
