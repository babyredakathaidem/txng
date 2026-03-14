<script setup>
import { Head, router } from "@inertiajs/vue3";
import { ref } from "vue";
import { Card, CardHeader, CardTitle, CardDescription, CardContent, CardFooter } from '@/Components/ui/card/index.js'
import { Button } from '@/Components/ui/button/index.js'
import { 
  ShieldCheck, 
  MapPin, 
  ArrowRight, 
  RefreshCw,
  Info
} from "lucide-vue-next";

const props = defineProps({ token: String });
const loading = ref(false);

function devicePayload() {
  const ua = navigator.userAgent || "";
  const platform = navigator.platform || "";
  return { device_name: ua, device_platform: platform };
}

function go() {
  loading.value = true;
  const device = devicePayload();

  if (!navigator.geolocation) {
    router.post(route("trace.resolve.private", props.token), { lat: null, lng: null, ...device });
    return;
  }

  navigator.geolocation.getCurrentPosition(
    (pos) => {
      router.post(route("trace.resolve.private", props.token), {
        lat: pos.coords.latitude,
        lng: pos.coords.longitude,
        ...device,
      });
    },
    () => {
      router.post(route("trace.resolve.private", props.token), { lat: null, lng: null, ...device });
    },
    { enableHighAccuracy: true, timeout: 8000 }
  );
}
</script>

<template>
  <Head title="Xác thực người tiêu dùng — AGU Trace" />
  
  <div class="min-h-screen bg-muted/10 flex items-center justify-center p-6">
    <Card class="max-w-md w-full border-border/50 shadow-2xl relative overflow-hidden rounded-[2.5rem]">
      <!-- Top decorative bar -->
      <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-emerald-400 via-emerald-500 to-emerald-400"></div>
      <div class="absolute -right-12 -top-12 w-48 h-48 bg-emerald-500/5 blur-3xl rounded-full"></div>
      
      <CardHeader class="text-center pb-2 pt-10">
        <div class="flex justify-center mb-6">
          <div class="relative">
             <div class="absolute inset-0 bg-emerald-500/20 blur-2xl rounded-full animate-pulse"></div>
             <div class="relative w-20 h-20 rounded-[2rem] bg-emerald-50 border border-emerald-200 flex items-center justify-center text-emerald-600 shadow-xl">
               <ShieldCheck class="w-10 h-10" />
             </div>
          </div>
        </div>
        <CardTitle class="text-2xl font-black  tracking-tighter text-foreground uppercase">Xác thực sản phẩm</CardTitle>
        <CardDescription class="text-muted-foreground font-medium pt-2  leading-relaxed px-4">
          QR Bảo mật (AI 21) dùng để xác thực tính chính danh của sản phẩm trực tiếp bởi người tiêu dùng.
        </CardDescription>
      </CardHeader>

      <CardContent class="space-y-6 pt-6 px-8">
        <div class="p-5 rounded-3xl bg-emerald-50/50 border border-dashed border-emerald-200 space-y-4 shadow-inner">
           <div class="flex items-start gap-3">
              <Info class="w-4 h-4 text-emerald-600 shrink-0 mt-0.5" />
              <p class="text-[11px] text-emerald-800 leading-relaxed font-bold uppercase tracking-tight">
                 Mã QR này sẽ tự động kích hoạt chế độ bảo mật 48 giờ kể từ thời điểm quét lần đầu tiên.
              </p>
           </div>
           <div class="flex items-start gap-3">
              <MapPin class="w-4 h-4 text-emerald-600 shrink-0 mt-0.5" />
              <p class="text-[11px] text-emerald-800 leading-relaxed font-bold uppercase tracking-tight">
                 Hệ thống sẽ ghi nhận thiết bị và tọa độ (nếu có) để phòng chống hàng giả.
              </p>
           </div>
        </div>

        <Button 
          @click="go" 
          :disabled="loading"
          class="w-full h-14 rounded-2xl font-black uppercase tracking-[0.2em] text-xs shadow-xl shadow-emerald-500/20 transition-all active:scale-95 flex items-center justify-center gap-3 bg-emerald-600 hover:bg-emerald-700 text-white"
        >
          <RefreshCw v-if="loading" class="w-5 h-5 animate-spin" />
          <span v-else>Xác thực & Tiếp tục</span>
          <ArrowRight v-if="!loading" class="w-4 h-4" />
        </Button>
      </CardContent>

      <CardFooter class="border-t bg-muted/5 py-8 flex justify-center">
         <p class="text-[10px] text-muted-foreground/30 font-black tracking-[0.3em] uppercase ">AGU Security Layer v2.0</p>
      </CardFooter>
    </Card>
  </div>
</template>
