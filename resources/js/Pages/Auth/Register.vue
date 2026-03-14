<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3'
import { Button } from '@/Components/ui/button/index.js'
import { Input } from '@/Components/ui/input/index.js'
import { Label } from '@/Components/ui/label/index.js'
import { Card, CardHeader, CardTitle, CardDescription, CardContent, CardFooter } from '@/Components/ui/card/index.js'

const form = useForm({
  name:                  '',
  email:                 '',
  password:              '',
  password_confirmation: '',
})

const submit = () => {
  form.post(route('register'), {
    onFinish: () => form.reset('password', 'password_confirmation'),
  })
}
</script>

<template>
  <Head title="Đăng ký tài khoản" />

  <Card class="border-none bg-transparent shadow-none p-0">
    <CardHeader class="px-0 pt-0 pb-6">
      <CardTitle class="text-3xl font-black tracking-tighter text-foreground uppercase ">Đăng ký</CardTitle>
      <CardDescription class="text-muted-foreground font-medium mt-1">
        Tạo tài khoản quản trị để đăng nhập hệ thống 
        <span class="font-bold text-primary">AGU Traceability</span>.
      </CardDescription>
    </CardHeader>
    
    <CardContent class="px-0 pb-6">
      <form class="space-y-4" @submit.prevent="submit">
        <div class="space-y-2">
          <Label for="name" class="text-xs font-bold uppercase tracking-widest text-muted-foreground">Họ và tên</Label>
          <Input 
            id="name"
            v-model="form.name" 
            type="text" 
            autocomplete="name" 
            placeholder="Nguyễn Văn A" 
            class="h-11 bg-muted/20 border-border/50 focus:border-primary/50 transition-all"
            required
          />
          <p v-if="form.errors.name" class="text-xs font-bold text-destructive mt-1">{{ form.errors.name }}</p>
        </div>

        <div class="space-y-2">
          <Label for="email" class="text-xs font-bold uppercase tracking-widest text-muted-foreground">Địa chỉ Email</Label>
          <Input 
            id="email"
            v-model="form.email" 
            type="email" 
            autocomplete="email" 
            placeholder="admin@company.com" 
            class="h-11 bg-muted/20 border-border/50 focus:border-primary/50 transition-all"
            required
          />
          <p v-if="form.errors.email" class="text-xs font-bold text-destructive mt-1">{{ form.errors.email }}</p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div class="space-y-2">
            <Label for="password" class="text-xs font-bold uppercase tracking-widest text-muted-foreground">Mật khẩu</Label>
            <Input 
              id="password"
              v-model="form.password" 
              type="password" 
              autocomplete="new-password" 
              placeholder="••••••••" 
              class="h-11 bg-muted/20 border-border/50 focus:border-primary/50 transition-all"
              required
            />
            <p v-if="form.errors.password" class="text-xs font-bold text-destructive mt-1">{{ form.errors.password }}</p>
          </div>

          <div class="space-y-2">
            <Label for="password_confirmation" class="text-xs font-bold uppercase tracking-widest text-muted-foreground">Xác nhận</Label>
            <Input 
              id="password_confirmation"
              v-model="form.password_confirmation" 
              type="password" 
              autocomplete="new-password" 
              placeholder="••••••••" 
              class="h-11 bg-muted/20 border-border/50 focus:border-primary/50 transition-all"
              required
            />
            <p v-if="form.errors.password_confirmation" class="text-xs font-bold text-destructive mt-1">{{ form.errors.password_confirmation }}</p>
          </div>
        </div>

        <Button 
          type="submit" 
          :disabled="form.processing" 
          class="w-full h-11 text-xs font-black uppercase tracking-widest shadow-lg shadow-primary/20 mt-2"
        >
          {{ form.processing ? 'Đang khởi tạo...' : 'TẠO TÀI KHOẢN NGAY' }}
        </Button>
      </form>
    </CardContent>

    <CardFooter class="px-0 border-t pt-6">
      <div class="text-center w-full text-sm text-muted-foreground">
        Đã có tài khoản quản trị?
        <Link :href="route('login')" class="text-primary hover:underline font-black ml-1 uppercase text-xs tracking-tighter">Đăng nhập</Link>
      </div>
    </CardFooter>
  </Card>
</template>
