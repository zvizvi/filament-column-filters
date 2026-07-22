@php
    $tooltip = __('filament-column-tools::filters.tooltip.' . $type);
@endphp

<span class="fct-header">
    <span class="fct-header-label">{!! $labelHtml !!}</span>

    <span
        class="fct"
        x-load
        x-load-src="{{ \Filament\Support\Facades\FilamentAsset::getAlpineComponentSrc('filament-column-tools', 'zvizvi/filament-column-tools') }}"
        x-data="filamentColumnTools(@js($config))"
    >
        <span
            x-ref="trigger"
            role="button"
            tabindex="0"
            class="fct-trigger @if ($isActive) fct-trigger--active @endif"
            x-on:click.stop.prevent="toggle"
            x-on:keydown.enter.stop.prevent="toggle"
            x-tooltip="{ content: @js($tooltip), theme: $store.theme }"
        >
            @if ($type === 'search')
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="fct-icon">
                    <path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 1 0 0 11 5.5 5.5 0 0 0 0-11ZM2 9a7 7 0 1 1 12.452 4.391l3.328 3.329a.75.75 0 1 1-1.06 1.06l-3.329-3.328A7 7 0 0 1 2 9Z" clip-rule="evenodd" />
                </svg>
            @elseif ($type === 'date')
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="fct-icon">
                    <path fill-rule="evenodd" d="M5.75 2a.75.75 0 0 1 .75.75V4h7V2.75a.75.75 0 0 1 1.5 0V4h.25A2.75 2.75 0 0 1 18 6.75v8.5A2.75 2.75 0 0 1 15.25 18H4.75A2.75 2.75 0 0 1 2 15.25v-8.5A2.75 2.75 0 0 1 4.75 4H5V2.75A.75.75 0 0 1 5.75 2Zm-1 5.5c-.69 0-1.25.56-1.25 1.25v6.5c0 .69.56 1.25 1.25 1.25h10.5c.69 0 1.25-.56 1.25-1.25v-6.5c0-.69-.56-1.25-1.25-1.25H4.75Z" clip-rule="evenodd" />
                </svg>
            @elseif ($type === 'range')
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="fct-icon">
                    <path fill-rule="evenodd" d="M9.493 2.853a.75.75 0 0 0-1.486-.205L7.545 6H4.198a.75.75 0 0 0 0 1.5h3.14l-.69 5H3.302a.75.75 0 0 0 0 1.5h3.14l-.435 3.148a.75.75 0 0 0 1.486.205L7.955 14h4.986l-.434 3.148a.75.75 0 0 0 1.486.205L14.455 14h3.347a.75.75 0 0 0 0-1.5h-3.14l.69-5h3.346a.75.75 0 0 0 0-1.5h-3.14l.435-3.147a.75.75 0 0 0-1.486-.205L14.045 6H9.059l.434-3.147ZM8.852 7.5l-.69 5h4.986l.69-5H8.852Z" clip-rule="evenodd" />
                </svg>
            @else
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="fct-icon">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 3c2.755 0 5.455.232 8.083.678.533.09.917.556.917 1.096v1.044a2.25 2.25 0 0 1-.659 1.591l-5.432 5.432a2.25 2.25 0 0 0-.659 1.591v2.927a2.25 2.25 0 0 1-1.244 2.013L9.75 21v-6.568a2.25 2.25 0 0 0-.659-1.591L3.659 7.409A2.25 2.25 0 0 1 3 5.818V4.774c0-.54.384-1.006.917-1.096A48.32 48.32 0 0 1 12 3Z" />
                </svg>
            @endif

            <span class="fct-active-dot" aria-hidden="true"></span>
        </span>

        <template x-teleport="body">
            <div
                x-ref="panel"
                x-on:click.outside="open && close()"
                x-on:keydown.escape.window="open && close()"
                x-bind:style="panelStyle"
                x-bind:class="{ 'fct-panel--open': open }"
                class="fct-panel"
            >
                @if ($type === 'search')
                    <div class="fct-section">
                        <input
                            type="text"
                            class="fct-input"
                            x-ref="searchInput"
                            x-model="state.value"
                            x-on:keydown.enter.prevent="apply"
                            placeholder="{{ $config['placeholder'] ?? '' }}"
                        />
                    </div>

                    <div class="fct-footer">
                        <div class="fct-footer-group">
                            <button type="button" class="fct-btn fct-btn--primary" x-on:click="apply">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="fct-btn-icon">
                                    <path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 1 0 0 11 5.5 5.5 0 0 0 0-11ZM2 9a7 7 0 1 1 12.452 4.391l3.328 3.329a.75.75 0 1 1-1.06 1.06l-3.329-3.328A7 7 0 0 1 2 9Z" clip-rule="evenodd" />
                                </svg>
                                {{ __('filament-column-tools::filters.search') }}
                            </button>
                            <button type="button" class="fct-btn" x-on:click="clear">
                                {{ __('filament-column-tools::filters.clear') }}
                            </button>
                        </div>
                        <button type="button" class="fct-link" x-on:click="close">
                            {{ __('filament-column-tools::filters.close') }}
                        </button>
                    </div>
                @elseif ($type === 'date')
                    <div class="fct-section">
                        <p class="fct-section-title">{{ __('filament-column-tools::filters.quick_select') }}</p>

                        <div class="fct-presets">
                            @foreach ($config['presets'] ?? [] as $preset)
                                <button
                                    type="button"
                                    class="fct-chip"
                                    x-bind:class="{ 'fct-chip--active': isPresetActive('{{ $preset }}') }"
                                    x-on:click="applyPreset('{{ $preset }}')"
                                >
                                    {{ __('filament-column-tools::filters.presets.' . $preset) }}
                                </button>
                            @endforeach
                        </div>
                    </div>

                    <div class="fct-section">
                        <p class="fct-section-title">{{ __('filament-column-tools::filters.custom_range') }}</p>

                        <div class="fct-date-range">
                            <label class="fct-field">
                                <span class="fct-field-label">{{ __('filament-column-tools::filters.from_date') }}</span>
                                <input
                                    type="date"
                                    class="fct-input"
                                    x-ref="fromDateInput"
                                    x-model="state.from"
                                    placeholder="{{ __('filament-column-tools::filters.start_date') }}"
                                />
                            </label>
                            <label class="fct-field">
                                <span class="fct-field-label">{{ __('filament-column-tools::filters.until_date') }}</span>
                                <input
                                    type="date"
                                    class="fct-input"
                                    x-model="state.until"
                                    placeholder="{{ __('filament-column-tools::filters.end_date') }}"
                                />
                            </label>
                        </div>
                    </div>

                    <div class="fct-footer">
                        <div class="fct-footer-group">
                            <button type="button" class="fct-btn fct-btn--primary" x-on:click="apply">
                                {{ __('filament-column-tools::filters.apply') }}
                            </button>
                            <button type="button" class="fct-link" x-on:click="close">
                                {{ __('filament-column-tools::filters.close') }}
                            </button>
                        </div>
                        <button type="button" class="fct-btn" x-on:click="clear">
                            {{ __('filament-column-tools::filters.reset') }}
                        </button>
                    </div>
                @elseif ($type === 'range')
                    <div class="fct-section">
                        <div class="fct-date-range">
                            <label class="fct-field">
                                <span class="fct-field-label">{{ __('filament-column-tools::filters.range_from') }}</span>
                                <input
                                    type="number"
                                    class="fct-input"
                                    x-ref="fromRangeInput"
                                    x-model="state.from"
                                    x-on:keydown.enter.prevent="apply"
                                    @if (filled($config['step'] ?? null)) step="{{ $config['step'] }}" @endif
                                    placeholder="{{ __('filament-column-tools::filters.min_placeholder') }}"
                                />
                            </label>
                            <label class="fct-field">
                                <span class="fct-field-label">{{ __('filament-column-tools::filters.range_until') }}</span>
                                <input
                                    type="number"
                                    class="fct-input"
                                    x-model="state.until"
                                    x-on:keydown.enter.prevent="apply"
                                    @if (filled($config['step'] ?? null)) step="{{ $config['step'] }}" @endif
                                    placeholder="{{ __('filament-column-tools::filters.max_placeholder') }}"
                                />
                            </label>
                        </div>
                    </div>

                    <div class="fct-footer">
                        <div class="fct-footer-group">
                            <button type="button" class="fct-btn fct-btn--primary" x-on:click="apply">
                                {{ __('filament-column-tools::filters.apply') }}
                            </button>
                            <button type="button" class="fct-link" x-on:click="close">
                                {{ __('filament-column-tools::filters.close') }}
                            </button>
                        </div>
                        <button type="button" class="fct-btn" x-on:click="clear">
                            {{ __('filament-column-tools::filters.reset') }}
                        </button>
                    </div>
                @else
                    @if ($config['searchable'] ?? false)
                        <div class="fct-section">
                            <input
                                type="text"
                                class="fct-input"
                                x-ref="optionSearchInput"
                                x-model="optionSearch"
                                placeholder="{{ __('filament-column-tools::filters.search_options') }}"
                            />
                        </div>
                    @endif

                    <div class="fct-section">
                        @if (($config['multiple'] ?? true) && count($config['options'] ?? []) > 1)
                            <div class="fct-bulk-actions">
                                <button type="button" class="fct-link" x-on:click="selectAll">
                                    {{ __('filament-column-tools::filters.select_all') }}
                                </button>
                                <button type="button" class="fct-link" x-on:click="deselectAll">
                                    {{ __('filament-column-tools::filters.deselect_all') }}
                                </button>
                            </div>
                        @endif

                        <div class="fct-options" x-ref="optionsList">
                            @forelse ($config['options'] ?? [] as $option)
                                <label class="fct-option" x-show="optionMatches(@js($option['label']))">
                                    @if ($config['multiple'] ?? true)
                                        <input
                                            type="checkbox"
                                            class="fct-checkbox"
                                            value="{{ $option['value'] }}"
                                            x-model="state.values"
                                        />
                                    @else
                                        <input
                                            type="radio"
                                            class="fct-radio"
                                            name="fct-{{ $config['filterName'] }}"
                                            value="{{ $option['value'] }}"
                                            x-model="state.value"
                                        />
                                    @endif
                                    <span>{{ $option['label'] }}</span>
                                </label>
                            @empty
                                <p class="fct-empty">{{ __('filament-column-tools::filters.no_options') }}</p>
                            @endforelse

                            @if (($config['searchable'] ?? false) && ($config['options'] ?? []) !== [])
                                <p class="fct-empty" x-cloak x-show="! hasVisibleOptions">
                                    {{ __('filament-column-tools::filters.no_options') }}
                                </p>
                            @endif
                        </div>
                    </div>

                    <div class="fct-footer">
                        <div class="fct-footer-group">
                            <button type="button" class="fct-btn fct-btn--primary" x-on:click="apply">
                                {{ __('filament-column-tools::filters.apply') }}
                            </button>
                            <button type="button" class="fct-btn" x-on:click="clear">
                                {{ __('filament-column-tools::filters.reset') }}
                            </button>
                        </div>
                        <button type="button" class="fct-link" x-on:click="close">
                            {{ __('filament-column-tools::filters.close') }}
                        </button>
                    </div>
                @endif
            </div>
        </template>
    </span>
</span>
