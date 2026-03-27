<script setup lang="ts">
import { ShieldAlertIcon, Settings as SettingsIcon, ArrowRight } from 'lucide-vue-next'
import { Button } from '@/components/ui/button'
import {
  Item,
  ItemActions,
  ItemContent,
  ItemDescription,
  ItemMedia,
  ItemTitle,
} from '@/components/ui/item'
import { ChartLine } from '@lucide/vue'
import { channelService } from '@/services/channelService'
import { onMounted, ref } from 'vue'
import type { Channel } from '@/types/channel'
import { RouterLink } from 'vue-router'

const channels = ref<Channel[]>([])

async function getChannels() {
  const res = await channelService.getChannels()

  if (res.ok && res.data) {
    channels.value = res.data
  }
}

onMounted(async () => {
  await getChannels()
})
</script>

<template>
  <div class="w-full gap-6 grid sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4">
    <Item v-for="channel in channels" :key="channel.id" variant="outline" as-child>
      <RouterLink
        :to="{
          name: 'Entrypoints',
          params: { channel: channel.slug },
        }"
      >
        <ItemContent>
          <ItemTitle>{{ channel.name }}</ItemTitle>
        </ItemContent>
        <ItemActions>
          <Button size="icon" variant="ghost">
            <component :is="ArrowRight" />
          </Button>
        </ItemActions>
      </RouterLink>
    </Item>
  </div>
</template>
