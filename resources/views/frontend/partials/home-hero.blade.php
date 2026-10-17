@if ($homeHero?->is_active)
    <section class="front-hero front-managed-hero" aria-labelledby="managed-hero-heading" dir="ltr">
        <div class="container-fluid front-managed-hero__container">
            <div class="front-managed-hero__grid {{ filled($homeHero->hero_image) ? '' : 'front-managed-hero__grid--text-only' }}">
                <div class="front-managed-hero__content">
                    <p class="front-managed-hero__eyebrow">{{ $homeHero->eyebrow }}</p>
                    <h2 class="front-managed-hero__heading" id="managed-hero-heading">
                        {{ $homeHero->heading_before_highlight }}
                        @if (filled($homeHero->highlighted_text))
                            <em class="front-managed-hero__highlight">{{ $homeHero->highlighted_text }}</em>
                        @endif
                        @if (filled($homeHero->heading_after_highlight))
                            {{ $homeHero->heading_after_highlight }}
                        @endif
                    </h2>
                    @if (filled($homeHero->description))
                        <p class="front-managed-hero__description">{{ $homeHero->description }}</p>
                    @endif
                    @if (filled($homeHero->primary_button_text) || filled($homeHero->secondary_button_text))
                        <div class="front-managed-hero__actions">
                            @if (filled($homeHero->primary_button_text))
                                @if (filled($homeHero->primary_button_url))
                                <a class="front-managed-hero__button front-managed-hero__button--primary" href="{{ $homeHero->primary_button_url }}">
                                    <span>{{ $homeHero->primary_button_text }}</span><i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                                </a>
                                @else
                                    <button class="front-managed-hero__button front-managed-hero__button--primary" type="button">
                                        <span>{{ $homeHero->primary_button_text }}</span><i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                                    </button>
                                @endif
                            @endif
                            @if (filled($homeHero->secondary_button_text))
                                @if (filled($homeHero->secondary_button_url))
                                <a class="front-managed-hero__button front-managed-hero__button--secondary" href="{{ $homeHero->secondary_button_url }}">
                                    <span>{{ $homeHero->secondary_button_text }}</span><i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                                </a>
                                @else
                                    <button class="front-managed-hero__button front-managed-hero__button--secondary" type="button">
                                        <span>{{ $homeHero->secondary_button_text }}</span><i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                                    </button>
                                @endif
                            @endif
                        </div>
                    @endif
                </div>
                @if (filled($homeHero->hero_image))
                    <div class="front-managed-hero__media">
                        <div class="front-managed-hero__frame" data-home-hero-frame>
                            <img class="front-managed-hero__image" src="{{ asset($homeHero->hero_image) }}"
                                alt="{{ $homeHero->image_alt ?: trim($homeHero->heading_before_highlight.' '.$homeHero->highlighted_text.' '.$homeHero->heading_after_highlight) }}"
                                decoding="async" data-home-hero-image>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </section>
@endif
