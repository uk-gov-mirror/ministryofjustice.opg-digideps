import { nodeListForEach } from '../main.js'

class ButtonToggler {
  init (toggle) {
    toggle.addEventListener('click', () => this.toggleButtonDisabled())
  }

  toggleButtonDisabled () {
    const $buttons = document.querySelectorAll('[data-module="opg-toggleable-button"]')

    nodeListForEach($buttons, function ($el) {
      const disabled = !$el.disabled

      $el.disabled = disabled
      $el.setAttribute('aria-disabled', disabled.toString())
      $el.classList.toggle('govuk-button--disabled')
    })
    // buttons.forEach(button => {
    //   const disabled = !button.disabled
    //
    //   button.disabled = disabled
    //   button.setAttribute('aria-disabled', disabled.toString())
    //   button.classList.toggle('govuk-button--disabled')
    // })
  }
}

export { ButtonToggler }
