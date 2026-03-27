import { onUnmounted, ref } from 'vue'

type Options = {
  /** Tempo com o botão “preso” visível após sair da linha (pós-copiar) */
  leaveDelayMs?: number
  /** Tempo que o ícone de check aparece após copiar */
  copiedFeedbackMs?: number
}

async function writeClipboardText(value: string): Promise<void> {
  try {
    await navigator.clipboard.writeText(value)
  } catch {
    const ta = document.createElement('textarea')
    ta.value = value
    ta.setAttribute('readonly', '')
    ta.style.position = 'fixed'
    ta.style.left = '-9999px'
    document.body.appendChild(ta)
    ta.select()
    document.execCommand('copy')
    document.body.removeChild(ta)
  }
}

/**
 * Copiar linha + após copiar, ao sair com o mouse, manter o botão visível por `leaveDelayMs`.
 */
export function useCopyRowStickyReveal(options: Options = {}) {
  const leaveDelayMs = options.leaveDelayMs ?? 1000
  const copiedFeedbackMs = options.copiedFeedbackMs ?? 2000

  const copiedRowId = ref<number | null>(null)
  /** Linha que copiou e ainda pode ganhar o “sticky” ao sair */
  const stickyEligibleRowId = ref<number | null>(null)
  /** Linha com botão forçado visível (durante o delay após mouseleave) */
  const stickyRowId = ref<number | null>(null)
  const leaveTimers = new Map<number, ReturnType<typeof setTimeout>>()

  function clearLeaveTimer(rowId: number) {
    const t = leaveTimers.get(rowId)
    if (t === undefined) {
      return
    }
    clearTimeout(t)
    leaveTimers.delete(rowId)
  }

  function onRowEnter(rowId: number) {
    clearLeaveTimer(rowId)
    if (stickyRowId.value === rowId) {
      stickyRowId.value = null
    }
  }

  function onRowLeave(rowId: number) {
    if (stickyEligibleRowId.value !== rowId) {
      return
    }
    stickyRowId.value = rowId
    clearLeaveTimer(rowId)
    leaveTimers.set(
      rowId,
      window.setTimeout(() => {
        stickyRowId.value = null
        stickyEligibleRowId.value = null
        leaveTimers.delete(rowId)
      }, leaveDelayMs),
    )
  }

  async function copyRow(text: string, rowId: number) {
    const value = text.trim()
    if (!value) {
      return
    }

    await writeClipboardText(value)

    copiedRowId.value = rowId
    stickyEligibleRowId.value = rowId
    window.setTimeout(() => {
      copiedRowId.value = null
    }, copiedFeedbackMs)
  }

  onUnmounted(() => {
    for (const t of leaveTimers.values()) {
      clearTimeout(t)
    }
    leaveTimers.clear()
  })

  return {
    copiedRowId,
    stickyRowId,
    onRowEnter,
    onRowLeave,
    copyRow,
  }
}
