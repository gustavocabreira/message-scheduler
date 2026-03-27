<script lang="ts">
export const description = "A sidebar that collapses to icons.";
export const iframeHeight = "800px";
export const containerClass = "w-full h-full";
</script>

<script setup lang="ts">
import AppSidebar from "@/components/AppSidebar.vue";
import {
  Breadcrumb,
  BreadcrumbItem,
  BreadcrumbLink,
  BreadcrumbList,
  BreadcrumbPage,
  BreadcrumbSeparator,
} from "@/components/ui/breadcrumb";
import { Button } from "@/components/ui/button";
import { Separator } from "@/components/ui/separator";
import { SidebarInset, SidebarProvider, SidebarTrigger } from "@/components/ui/sidebar";
import { useWorkspaceStore } from "@/stores/workspaceStore";
import { useUiStore } from "@/stores/uiStore";
import { computed, onMounted } from "vue";
import { useRoute, useRouter } from "vue-router";
import { useColorMode } from "@vueuse/core";
import { Sun, Moon } from "@lucide/vue";

const workspaceStore = useWorkspaceStore();
const uiStore = useUiStore();
const route = useRoute();
const router = useRouter();
const mode = useColorMode();

const breadcrumbs = computed(() => {
  const segments = route.path.split("/").filter(Boolean);

  if (segments.length === 0) {
    return [
      { label: String(route.name ?? "Home"), path: "/", isLast: true, navigable: false },
    ];
  }

  return segments.map((segment, index) => {
    const path = "/" + segments.slice(0, index + 1).join("/");
    const label = segment.charAt(0).toUpperCase() + segment.slice(1);
    const isLast = index === segments.length - 1;
    const resolved = router.resolve(path);
    const navigable = !isLast && !!resolved.name && resolved.name !== "404";
    return { label, path, isLast, navigable };
  });
});

const currentPage = computed(() => route.name as string);

onMounted(async () => {
  await Promise.all([
    workspaceStore.fetchWorkspaces(),
    workspaceStore.fetchActiveWorkspace(),
  ]);
});

function toggleMode() {
  mode.value = mode.value === "dark" ? "light" : "dark";
}
</script>

<template>
  <SidebarProvider>
    <AppSidebar />
    <SidebarInset>
      <header
        class="flex h-16 shrink-0 items-center justify-between gap-2 transition-[width,height] ease-linear group-has-data-[collapsible=icon]/sidebar-wrapper:h-12"
      >
        <div class="flex items-center gap-2 px-4">
          <SidebarTrigger class="-ml-1" />
          <Separator
            orientation="vertical"
            class="mr-2 data-[orientation=vertical]:h-4"
          />
          <Breadcrumb>
            <BreadcrumbList>
              <template v-for="crumb in breadcrumbs" :key="crumb.path">
                <BreadcrumbItem :class="!crumb.isLast ? 'hidden md:block' : ''">
                  <BreadcrumbLink
                    v-if="!crumb.isLast"
                    :as-child="crumb.navigable"
                    class="capitalize"
                  >
                    <router-link v-if="crumb.navigable" :to="crumb.path">
                      {{ crumb.label.split("-").join(" ") }}
                    </router-link>
                    <template v-else>{{ crumb.label.split("-").join(" ") }}</template>
                  </BreadcrumbLink>
                  <BreadcrumbPage v-else>{{
                    crumb.label.split("-").join(" ")
                  }}</BreadcrumbPage>
                </BreadcrumbItem>
                <BreadcrumbSeparator v-if="!crumb.isLast" class="hidden md:block" />
              </template>
            </BreadcrumbList>
          </Breadcrumb>
        </div>
        <div class="px-4">
          <Button size="icon" variant="ghost" @click="toggleMode">
            <Sun v-if="mode === 'dark'" />
            <Moon v-else />
          </Button>
        </div>
      </header>
      <div class="relative flex flex-1 flex-col gap-4 p-4 pt-0">
        <h1 class="scroll-m-20 text-2xl font-bold text-balance">
          {{ currentPage }}
        </h1>
        <router-view :key="workspaceStore.activeWorkspace?.id" />
        <Transition name="fade">
          <div
            v-if="uiStore.loadingVisible"
            class="absolute inset-0 z-10 flex items-center justify-center backdrop-blur-sm bg-background/60 rounded-lg"
          >
            <div class="flex flex-col items-center gap-3 text-muted-foreground">
              <svg
                class="animate-spin size-6"
                xmlns="http://www.w3.org/2000/svg"
                fill="none"
                viewBox="0 0 24 24"
              >
                <circle
                  class="opacity-25"
                  cx="12"
                  cy="12"
                  r="10"
                  stroke="currentColor"
                  stroke-width="4"
                />
                <path
                  class="opacity-75"
                  fill="currentColor"
                  d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"
                />
              </svg>
              <span class="text-sm font-medium">{{ uiStore.loadingMessage }}</span>
            </div>
          </div>
        </Transition>
      </div>
    </SidebarInset>
  </SidebarProvider>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.2s ease;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>
