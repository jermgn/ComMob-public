import { ref, Ref, nextTick } from 'vue'

export function useFocusManagement() {
  const focusedIndex = ref(0)
  const focusableElements = ref<HTMLElement[]>([])

  function setFocusableElements(elements: HTMLElement[]) {
    focusableElements.value = elements
  }

  function focusElement(index: number) {
    if (index >= 0 && index < focusableElements.value.length) {
      focusedIndex.value = index
      focusableElements.value[index]?.focus()
    }
  }

  function focusFirst() {
    focusElement(0)
  }

  function focusLast() {
    focusElement(focusableElements.value.length - 1)
  }

  function focusNext() {
    const nextIndex = (focusedIndex.value + 1) % focusableElements.value.length
    focusElement(nextIndex)
  }

  function focusPrevious() {
    const prevIndex =
      focusedIndex.value === 0
        ? focusableElements.value.length - 1
        : focusedIndex.value - 1
    focusElement(prevIndex)
  }

  function trapFocus(containerRef: Ref<HTMLElement | null>) {
    nextTick(() => {
      if (!containerRef.value) return

      const focusable = containerRef.value.querySelectorAll<HTMLElement>(
        'button:not(:disabled), [href], input:not(:disabled), select:not(:disabled), textarea:not(:disabled), [tabindex]:not([tabindex="-1"])'
      )

      setFocusableElements(Array.from(focusable))

      if (focusable.length > 0) {
        focusFirst()
      }
    })
  }

  function getFocusableElements(container: HTMLElement): HTMLElement[] {
    const selector =
      'button:not(:disabled), [href], input:not(:disabled), select:not(:disabled), textarea:not(:disabled), [tabindex]:not([tabindex="-1"])'

    return Array.from(container.querySelectorAll<HTMLElement>(selector))
  }

  function restoreFocus(previousElement: HTMLElement | null) {
    if (previousElement && document.contains(previousElement)) {
      previousElement.focus()
    }
  }

  return {
    focusedIndex,
    focusableElements,
    setFocusableElements,
    focusElement,
    focusFirst,
    focusLast,
    focusNext,
    focusPrevious,
    trapFocus,
    getFocusableElements,
    restoreFocus
  }
}
