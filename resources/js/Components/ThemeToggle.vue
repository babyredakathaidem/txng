<script setup>
import { useColorMode } from '@vueuse/core'
import { Sun, Moon, Laptop } from 'lucide-vue-next'
import { Button } from '@/Components/ui/button'
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger,
} from '@/Components/ui/dropdown-menu'

const mode = useColorMode()
</script>

<template>
  <DropdownMenu>
    <DropdownMenuTrigger as-child>
      <Button variant="ghost" size="icon" class="text-muted-foreground hover:text-foreground rounded-full transition-all">
        <Sun class="h-[1.2rem] w-[1.2rem] rotate-0 scale-100 transition-all dark:-rotate-90 dark:scale-0" />
        <Moon class="absolute h-[1.2rem] w-[1.2rem] rotate-90 scale-0 transition-all dark:rotate-0 dark:scale-100" />
        <span class="sr-only">Toggle theme</span>
      </Button>
    </DropdownMenuTrigger>
    <DropdownMenuContent align="right" class="bg-background border-white/10 text-foreground">
      <DropdownMenuItem @click="mode = 'light'" class="cursor-pointer gap-2">
        <Sun class="h-4 w-4 opacity-60" />
        Sáng
      </DropdownMenuItem>
      <DropdownMenuItem @click="mode = 'dark'" class="cursor-pointer gap-2">
        <Moon class="h-4 w-4 opacity-60" />
        Tối
      </DropdownMenuItem>
      <DropdownMenuItem @click="mode = 'auto'" class="cursor-pointer gap-2">
        <Laptop class="h-4 w-4 opacity-60" />
        Hệ thống
      </DropdownMenuItem>
    </DropdownMenuContent>
  </DropdownMenu>
</template>
