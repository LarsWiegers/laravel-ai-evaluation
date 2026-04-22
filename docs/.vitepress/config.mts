import { defineConfig } from 'vitepress'

export default defineConfig({
  base: '/',
  title: 'Laravel AI Evaluation',
  description: 'Real-call LLM evals for Laravel AI',
  themeConfig: {
    logo: '/box.svg',
    search: {
      provider: 'local',
    },
    nav: [
      { text: 'Home', link: '/' },
      {
        text: 'Pest',
        items: [{ text: 'Run in Pest', link: '/running-in-pest' }],
      },
      {
        text: 'Standalone',
        items: [{ text: 'Run standalone', link: '/running-standalone' }],
      },
      {
        text: 'Expectations',
        items: [
          { text: 'Overview', link: '/expectations' },
          { text: 'Deterministic expectations', link: '/deterministic-expectations' },
          { text: 'LLM-as-judge expectations', link: '/llm-as-judge-expectations' },
        ],
      },
      {
        text: 'Guides',
        items: [
          { text: 'Installation', link: '/installation' },
          { text: 'Create eval files', link: '/creating-evals' },
          { text: 'Dealing with rate limits', link: '/dealing-with-rate-limits' },
          { text: 'When to run evals', link: '/when-to-run-evals' },
          { text: 'Run in CI', link: '/running-in-ci' },
        ],
      },
    ],
    socialLinks: [
      {
        icon: {
          svg: '<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path fill="currentColor" d="M12 2 3 6.5v11L12 22l9-4.5v-11L12 2Zm0 2.2 6.72 3.36L12 10.92 5.28 7.56 12 4.2Zm-7 4.98 6 3v7.3l-6-3v-7.3Zm8 10.3v-7.3l6-3v7.3l-6 3Z"/></svg>',
        },
        link: 'https://packagist.org/packages/LarsWiegers/laravel-ai-evaluation',
      },
    ],
    sidebar: [
      {
        text: 'Getting Started',
        items: [
          { text: 'Installation', link: '/installation' },
          { text: 'Create eval files', link: '/creating-evals' },
          { text: 'Dealing with rate limits', link: '/dealing-with-rate-limits' },
          { text: 'When to run evals', link: '/when-to-run-evals' },
          { text: 'Run in CI', link: '/running-in-ci' },
        ],
      },
      {
        text: 'Pest',
        items: [{ text: 'Run in Pest', link: '/running-in-pest' }],
      },
      {
        text: 'Standalone',
        items: [{ text: 'Run standalone', link: '/running-standalone' }],
      },
      {
        text: 'Expectations',
        items: [
          { text: 'Overview', link: '/expectations' },
          { text: 'Deterministic expectations', link: '/deterministic-expectations' },
          { text: 'LLM-as-judge expectations', link: '/llm-as-judge-expectations' },
        ],
      },
    ],
  },
})
