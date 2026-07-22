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
            @else
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="fct-icon">
                    <path fill-rule="evenodd" d="M2.628 1.601C5.028 1.206 7.49 1 10 1s4.973.206 7.372.601a.75.75 0 0 1 .628.74v2.288a2.25 2.25 0 0 1-.659 1.59l-4.682 4.683a2.25 2.25 0 0 0-.659 1.59v3.037c0 .684-.31 1.33-.844 1.757l-1.937 1.55A.75.75 0 0 1 8 18.25v-5.757a2.25 2.25 0 0 0-.659-1.591L2.659 6.22A2.25 2.25 0 0 1 2 4.629V2.34a.75.75 0 0 1 .628-.74Z" clip-rule="evenodd" />
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

                    <div class="fct-section fct-options" x-ref="optionsList">
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
