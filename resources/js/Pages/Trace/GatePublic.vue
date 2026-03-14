<script setup>
import { Head, router } from "@inertiajs/vue3";
import { ref } from "vue";
import GuestLayout from "@/Layouts/GuestLayout.vue";
import { Card, CardHeader, CardTitle, CardDescription, CardContent, CardFooter } from '@/Components/ui/card/index.js'
import { Button } from '@/Components/ui/button/index.js'
import { Alert, AlertDescription } from '@/Components/ui/alert/index.js'
import { MapPin, ShieldCheck, AlertTriangle, RefreshCw } from "lucide-vue-next";

defineOptions({ layout: GuestLayout });

const props = defineProps({ token: String });
const loading = ref(false);
const error = ref("");

function devicePayload() {
  const ua = navigator.userAgent || "";
  const platform = navigator.platform || "";
  return { device_name: ua, device_platform: platform };
}

function go() {
  loading.value = true;
  error.value = "";
  const device = devicePayload();

  if (!navigator.geolocation) {
    error.value = "Trình duyệt của bạn không hỗ trợ định vị GPS.";
    loading.value = false;
    return;
  }

  navigator.geolocation.getCurrentPosition(
    (pos) => {
      router.post(route("trace.resolve.public", props.token), {
        lat: pos.coords.latitude,
        lng: pos.coords.longitude,
        ...device,
      });
    },
    (err) => {
      loading.value = false;
      error.value = "Không thể lấy vị trí. Vui lòng kiểm tra quyền truy cập GPS của trình duyệt.";
      console.error(err);
    },
    { enableHighAccuracy: true, timeout: 10000 }
  );
}
</script>

<template>
  <Head title="Xác thực vị trí — AGU Trace" />
  
  <div class="max-w-md mx-auto px-6 py-20">
    <Card class="border-border/50 bg-card/50 backdrop-blur-xl shadow-2xl relative overflow-hidden rounded-[2.5rem]">
      <!-- Top aesthetic bar -->
      <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-primary/50 via-primary to-primary/50"></div>
      
      <CardHeader class="text-center pb-2">
        <div class="flex justify-center mb-6">
          <div class="relative">
             <div class="absolute inset-0 bg-primary/20 blur-2xl rounded-full animate-pulse"></div>
             <div class="relative w-20 h-20 rounded-3xl bg-primary/10 border border-primary/20 flex items-center justify-center text-primary shadow-xl">
               <MapPin class="w-10 h-10" />
             </div>
          </div>
        </div>
        <CardTitle class="text-3xl font-black  tracking-tighter text-foreground uppercase">Xác thực vị trí</CardTitle>
        <CardDescription class="text-muted-foreground font-medium pt-2 leading-relaxed">
          Theo tiêu chuẩn <span class="text-primary font-bold">TCVN 12850</span>, hệ thống cần đối chiếu tọa độ quét để đảm bảo tính minh bạch tại điểm phát hành.
        </CardDescription>
      </CardHeader>

      <CardContent class="space-y-6 pt-6 text-center">
        <div v-if="error" class="animate-in zoom-in duration-300">
           <Alert variant="destructive" class="bg-destructive/5 border-destructive/20 text-left">
              <AlertTriangle class="h-4 w-4" />
              <AlertDescription class="text-xs font-bold uppercase tracking-tight leading-relaxed">
                 {{ error }}
              </AlertDescription>
           </Alert>
        </div>

        <Button 
          @click="go" 
          :disabled="loading"
          class="w-full h-14 rounded-2xl font-black uppercase tracking-[0.2em] text-xs shadow-xl shadow-primary/20 transition-all active:scale-95 flex items-center justify-center gap-3"
        >
          <RefreshCw v-if="loading" class="w-5 h-5 animate-spin" />
          <span v-else>Đồng ý & Chia sẻ vị trí</span>
        </Button>
        
        <div class="flex items-center justify-center gap-2 text-[10px] text-muted-foreground/40 uppercase font-black tracking-widest ">
          <ShieldCheck class="w-4 h-4" />
          Dữ liệu được mã hóa & Bảo mật tuyệt đối
        </div>
      </CardContent>

      <CardFooter class="border-t bg-muted/5 py-6 flex justify-center">
         <p class="text-[10px] text-muted-foreground/30 font-black tracking-[0.3em] uppercase">AGU Traceability System</p>
      </CardFooter>
    </Card>
  </div>
</template>
