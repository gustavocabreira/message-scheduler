<template>
  <div>
    <p>Autenticando...</p>
  </div>
</template>

<script setup lang="ts">
import { onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { getCsrfCookie } from '@/services/http';
import { useUserStore } from '@/stores/userStore';

const router = useRouter();
const userStore = useUserStore();

onMounted(async () => {
  await getCsrfCookie();
  const res = await userStore.fetchUser();

  if (res.ok) {
    router.push({ name: 'Dashboard' });
  } else {
    console.error('Falha na autenticação.');
    router.push({ name: 'Login' });
  }
});
</script>
