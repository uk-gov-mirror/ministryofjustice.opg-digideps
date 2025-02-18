import { GoogleAnalyticsEvents } from '../../modules/googleAnalyticsEvents'
import { beforeAll, describe, it, jest } from '@jest/globals'

const globals = (() => {
  window.gtag = jest.fn()

  function gtagWrapper (event, eventName, eventParameters) {
    window.gtag(event, eventName, eventParameters)
  }

  return {
    gtag: gtagWrapper
  }
})()

window.globals = globals

const setDocumentBody = () => {
  document.body.innerHTML = `
        <div>
            <button
              id='button1'
              data-attribute="ga-event"
              data-ga-action="form-submitted"
              data-ga-category="user-journeys"
              data-ga-label="button-clicks"
            >1</button>
            <button
              id='button2'
              data-attribute="ga-event"
              data-ga-action="back-to-report"
              data-ga-category="testing"
              data-ga-label="site-interaction"
            >2</button>
        </div>
    `
}

beforeAll(() => {
  setDocumentBody()
  GoogleAnalyticsEvents.init()
})

describe('googleAnalyticsEvents', () => {
  describe('extractEventInfo', () => {
    it('extracts event action, event_category and event_label from ga-event element', () => {
      const button1 = document.getElementById('button1')

      const actualEventInfo = GoogleAnalyticsEvents.extractEventInfo(button1)

      const expectedEventInfo = {
        action: 'form-submitted',
        event_params: { event_category: 'user-journeys', event_label: 'button-clicks' }
      }

      expect(actualEventInfo).toEqual(expectedEventInfo)
    })
  })

  describe('clicking button', () => {
    describe('when gtag is loaded', () => {
      it('dispatches gtag event', () => {
        document.getElementById('button1').click()
        document.getElementById('button2').click()

        expect(window.gtag).toHaveBeenCalledWith(
          'event',
          'form-submitted',
          { event_category: 'user-journeys', event_label: 'button-clicks' }
        )

        expect(window.gtag).toHaveBeenCalledWith(
          'event',
          'back-to-report',
          { event_category: 'testing', event_label: 'site-interaction' })
      })
    })

    describe('when gtag is not loaded', () => {
      it('does not dispatch gtag event', () => {
        window.globals.gtag = null

        document.getElementById('button1').click()
        document.getElementById('button2').click()

        expect(window.gtag).not.toHaveBeenCalled()
      })
    })
  })
})
