import { defineConfig } from 'vitepress'

export default defineConfig({
  title: 'Laravel AI Evaluation',
  description: 'Real-call LLM evals for Laravel AI',
  themeConfig: {
    search: {
      provider: 'local',
    },
    nav: [
      { text: 'Overview', link: '/' },
      {
        text: 'Guides',
        items: [
          { text: 'When to run evals', link: '/when-to-run-evals' },
          { text: 'Expectations', link: '/expectations' },
          { text: 'Run in Pest', link: '/running-in-pest' },
          { text: 'Run standalone', link: '/running-standalone' },
          { text: 'Run in CI', link: '/running-in-ci' },
        ],
      },
    ],
    sidebar: [
      {
        text: 'Getting Started',
        items: [
          { text: 'Overview', link: '/' },
          { text: 'When to run evals', link: '/when-to-run-evals' },
          { text: 'Expectations', link: '/expectations' },
          { text: 'Run in Pest', link: '/running-in-pest' },
          { text: 'Run standalone', link: '/running-standalone' },
          { text: 'Run in CI', link: '/running-in-ci' },
        ],
      },
    ],
  },
})
