<script lang="ts">
export const description
  = "A sidebar that collapses to icons."
export const iframeHeight = "800px"
export const containerClass = "w-full h-full"
</script>

<script setup lang="ts">
import AppSidebar from "@/components/AppSidebar.vue"
import {
  Breadcrumb,
  BreadcrumbItem,
  BreadcrumbLink,
  BreadcrumbList,
  BreadcrumbPage,
  BreadcrumbSeparator,
} from "@/components/ui/breadcrumb"
import { Separator } from "@/components/ui/separator"
import {
  SidebarInset,
  SidebarProvider,
  SidebarTrigger,
} from "@/components/ui/sidebar"
import { useWorkspaceStore } from "@/stores/workspaceStore"
import { computed, onMounted } from "vue"
import { useRoute } from "vue-router"

const workspaceStore = useWorkspaceStore();
const route = useRoute();

const breadcrumbs = computed(() => {
  const segments = route.path.split('/').filter(Boolean)

  if (segments.length === 0) {
    return [{ label: String(route.name ?? 'Home'), path: '/', isLast: true }]
  }

  return segments.map((segment, index) => {
    const path = '/' + segments.slice(0, index + 1).join('/')
    const label = segment.charAt(0).toUpperCase() + segment.slice(1)
    const isLast = index === segments.length - 1
    return { label, path, isLast }
  })
})

const currentPage = computed(() => route.name as string)

onMounted(async () => {
  await Promise.all([
    workspaceStore.fetchWorkspaces(),
    workspaceStore.fetchActiveWorkspace(),
  ]);
});
</script>

<template>
  <SidebarProvider>
    <AppSidebar />
    <SidebarInset>
      <header class="flex h-16 shrink-0 items-center gap-2 transition-[width,height] ease-linear group-has-data-[collapsible=icon]/sidebar-wrapper:h-12">
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
                  <BreadcrumbLink v-if="!crumb.isLast">
                    {{ crumb.label }}
                  </BreadcrumbLink>
                  <BreadcrumbPage v-else>{{ crumb.label }}</BreadcrumbPage>
                </BreadcrumbItem>
                <BreadcrumbSeparator v-if="!crumb.isLast" class="hidden md:block" />
              </template>
            </BreadcrumbList>
          </Breadcrumb>
        </div>
      </header>
      <div class="flex flex-1 flex-col gap-4 p-4 pt-0">
        <h1 class="scroll-m-20 text-2xl font-bold text-balance">
          {{ currentPage }}
        </h1>
        <router-view :key="workspaceStore.activeWorkspace?.id" />
      </div>
    </SidebarInset>
  </SidebarProvider>
</template>
