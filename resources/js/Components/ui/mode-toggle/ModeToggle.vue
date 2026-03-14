<script setup>
import { Moon, Sun } from 'lucide-vue-next'
import { useDark, useToggle } from '@vueuse/core'
import { Button } from '@/Components/ui/button'

const isDark   = useDark()
const toggleDark = useToggle(isDark)
</script>

<template>
  <Button variant="outline" size="icon" @click="toggleDark()">
    <Sun  v-if="isDark"  class="h-4 w-4" />
    <Moon v-else         class="h-4 w-4" />
  </Button>
</template>
