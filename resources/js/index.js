function pad(number) {
    return String(number).padStart(2, '0')
}

function formatDate(date) {
    return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}`
}

function startOfWeek(date, weekStartsOn) {
    const result = new Date(date)
    const diff = (result.getDay() - weekStartsOn + 7) % 7
    result.setDate(result.getDate() - diff)

    return result
}

function addDays(date, days) {
    const result = new Date(date)
    result.setDate(result.getDate() + days)

    return result
}

export function presetRange(preset, weekStartsOn = 0) {
    const today = new Date()
    today.setHours(0, 0, 0, 0)

    switch (preset) {
        case 'today':
            return [today, today]
        case 'yesterday': {
            const yesterday = addDays(today, -1)

            return [yesterday, yesterday]
        }
        case 'this_week': {
            const start = startOfWeek(today, weekStartsOn)

            return [start, addDays(start, 6)]
        }
        case 'last_week': {
            const start = addDays(startOfWeek(today, weekStartsOn), -7)

            return [start, addDays(start, 6)]
        }
        case 'this_month': {
            const start = new Date(today.getFullYear(), today.getMonth(), 1)

            return [start, new Date(today.getFullYear(), today.getMonth() + 1, 0)]
        }
        case 'last_month': {
            const start = new Date(today.getFullYear(), today.getMonth() - 1, 1)

            return [start, new Date(today.getFullYear(), today.getMonth(), 0)]
        }
        case 'last_7_days':
            return [addDays(today, -6), today]
        case 'last_30_days':
            return [addDays(today, -29), today]
        case 'this_year': {
            const start = new Date(today.getFullYear(), 0, 1)

            return [start, new Date(today.getFullYear(), 11, 31)]
        }
        case 'last_year': {
            const start = new Date(today.getFullYear() - 1, 0, 1)

            return [start, new Date(today.getFullYear() - 1, 11, 31)]
        }
        default:
            return [null, null]
    }
}

// Only one popup may be open at a time, across all columns and tables on the
// page — opening one closes the currently open one.
let openInstance = null

export default function filamentColumnTools(config) {
    return {
        open: false,

        state: {},

        optionSearch: '',

        panelStyle: {},

        init() {
            this.resetLocalState()
        },

        get wirePath() {
            return `tableFilters.${config.filterName}`
        },

        currentWireState() {
            let current = null

            try {
                current = this.$wire.get(this.wirePath)
            } catch (error) {
                current = null
            }

            return current && typeof current === 'object' ? current : {}
        },

        resetLocalState() {
            const current = this.currentWireState()

            if (config.type === 'search') {
                this.state = {
                    value: current[config.fields.value] ?? '',
                }
            } else if (config.type === 'date') {
                this.state = {
                    from: current[config.fields.from] ?? null,
                    until: current[config.fields.until] ?? null,
                }
            } else if (config.type === 'select') {
                if (config.multiple) {
                    const values = current[config.fields.value]

                    this.state = {
                        values: Array.isArray(values) ? values.map(String) : [],
                    }
                } else {
                    const value = current[config.fields.value]

                    this.state = {
                        value: value === null || value === undefined || value === '' ? null : String(value),
                    }
                }
            }
        },

        toggle() {
            this.open ? this.close() : this.openPanel()
        },

        openPanel() {
            if (openInstance && openInstance !== this) {
                openInstance.close()
            }

            openInstance = this

            this.resetLocalState()
            this.optionSearch = ''
            this.open = true

            this.$nextTick(() => {
                this.position()

                // The positioning style lands asynchronously, so prevent the
                // focus from scrolling the page toward the panel's
                // pre-positioned location.
                if (config.type === 'search' && this.$refs.searchInput) {
                    this.$refs.searchInput.focus({ preventScroll: true })
                } else if (config.type === 'select' && this.$refs.optionSearchInput) {
                    this.$refs.optionSearchInput.focus({ preventScroll: true })
                }
            })
        },

        close() {
            if (openInstance === this) {
                openInstance = null
            }

            this.open = false
        },

        optionMatches(label) {
            const search = this.optionSearch.trim().toLowerCase()

            return search === '' || String(label).toLowerCase().includes(search)
        },

        get hasVisibleOptions() {
            return (config.options ?? []).some((option) => this.optionMatches(option.label))
        },

        position() {
            const trigger = this.$refs.trigger

            if (! trigger) {
                return
            }

            const rect = trigger.getBoundingClientRect()
            const panel = this.$refs.panel
            const panelWidth = panel ? panel.offsetWidth : 288
            const panelHeight = panel ? panel.offsetHeight : 200
            const margin = 8

            let left = rect.left + rect.width / 2 - panelWidth / 2
            left = Math.min(Math.max(margin, left), window.innerWidth - panelWidth - margin)

            let top = rect.bottom + 6

            if (top + panelHeight > window.innerHeight - margin) {
                top = Math.max(margin, rect.top - panelHeight - 6)
            }

            this.panelStyle = {
                position: 'fixed',
                top: `${top}px`,
                left: `${left}px`,
            }
        },

        stateForWire() {
            const current = this.currentWireState()
            const next = { ...current }

            if (config.type === 'search') {
                next[config.fields.value] = this.state.value?.trim?.() ? this.state.value.trim() : null
            } else if (config.type === 'date') {
                next[config.fields.from] = this.state.from || null
                next[config.fields.until] = this.state.until || null
            } else if (config.type === 'select') {
                next[config.fields.value] = config.multiple
                    ? [...(this.state.values ?? [])]
                    : (this.state.value ?? null)
            }

            return next
        },

        apply() {
            this.close()

            // A live set triggers Livewire's `updatedTableFilters` hook, which
            // syncs deferred filter state and resets pagination.
            this.$wire.set(this.wirePath, this.stateForWire())
        },

        clear() {
            if (config.type === 'search') {
                this.state = { value: '' }
            } else if (config.type === 'date') {
                this.state = { from: null, until: null }
            } else if (config.type === 'select') {
                this.state = config.multiple ? { values: [] } : { value: null }
            }

            this.apply()
        },

        applyPreset(preset) {
            const [from, until] = presetRange(preset, config.weekStartsOn ?? 0)

            this.state.from = from ? formatDate(from) : null
            this.state.until = until ? formatDate(until) : null
        },

        isPresetActive(preset) {
            const [from, until] = presetRange(preset, config.weekStartsOn ?? 0)

            return Boolean(
                from
                && until
                && this.state.from === formatDate(from)
                && this.state.until === formatDate(until),
            )
        },
    }
}
