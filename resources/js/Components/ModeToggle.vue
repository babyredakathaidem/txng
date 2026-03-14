<script setup>
import { useDark, useToggle } from '@vueuse/core'
import { Sun, Moon } from 'lucide-vue-next'
import { Button } from '@/Components/ui/button'

const isDark = useDark()
const toggleDark = useToggle(isDark)
</script>

<template>
  <Button 
    variant="ghost" 
    size="icon" 
    @click="toggleDark()" 
    class="rounded-xl w-10 h-10 text-muted-foreground hover:text-primary hover:bg-primary/10 transition-all border border-transparent hover:border-primary/20"
  >
    <Sun v-if="isDark" class="h-5 w-5 transition-all" />
    <Moon v-else class="h-5 w-5 transition-all" />
    <span class="sr-only">Toggle theme</span>
  </Button>
</template>
