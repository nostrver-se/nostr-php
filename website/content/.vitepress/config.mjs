import { defineConfig } from 'vitepress'

// https://vitepress.dev/reference/site-config
export default defineConfig({
  title: "Nostr-PHP",
  description: "A PHP helper library for Nostr",
  themeConfig: {
    // https://vitepress.dev/reference/default-theme-config
    logo: '/assets/nostr-php_hero-splash.png',
    nav: [
      { text: 'Home', link: '/' },
      { text: 'Guides', link: '/guides/get-started' },
      { text: 'phpDoc', link: 'https://phpdoc.nostr-php.dev' },
      { text: 'Fork me', link: 'https://github.com/nostrver-se/nostr-php/fork' }
    ],

    sidebar: [
      {
        text: 'Guides',
        items: [
          { text: 'Get started', link: '/guides/get-started' },
          { text: 'Generate keys', link: '/guides/generate-keys' },
          { text: 'Publish events', link: '/guides/publish-events' },
          { text: 'Read events', link: '/guides/read-events' }
        ]
      }
    ],

    socialLinks: [
      { icon: 'github', link: 'https://github.com/nostrver-se/nostr-php' }
    ],

    editLink: {
      pattern: 'https://github.com/nostrver-se/nostr-php/tree/main/website/content/:path'
    }
  },
  cleanUrls: true
})
