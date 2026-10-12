<div class="state">
    <p>{{ $message }}</p>
    @if(!empty($retry))
        <a class="retry" href="{{ url()->current() }}">Reintentar</a>
    @endif
</div>
