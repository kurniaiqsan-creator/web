/*!
 * Visi — global front-end helpers.
 * - window.BASE_URL: dirinjeksikan dari PHP (variabel global di layout).
 * - base_url(path): membentuk URL absolut dengan prefix BASE_URL.
 * - showToast(message, type): tampilkan toast ringan di pojok kanan atas.
 */

(function () {
  if (typeof window.base_url !== 'function') {
    window.base_url = function (path) {
      path = String(path || '')
      var base = window.BASE_URL || ''
      return base + '/' + path.replace(/^\/+/, '')
    }
  }

  if (typeof window.showToast !== 'function') {
    window.showToast = function (message, type) {
      type = type || 'success'
      var container = document.getElementById('visiToastContainer')
      if (!container) {
        container = document.createElement('div')
        container.id = 'visiToastContainer'
        container.className = 'visi-toast-container'
        document.body.appendChild(container)
      }
      var colors = {
        success: 'bg-success text-white',
        error: 'bg-danger text-white',
        warning: 'bg-warning text-dark',
        info: 'bg-info text-white'
      }
      var toast = document.createElement('div')
      toast.className = 'toast show align-items-center border-0 ' + (colors[type] || colors.success)
      toast.setAttribute('role', 'alert')
      toast.innerHTML =
        '<div class="d-flex">' +
        '<div class="toast-body">' + String(message) + '</div>' +
        '<button type="button" class="btn-close btn-close-white me-2 m-auto" aria-label="Close"></button>' +
        '</div>'
      container.appendChild(toast)
      toast.querySelector('.btn-close').addEventListener('click', function () {
        toast.remove()
      })
      setTimeout(function () {
        toast.classList.remove('show')
        setTimeout(function () { toast.remove() }, 300)
      }, 3500)
    }
  }
})()
