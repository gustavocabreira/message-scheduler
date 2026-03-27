<script setup lang="ts">
import { channelService } from "@/services/channelService";
import { onMounted, ref } from "vue";
import type { Entrypoint } from "@/types/channel";
import { useRoute } from "vue-router";
import { Item, ItemContent, ItemDescription, ItemTitle } from "@/components/ui/item";
import { Separator } from "@/components/ui/separator";
import { Button } from "@/components/ui/button";
import { Check, Copy } from "lucide-vue-next";
import { cn } from "@/lib/utils";
import { useCopyRowStickyReveal } from "@/composables/useCopyRowStickyReveal";
import { useUiStore } from "@/stores/uiStore";

const route = useRoute();
const uiStore = useUiStore();

const entrypoints = ref<Entrypoint[]>([]);

const {
  copiedRowId,
  stickyRowId,
  onRowEnter,
  onRowLeave,
  copyRow,
} = useCopyRowStickyReveal();

const copyBtnBase = "absolute right-0 transition-all duration-100";
const copyBtnPinned = "opacity-100 translate-x-0 pointer-events-auto";
const copyBtnHoverReveal =
  "opacity-0 translate-x-2 pointer-events-none group-hover:opacity-100 group-hover:translate-x-0 group-hover:pointer-events-auto [@media(hover:none)]:opacity-100 [@media(hover:none)]:translate-x-0 [@media(hover:none)]:pointer-events-auto";

function copyButtonClass(rowId: number) {
  return cn(
    copyBtnBase,
    stickyRowId.value === rowId ? copyBtnPinned : copyBtnHoverReveal
  );
}

async function getEntrypoints() {
  const channelSlug = route.params.channel as string;

  const res = await channelService.getChannelEntrypoints(channelSlug);

  if (res.ok && res.data) {
    entrypoints.value = res.data;
  }
}

onMounted(async () => {
  await uiStore.withLoadingOverlay("Loading entrypoints...", getEntrypoints);
});
</script>

<template>
  <div class="w-full gap-6 grid sm:grid-cols-2 lg:grid-cols-3">
    <Item v-for="entrypoint in entrypoints" :key="entrypoint.id" variant="outline">
      <ItemContent class="overflow-hidden">
        <ItemTitle class="truncate min-w-0">{{ entrypoint.name }}</ItemTitle>
        <ItemDescription>{{ entrypoint.provider }}</ItemDescription>

        <Separator class="my-2" />

        <div
          class="flex items-center gap-4 h-8 group relative"
          @mouseenter="onRowEnter(entrypoint.id)"
          @mouseleave="onRowLeave(entrypoint.id)"
        >
          <p
            class="truncate min-w-0 text-sm group-hover:pr-10 [@media(hover:none)]:pr-10"
            :class="{ 'pr-10': stickyRowId === entrypoint.id }"
          >
            {{ entrypoint.entrypoint }}
          </p>

          <Button
            type="button"
            variant="secondary"
            size="sm"
            :class="copyButtonClass(entrypoint.id)"
            title="Copiar"
            @click.stop="copyRow(entrypoint.entrypoint, entrypoint.id)"
          >
            <Check v-if="copiedRowId === entrypoint.id" class="size-4" />
            <Copy v-else class="size-4" />
          </Button>
        </div>
      </ItemContent>
    </Item>
  </div>
</template>
