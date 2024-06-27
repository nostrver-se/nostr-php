<script setup>
import DefaultTheme from 'vitepress/theme'

const { Layout } = DefaultTheme
</script>

<template>
  <Layout>
    <template #layout-bottom>
      <footer class="text-center">
        Released under the MIT License. Supported by OpenSats.
        <br />
        <a href="https://github.com/nostrver-se/nostr-php/tree/main/website)" target="_blank">Source Code</a>
      </footer>
    </template>
  </Layout>
</template>