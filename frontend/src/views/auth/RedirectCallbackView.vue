<template>
  <div>
    <p>Autenticando...</p>
  </div>
</template>

<script setup lang="ts">
import { onMounted } from 'vue';
import { useRouter } from 'vue-router';

const router = useRouter();

onMounted(async () => {
  const params = new URLSearchParams(window.location.search);
  const token = params.get('token');
  if (token) {
    router.push({ name: 'Dashboard' });
  } else {
    console.error('Falha na autenticação: Nenhum token foi fornecido.');
    router.push({ name: 'Login' });
  }
});
</script>
