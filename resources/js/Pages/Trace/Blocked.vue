<script setup>
import { Head, Link } from "@inertiajs/vue3";
import { Card, CardHeader, CardTitle, CardDescription, CardContent, CardFooter } from '@/Components/ui/card/index.js'
import { Button } from '@/Components/ui/button/index.js'
import { Alert, AlertDescription, AlertTitle } from '@/Components/ui/alert/index.js'
import { Label } from '@/Components/ui/label/index.js'
import { 
  Lock, 
  MapPin, 
  AlertTriangle, 
  ShieldAlert,
  ArrowLeft
} from "lucide-vue-next";

defineProps({ title: String, message: String, distance_m: Number, radius_m: Number });
</script>

<template>
  <Head :title="title || 'Truy cập bị từ chối'" />
  
  <div class="min-h-screen bg-muted/10 flex items-center justify-center p-6">
    <Card class="max-w-md w-full border-border/50 shadow-2xl overflow-hidden relative rounded-[2.5rem]">
      <div class="absolute top-0 left-0 right-0 h-1 bg-destructive"></div>
      
      <CardHeader class="text-center pb-2">
        <div class="flex justify-center mb-6">
          <div class="w-20 h-20 rounded-full bg-destructive/10 flex items-center justify-center text-destructive border-2 border-destructive/20 animate-in zoom-in duration-500 shadow-lg shadow-destructive/5">
            <ShieldAlert class="w-10 h-10" />
          </div>
        </div>
        <CardTitle class="text-2xl font-black  tracking-tighter text-foreground uppercase">{{ title || 'Không thể truy cập' }}</CardTitle>
        <CardDescription class="text-muted-foreground font-medium pt-2  leading-relaxed">
          {{ message || 'Yêu cầu của bạn đã bị hệ thống bảo mật từ chối.' }}
        </CardDescription>
      </CardHeader>

      <CardContent class="space-y-6 pt-6">
        <div v-if="distance_m !== undefined && radius_m !== undefined" class="p-6 rounded-3xl bg-muted/30 border border-dashed border-border space-y-6 shadow-inner">
           <div class="flex items-center gap-2 text-[10px] font-black uppercase tracking-widest text-muted-foreground">
              <MapPin class="w-4 h-4 text-primary" />
              Chi tiết sai lệch vị trí
           </div>
           
           <div class="grid grid-cols-2 gap-6">
              <div class="space-y-1">
                 <Label class="text-[9px] font-black uppercase text-muted-foreground/60 px-1">Khoảng cách thực tế</Label>
                 <p class="text-xl font-black text-destructive  tracking-tighter">{{ distance_m.toLocaleString() }}m</p>
              </div>
              <div class="space-y-1">
                 <Label class="text-[9px] font-black uppercase text-muted-foreground/60 px-1">Bán kính cho phép</Label>
                 <p class="text-xl font-black text-foreground tracking-tighter">{{ radius_m.toLocaleString() }}m</p>
              </div>
           </div>

           <Alert variant="destructive" class="bg-destructive/5 border-destructive/10 rounded-2xl">
              <AlertTriangle class="h-4 w-4" />
              <AlertDescription class="text-[10px] font-bold uppercase tracking-tight leading-relaxed">
                 Vị trí quét không nằm trong phạm vi cho phép của điểm phát hành sản phẩm.
              </AlertDescription>
           </Alert>
        </div>

        <p class="text-[11px] text-muted-foreground leading-relaxed text-center  px-4">
           Nếu bạn tin rằng đây là một sự nhầm lẫn, vui lòng kiểm tra lại quyền truy cập vị trí trên trình duyệt hoặc liên hệ với nhà sản xuất để được hỗ trợ.
        </p>
      </CardContent>

      <CardFooter class="border-t bg-muted/5 py-8">
         <Button variant="outline" as-child class="w-full h-12 font-black uppercase tracking-widest text-[10px] rounded-xl border-2 hover:bg-muted active:scale-95 transition-all">
            <Link href="/">
               <ArrowLeft class="w-4 h-4 mr-2" /> Quay lại trang chủ
            </Link>
         </Button>
      </CardFooter>
    </Card>
  </div>
</template>
