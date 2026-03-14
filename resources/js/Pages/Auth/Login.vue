<script setup>
import { Head, useForm, Link } from '@inertiajs/vue3'
import { Button } from '@/Components/ui/button/index.js'
import { Input } from '@/Components/ui/input/index.js'
import { Label } from '@/Components/ui/label/index.js'
import { Checkbox } from '@/Components/ui/checkbox/index.js'
import { Card, CardHeader, CardTitle, CardDescription, CardContent, CardFooter } from '@/Components/ui/card/index.js'

const form = useForm({
  email: '',
  password: '',
  remember: false,
})

const submit = () => {
  form.post('/login', { preserveScroll: true })
}
</script>

<template>
  <Head title="Đăng nhập hệ thống" />

  <Card class="border-none bg-transparent shadow-none p-0">
    <CardHeader class="px-0 pt-0 pb-6">
      <CardTitle class="text-3xl font-black tracking-tighter text-foreground uppercase ">Đăng nhập</CardTitle>
      <CardDescription class="text-muted-foreground font-medium mt-1">Truy cập hệ thống truy xuất nguồn gốc AGU.</CardDescription>
    </CardHeader>
    
    <CardContent class="px-0 pb-6">
      <form class="space-y-5" @submit.prevent="submit">
        <div class="space-y-2">
          <Label for="email" class="text-xs font-bold uppercase tracking-widest text-muted-foreground">Email tài khoản</Label>
          <Input 
            id="email"
            v-model="form.email" 
            type="email" 
            autocomplete="email" 
            placeholder="name@company.com" 
            class="h-11 bg-muted/20 border-border/50 focus:border-primary/50 transition-all"
          />
          <p v-if="form.errors.email" class="text-xs font-bold text-destructive mt-1">{{ form.errors.email }}</p>
        </div>

        <div class="space-y-2">
          <div class="flex items-center justify-between">
            <Label for="password" class="text-xs font-bold uppercase tracking-widest text-muted-foreground">Mật khẩu</Label>
            <Link href="/forgot-password" class="text-[10px] font-black uppercase text-primary hover:underline">Quên mật khẩu?</Link>
          </div>
          <Input 
            id="password"
            v-model="form.password" 
            type="password" 
            autocomplete="current-password" 
            placeholder="••••••••" 
            class="h-11 bg-muted/20 border-border/50 focus:border-primary/50 transition-all"
          />
          <p v-if="form.errors.password" class="text-xs font-bold text-destructive mt-1">{{ form.errors.password }}</p>
        </div>

        <div class="flex items-center space-x-2 py-1">
          <Checkbox id="remember" :checked="form.remember" @update:checked="(v) => (form.remember = v)" />
          <Label for="remember" class="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70 cursor-pointer">
            Ghi nhớ đăng nhập
          </Label>
        </div>

        <Button 
          type="submit" 
          :disabled="form.processing" 
          class="w-full h-11 text-xs font-black uppercase tracking-widest shadow-lg shadow-primary/20"
        >
          {{ form.processing ? 'Đang xác thực...' : 'ĐĂNG NHẬP NGAY' }}
        </Button>
      </form>
    </CardContent>

    <CardFooter class="px-0 border-t pt-6 flex flex-col gap-4">
      <div class="text-center text-sm text-muted-foreground">
        Chưa có tài khoản doanh nghiệp?
        <Link href="/register" class="text-primary hover:underline font-black ml-1 uppercase text-xs tracking-tighter">Đăng ký mới</Link>
      </div>
    </CardFooter>
  </Card>
</template>
