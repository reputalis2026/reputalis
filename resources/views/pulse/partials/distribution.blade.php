<div class="bars{{ !empty($tracked) ? ' is-tracked' : '' }}{{ !empty($plot) ? ' is-plot' : '' }}" @if(empty($showCounts) && empty($tracked) && empty($plot)) aria-hidden="true" @endif>
    @foreach($bars as $bar)
        <div class="bar-col">
            @if(!empty($plot))
                <span class="bar-plot" style="--h: {{ $bar['count'] > 0 ? $bar['height'] : 0 }}%">
                    @if($bar['count'] > 0)
                        <b class="bar-count">{{ \App\Support\Pulse\PulseFormat::integer($bar['count']) }}</b>
                    @endif
                    <i class="s{{ $bar['score'] }}"></i>
                </span>
            @else
                @if(!empty($tracked) || (!empty($showCounts) && $bar['count'] > 0))
                    <b class="bar-count">{{ \App\Support\Pulse\PulseFormat::integer($bar['count']) }}</b>
                @endif
                @if(!empty($tracked))
                    <span class="bar-track"><i class="s{{ $bar['score'] }}" style="height: {{ $bar['count'] > 0 ? $bar['height'] : 0 }}%"></i></span>
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
