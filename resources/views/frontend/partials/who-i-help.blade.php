@if ($whoIHelpSection)
    <section class="front-who-i-help" @if (filled($whoIHelpSection->heading)) aria-labelledby="who-i-help-heading" @endif>
        <div class="container-fluid front-who-i-help__container">
            <div class="front-who-i-help__intro">
                @if (filled($whoIHelpSection->eyebrow))
                    <span class="front-who-i-help__eyebrow">{{ $whoIHelpSection->eyebrow }}</span>
                @endif

                @if (filled($whoIHelpSection->heading))
                    <h2 id="who-i-help-heading">{{ $whoIHelpSection->heading }}</h2>
                @endif

                @if (filled($whoIHelpSection->description))
                    <p class="front-who-i-help__description">{{ $whoIHelpSection->description }}</p>
                @endif
            </div>

            <ul class="front-who-i-help__grid" role="list">
                @foreach ($whoIHelpSection->items as $item)
                    <li class="front-who-i-help__card">
                        <i class="fa-solid fa-check front-who-i-help__check" aria-hidden="true"></i>
                        <span>{{ $item->title }}</span>
                    </li>
                @endforeach
            </ul>

            @if (filled($whoIHelpSection->bottom_message))
                <p class="front-who-i-help__bottom-message">{{ $whoIHelpSection->bottom_message }}</p>
            @endif
        </div>
    </section>
@endif