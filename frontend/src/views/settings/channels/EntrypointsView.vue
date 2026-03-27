<script setup lang="ts">
import { channelService } from '@/services/channelService'
import { onMounted, ref } from 'vue'
import type { Entrypoint } from '@/types/channel'
import { useRoute } from 'vue-router'
import { Item, ItemContent, ItemDescription, ItemTitle } from '@/components/ui/item'
import { Separator } from '@/components/ui/separator'
import { Button } from '@/components/ui/button'
import { Copy } from 'lucide-vue-next'
import { useClipboard } from '@vueuse/core'

const route = useRoute()

const entrypoints = ref<Entrypoint[]>([])
const copiedId = ref<number | null>(null)
const textToCopy = ref('')

const { copy, copied, isSupported } = useClipboard({ source: textToCopy })

async function getEntrypoints() {
  const channelSlug = route.params.channel as string

  const res = await channelService.getChannelEntrypoints(channelSlug)

  if (res.ok && res.data) {
    entrypoints.value = res.data
  }
}

async function copyToClipboard(text: string) {
  textToCopy.value = text
  copy(textToCopy)
}

onMounted(async () => {
  await getEntrypoints()
})
</script>

<template>
  <div class="w-full gap-6 grid sm:grid-cols-2 lg:grid-cols-3">
    <Item v-for="entrypoint in entrypoints" :key="entrypoint.id" variant="outline">
      <ItemContent class="overflow-hidden">
        <ItemTitle class="truncate min-w-0">{{ entrypoint.name }}</ItemTitle>
        <ItemDescription>{{ entrypoint.provider }}</ItemDescription>

        <Separator class="my-2" />

        <div class="flex items-center gap-4 h-8 group relative">
          <p class="truncate group-hover:pr-10">
            {{ entrypoint.entrypoint }}
          </p>

          <Button
            variant="secondary"
            size="sm"
            class="absolute right-0 opacity-0 translate-x-2 group-hover:opacity-100 group-hover:translate-x-0 transition-all duration-100 pointer-events-none group-hover:pointer-events-auto"
            @click="copyToClipboard(entrypoint.entrypoint, entrypoint.id)"
          >
            <Copy />
          </Button>
        </div>
      </ItemContent>
    </Item>
  </div>
</template>
