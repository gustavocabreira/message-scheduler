<script setup lang="ts">
import type { Component } from "vue"
import type { Workspace } from "@/types/workspace"

import { ChevronsUpDown } from "lucide-vue-next"
import { computed } from "vue"
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuLabel,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'

import {
  SidebarMenu,
  SidebarMenuButton,
  SidebarMenuItem,
  useSidebar,
} from '@/components/ui/sidebar'

import { useWorkspaceStore } from "@/stores/workspaceStore"

const props = defineProps<{
  teams: (Workspace & {
    logo?: Component
    plan?: string
  })[]
}>()

const { isMobile } = useSidebar()
const workspaceStore = useWorkspaceStore()

const activeTeam = computed(() => {
  const active = workspaceStore.activeWorkspace
  if (active) return props.teams.find((t) => t.id === active.id) ?? props.teams[0] ?? null
  return props.teams[0] ?? null
})

async function selectTeam(team: typeof props.teams[0]) {
  await workspaceStore.activateWorkspace(team)
}

function initials(name: string): string {
  return name
    .split(' ')
    .slice(0, 2)
    .map((w) => w[0]?.toUpperCase() ?? '')
    .join('')
}
</script>

<template>
  <SidebarMenu>
    <SidebarMenuItem>
      <DropdownMenu>
        <DropdownMenuTrigger as-child>
          <SidebarMenuButton
            size="lg"
            class="data-[state=open]:bg-sidebar-accent data-[state=open]:text-sidebar-accent-foreground"
          >
            <template v-if="activeTeam">
              <div class="flex aspect-square size-8 items-center justify-center rounded-lg bg-sidebar-primary text-sidebar-primary-foreground text-xs font-semibold shrink-0">
                <component v-if="activeTeam.logo" :is="activeTeam.logo" class="size-4" />
                <span v-else>{{ initials(activeTeam.name) }}</span>
              </div>
              <div class="grid flex-1 text-left text-sm leading-tight">
                <span class="truncate font-medium">{{ activeTeam.name }}</span>
                <span v-if="activeTeam.role" class="truncate text-xs text-muted-foreground capitalize">{{ activeTeam.role }}</span>
              </div>
            </template>
            <ChevronsUpDown class="ml-auto" />
          </SidebarMenuButton>
        </DropdownMenuTrigger>
        <DropdownMenuContent
          class="w-[--reka-dropdown-menu-trigger-width] min-w-56 rounded-lg"
          align="start"
          :side="isMobile ? 'bottom' : 'right'"
          :side-offset="4"
        >
          <DropdownMenuLabel class="text-xs text-muted-foreground">
            Workspaces
          </DropdownMenuLabel>
          <DropdownMenuItem
            v-for="team in teams"
            :key="team.id"
            class="gap-2 p-2"
            @click="selectTeam(team)"
          >
            <div class="flex size-6 items-center justify-center rounded-sm border bg-sidebar-primary text-sidebar-primary-foreground text-xs font-semibold shrink-0">
              <component v-if="team.logo" :is="team.logo" class="size-3.5 shrink-0" />
              <span v-else>{{ initials(team.name) }}</span>
            </div>
            <div class="grid flex-1 text-left text-sm leading-tight">
              <span class="truncate font-medium">{{ team.name }}</span>
              <span v-if="team.role" class="truncate text-xs text-muted-foreground capitalize">{{ team.role }}</span>
            </div>
          </DropdownMenuItem>
        </DropdownMenuContent>
      </DropdownMenu>
    </SidebarMenuItem>
  </SidebarMenu>
</template>
