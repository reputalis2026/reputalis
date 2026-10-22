@extends('pulse.layout')

@section('content')
    <main class="screen stack support">
        <section class="card support-hero">
            <span class="support-icon" aria-hidden="true">
                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#0e7f88" stroke-width="1.6"><circle cx="12" cy="8" r="3"/><path d="M5 19c1.5-3 4-4.5 7-4.5S17.5 16 19 19"/></svg>
            </span>
            <div>
                <h2>Estamos a tu disposición</h2>
                <p class="muted">Atención de persona a persona<br>cuando lo necesitas.</p>
            </div>
        </section>
        <section class="support-help">
            <h2 class="block-title">Podemos ayudarte con</h2>
            <div class="card">
                <ul class="support-list">
                    <li>Altas y bajas del personal</li>
                    <li>Consultas sobre REPUTALIS</li>
                    <li>Asesoramiento y soporte</li>
                </ul>
            </div>
        </section>
        <section class="support-contact">
            <h2 class="block-title">Contacto</h2>
            <div class="stack">
                @if($phone)
                    <div class="card contact">
                        <span class="support-icon" aria-hidden="true">
                            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#0e7f88" stroke-width="1.7"><path d="M6 4h3l2 5-2 1a12 12 0 0 0 5 5l1-2 5 2v3a2 2 0 0 1-2 2A16 16 0 0 1 4 6a2 2 0 0 1 2-2z"/></svg>
                        </span>
                        <div class="grow">
                            <span class="muted">Teléfono</span>
                            <strong>{{ $phone }}</strong>
                        </div>
                        <a class="btn" href="tel:{{ preg_replace('/\s+/', '', $phone) }}">Llamar</a>
                    </div>
                @endif
                @if($email)
                    <div class="card contact">
                        <span class="support-icon" aria-hidden="true">
                            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#0e7f88" stroke-width="1.7"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 7l9 7 9-7"/></svg>
                        </span>
                        <div class="grow">
                            <span class="muted">Correo electrónico</span>
                            <strong>{{ $email }}</strong>
                        </div>
                        <a class="btn btn-ghost" href="mailto:{{ $email }}">Enviar correo</a>
                    </div>
                @endif
            </div>
        </section>
        <form class="logout" id="pulse-logout" method="POST" action="{{ url('/logout') }}" onsubmit="try{localStorage.removeItem('pulse_auth')}catch(e){}">
            @csrf
            <button type="button" class="btn btn-ghost" id="pulse-logout-open">Cerrar sesión</button>
        </form>
        <dialog class="confirm" id="pulse-logout-dialog">
            <p>Si cierras sesión tendrás que volver a entrar con tu usuario y contraseña.</p>
            <div class="confirm-actions">
                <button type="button" class="btn btn-ghost" id="pulse-logout-cancel">Cancelar</button>
                <button type="submit" class="btn" form="pulse-logout">Cerrar sesión</button>
            </div>
        </dialog>
    </main>
@endsection

@push('scripts')
<script>
(function () {
    var open = document.getElementById('pulse-logout-open');
    var dialog = document.getElementById('pulse-logout-dialog');
    var cancel = document.getElementById('pulse-logout-cancel');
    if (!open || !dialog || !cancel) return;
    open.addEventListener('click', function () {
        if (typeof dialog.showModal === 'function') {
            dialog.showModal();
            cancel.focus();
            return;
        }
        if (window.confirm('Si cierras sesión tendrás que volver a entrar con tu usuario y contraseña.')) {
            document.getElementById('pulse-logout').requestSubmit();
        }
    });
    cancel.addEventListener('click', function () { dialog.close(); });
})();
</script>
@endpush
