<script setup>
const nav = [
  { label:'TRANG CHỦ', href:'/' },
  { label:'DANH MỤC SẢN PHẨM', href:'/categories' },
  { label:'HƯỚNG DẪN DOANH NGHIỆP', href:'/guide' },
  { label:'GỬI TIN BÁO', href:'/report' },
]
</script>

<template>
  <header class="border-b border-glass bg-black/10 backdrop-blur">
    <div class="mx-auto max-w-7xl px-6 py-4 flex items-center justify-between gap-6">
      <div class="flex items-center gap-4">
        <img src="/brand/state-logo.png" alt="Logo" class="h-12 w-12 object-contain" onerror="this.style.display='none';" />
        <div class="leading-tight">
          <div class="text-sm font-semibold uppercase text-white/90">AGU TRACEABILITY</div>
          <div class="text-[11px] text-white/60 uppercase">HỆ THỐNG TRUY XUẤT NGUỒN GỐC SẢN PHÂM HÀNG HOÁ THEO TIÊU CHUẨN QUỐC GIA</div>
        </div>
      </div>

      <div class="flex items-center gap-3">
        <a href="/register" class="px-4 py-2 rounded-lg border border-glass text-sm font-medium text-white/90 hover:border-brand-500/60 hover:text-brand-200 transition">
          Đăng ký
        </a>
        <a href="/login" class="px-4 py-2 rounded-lg bg-brand-500 text-cosmic-950 text-sm font-extrabold hover:bg-brand-600 transition">
          Đăng nhập
        </a>
      </div>
    </div>

    <nav class="border-t border-glass bg-black/25">
      <div class="mx-auto max-w-7xl px-6">
        <ul class="flex flex-wrap">
          <li v-for="it in nav" :key="it.href">
            <a :href="it.href" class="block px-5 py-3 text-xs font-semibold text-white/85 hover:bg-white/5 hover:text-brand-200 transition">
              {{ it.label }}
            </a>
          </li>
        </ul>
      </div>
    </nav>
  </header>
</template>
