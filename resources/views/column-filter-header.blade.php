@php
    $tooltip = __('filament-column-filters::filters.tooltip.' . $type);
@endphp

<span class="fcf-header">
    <span class="fcf-header-label">{!! $labelHtml !!}</span>

    <span
        class="fcf"
        x-load
        x-load-src="{{ \Filament\Support\Facades\FilamentAsset::getAlpineComponentSrc('filament-column-filters', 'zvizvi/filament-column-filters') }}"
        x-data="filamentColumnFilters(@js($config))"
    >
        <span
            x-ref="trigger"
            role="button"
            tabindex="0"
            class="fcf-trigger @if ($isActive) fcf-trigger--active @endif"
            x-on:click.stop.prevent="toggle"
            x-on:keydown.enter.stop.prevent="toggle"
            x-tooltip="{ content: @js($tooltip), theme: $store.theme }"
        >
            @if ($type === 'search')
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="fcf-icon">
                    <path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 1 0 0 11 5.5 5.5 0 0 0 0-11ZM2 9a7 7 0 1 1 12.452 4.391l3.328 3.329a.75.75 0 1 1-1.06 1.06l-3.329-3.328A7 7 0 0 1 2 9Z" clip-rule="evenodd" />
                </svg>
            @elseif ($type === 'date')
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="fcf-icon">
                    <path fill-rule="evenodd" d="M5.75 2a.75.75 0 0 1 .75.75V4h7V2.75a.75.75 0 0 1 1.5 0V4h.25A2.75 2.75 0 0 1 18 6.75v8.5A2.75 2.75 0 0 1 15.25 18H4.75A2.75 2.75 0 0 1 2 15.25v-8.5A2.75 2.75 0 0 1 4.75 4H5V2.75A.75.75 0 0 1 5.75 2Zm-1 5.5c-.69 0-1.25.56-1.25 1.25v6.5c0 .69.56 1.25 1.25 1.25h10.5c.69 0 1.25-.56 1.25-1.25v-6.5c0-.69-.56-1.25-1.25-1.25H4.75Z" clip-rule="evenodd" />
                </svg>
            @else
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="fcf-icon">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 3c2.755 0 5.455.232 8.083.678.533.09.917.556.917 1.096v1.044a2.25 2.25 0 0 1-.659 1.591l-5.432 5.432a2.25 2.25 0 0 0-.659 1.591v2.927a2.25 2.25 0 0 1-1.244 2.013L9.75 21v-6.568a2.25 2.25 0 0 0-.659-1.591L3.659 7.409A2.25 2.25 0 0 1 3 5.818V4.774c0-.54.384-1.006.917-1.096A48.32 48.32 0 0 1 12 3Z" />
                </svg>
            @endif

            <span class="fcf-active-dot" aria-hidden="true"></span>
        </span>

        <template x-teleport="body">
            <div
                x-ref="panel"
                x-on:click.outside="open && close()"
                x-on:keydown.escape.window="open && close()"
                x-bind:style="panelStyle"
                x-bind:class="{ 'fcf-panel--open': open }"
                class="fcf-panel"
            >
                @if ($type === 'search')
                    <div class="fcf-section">
                        <input
                            type="text"
                            class="fcf-input"
                            x-ref="searchInput"
                            x-model="state.value"
                            x-on:keydown.enter.prevent="apply"
                            placeholder="{{ $config['placeholder'] ?? '' }}"
                        />
                    </div>

                    <div class="fcf-footer">
                        <div class="fcf-footer-group">
                            <button type="button" class="fcf-btn fcf-btn--primary" x-on:click="apply">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="fcf-btn-icon">
                                    <path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 1 0 0 11 5.5 5.5 0 0 0 0-11ZM2 9a7 7 0 1 1 12.452 4.391l3.328 3.329a.75.75 0 1 1-1.06 1.06l-3.329-3.328A7 7 0 0 1 2 9Z" clip-rule="evenodd" />
                                </svg>
                                {{ __('filament-column-filters::filters.search') }}
                            </button>
                            <button type="button" class="fcf-btn" x-on:click="clear">
                                {{ __('filament-column-filters::filters.clear') }}
                            </button>
                        </div>
                        <button type="button" class="fcf-link" x-on:click="close">
                            {{ __('filament-column-filters::filters.close') }}
                        </button>
                    </div>
                @elseif ($type === 'date')
                    <div class="fcf-section">
                        <p class="fcf-section-title">{{ __('filament-column-filters::filters.quick_select') }}</p>

                        <div class="fcf-presets">
                            @foreach ($config['presets'] ?? [] as $preset)
                                <button
                                    type="button"
                                    class="fcf-chip"
                                    x-bind:class="{ 'fcf-chip--active': isPresetActive('{{ $preset }}') }"
                                    x-on:click="applyPreset('{{ $preset }}')"
                                >
                                    {{ __('filament-column-filters::filters.presets.' . $preset) }}
                                </button>
                            @endforeach
                        </div>
                    </div>

                    <div class="fcf-section">
                        <p class="fcf-section-title">{{ __('filament-column-filters::filters.custom_range') }}</p>

                        <div class="fcf-date-range">
                            <label class="fcf-field">
                                <span class="fcf-field-label">{{ __('filament-column-filters::filters.from_date') }}</span>
                                <input
                                    type="date"
                                    class="fcf-input"
                                    x-ref="fromDateInput"
                                    x-model="state.from"
                                    placeholder="{{ __('filament-column-filters::filters.start_date') }}"
                                />
                            </label>
                            <label class="fcf-field">
                                <span class="fcf-field-label">{{ __('filament-column-filters::filters.until_date') }}</span>
                                <input
                                    type="date"
                                    class="fcf-input"
                                    x-model="state.until"
                                    placeholder="{{ __('filament-column-filters::filters.end_date') }}"
                                />
                            </label>
                        </div>
                    </div>

                    <div class="fcf-footer">
                        <div class="fcf-footer-group">
                            <button type="button" class="fcf-btn fcf-btn--primary" x-on:click="apply">
                                {{ __('filament-column-filters::filters.apply') }}
                            </button>
                            <button type="button" class="fcf-link" x-on:click="close">
                                {{ __('filament-column-filters::filters.close') }}
                            </button>
                        </div>
                        <button type="button" class="fcf-btn" x-on:click="clear">
                            {{ __('filament-column-filters::filters.reset') }}
                        </button>
                    </div>
                @elseif ($type === 'range')
                    <div class="fcf-section">
                        <div class="fcf-date-range">
                            <label class="fcf-field">
                                <span class="fcf-field-label">{{ __('filament-column-filters::filters.range_from') }}</span>
                                <input
                                    type="number"
                                    class="fcf-input"
                                    x-ref="fromRangeInput"
                                    x-model="state.from"
                                    x-on:keydown.enter.prevent="apply"
                                    @if (filled($config['step'] ?? null)) step="{{ $config['step'] }}" @endif
                                    placeholder="{{ __('filament-column-filters::filters.min_placeholder') }}"
                                />
                            </label>
                            <label class="fcf-field">
                                <span class="fcf-field-label">{{ __('filament-column-filters::filters.range_until') }}</span>
                                <input
                                    type="number"
                                    class="fcf-input"
                                    x-model="state.until"
                                    x-on:keydown.enter.prevent="apply"
                                    @if (filled($config['step'] ?? null)) step="{{ $config['step'] }}" @endif
                                    placeholder="{{ __('filament-column-filters::filters.max_placeholder') }}"
                                />
                            </label>
                        </div>
                    </div>

                    <div class="fcf-footer">
                        <div class="fcf-footer-group">
                            <button type="button" class="fcf-btn fcf-btn--primary" x-on:click="apply">
                                {{ __('filament-column-filters::filters.apply') }}
                            </button>
                            <button type="button" class="fcf-link" x-on:click="close">
                                {{ __('filament-column-filters::filters.close') }}
                            </button>
                        </div>
                        <button type="button" class="fcf-btn" x-on:click="clear">
                            {{ __('filament-column-filters::filters.reset') }}
                        </button>
                    </div>
                @else
                    @if ($config['searchable'] ?? false)
                        <div class="fcf-section">
                            <input
                                type="text"
                                class="fcf-input"
                                x-ref="optionSearchInput"
                                x-model="optionSearch"
                                placeholder="{{ __('filament-column-filters::filters.search_options') }}"
                            />
                        </div>
                    @endif

                    <div class="fcf-section">
                        @if (($config['multiple'] ?? true) && count($config['options'] ?? []) > 1)
                            <div class="fcf-bulk-actions">
                                <button type="button" class="fcf-link" x-on:click="selectAll">
                                    {{ __('filament-column-filters::filters.select_all') }}
                                </button>
                                <button type="button" class="fcf-link" x-on:click="deselectAll">
                                    {{ __('filament-column-filters::filters.deselect_all') }}
                                </button>
                            </div>
                        @endif

                        <div class="fcf-options" x-ref="optionsList">
                            @forelse ($config['options'] ?? [] as $option)
                                <label class="fcf-option" x-show="optionMatches(@js($option['label']))">
                                    @if ($config['multiple'] ?? true)
                                        <input
                                            type="checkbox"
                                            class="fcf-checkbox"
                                            value="{{ $option['value'] }}"
                                            x-model="state.values"
                                        />
                                    @else
                                        <input
                                            type="radio"
                                            class="fcf-radio"
                                            name="fcf-{{ $config['filterName'] }}"
                                            value="{{ $option['value'] }}"
                                            x-model="state.value"
                                        />
                                    @endif
                                    <span>{{ $option['label'] }}</span>
                                </label>
                            @empty
                                <p class="fcf-empty">{{ __('filament-column-filters::filters.no_options') }}</p>
                            @endforelse

                            @if (($config['searchable'] ?? false) && ($config['options'] ?? []) !== [])
                                <p class="fcf-empty" x-cloak x-show="! hasVisibleOptions">
                                    {{ __('filament-column-filters::filters.no_options') }}
                                </p>
                            @endif
                        </div>
                    </div>

                    <div class="fcf-footer">
                        <div class="fcf-footer-group">
                            <button type="button" class="fcf-btn fcf-btn--primary" x-on:click="apply">
                                {{ __('filament-column-filters::filters.apply') }}
                            </button>
                            <button type="button" class="fcf-btn" x-on:click="clear">
                                {{ __('filament-column-filters::filters.reset') }}
                            </button>
                        </div>
                        <button type="button" class="fcf-link" x-on:click="close">
                            {{ __('filament-column-filters::filters.close') }}
                        </button>
                    </div>
                @endif
            </div>
        </template>
    </span>
</span>
