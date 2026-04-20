import DefaultTheme from 'vitepress/theme'
import { inBrowser, onContentUpdated, withBase } from 'vitepress'
import { h } from 'vue'
import './custom.css'

const TAB_MODE_KEY = 'laravel-ai-evaluation-docs-mode'
const SYNC_TITLES = new Set(['Pest', 'Standalone', 'Text', 'JSON'])
const FOOTER_SECTIONS = [
  {
    title: 'Get Started',
    links: [
      { text: 'Installation', href: '/installation' },
      { text: 'Create eval files', href: '/creating-evals' },
      { text: 'When to run evals', href: '/when-to-run-evals' },
    ],
  },
  {
    title: 'Run Evals',
    links: [
      { text: 'Run in Pest', href: '/running-in-pest' },
      { text: 'Run standalone', href: '/running-standalone' },
      { text: 'Run in CI', href: '/running-in-ci' },
    ],
  },
  {
    title: 'Expectations',
    links: [
      { text: 'Overview', href: '/expectations' },
      { text: 'Deterministic', href: '/deterministic-expectations' },
      { text: 'LLM-as-judge', href: '/llm-as-judge-expectations' },
    ],
  },
  {
    title: 'Creator',
    links: [
      { text: 'Twitter/X', href: 'https://x.com/larswiegers', external: true },
      { text: 'Website', href: 'https://larswiegers.nl', external: true },
    ],
  },
]

function applySelectedModeToAllGroups() {
  if (!inBrowser) {
    return
  }

  const selectedTitle = window.localStorage.getItem(TAB_MODE_KEY)

  if (!selectedTitle || !SYNC_TITLES.has(selectedTitle)) {
    return
  }

  const groups = document.querySelectorAll<HTMLElement>('.vp-code-group')

  groups.forEach((group) => {
    const labels = Array.from(group.querySelectorAll<HTMLLabelElement>('.tabs label[data-title]'))
    const target = labels.find((label) => label.dataset.title === selectedTitle)

    if (!target) {
      return
    }

    const inputId = target.getAttribute('for')

    if (!inputId) {
      return
    }

    const input = group.querySelector<HTMLInputElement>(`#${CSS.escape(inputId)}`)

    if (input?.checked) {
      return
    }

    target.click()
  })
}

function wireModeSyncListeners() {
  if (!inBrowser) {
    return
  }

  const labels = document.querySelectorAll<HTMLLabelElement>('.vp-code-group .tabs label[data-title]')

  labels.forEach((label) => {
    if (label.dataset.modeSyncBound === '1') {
      return
    }

    label.dataset.modeSyncBound = '1'

    label.addEventListener('click', () => {
      const title = label.dataset.title

      if (!title || !SYNC_TITLES.has(title)) {
        return
      }

      window.localStorage.setItem(TAB_MODE_KEY, title)

      window.requestAnimationFrame(() => {
        applySelectedModeToAllGroups()
      })
    })
  })

  applySelectedModeToAllGroups()
}

function renderFooter() {
  return h('footer', { class: 'lae-footer' }, [
    h('div', { class: 'lae-footer__inner' }, [
      h(
        'div',
        { class: 'lae-footer__grid' },
        FOOTER_SECTIONS.map((section) =>
          h('section', { class: 'lae-footer__section' }, [
            h('h3', { class: 'lae-footer__title' }, section.title),
            h(
              'div',
              { class: 'lae-footer__links' },
              section.links.map((link) =>
                h(
                  'a',
                  {
                    class: 'lae-footer__link',
                    href: link.external ? link.href : withBase(link.href),
                    target: link.external ? '_blank' : undefined,
                    rel: link.external ? 'noreferrer' : undefined,
                  },
                  link.text,
                ),
              ),
            ),
          ]),
        ),
      ),
      h('div', { class: 'lae-footer__meta' }, 'Released under the MIT License. Copyright © Lars Wiegers'),
    ]),
  ])
}

export default {
  extends: DefaultTheme,
  Layout() {
    return h(DefaultTheme.Layout, null, {
      'layout-bottom': () => renderFooter(),
    })
  },
  enhanceApp({ router }) {
    if (!inBrowser) {
      return
    }

    onContentUpdated(() => {
      wireModeSyncListeners()
    })

    router.onAfterRouteChange = () => {
      window.requestAnimationFrame(() => {
        wireModeSyncListeners()
      })
    }
  },
}
