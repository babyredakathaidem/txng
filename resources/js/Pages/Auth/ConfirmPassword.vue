<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { Card, CardHeader, CardTitle, CardDescription, CardContent, CardFooter } from '@/Components/ui/card/index.js'
import { Button } from '@/Components/ui/button/index.js'
import { Input } from '@/Components/ui/input/index.js'
import { Label } from '@/Components/ui/label/index.js'
import { Lock, ShieldAlert } from 'lucide-vue-next'

const form = useForm({
    password: '',
});

const submit = () => {
    form.post(route('password.confirm'), {
        onFinish: () => form.reset(),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Xác nhận bảo mật — AGU" />

        <div class="max-w-md mx-auto">
            <Card class="border-none bg-transparent shadow-none p-0">
                <CardHeader class="px-0 pt-0 pb-6">
                    <CardTitle class="text-2xl font-black tracking-tighter text-foreground uppercase  flex items-center gap-2">
                        <ShieldAlert class="h-8 w-8 text-primary" />
                        Xác nhận bảo mật
                    </CardTitle>
                    <CardDescription class="text-muted-foreground font-medium mt-1">
                        Đây là khu vực an toàn. Vui lòng xác nhận mật khẩu trước khi tiếp tục thao tác.
                    </CardDescription>
                </CardHeader>
                
                <CardContent class="px-0 pb-6">
                    <form @submit.prevent="submit" class="space-y-5">
                        <div class="space-y-2">
                            <Label for="password" class="text-xs font-bold uppercase tracking-widest text-muted-foreground">Mật khẩu của bạn</Label>
                            <div class="relative">
                                <Lock class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted-foreground" />
                                <Input 
                                    id="password"
                                    v-model="form.password" 
                                    type="password" 
                                    autocomplete="current-password" 
                                    placeholder="••••••••" 
                                    class="h-11 pl-10 rounded-xl bg-muted/20 border-border/50 focus:border-primary/50 transition-all"
                                    required
                                    autofocus
                                />
                            </div>
                            <p v-if="form.errors.password" class="text-xs font-bold text-destructive mt-1 ">{{ form.errors.password }}</p>
                        </div>

                        <Button 
                            type="submit" 
                            :disabled="form.processing" 
                            class="w-full h-11 rounded-xl font-black uppercase tracking-widest text-xs shadow-lg shadow-primary/20 transition-all active:scale-95"
                        >
                            {{ form.processing ? 'ĐANG XÁC THỰC...' : 'XÁC NHẬN MẬT KHẨU' }}
                        </Button>
                    </form>
                </CardContent>

                <CardFooter class="px-0 border-t pt-6">
                    <div class="text-center w-full text-[10px] text-muted-foreground uppercase font-bold tracking-widest ">
                        AGU Traceability Security Layer
                    </div>
                </CardFooter>
            </Card>
        </div>
    </GuestLayout>
</template>
