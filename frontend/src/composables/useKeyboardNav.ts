import { onMounted, onUnmounted } from 'vue'

export interface KeyboardNavOptions {
  onEscape?: () => void
  onEnter?: () => void
  onSpace?: () => void
  onArrowUp?: () => void
  onArrowDown?: () => void
  onArrowLeft?: () => void
  onArrowRight?: () => void
  onTab?: () => void
  onHome?: () => void
  onEnd?: () => void
}

export function useKeyboardNav(options: KeyboardNavOptions) {
  const handleKeydown = (event: KeyboardEvent) => {
    const target = event.target as HTMLElement
    if (target && (target.tagName === 'INPUT' || target.tagName === 'TEXTAREA' || target.isContentEditable)) {
      return
    }

    const { key } = event

    switch (key) {
      case 'Escape':
        if (options.onEscape) {
          options.onEscape()
          event.preventDefault()
        }
        break

      case 'Enter':
        if (options.onEnter) {
          options.onEnter()
          event.preventDefault()
        }
        break

      case ' ':
        if (options.onSpace) {
          options.onSpace()
          event.preventDefault()
        }
        break

      case 'ArrowUp':
        if (options.onArrowUp) {
          options.onArrowUp()
          event.preventDefault()
        }
        break

      case 'ArrowDown':
        if (options.onArrowDown) {
          options.onArrowDown()
          event.preventDefault()
        }
        break

      case 'ArrowLeft':
        if (options.onArrowLeft) {
          options.onArrowLeft()
          event.preventDefault()
        }
        break

      case 'ArrowRight':
        if (options.onArrowRight) {
          options.onArrowRight()
          event.preventDefault()
        }
        break

      case 'Tab':
        if (options.onTab) {
          options.onTab()
        }
        break

      case 'Home':
        if (options.onHome) {
          options.onHome()
          event.preventDefault()
        }
        break

      case 'End':
        if (options.onEnd) {
          options.onEnd()
          event.preventDefault()
        }
        break
    }
  }

  onMounted(() => {
    document.addEventListener('keydown', handleKeydown)
  })

  onUnmounted(() => {
    document.removeEventListener('keydown', handleKeydown)
  })

  return {
    handleKeydown
  }
}
