@if ($thriveFramework?->is_active)
    @php
        $hasFrameworkHeader = collect(['eyebrow', 'heading', 'description_one', 'description_two'])
            ->contains(fn (string $field): bool => filled($thriveFramework->{$field}));
        $frameworkStages = $thriveFramework->stages;
    @endphp
    @if ($hasFrameworkHeader || $frameworkStages->isNotEmpty())
        <section class="front-thrive-framework" @if (filled($thriveFramework->heading)) aria-labelledby="thrive-framework-heading" @endif>
            <div class="container-fluid front-thrive-framework__container">
                @if ($hasFrameworkHeader)
                    <header class="front-thrive-framework__header">
                        @if (filled($thriveFramework->eyebrow))
                            <p class="front-thrive-framework__eyebrow">{{ $thriveFramework->eyebrow }}</p>
                        @endif
                        @if (filled($thriveFramework->heading))
                            <h2 class="front-thrive-framework__heading" id="thrive-framework-heading">{{ $thriveFramework->heading }}</h2>
                        @endif
                        @if (filled($thriveFramework->description_one))
                            <p class="front-thrive-framework__description">{{ $thriveFramework->description_one }}</p>
                        @endif
                        @if (filled($thriveFramework->description_two))
                            <p class="front-thrive-framework__description">{{ $thriveFramework->description_two }}</p>
                        @endif
                    </header>
                @endif
                @if ($frameworkStages->isNotEmpty())
                    <div class="front-thrive-framework__grid" style="--framework-columns: {{ in_array((int) $thriveFramework->cards_per_row, [3, 4], true) ? (int) $thriveFramework->cards_per_row : 3 }};">
                        @foreach ($frameworkStages as $stage)
                            <article class="front-thrive-framework__card">
                                <div class="front-thrive-framework__meta">
                                    @if (filled($stage->letter))
                                        <span class="front-thrive-framework__letter">{{ $stage->letter }}</span>
                                    @endif
                                    <span class="front-thrive-framework__stage-label">{{ filled($stage->stage_label) ? $stage->stage_label : 'STAGE '.$loop->iteration }}</span>
                                </div>
                                @if (filled($stage->title))
                                    <h3 class="front-thrive-framework__title">{{ $stage->title }}</h3>
                                @endif
                                @if (filled($stage->description))
                                    <p class="front-thrive-framework__stage-description">{{ $stage->description }}</p>
                                @endif
                            </article>
                        @endforeach
                    </div>
                @endif
            </div>
        </section>
    @endif
@endif
