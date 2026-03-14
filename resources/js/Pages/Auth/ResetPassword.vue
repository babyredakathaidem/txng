<script setup>
import { Head, useForm, Link } from '@inertiajs/vue3'
import { Card, CardHeader, CardTitle, CardDescription, CardContent, CardFooter } from '@/Components/ui/card/index.js'
import { Button } from '@/Components/ui/button/index.js'
import { Input } from '@/Components/ui/input/index.js'
import { Label } from '@/Components/ui/label/index.js'
import { ShieldCheck, Lock, Mail } from 'lucide-vue-next'

const props = defineProps({
  email: { type: String, required: true },
  token: { type: String, required: true },
})

const form = useForm({
  token:                 props.token,
  email:                 props.email,
  password:              '',
  password_confirmation: '',
})

const submit = () => {
  form.post(route('password.store'), {
    onFinish: () => form.reset('password', 'password_confirmation'),
  })
}
</script>

<template>
  <Head title="Khôi phục mật khẩu — AGU" />

  <Card class="border-none bg-transparent shadow-none p-0">
    <CardHeader class="px-0 pt-0 pb-6">
      <CardTitle class="text-3xl font-black tracking-tighter text-foreground uppercase  flex items-center gap-2">
         <ShieldCheck class="h-8 w-8 text-primary" />
         Đặt lại mật khẩu
      </CardTitle>
      <CardDescription class="text-muted-foreground font-medium mt-1">
        Hoàn tất quá trình khôi phục bằng cách thiết lập mật khẩu mới an toàn hơn.
      </CardDescription>
    </CardHeader>
    
    <CardContent class="px-0 pb-6">
      <form class="space-y-5" @submit.prevent="submit">
        <div class="space-y-2">
          <Label for="email" class="text-xs font-bold uppercase tracking-widest text-muted-foreground">Địa chỉ Email</Label>
          <div class="relative">
             <Mail class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted-foreground" />
             <Input 
               id="email"
               v-model="form.email" 
               type="email" 
               autocomplete="email" 
               class="h-11 pl-10 bg-muted/20 border-border/50 focus:border-primary/50"
               required
             />
          </div>
          <p v-if="form.errors.email" class="text-xs font-bold text-destructive mt-1 ">{{ form.errors.email }}</p>
        </div>

        <div class="space-y-2">
          <Label for="password" class="text-xs font-bold uppercase tracking-widest text-muted-foreground">Mật khẩu mới *</Label>
          <div class="relative">
             <Lock class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted-foreground" />
             <Input 
               id="password"
               v-model="form.password" 
               type="password" 
               autocomplete="new-password" 
               placeholder="Tối thiểu 8 ký tự"
               class="h-11 pl-10 bg-muted/20 border-border/50 focus:border-primary/50"
               required
               autofocus
             />
          </div>
          <p v-if="form.errors.password" class="text-xs font-bold text-destructive mt-1 ">{{ form.errors.password }}</p>
        </div>

        <div class="space-y-2">
          <Label for="password_confirmation" class="text-xs font-bold uppercase tracking-widest text-muted-foreground">Xác nhận mật khẩu mới *</Label>
          <div class="relative">
             <Lock class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted-foreground" />
             <Input 
               id="password_confirmation"
               v-model="form.password_confirmation" 
               type="password" 
               autocomplete="new-password" 
               placeholder="Nhập lại mật khẩu mới"
               class="h-11 pl-10 bg-muted/20 border-border/50 focus:border-primary/50"
               required
             />
          </div>
          <p v-if="form.errors.password_confirmation" class="text-xs font-bold text-destructive mt-1 ">{{ form.errors.password_confirmation }}</p>
        </div>

        <Button 
          type="submit" 
          :disabled="form.processing" 
          class="w-full h-11 rounded-xl font-black uppercase tracking-widest text-xs shadow-lg shadow-primary/20 transition-all active:scale-95"
        >
          {{ form.processing ? 'ĐANG CẬP NHẬT...' : 'XÁC NHẬN ĐỔI MẬT KHẨU' }}
        </Button>
      </form>
    </CardContent>

    <CardFooter class="px-0 border-t pt-6">
      <div class="text-center w-full text-sm text-muted-foreground">
        Hành động này sẽ cập nhật mật khẩu truy cập cho email <span class="font-bold text-foreground">{{ email }}</span>.
      </div>
    </CardFooter>
  </Card>
</template>
