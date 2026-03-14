import './bootstrap'

import { createInertiaApp } from '@inertiajs/vue3'
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers'
import { createApp, h } from 'vue'

// Animation
import AOS from 'aos'
import 'aos/dist/aos.css'
import { autoAnimatePlugin } from '@formkit/auto-animate/vue'

// Ziggy
import { ZiggyVue } from '../../vendor/tightenco/ziggy'

import AppLayout from '@/Layouts/AppLayout.vue'
import AuthLayout from '@/Layouts/AuthLayout.vue'
import GuestLayout from '@/Layouts/GuestLayout.vue'

createInertiaApp({
  title: (title) => (title ? `${title} — AGU Traceability` : 'AGU Traceability'),
  resolve: (name) => {
    const page = resolvePageComponent(
      `./Pages/${name}.vue`,
      import.meta.glob('./Pages/**/*.vue')
    )

    page.then((module) => {
      const comp = module.default

      // Nếu page tự define layout thì không override
      if (comp.layout) return

      // Auth + Onboarding: Giao diện tối giản
      if (name.startsWith('Auth/') || name.startsWith('Onboarding/')) {
        comp.layout = AuthLayout
        return
      }

      // Public pages (người tiêu dùng quét QR, trang chủ tra cứu)
      if (name.startsWith('Public/') || name.startsWith('Trace/') || name === 'Welcome') {
        comp.layout = GuestLayout
        return
      }

      // Default: Tất cả trang quản trị (Doanh nghiệp & Hệ thống)
      // Dùng chung AppLayout đã hợp nhất thiết kế
      comp.layout = AppLayout
    })

    return page
  },
  setup({ el, App, props, plugin }) {
    const vueApp = createApp({ render: () => h(App, props) })
    vueApp.use(plugin)
    vueApp.use(ZiggyVue, window.Ziggy)
    vueApp.use(autoAnimatePlugin)
    
    // Khởi tạo AOS
    AOS.init({
      duration: 800,
      once: true,
      easing: 'ease-out-quad',
    })

    // Re-init AOS khi Inertia chuyển trang
    document.addEventListener('inertia:finish', () => {
      AOS.refresh()
    })

    vueApp.mount(el)
  },
})  