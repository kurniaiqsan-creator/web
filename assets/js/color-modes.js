/*!
 * Color mode toggler for Visi (adapted from CoreUI's free admin template).
 * Licensed under the Creative Commons Attribution 3.0 Unported License.
 *
 * Modes: 'light' | 'dark' | 'auto'.
 * Theme diset via [data-coreui-theme] di <html>, preferensi disimpan di localStorage.
 */

(() => {
  const THEME_KEY = 'visi-theme'

  const getStoredTheme = () => localStorage.getItem(THEME_KEY)
  const setStoredTheme = theme => localStorage.setItem(THEME_KEY, theme)

  const getPreferredTheme = () => {
    const stored = getStoredTheme()
    if (stored) {
      return stored
    }
    return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light'
  }

  const setTheme = theme => {
    const resolved =
      theme === 'auto'
        ? (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light')
        : theme
    document.documentElement.setAttribute('data-coreui-theme', resolved)
    document.documentElement.dispatchEvent(new Event('ColorSchemeChange'))
  }

  setTheme(getPreferredTheme())

  const showActiveTheme = theme => {
    const activeThemeIcon = document.querySelector('.theme-icon-active')
    const btnToActive = document.querySelector(`[data-coreui-theme-value="${theme}"]`)
    if (!btnToActive) {
      return
    }
    for (const el of document.querySelectorAll('[data-coreui-theme-value]')) {
      el.classList.remove('active')
    }
    btnToActive.classList.add('active')

    if (activeThemeIcon) {
      const iconClass = btnToActive.getAttribute('data-icon-class')
      if (iconClass) {
        activeThemeIcon.className = 'theme-icon-active ' + iconClass
      }
    }
  }

  window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
    const stored = getStoredTheme()
    if (stored !== 'light' && stored !== 'dark') {
      setTheme(getPreferredTheme())
    }
  })

  window.addEventListener('DOMContentLoaded', () => {
    const stored = getStoredTheme() || 'auto'
    showActiveTheme(stored)

    for (const toggle of document.querySelectorAll('[data-coreui-theme-value]')) {
      toggle.addEventListener('click', () => {
        const theme = toggle.getAttribute('data-coreui-theme-value')
        setStoredTheme(theme)
        setTheme(theme)
        showActiveTheme(theme)
      })
    }
  })
})()
