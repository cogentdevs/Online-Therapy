@if ($workTogether?->is_active)
    @php
        $hasWorkHeader = collect(['eyebrow', 'heading', 'description'])
            ->contains(fn (string $field): bool => filled($workTogether->{$field}));
        $workCards = $workTogether->cards;
        $hasBottomButton = filled($workTogether->bottom_button_text) && filled($workTogether->bottom_button_url);
        $hasWorkBottom = filled($workTogether->bottom_message) || $hasBottomButton;
    @endphp
    @if ($hasWorkHeader || $workCards->isNotEmpty() || $hasWorkBottom)
        <section class="front-work-together" @if (filled($workTogether->heading)) aria-labelledby="work-together-heading" @endif>
            <div class="container-fluid front-work-together__container">
                @if ($hasWorkHeader)
                    <header class="front-work-together__header">
                        @if (filled($workTogether->eyebrow))
                            <p class="front-work-together__eyebrow">{{ $workTogether->eyebrow }}</p>
                        @endif
                        @if (filled($workTogether->heading))
                            <h2 class="front-work-together__heading" id="work-together-heading">{{ $workTogether->heading }}</h2>
                        @endif
                        @if (filled($workTogether->description))
                            <p class="front-work-together__description">{{ $workTogether->description }}</p>
                        @endif
                    </header>
                @endif
                @if ($workCards->isNotEmpty())
                    <div class="front-work-together__grid" style="--work-columns: {{ in_array((int) $workTogether->cards_per_row, [3, 4], true) ? (int) $workTogether->cards_per_row : 3 }};">
                        @foreach ($workCards as $card)
                            <article @class(['front-work-together__card', 'front-work-together__card--full' => $card->layout_type === 'full_width'])>
                                <div class="front-work-together__identity">
                                    <span class="front-work-together__number">{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                                    @if (filled($card->title))
                                        <h3 class="front-work-together__title">{{ $card->title }}</h3>
                                    @endif
                                </div>
                                @if (filled($card->description) || filled($card->additional_details))
                                    <div class="front-work-together__body">
                                        @if (filled($card->description))
                                            <p class="front-work-together__card-description">{{ $card->description }}</p>
                                        @endif
                                        @if (filled($card->additional_details))
                                            <p class="front-work-together__details">{{ $card->additional_details }}</p>
                                        @endif
                                    </div>
                                @endif
                                @if (filled($card->button_text) && filled($card->button_url))
                                    <a class="front-work-together__link" href="{{ $card->button_url }}">{{ $card->button_text }} <span aria-hidden="true">&rarr;</span></a>
                                @endif
                            </article>
                        @endforeach
                    </div>
                @endif
                @if ($hasWorkBottom)
                    <div class="front-work-together__bottom">
                        @if (filled($workTogether->bottom_message))
                            <p class="front-work-together__bottom-message">{{ $workTogether->bottom_message }}</p>
                        @endif
                        @if ($hasBottomButton)
                            <a class="front-work-together__button" href="{{ $workTogether->bottom_button_url }}">{{ $workTogether->bottom_button_text }} <span aria-hidden="true">&rarr;</span></a>
                        @endif
                    </div>
                @endif
            </div>
        </section>
    @endif
@endif
