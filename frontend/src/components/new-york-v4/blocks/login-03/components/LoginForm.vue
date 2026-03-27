<script setup lang="ts">
import { onMounted, type HTMLAttributes } from "vue"
import { cn } from "@/lib/utils"
import { Button } from '@/components/ui/button'
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from '@/components/ui/card'
import {
  Field,
  FieldDescription,
  FieldGroup,
  FieldLabel,
  FieldSeparator,
} from '@/components/ui/field'
import { Input } from '@/components/ui/input'
import { userService } from "@/services/userService"
import { getCsrfCookie } from "@/services/http";

const props = defineProps<{
  class?: HTMLAttributes["class"]
}>()

function redirectToLogin() {
  window.location.href = import.meta.env.VITE_API_URL + '/auth/huggy'
}

onMounted(async () => {
  getCsrfCookie();
})
</script>

<template>
  <div :class="cn('flex flex-col gap-6', props.class)">
    <Card>
      <CardHeader class="text-center">
        <CardTitle class="text-xl">
          Welcome back
        </CardTitle>
        <CardDescription>
          Login with your Huggy account
        </CardDescription>
      </CardHeader>
      <CardContent>
        <form>
          <FieldGroup>
            <Field>
              <Button @click="redirectToLogin" variant="outline" type="button">
                Login with Huggy
              </Button>
            </Field>
          </FieldGroup>
        </form>
      </CardContent>
    </Card>
    <FieldDescription class="px-6 text-center">
      By clicking continue, you agree to our <a href="#">Terms of Service</a>
      and <a href="#">Privacy Policy</a>.
    </FieldDescription>
  </div>
</template>
