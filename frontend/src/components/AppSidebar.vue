<script setup lang="ts">
import { computed } from "vue"
import type { SidebarProps } from '@/components/ui/sidebar'

import { ChartLine } from '@lucide/vue';
import {
  Settings2,
} from "lucide-vue-next"
import NavMain from '@/components/NavMain.vue'
import NavUser from '@/components/NavUser.vue'
import TeamSwitcher from '@/components/TeamSwitcher.vue'

import {
  Sidebar,
  SidebarContent,
  SidebarFooter,
  SidebarHeader,
  SidebarRail,
} from '@/components/ui/sidebar'
import { useUserStore } from '@/stores/userStore'
import type { User } from "@/types/user"
import type { Workspace } from "@/types/workspace"
import { useWorkspaceStore } from "@/stores/workspaceStore"
import { useRoute } from "vue-router"

const props = withDefaults(defineProps<SidebarProps>(), {
  collapsible: "icon",
})

const userStore = useUserStore()
const workspaceStore = useWorkspaceStore()
const route = useRoute()

const navMain = computed(() => [
   {
      title: "Dashboard",
      route: "Dashboard",
      icon: ChartLine,
  },
  {
    title: "Settings",
    route: null,
    icon: Settings2,
    isActive: ["Channels"].includes(route.name as string),
    items: [
      {
        title: "Channels",
        route: "Channels",
      },
    ],
  },
])

const user = computed<User | null>(() => userStore.getUser())
const workspaces = computed<Workspace[]>(() => workspaceStore.getWorkspaces())
</script>

<template>
  <Sidebar v-bind="props">
    <SidebarHeader>
      <TeamSwitcher :teams="workspaces" />
    </SidebarHeader>
    <SidebarContent>
      <NavMain :items="navMain" />
    </SidebarContent>
    <SidebarFooter>
      <NavUser v-if="user" :user="user" />
    </SidebarFooter>
    <SidebarRail />
  </Sidebar>
</template>
