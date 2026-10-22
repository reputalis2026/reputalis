@php
    $countsAreLong = collect($bars)->contains(fn ($bar) => ($bar['count'] ?? 0) >= 1000);
@endphp
<div class="bars{{ !empty($tracked) ? ' is-tracked' : '' }}{{ !empty($plot) ? ' is-plot' : '' }}{{ $countsAreLong ? ' is-long' : '' }}" @if(empty($showCounts) && empty($tracked) && empty($plot)) aria-hidden="true" @endif>
    @foreach($bars as $bar)
        @php
            $countLabel = \App\Support\Pulse\PulseFormat::integer($bar['count']);
        @endphp
        <div class="bar-col">
            @if(!empty($plot))
                <span class="bar-plot{{ $bar['count'] > 0 ? '' : ' is-empty' }}" style="--h: {{ $bar['count'] > 0 ? $bar['height'] : 0 }}%">
                    @if($bar['count'] > 0 || !empty($showZero))
                        <b class="bar-count">{{ $countLabel }}</b>
                    @endif
                    <i class="s{{ $bar['score'] }}"></i>
                </span>
            @else
                @if(!empty($tracked) || (!empty($showCounts) && $bar['count'] > 0))
                    <b class="bar-count">{{ $countLabel }}</b>
                @endif
                @if(!empty($tracked))
                    <span class="bar-track"><i class="s{{ $bar['score'] }}{{ $bar['count'] > 0 ? '' : ' is-empty' }}" @if($bar['count'] > 0) style="height: {{ $bar['height'] }}%" @endif></i></span>
                @else
                    <i class="s{{ $bar['score'] }}" style="height: {{ $bar['height'] }}%"></i>
                @endif
            @endif
            @if(empty($hideScale))
                <em>{{ $bar['score'] }}@if(!empty($showStars))★@endif</em>
            @endif
        </div>
    @endforeach
</div>
