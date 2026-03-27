<script setup lang="ts">
import { channelService } from '@/services/channelService';
import { onMounted, ref } from 'vue';
import type { Entrypoint } from '@/types/channel';
import { useRoute } from 'vue-router';
const entrypoints = ref<Entrypoint[]>([]);

const route = useRoute();

async function getEntrypoints() {
    const channelSlug = route.params.channel as string;

    const res = await channelService.getChannelEntrypoints(channelSlug);

    if (res.ok && res.data) {
      entrypoints.value = res.data;
    }
}

onMounted(async () => {
  await getEntrypoints();
});
</script>

<template>
    <div>entrypoints</div>
</template>