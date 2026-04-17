import { defineConfig } from 'vitepress'

export default defineConfig({
  base: '/laravel-ai-evaluation/',
  title: 'Laravel AI Evaluation',
  description: 'Real-call LLM evals for Laravel AI',
  themeConfig: {
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
          { text: 'When to run evals', link: '/when-to-run-evals' },
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
