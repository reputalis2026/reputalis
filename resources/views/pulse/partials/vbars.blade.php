<div class="vbars{{ !empty($plot) ? ' is-plot' : '' }}" role="img" aria-label="{{ $caption ?? '' }}">
    @foreach($points as $point)
        @php
            $value = $point['value'];
            $height = ($value === null || $max <= 0) ? 0 : max(8, ($value / $max) * 100);
        @endphp
        <div class="bar-col">
            @if(!empty($plot))
                <span class="bar-plot" style="--h: {{ $height }}%">
                    @if(($point['text'] ?? '') !== '')
                        <b>{{ $point['text'] }}</b>
                    @endif
                    <i class="{{ $tone ?? '' }} {{ $loop->last ? ($lastTone ?? 'is-last') : '' }}"></i>
                </span>
                <em>{{ $point['label'] }}</em>
            @else
                <b>{{ $point['text'] ?? '' }}</b>
                <i class="{{ $tone ?? '' }} {{ $loop->last ? ($lastTone ?? 'is-last') : '' }}" style="height: {{ $height }}%"></i>
                <em>{{ $point['label'] }}</em>
            @endif
        </div>
    @endforeach
</div>
