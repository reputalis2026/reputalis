@php
    $isPwa = isset($client) && $client !== null;
    $clientCode = $isPwa ? $client->code : null;
    $clientName = $isPwa ? $client->namecommercial : '';

    $showNfcDemo = isset($showNfcDemo) ? (bool) $showNfcDemo : true;
    $employeeDisplayName = isset($employee) && $employee ? ($employee->alias ?: $employee->name) : null;
    $employeeCodeResolved = isset($employeeCode) ? trim((string) $employeeCode) : null;
    $employeeIdResolved = isset($employeeId) ? (string) $employeeId : (isset($employee) && $employee ? (string) $employee->id : null);
    $surveyDisplayMode = isset($surveyDisplayMode) && $surveyDisplayMode === 'faces' ? 'faces' : 'numbers';
    $surveyLocale = in_array(($surveyLocale ?? 'es'), ['es', 'pt', 'en'], true) ? $surveyLocale : 'es';
    $surveyUiTexts = [
        'es' => [
            'questionFallback' => '¿Cómo le hemos atendido hoy?',
            'selectOption' => 'Selecciona una opción',
            'ratingLow' => 'Muy mal',
            'ratingHigh' => 'Excelente',
            'managedBy' => 'Encuesta gestionada por REPUTALIS',
            'whyFallback' => '¿Qué podríamos mejorar?',
            'thanks' => 'Gracias por tu opinión',
            'thanksSub' => 'En unos segundos abriremos Google para que puedas compartir tu experiencia.',
            'countdownRedirect' => 'Redirección automática',
            'countdownSeconds' => 'segundos',
            'thanksLow' => '¡Gracias!',
            'thanksLowSub' => 'Tu opinión nos ayuda a mejorar.',
            'sending' => 'Enviando...',
        ],
        'pt' => [
            'questionFallback' => 'Como fomos no seu atendimento hoje?',
            'selectOption' => 'Selecione uma opção',
            'ratingLow' => 'Muito mau',
            'ratingHigh' => 'Excelente',
            'managedBy' => 'Inquérito gerido por REPUTALIS',
            'whyFallback' => 'O que poderíamos melhorar?',
            'thanks' => 'Obrigado pela sua opinião',
            'thanksSub' => 'Em alguns segundos abriremos o Google para partilhar a sua experiência.',
            'countdownRedirect' => 'Redirecionamento automático',
            'countdownSeconds' => 'segundos',
            'thanksLow' => 'Obrigado!',
            'thanksLowSub' => 'A sua opinião ajuda-nos a melhorar.',
            'sending' => 'A enviar...',
        ],
        'en' => [
            'questionFallback' => 'How was your experience today?',
            'selectOption' => 'Select an option',
            'ratingLow' => 'Very bad',
            'ratingHigh' => 'Excellent',
            'managedBy' => 'Survey managed by REPUTALIS',
            'whyFallback' => 'What could we improve?',
            'thanks' => 'Thanks for your feedback',
            'thanksSub' => 'In a few seconds we will open Google so you can share your experience.',
            'countdownRedirect' => 'Automatic redirect',
            'countdownSeconds' => 'seconds',
            'thanksLow' => 'Thank you!',
            'thanksLowSub' => 'Your feedback helps us improve.',
            'sending' => 'Sending...',
        ],
    ][$surveyLocale];

    $ratingNumbersWithImagesReveal = false;
    if ($surveyDisplayMode === 'numbers') {
        foreach ([1, 2, 3, 4, 5] as $i) {
            if (is_file(public_path('survey-rating/numbers/'.$i.'.png'))) {
                $ratingNumbersWithImagesReveal = true;
                break;
            }
        }
    }
    $ratingSpinnerReveal = $surveyDisplayMode === 'faces' || $ratingNumbersWithImagesReveal;

    $surveyRatingPreloadUrls = [];
    if ($isPwa) {
        $ratingPreloadFacesMode = file_exists(public_path('survey-rating/faces/cara1.png'));
        if ($ratingPreloadFacesMode) {
            foreach ([1, 2, 3, 4, 5] as $i) {
                $facePath = public_path('survey-rating/faces/cara'.$i.'.png');
                if (file_exists($facePath)) {
                    $surveyRatingPreloadUrls[] = asset('survey-rating/faces/cara'.$i.'.png');
                }
            }
        } else {
            foreach ([1, 2, 3, 4, 5] as $i) {
                $numPath = public_path('survey-rating/numbers/'.$i.'.png');
                if (file_exists($numPath)) {
                    $surveyRatingPreloadUrls[] = asset('survey-rating/numbers/'.$i.'.png');
                }
            }
        }
    }
@endphp
<!DOCTYPE html>
<html lang="{{ $surveyLocale }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#ffffff">
    <title>{{ $isPwa ? "Reputalis - {$clientName}" : __('Encuesta de satisfacción') }}</title>
    @if($isPwa)
    <link rel="manifest" href="{{ route('survey.manifest', ['client_code' => $clientCode]) }}">
    @foreach($surveyRatingPreloadUrls as $preloadHref)
    <link rel="preload" href="{{ $preloadHref }}" as="image" fetchpriority="high">
    @endforeach
    @endif
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        html, body { height: 100%; }
        @if($isPwa)
        body { overflow: hidden; }
        @endif
        [data-step]:not([data-step="active"]) { display: none; }
        [data-step="active"] { display: block; }
        #step-rating[data-step="active"] {
            display: flex;
            flex-direction: column;
            flex: 1 1 auto;
            min-height: 0;
            height: 100%;
        }
        .btn-score { min-height: 3.25rem; font-size: 1.5rem; }
        /* Imágenes de puntuación (modo números o caritas): celda cuadrada, sin padding. */
        .btn-score.btn-score--numbers,
        .btn-score.btn-score--faces {
            display: flex;
            align-items: center;
            justify-content: center;
            aspect-ratio: 1 / 1;
            min-height: 0;
            min-width: 0;
            max-height: min(4.5rem, 18vw);
            padding: 0;
            overflow: hidden;
            font-size: 0;
            line-height: 0;
        }
        .btn-score.btn-score--numbers img,
        .btn-score.btn-score--faces img {
            display: block;
            width: 100%;
            height: 100%;
            object-fit: contain;
            object-position: center;
        }
        /* Solo la imagen visible: sin marco ni fondo del botón */
        .btn-score.btn-score--faces,
        .btn-score.btn-score--numbers {
            border: none !important;
            background: transparent !important;
            box-shadow: none;
        }
        .btn-score.btn-score--faces:hover,
        .btn-score.btn-score--numbers:hover {
            border: none !important;
            background: transparent !important;
            opacity: 0.88;
            transform: scale(1.04);
        }
        .btn-score.btn-score--faces:focus,
        .btn-score.btn-score--numbers:focus {
            outline: none;
        }
        .btn-score.btn-score--faces:focus-visible,
        .btn-score.btn-score--numbers:focus-visible {
            box-shadow: 0 0 0 2px rgba(14, 165, 233, 0.45);
            border-radius: 0.75rem;
        }
        .btn-reason {
            display: block;
            width: 100%;
            min-height: 3.5rem;
            margin: 0;
            padding: 1.05rem 1.25rem;
            border: none;
            border-radius: 0.9rem;
            background: #eef2f6;
            color: #0f172a;
            font-size: 1.05rem;
            font-weight: 600;
            line-height: 1.3;
            text-align: center;
            box-shadow: none;
            transition: background-color 0.15s ease, transform 0.1s ease, opacity 0.15s ease;
        }
        .btn-reason:hover {
            background: #e4eaf1;
        }
        .btn-reason:active {
            transform: scale(0.99);
        }
        .btn-reason:focus {
            outline: none;
        }
        .btn-reason:focus-visible {
            box-shadow: 0 0 0 2px rgba(14, 165, 233, 0.4);
        }
        .btn-reason.is-selected {
            background: #d7e0ea;
            color: #0f172a;
        }
        .btn-reason:disabled {
            cursor: wait;
            opacity: 0.8;
        }
        #step-reason[data-step="active"] {
            display: flex;
            flex-direction: column;
            flex: 1 1 auto;
            min-height: 0;
            height: 100%;
        }
        .survey-reason-main {
            flex: 1 1 auto;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: flex-start;
            text-align: center;
            padding: 1.1rem 0 1rem;
            min-height: 0;
            overflow-y: auto;
            -webkit-overflow-scrolling: touch;
            width: 100%;
        }
        .survey-reason-list {
            width: 100%;
            max-width: 22rem;
            margin-top: 2rem;
            display: flex;
            flex-direction: column;
            gap: 0.85rem;
        }
        #step-thanks-low[data-step="active"] {
            display: flex;
            flex-direction: column;
            flex: 1 1 auto;
            min-height: 0;
            height: 100%;
        }
        .survey-thanks-main {
            flex: 1 1 auto;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 0.25rem 0.5rem 2.5rem;
            min-height: 0;
            transform: translateY(-1.75rem);
        }
        .survey-thanks-check {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 5.75rem;
            height: 5.75rem;
            margin: 0 auto 2.65rem;
            border-radius: 9999px;
            background: #38b2ce;
            box-shadow: 0 0 0 14px rgba(56, 178, 206, 0.14);
        }
        .survey-thanks-check svg {
            width: 2.6rem;
            height: 2.6rem;
            display: block;
        }
        .survey-thanks-title {
            margin: 0;
            font-size: clamp(1.9rem, 7.4vw, 2.4rem);
            line-height: 1.18;
            font-weight: 500;
            letter-spacing: -0.02em;
            color: #0f172a;
        }
        .survey-thanks-sub {
            margin: 1.55rem 0 0;
            max-width: 18rem;
            font-size: 1.05rem;
            line-height: 1.4;
            color: #64748b;
        }
        #step-thanks-high[data-step="active"] {
            display: flex;
            flex-direction: column;
            flex: 1 1 auto;
            min-height: 0;
            height: 100%;
        }
        .survey-thanks-high-main {
            flex: 1 1 auto;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: flex-start;
            text-align: center;
            padding: 1.1rem 0.5rem 1.25rem;
            min-height: 0;
        }
        .survey-thanks-high-copy {
            width: 100%;
            max-width: 22rem;
        }
        .survey-thanks-high-copy .survey-thanks-sub {
            margin-top: 1rem;
            max-width: none;
        }
        .survey-countdown {
            margin-top: 2.75rem;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 1.65rem;
        }
        .survey-countdown-label,
        .survey-countdown-unit {
            margin: 0;
            font-size: 0.9rem;
            line-height: 1.3;
            color: #94a3b8;
        }
        .survey-countdown-label {
            margin-bottom: 0.15rem;
        }
        .survey-countdown-ring {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 7.25rem;
            height: 7.25rem;
            border-radius: 9999px;
            border: 3px solid #38b2ce;
            box-sizing: border-box;
        }
        .survey-countdown-number {
            margin: 0;
            font-size: 3.25rem;
            line-height: 1;
            font-weight: 600;
            font-variant-numeric: tabular-nums;
            color: #0f172a;
        }
        .survey-brand-logo {
            display: block;
            width: min(11.5rem, 52vw);
            height: auto;
            margin: 0 auto;
        }
        .survey-rating-shell {
            display: flex;
            flex-direction: column;
            flex: 1 1 auto;
            height: 100%;
            min-height: 0;
            width: 100%;
        }
        .survey-rating-top {
            flex: 0 0 auto;
            padding-top: 0.25rem;
            padding-bottom: 1.15rem;
            text-align: center;
        }
        .survey-rating-main {
            flex: 0 0 auto;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: flex-start;
            text-align: center;
            padding: 1.1rem 0 0;
            min-height: 0;
        }
        .survey-rating-question {
            margin: 0;
            padding: 0 0.25rem;
            font-size: clamp(1.9rem, 7.4vw, 2.4rem);
            line-height: 1.18;
            font-weight: 500;
            letter-spacing: -0.02em;
            color: #0f172a;
        }
        .survey-rating-select {
            margin-top: 1.15rem;
            font-size: 1rem;
            line-height: 1.35;
            color: #94a3b8;
        }
        .survey-rating-footer {
            flex: 0 0 auto;
            margin-top: auto;
            padding: 0.75rem 0 0.25rem;
            border-top: 1px solid #e2e8f0;
            text-align: center;
        }
        .survey-rating-scale {
            width: 100%;
            max-width: 21rem;
            margin-top: 3.15rem;
        }
        .survey-rating-labels {
            display: flex;
            justify-content: space-between;
            margin-top: 0.5rem;
            padding: 0 0.1rem;
            font-size: 0.78rem;
            line-height: 1.2;
            font-weight: 700;
            color: #0f172a;
        }
        @media (max-height: 700px) {
            .survey-brand-logo { width: min(9.75rem, 46vw); }
            .survey-rating-question { font-size: clamp(1.7rem, 6.6vw, 2.05rem); }
            .survey-rating-scale { margin-top: 2.4rem; }
            .survey-rating-select { margin-top: 0.95rem; }
            .survey-rating-top { padding-bottom: 0.75rem; }
            .survey-rating-main { padding-top: 0.75rem; }
            .btn-score.btn-score--numbers,
            .btn-score.btn-score--faces { max-height: min(3.6rem, 14.5vw); }
        }
    </style>
</head>
<body class="bg-white text-slate-800 antialiased {{ $isPwa ? '' : 'min-h-screen' }}">
    <div class="mx-auto max-w-md flex flex-col px-5 {{ $isPwa ? 'pt-3 pb-3' : 'min-h-screen py-6' }}" id="app" @if($isPwa) style="height: 100dvh; max-height: 100dvh; overflow: hidden;" @endif>

        @if(!$isPwa)
        {{-- Landing: lista de clientes → Abrir PWA --}}
        <section class="flex-1">
            <div class="rounded-2xl overflow-hidden bg-white shadow-sm ring-1 ring-slate-200 mb-6">
                <div class="aspect-[2/1] bg-gradient-to-br from-amber-100 to-amber-50 flex items-center justify-center">
                    <svg class="w-20 h-20 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                </div>
                <div class="p-6">
                    <h1 class="text-xl font-semibold text-slate-800 mb-1">Reputalis</h1>
                    <p class="text-slate-600 mb-6">{{ __('Seleccione su cliente para abrir la encuesta') }}</p>
                    <ul class="space-y-2">
                        @forelse($clients as $c)
                            <li>
                                <a href="{{ url("/survey/{$c->code}") }}" class="flex items-center justify-between rounded-xl border border-slate-200 bg-white px-4 py-3 text-slate-700 transition hover:border-amber-300 hover:bg-amber-50">
                                    <span>{{ $c->namecommercial }} <span class="text-slate-400">({{ $c->code }})</span></span>
                                    <span class="text-amber-600 font-medium">{{ __('Abrir PWA') }}</span>
                                </a>
                            </li>
                        @empty
                            <li class="text-slate-500 py-4">{{ __('No hay clientes disponibles.') }}</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </section>
        @else
        {{-- PWA: encuesta fija a este cliente (sin selector) --}}
        @if($showNfcDemo)
            {{-- Demo NFC solo en PWA --}}
            <section class="mb-4 rounded-xl bg-white p-4 shadow-sm ring-1 ring-slate-200">
                <h2 class="text-xs font-semibold uppercase tracking-wider text-slate-400 mb-2">Demo</h2>
                <div class="flex gap-2">
                    <input type="text" id="nfc-uid" placeholder="UID chip" class="flex-1 rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    <button type="button" id="btn-nfc" class="rounded-lg bg-slate-200 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-300">{{ __('Leer NFC') }}</button>
                </div>
            </section>
        @endif

        <section data-step="active" id="step-rating" class="flex-1">
            <div class="survey-rating-shell">
                <div class="survey-rating-top">
                    <img
                        src="{{ asset('img/logoReputalis.png') }}"
                        alt="REPUTALIS"
                        class="survey-brand-logo"
                        width="200"
                        height="40"
                        decoding="async"
                    >
                </div>

                <div class="survey-rating-main">
                    <h1 class="survey-rating-question" id="text-question">
                        {{ $surveyQuestion ?? $surveyUiTexts['questionFallback'] }}
                    </h1>
                    <p class="survey-rating-select" id="text-select-option">
                        {{ $surveyUiTexts['selectOption'] }}
                    </p>

                    <div class="survey-rating-scale">
                        @if($surveyDisplayMode === 'faces')
                            <div>
                                <div id="rating-spinner" class="grid grid-cols-5 gap-2">
                                    @for($i = 1; $i <= 5; $i++)
                                        <button type="button" disabled class="btn-score btn-score--faces rounded-xl bg-transparent pointer-events-none flex items-center justify-center text-slate-400" aria-hidden="true" tabindex="-1">
                                            <svg class="w-8 h-8 animate-spin" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3"/>
                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/>
                                            </svg>
                                        </button>
                                    @endfor
                                </div>
                                <div id="rating-buttons" class="grid grid-cols-5 gap-2 hidden opacity-0 transition-opacity duration-300 ease-out">
                                    @foreach([1,2,3,4,5] as $n)
                                        <button type="button" class="btn-score rounded-xl transition focus:outline-none btn-score--faces" data-score="{{ $n }}">
                                            <picture class="contents">
                                                <source srcset="{{ asset('survey-rating/faces/cara'.$n.'.webp') }}" type="image/webp">
                                                <img src="{{ asset('survey-rating/faces/cara'.$n.'.png') }}" alt="" role="presentation" class="h-full w-full object-contain" loading="eager" fetchpriority="high" decoding="async">
                                            </picture>
                                        </button>
                                    @endforeach
                                </div>
                            </div>
                        @else
                            @if($ratingNumbersWithImagesReveal)
                                <div>
                                    <div id="rating-spinner" class="grid grid-cols-5 gap-2">
                                        @for($i = 1; $i <= 5; $i++)
                                            <button type="button" disabled class="btn-score btn-score--numbers rounded-xl bg-transparent pointer-events-none flex items-center justify-center text-slate-400" aria-hidden="true" tabindex="-1">
                                                <svg class="w-8 h-8 animate-spin" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3"/>
                                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/>
                                                </svg>
                                            </button>
                                        @endfor
                                    </div>
                                    <div id="rating-buttons" class="grid grid-cols-5 gap-2 hidden opacity-0 transition-opacity duration-300 ease-out">
                                        @foreach([1,2,3,4,5] as $n)
                                            @php
                                                $numbersImgPath = public_path('survey-rating/numbers/'.$n.'.png');
                                                $useNumbersImg = is_file($numbersImgPath);
                                            @endphp
                                            <button type="button" class="btn-score rounded-xl transition focus:outline-none {{ $useNumbersImg ? 'btn-score--numbers' : 'border-2 border-slate-200 bg-white font-semibold text-slate-600 hover:border-sky-400 hover:bg-sky-50 hover:text-sky-700 focus:ring-2 focus:ring-sky-500' }}" data-score="{{ $n }}">
                                                @if($useNumbersImg)
                                                    <picture class="contents">
                                                        <source srcset="{{ asset('survey-rating/numbers/'.$n.'.webp') }}" type="image/webp">
                                                        <img src="{{ asset('survey-rating/numbers/'.$n.'.png') }}" alt="" role="presentation" class="h-full w-full object-contain" loading="eager" fetchpriority="high" decoding="async">
                                                    </picture>
                                                @else
                                                    {{ $n }}
                                                @endif
                                            </button>
                                        @endforeach
                                    </div>
                                </div>
                            @else
                                <div class="grid grid-cols-5 gap-2">
                                    @foreach([1,2,3,4,5] as $n)
                                        <button type="button" class="btn-score rounded-xl border-2 border-slate-200 bg-white font-semibold text-slate-600 transition hover:border-sky-400 hover:bg-sky-50 hover:text-sky-700 focus:outline-none focus:ring-2 focus:ring-sky-500" data-score="{{ $n }}">{{ $n }}</button>
                                    @endforeach
                                </div>
                            @endif
                        @endif

                        <div class="survey-rating-labels" aria-hidden="true">
                            <span>{{ $surveyUiTexts['ratingLow'] }}</span>
                            <span>{{ $surveyUiTexts['ratingHigh'] }}</span>
                        </div>
                    </div>
                </div>

                <footer class="survey-rating-footer">
                    <p class="text-xs text-slate-400">{{ $surveyUiTexts['managedBy'] }}</p>
                </footer>
            </div>
        </section>

        <section data-step id="step-thanks-high" class="flex-1">
            <div class="survey-rating-shell">
                <div class="survey-rating-top">
                    <img
                        src="{{ asset('img/logoReputalis.png') }}"
                        alt="REPUTALIS"
                        class="survey-brand-logo"
                        width="200"
                        height="40"
                        decoding="async"
                    >
                </div>

                <div class="survey-thanks-high-main">
                    <div class="survey-thanks-high-copy">
                        <h1 class="survey-thanks-title">{{ $surveyUiTexts['thanks'] }}</h1>
                        <p class="survey-thanks-sub" id="text-google-review">{{ $surveyUiTexts['thanksSub'] }}</p>
                    </div>

                    @if(!empty($googleReviewUrl))
                        <div class="survey-countdown" id="google-countdown">
                            <p class="survey-countdown-label" id="text-countdown-label">{{ $surveyUiTexts['countdownRedirect'] }}</p>
                            <div class="survey-countdown-ring" aria-hidden="true">
                                <p class="survey-countdown-number" id="countdown-number" aria-live="polite">5</p>
                            </div>
                            <p class="survey-countdown-unit">{{ $surveyUiTexts['countdownSeconds'] }}</p>
                        </div>
                    @endif
                </div>

                <footer class="survey-rating-footer">
                    <p class="text-xs text-slate-400">{{ $surveyUiTexts['managedBy'] }}</p>
                </footer>
            </div>
        </section>

        <section data-step id="step-reason" class="flex-1">
            <div class="survey-rating-shell">
                <div class="survey-rating-top">
                    <img
                        src="{{ asset('img/logoReputalis.png') }}"
                        alt="REPUTALIS"
                        class="survey-brand-logo"
                        width="200"
                        height="40"
                        decoding="async"
                    >
                </div>

                <div class="survey-reason-main">
                    <h1 class="survey-rating-question" id="text-why">
                        {{ isset($improvementBlock['title']) ? $improvementBlock['title'] : $surveyUiTexts['whyFallback'] }}
                    </h1>
                    <p class="survey-rating-select" id="text-reason-select-option">
                        {{ $surveyUiTexts['selectOption'] }}
                    </p>

                    <div class="survey-reason-list" id="reasons-list">
                        @if(!empty($improvementBlock['options']))
                            @foreach($improvementBlock['options'] as $opt)
                                <button type="button" class="btn-reason" data-option-id="{{ $opt['id'] }}">
                                    {{ $opt['label'] }}
                                </button>
                            @endforeach
                        @endif
                    </div>
                </div>

                <footer class="survey-rating-footer">
                    <p class="text-xs text-slate-400">{{ $surveyUiTexts['managedBy'] }}</p>
                </footer>
            </div>
        </section>

        <section data-step id="step-thanks-low" class="flex-1">
            <div class="survey-rating-shell">
                <div class="survey-rating-top">
                    <img
                        src="{{ asset('img/logoReputalis.png') }}"
                        alt="REPUTALIS"
                        class="survey-brand-logo"
                        width="200"
                        height="40"
                        decoding="async"
                    >
                </div>

                <div class="survey-thanks-main">
                    <div class="survey-thanks-check" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M5 12.5l4.5 4.5L19 7.5" stroke="#ffffff" stroke-width="2.8" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>
                    <h1 class="survey-thanks-title">{{ $surveyUiTexts['thanksLow'] }}</h1>
                    <p class="survey-thanks-sub">{{ $surveyUiTexts['thanksLowSub'] }}</p>
                </div>

                <footer class="survey-rating-footer">
                    <p class="text-xs text-slate-400">{{ $surveyUiTexts['managedBy'] }}</p>
                </footer>
            </div>
        </section>

        <div id="overlay" class="fixed inset-0 z-10 hidden items-center justify-center bg-slate-900/40">
            <div class="rounded-2xl bg-white px-8 py-6 shadow-xl flex flex-col items-center gap-3">
                <svg class="h-10 w-10 animate-spin text-amber-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                <span class="text-slate-600" id="overlay-text">{{ $surveyUiTexts['sending'] }}</span>
            </div>
        </div>
        @endif
    </div>

    @if($isPwa)
    <script>
(function() {
    const CLIENT_CODE = @json($clientCode);
    const EMPLOYEE_CODE = @json($employeeCodeResolved);
    const EMPLOYEE_ID = @json($employeeIdResolved);
    const SURVEY_LOCALE = @json($surveyLocale);
    const POSITIVE_SCORES = @json(array_values($surveyPositiveScores ?? [4, 5]));
    const GOOGLE_REVIEW_URL = @json($googleReviewUrl ?? null);
    const COUNTDOWN_SECONDS = 5;
    const STORAGE_KEY_DEVICE = 'reputalis_' + CLIENT_CODE + '_devicehash';
    const STORAGE_KEY_PENDING = 'reputalis_' + CLIENT_CODE + '_pending_surveys';

    const i18n = {
        es: { question: '¿Cómo le hemos atendido hoy?', why: '¿Qué podríamos mejorar?', thanks: 'Gracias por tu opinión', thanksLow: '¡Gracias!', thanksSub: 'En unos segundos abriremos Google para que puedas compartir tu experiencia.', thanksLowSub: 'Tu opinión nos ayuda a mejorar.', countdownRedirect: 'Redirección automática', countdownSeconds: 'segundos', sending: 'Enviando...', error: 'No se pudo enviar. Inténtelo de nuevo.', errorNetwork: 'Error de conexión.' },
        pt: { question: 'Como fomos no seu atendimento hoje?', why: 'O que poderíamos melhorar?', thanks: 'Obrigado pela sua opinião', thanksLow: 'Obrigado!', thanksSub: 'Em alguns segundos abriremos o Google para partilhar a sua experiência.', thanksLowSub: 'A sua opinião ajuda-nos a melhorar.', countdownRedirect: 'Redirecionamento automático', countdownSeconds: 'segundos', sending: 'A enviar...', error: 'Não foi possível enviar. Tente novamente.', errorNetwork: 'Erro de ligação.' },
        en: { question: 'How was your experience today?', why: 'What could we improve?', thanks: 'Thanks for your feedback', thanksLow: 'Thank you!', thanksSub: 'In a few seconds we will open Google so you can share your experience.', thanksLowSub: 'Your feedback helps us improve.', countdownRedirect: 'Automatic redirect', countdownSeconds: 'seconds', sending: 'Sending...', error: 'Could not send. Please try again.', errorNetwork: 'Connection error.' }
    };
    const lang = i18n[SURVEY_LOCALE] ? SURVEY_LOCALE : 'es';
    const t = (key) => i18n[lang][key] ?? i18n.es[key] ?? key;

    function getDeviceHash() {
        let id = localStorage.getItem(STORAGE_KEY_DEVICE);
        if (!id) {
            id = Math.random().toString(36).slice(2) + Date.now().toString(36);
            localStorage.setItem(STORAGE_KEY_DEVICE, id);
        }
        const str = id + navigator.userAgent;
        return btoa(str).replace(/[^A-Za-z0-9]/g, '').slice(0, 255);
    }

    function getLocale() {
        return SURVEY_LOCALE || 'es';
    }

    function isPositiveScore(score) {
        return POSITIVE_SCORES.includes(parseInt(score, 10));
    }

    function showStep(stepId) {
        document.querySelectorAll('[data-step]').forEach(el => { el.removeAttribute('data-step'); el.style.display = 'none'; });
        const el = document.getElementById(stepId);
        if (el) {
            el.setAttribute('data-step', 'active');
            el.style.display = (stepId === 'step-rating' || stepId === 'step-reason' || stepId === 'step-thanks-low' || stepId === 'step-thanks-high') ? 'flex' : 'block';
        }
    }

    let googleReviewCountdownTimer = null;

    function clearGoogleReviewCountdown() {
        if (googleReviewCountdownTimer !== null) {
            clearInterval(googleReviewCountdownTimer);
            googleReviewCountdownTimer = null;
        }
    }

    function startGoogleReviewCountdown() {
        if (!GOOGLE_REVIEW_URL) return;

        clearGoogleReviewCountdown();

        const countdownEl = document.getElementById('countdown-number');
        let remaining = COUNTDOWN_SECONDS;
        if (countdownEl) countdownEl.textContent = String(remaining);

        googleReviewCountdownTimer = setInterval(function() {
            remaining -= 1;
            if (countdownEl) countdownEl.textContent = String(Math.max(remaining, 0));
            if (remaining <= 0) {
                clearGoogleReviewCountdown();
                window.location.href = GOOGLE_REVIEW_URL;
            }
        }, 1000);
    }

    function showPositiveThanksStep() {
        showStep('step-thanks-high');
        startGoogleReviewCountdown();
    }

    function setOverlay(show, text) {
        const ov = document.getElementById('overlay');
        if (ov) { ov.classList.toggle('hidden', !show); ov.classList.toggle('flex', show); var txt = document.getElementById('overlay-text'); if (txt && text) txt.textContent = text; }
    }

    const apiUrl = @json(url('/api/surveys/create'));
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const hasImprovementBlock = @json(!empty($improvementBlock) && !empty($improvementBlock['options']));

    function savePending(payload) {
        try {
            const raw = localStorage.getItem(STORAGE_KEY_PENDING) || '[]';
            const arr = JSON.parse(raw);
            const effectiveEmployeeCode = payload.employee_code || EMPLOYEE_CODE || null;
            const effectiveEmployeeId = payload.employee_id || EMPLOYEE_ID || null;
            arr.push({
                ...payload,
                employee_id: effectiveEmployeeId,
                employee_code: effectiveEmployeeCode,
                locale_used: getLocale(),
                device_hash: getDeviceHash(),
                _ts: Date.now(),
            });
            localStorage.setItem(STORAGE_KEY_PENDING, JSON.stringify(arr));
        } catch (e) {}
    }

    function getPending() {
        try {
            return JSON.parse(localStorage.getItem(STORAGE_KEY_PENDING) || '[]');
        } catch (e) { return []; }
    }

    function clearPending() {
        localStorage.removeItem(STORAGE_KEY_PENDING);
    }

    function flushPending() {
        const pending = getPending();
        if (pending.length === 0) return Promise.resolve();
        const first = pending[0];
        const payload = { 
            client_code: CLIENT_CODE,
            employee_id: first.employee_id || EMPLOYEE_ID || null,
            employee_code: first.employee_code || EMPLOYEE_CODE || null,
            score: first.score,
            improvement_option_id: first.improvement_option_id || null,
            locale_used: first.locale_used || getLocale(),
            device_hash: first.device_hash || getDeviceHash()
        };
        return fetch(apiUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify(payload),
        }).then(res => {
            const next = pending.slice(1);
            if (next.length) localStorage.setItem(STORAGE_KEY_PENDING, JSON.stringify(next));
            else clearPending();
            if (res.ok) return flushPending();
        }).catch(() => {});
    }

    function submitSurvey(payload, fromQueue) {
        if (!fromQueue) setOverlay(true, t('sending'));
        const effectiveEmployeeCode = payload.employee_code || EMPLOYEE_CODE || null;
        const effectiveEmployeeId = payload.employee_id || EMPLOYEE_ID || null;
        const body = { 
            client_code: CLIENT_CODE, 
            employee_id: effectiveEmployeeId,
            employee_code: effectiveEmployeeCode,
            score: payload.score, 
            improvement_option_id: payload.improvement_option_id || null, 
            locale_used: getLocale(), 
            device_hash: getDeviceHash()
        };
        fetch(apiUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify(body),
        })
        .then(res => res.json().then(data => ({ status: res.status, data })))
        .then(({ status, data }) => {
            if (!fromQueue) setOverlay(false);
            if (status >= 200 && status < 300) {
                if (!fromQueue) {
                    if (isPositiveScore(payload.score)) {
                        showPositiveThanksStep();
                    } else showStep('step-thanks-low');
                }
            } else {
                if (!fromQueue) { savePending({ ...payload, employee_id: effectiveEmployeeId, employee_code: effectiveEmployeeCode }); alert(data.message || t('error')); }
            }
        })
        .catch(() => {
            if (!fromQueue) { setOverlay(false); savePending({ ...payload, employee_id: effectiveEmployeeId, employee_code: effectiveEmployeeCode }); alert(t('errorNetwork')); }
        });
    }

    document.querySelectorAll('[data-score]').forEach(btn => {
        btn.addEventListener('click', function() {
            const score = parseInt(this.dataset.score, 10);
            if (isPositiveScore(score)) submitSurvey({ score });
            else if (hasImprovementBlock) { window._pendingSurvey = { score }; showStep('step-reason'); }
            else submitSurvey({ score });
        });
    });

    document.getElementById('reasons-list')?.addEventListener('click', function(e) {
        const btn = e.target.closest('[data-option-id]');
        if (!btn || !window._pendingSurvey) return;
        this.querySelectorAll('[data-option-id]').forEach(function(optionBtn) {
            optionBtn.classList.remove('is-selected');
        });
        btn.classList.add('is-selected');
        const payload = { ...window._pendingSurvey, improvement_option_id: btn.dataset.optionId };
        window._pendingSurvey = null;
        submitSurvey(payload);
    });

    document.getElementById('btn-nfc')?.addEventListener('click', function() {
        const uid = document.getElementById('nfc-uid');
        if (uid) uid.value = 'DEMO-' + Math.random().toString(36).slice(2, 10).toUpperCase();
    });

    if (navigator.onLine) flushPending();
    window.addEventListener('online', () => flushPending());

@if($ratingSpinnerReveal)
    (function() {
        function revealRatingButtons() {
            const spin = document.getElementById('rating-spinner');
            const btns = document.getElementById('rating-buttons');
            if (spin) spin.classList.add('hidden');
            if (btns) {
                btns.classList.remove('hidden');
                requestAnimationFrame(function() {
                    btns.classList.remove('opacity-0');
                    btns.classList.add('opacity-100');
                });
            }
        }
        function waitForRatingImages() {
            const imgs = document.querySelectorAll('#rating-buttons picture img, #rating-buttons img');
            const seen = new Set();
            const images = [];
            imgs.forEach(function(img) {
                if (!seen.has(img)) { seen.add(img); images.push(img); }
            });
            const total = images.length;
            if (total === 0) {
                revealRatingButtons();
                return;
            }
            let loaded = 0;
            let finished = false;
            function checkAllLoaded() {
                if (finished) return;
                if (loaded >= total) {
                    finished = true;
                    clearTimeout(fallbackTimer);
                    revealRatingButtons();
                }
            }
            const fallbackTimer = setTimeout(function() {
                if (finished) return;
                finished = true;
                revealRatingButtons();
            }, 3000);
            images.forEach(function(img) {
                if (img.complete && img.naturalWidth > 0) {
                    loaded++;
                } else {
                    img.addEventListener('load', function() { loaded++; checkAllLoaded(); }, { once: true });
                    img.addEventListener('error', function() { loaded++; checkAllLoaded(); }, { once: true });
                }
            });
            checkAllLoaded();
        }
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', waitForRatingImages);
        } else {
            waitForRatingImages();
        }
    })();
@endif
})();
    </script>
    @if($clientCode)
    <script>
(function() {
    const code = @json($clientCode);
    const swUrl = @json(route('survey.sw', ['client_code' => $clientCode]));
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register(swUrl, { scope: '/survey/' + encodeURIComponent(code) + '/' }).catch(function() {});
    }
})();
    </script>
    @endif
    @endif
</body>
</html>
