
import { ref, watchEffect } from 'vue'

const STORAGE_KEY = 'agu-theme'
const theme = ref(localStorage.getItem(STORAGE_KEY) ?? 'light') // mặc định light

watchEffect(() => {
    if (theme.value === 'dark') {
        document.documentElement.classList.add('dark')
    } else {
        document.documentElement.classList.remove('dark')
    }
    localStorage.setItem(STORAGE_KEY, theme.value)
})

export function useTheme() {
    const toggle = () => {
        theme.value = theme.value === 'dark' ? 'light' : 'dark'
    }
    return { theme, toggle }
}