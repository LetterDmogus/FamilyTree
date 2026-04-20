<script setup lang="ts">
import { type HTMLAttributes } from 'vue'
import { Trees } from 'lucide-vue-next'
import { cn } from '@/lib/utils'

const props = defineProps<{
  class?: HTMLAttributes['class']
}>()
</script>

<template>
  <Trees :class="cn('w-full h-full text-indigo-600 stroke-[2.5]', props.class)" />
</template>
