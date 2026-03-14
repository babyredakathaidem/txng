<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue'
import { Card, CardHeader, CardTitle, CardDescription, CardContent } from '@/Components/ui/card/index.js'
import { Badge } from '@/Components/ui/badge/index.js'

defineOptions({ layout: GuestLayout })
</script>

<template>
  <div class="max-w-7xl mx-auto px-6 py-20">
    <Card class="max-w-3xl mx-auto border-primary/20 bg-card/50 backdrop-blur-xl shadow-2xl shadow-primary/10">
      <CardHeader>
        <div class="flex items-center gap-2 mb-2">
           <Badge variant="default" class="uppercase tracking-widest font-black text-[10px]">Welcome</Badge>
        </div>
        <CardTitle class="text-4xl font-black tracking-tighter text-foreground uppercase ">
          AGU Traceability
        </CardTitle>
        <CardDescription class="text-lg font-medium text-muted-foreground mt-2">
          Hệ thống truy xuất nguồn gốc nông sản chuẩn quốc gia.
        </CardDescription>
      </CardHeader>
      <CardContent>
        <p class="text-muted-foreground leading-relaxed">
          Chào mừng bạn đến với nền tảng quản lý và minh bạch hóa chuỗi cung ứng. 
          Giao diện đã được đồng bộ hóa với hệ thống thiết kế <span class="text-primary font-bold">shadcn-vue</span> chuẩn vũ trụ.
        </p>
      </CardContent>
    </Card>
  </div>
</template>
