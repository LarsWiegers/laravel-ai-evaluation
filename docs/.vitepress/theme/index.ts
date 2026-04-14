import DefaultTheme from 'vitepress/theme'
import { inBrowser, onContentUpdated } from 'vitepress'

const TAB_MODE_KEY = 'laravel-ai-evaluation-docs-mode'
const SYNC_TITLES = new Set(['Pest', 'Standalone'])

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

export default {
  extends: DefaultTheme,
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
