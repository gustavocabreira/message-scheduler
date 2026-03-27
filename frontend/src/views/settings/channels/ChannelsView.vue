<script setup lang="ts">
import { ShieldAlertIcon, Settings as SettingsIcon } from 'lucide-vue-next'
import { Button } from '@/components/ui/button'
import {
  Item,
  ItemActions,
  ItemContent,
  ItemDescription,
  ItemMedia,
  ItemTitle,
} from '@/components/ui/item'
import { ChartLine } from '@lucide/vue';
import { channelService } from '@/services/channelService';
import { onMounted, ref } from 'vue';
import type { Channel } from '@/types/channel';

const channels = ref<Channel[]>([]);

async function getChannels() {
    const res = await channelService.getChannels();

    if (res.ok && res.data) {
      channels.value = res.data;
    }
}

onMounted(async () => {
  await getChannels();
});
</script>

<template>
  <div class="w-full gap-6 grid md:grid-cols-3">
    <Item v-for="channel in channels" :key="channel.id" variant="outline">
      <!-- <ItemMedia variant="icon">
        <ShieldAlertIcon />
      </ItemMedia> -->
      <ItemContent>
        <ItemTitle>{{ channel.name }}</ItemTitle>
      </ItemContent>
      <ItemActions>
        <Button size="sm" variant="outline">
          <component :is="SettingsIcon"/>
        </Button>
      </ItemActions>
    </Item>
  </div>
</template>
