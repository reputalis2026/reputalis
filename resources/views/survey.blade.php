@php
    $isPwa = isset($client) && $client !== null;
    $clientCode = $isPwa ? $client->code : null;
    $clientName = $isPwa ? $client->namecommercial : '';

    $showNfcDemo = isset($showNfcDemo) ? (bool) $showNfcDemo : true;
    $employeeDisplayName = isset($employee) && $employee ? ($employee->alias ?: $employee->name) : null;
    $employeeCodeResolved = isset($employeeCode) ? $employeeCode : null;
    $surveyDisplayMode = isset($surveyDisplayMode) && $surveyDisplayMode === 'faces' ? 'faces' : 'numbers';

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
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#f59e0b">
    <title>{{ $isPwa ? "Reputalis - {$clientName}" : __('Encuesta de satisfacción') }}</title>
    @if($isPwa)
    <link rel="manifest" href="{{ route('survey.manifest', ['client_code' => $clientCode]) }}">
    @foreach($surveyRatingPreloadUrls as $preloadHref)
    <link rel="preload" href="{{ $preloadHref }}" as="image" fetchpriority="high">
    @endforeach
    @endif
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        [data-step]:not([data-step="active"]) { display: none; }
        [data-step="active"] { display: block; }
        .btn-score { min-height: 3.5rem; font-size: 1.5rem; }
        /* Imágenes de puntuación (modo números o caritas): celda cuadrada, sin padding. */
        .btn-score.btn-score--numbers,
        .btn-score.btn-score--faces {
            display: flex;
            align-items: center;
            justify-content: center;
            aspect-ratio: 1 / 1;
            min-height: 0;
            min-width: 0;
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
        .btn-reason { min-height: 2.75rem; }
    </style>
</head>
<body class="min-h-screen bg-slate-50 text-slate-800 antialiased">
    <div class="mx-auto max-w-md min-h-screen flex flex-col px-4 py-6" id="app">

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
        @if(isset($employee) && $employee)
            <section class="mb-3 rounded-xl bg-white px-4 py-3 shadow-sm ring-1 ring-slate-200">
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">{{ __('Cliente') }}</p>
                <p class="text-base font-semibold text-slate-900">{{ $client->namecommercial }}</p>
                @if($employeeDisplayName)
                    <p class="text-sm font-medium text-slate-600 mt-0.5">{{ __('Empleado') }}: {{ $employeeDisplayName }}</p>
                @endif
            </section>
        @endif

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
            <div class="rounded-2xl overflow-hidden bg-white shadow-sm ring-1 ring-slate-200 mb-6">
                <div class="aspect-[2/1] bg-gradient-to-br from-amber-100 to-amber-50 flex items-center justify-center">
                    <svg class="w-20 h-20 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                </div>
                <div class="p-6 text-center">
                    <p class="text-lg font-medium text-slate-700" id="text-question">{{ __('¿Cómo le hemos atendido hoy?') }}</p>
                    <div class="mt-6 grid grid-cols-5 gap-2">
                        @foreach([1,2,3,4,5] as $n)
                            @php
                                $numbersImgPath = public_path('survey-rating/numbers/'.$n.'.png');
                                $useNumbersImg = $surveyDisplayMode === 'numbers' && is_file($numbersImgPath);
                            @endphp
                            <button type="button" class="btn-score rounded-xl border-2 border-slate-200 bg-white font-semibold text-slate-600 transition hover:border-amber-400 hover:bg-amber-50 hover:text-amber-700 focus:outline-none focus:ring-2 focus:ring-amber-500 {{ $surveyDisplayMode === 'faces' ? 'btn-score--faces' : ($useNumbersImg ? 'btn-score--numbers' : '') }}" data-score="{{ $n }}">
                                @if($surveyDisplayMode === 'faces')
                                    <picture class="contents">
                                        <source srcset="{{ asset('survey-rating/faces/cara'.$n.'.webp') }}" type="image/webp">
                                        <img src="{{ asset('survey-rating/faces/cara'.$n.'.png') }}" alt="" role="presentation" class="h-full w-full object-contain" loading="eager" fetchpriority="high" decoding="async">
                                    </picture>
                                @elseif($useNumbersImg)
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
            </div>
        </section>

        <section data-step id="step-thanks-high" class="flex-1 text-center py-8">
            <p class="text-2xl font-semibold text-slate-800 mb-2">{{ __('¡Gracias!') }}</p>
            <p class="text-slate-600 mb-6">{{ __('Su opinión nos ayuda a mejorar.') }}</p>
            <a id="link-google" href="#" target="_blank" rel="noopener" class="inline-flex items-center gap-2 rounded-xl bg-white px-6 py-3 font-medium text-slate-700 shadow-sm ring-1 ring-slate-200 hover:bg-slate-50">
                <svg class="w-5 h-5" viewBox="0 0 24 24"><path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/><path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/><path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/><path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/></svg>
                {{ __('Dejar reseña en Google') }}
            </a>
        </section>

        <section data-step id="step-reason" class="flex-1">
            <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                <p class="text-lg font-medium text-slate-700 mb-4" id="text-why">{{ isset($improvementBlock['title']) ? $improvementBlock['title'] : __('¿Por qué?') }}</p>
                <div class="flex flex-wrap gap-2" id="reasons-list">
                    @if(!empty($improvementBlock['options']))
                        @foreach($improvementBlock['options'] as $opt)
                            <button type="button" class="btn-reason rounded-xl border border-slate-200 bg-white px-4 py-3 text-slate-700 transition hover:border-amber-300 hover:bg-amber-50" data-option-id="{{ $opt['id'] }}">{{ $opt['label'] }}</button>
                        @endforeach
                    @endif
                </div>
            </div>
        </section>

        <section data-step id="step-thanks-low" class="flex-1 text-center py-8">
            <p class="text-2xl font-semibold text-slate-800 mb-2">{{ __('¡Gracias por ayudarnos a mejorar!') }}</p>
            <p class="text-slate-600">{{ __('Tendremos en cuenta su opinión.') }}</p>
        </section>

        <div id="overlay" class="fixed inset-0 z-10 hidden items-center justify-center bg-slate-900/40">
            <div class="rounded-2xl bg-white px-8 py-6 shadow-xl flex flex-col items-center gap-3">
                <svg class="h-10 w-10 animate-spin text-amber-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                <span class="text-slate-600" id="overlay-text">{{ __('Enviando...') }}</span>
            </div>
        </div>
        @endif
    </div>

    @if($isPwa)
    <script>
(function() {
    const CLIENT_CODE = @json($clientCode);
    const CLIENT_NAME = @json($clientName);
    const EMPLOYEE_CODE = @json($employeeCodeResolved);
    const STORAGE_KEY_DEVICE = 'reputalis_' + CLIENT_CODE + '_devicehash';
    const STORAGE_KEY_PENDING = 'reputalis_' + CLIENT_CODE + '_pending_surveys';

    const i18n = {
        es: { question: '¿Cómo le hemos atendido hoy?', why: '¿Por qué?', thanks: '¡Gracias!', thanksLow: '¡Gracias por ayudarnos a mejorar!', thanksSub: 'Su opinión nos ayuda a mejorar.', thanksLowSub: 'Tendremos en cuenta su opinión.', leaveReview: 'Dejar reseña en Google', sending: 'Enviando...', error: 'No se pudo enviar. Inténtelo de nuevo.', errorNetwork: 'Error de conexión.' },
        en: { question: 'How was your experience today?', why: 'Why?', thanks: 'Thank you!', thanksLow: 'Thanks for helping us improve!', thanksSub: 'Your feedback helps us improve.', thanksLowSub: "We'll take your feedback into account.", leaveReview: 'Leave a review on Google', sending: 'Sending...', error: 'Could not send. Please try again.', errorNetwork: 'Connection error.' }
    };
    const lang = (navigator.language || navigator.userLanguage || '').toLowerCase().startsWith('en') ? 'en' : 'es';
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
        return (navigator.language || navigator.userLanguage || 'es').toLowerCase().slice(0, 2);
    }

    function showStep(stepId) {
        document.querySelectorAll('[data-step]').forEach(el => { el.removeAttribute('data-step'); el.style.display = 'none'; });
        const el = document.getElementById(stepId);
        if (el) { el.setAttribute('data-step', 'active'); el.style.display = 'block'; }
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
            arr.push({ ...payload, employee_code: effectiveEmployeeCode, locale_used: getLocale(), device_hash: getDeviceHash(), _ts: Date.now() });
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
        const body = { 
            client_code: CLIENT_CODE, 
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
                    if (payload.score >= 4) {
                        document.getElementById('link-google').href = 'https://www.google.com/maps/search/?api=1&query=' + encodeURIComponent(CLIENT_NAME);
                        showStep('step-thanks-high');
                    } else showStep('step-thanks-low');
                }
            } else {
                if (!fromQueue) { savePending({ ...payload, employee_code: effectiveEmployeeCode }); alert(data.message || t('error')); }
            }
        })
        .catch(() => {
            if (!fromQueue) { setOverlay(false); savePending({ ...payload, employee_code: effectiveEmployeeCode }); alert(t('errorNetwork')); }
        });
    }

    document.querySelectorAll('[data-score]').forEach(btn => {
        btn.addEventListener('click', function() {
            const score = parseInt(this.dataset.score, 10);
            if (score >= 4) submitSurvey({ score });
            else if (hasImprovementBlock) { window._pendingSurvey = { score }; showStep('step-reason'); }
            else submitSurvey({ score });
        });
    });

    document.getElementById('reasons-list')?.addEventListener('click', function(e) {
        const btn = e.target.closest('[data-option-id]');
        if (!btn || !window._pendingSurvey) return;
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
