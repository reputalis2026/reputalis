@php
    $asPercent = $asPercent ?? false;
    $area = $area ?? false;
    $zoom = $zoom ?? false;
    $labels = $chart['labels'] ?? [];
    $series = $chart['series'] ?? [];
    $width = $area ? 360 : 320;
    $height = $area ? 188 : 150;
    $padL = $area ? 42 : 28;
    $padT = $area ? 16 : 8;
    $padB = 22;
    $max = 1;
    foreach ($series as $serie) {
        foreach ($serie['values'] as $value) {
            if ($value !== null && $value > $max) {
                $max = $value;
            }
        }
    }
    $floor = 0.0;
    $present = [];
    foreach ($series as $serie) {
        foreach ($serie['values'] as $value) {
            if ($value !== null) {
                $present[] = $value;
            }
        }
    }
    $minValue = $present === [] ? 0 : min($present);
    if ($area && $zoom && $minValue > 0 && $max > $minValue && (($max - $minValue) / $max) < 0.35) {
        $span = $max - $minValue;
        $low = max(0, $minValue - ($span * 0.8));
        $high = $max + ($span * 0.55);
        $rough = max(($high - $low) / 4, 1);
        $pow = 10 ** floor(log10($rough));
        $frac = $rough / $pow;
        $niceFrac = $frac <= 1 ? 1 : ($frac <= 2 ? 2 : ($frac <= 2.5 ? 2.5 : ($frac <= 5 ? 5 : 10)));
        $step = $niceFrac * $pow;
        $floor = floor($low / $step) * $step;
        $max = ceil($high / $step) * $step;
        if ($max <= $floor) {
            $max = $floor + ($step * 4);
        }
        $ticks = [];
        for ($tick = $floor; $tick <= $max + ($step / 10); $tick += $step) {
            $ticks[] = $tick;
        }
    } elseif ($area) {
        $target = max($max * 1.12, 4);
        $rough = $target / 4;
        $pow = 10 ** floor(log10(max($rough, 1)));
        $frac = $rough / $pow;
        $niceFrac = $frac <= 1 ? 1 : ($frac <= 2 ? 2 : ($frac <= 2.5 ? 2.5 : ($frac <= 5 ? 5 : 10)));
        $step = $niceFrac * $pow;
        $max = max($step, ceil($target / $step) * $step);
        $ticks = [];
        for ($i = 0; $i <= 4; $i++) {
            $ticks[] = $max * $i / 4;
        }
    } else {
        $max = max($max, 5);
        $ticks = [0, $max / 2, $max];
    }
    if ($area && $max >= 1000) {
        $padL = 52;
    }
    $count = max(1, count($labels) - 1);
    $innerW = $width - $padL - ($area ? 14 : 8);
    $innerH = $height - $padB - $padT;
@endphp
<svg class="line-chart{{ $area ? ' is-area' : '' }}" viewBox="0 0 {{ $width }} {{ $height }}" role="img">
    @foreach($ticks as $tick)
        @php $y = $padT + $innerH - (($max - $floor) > 0 ? (($tick - $floor) / ($max - $floor)) * $innerH : 0); @endphp
        <text x="0" y="{{ $y + 3 }}" font-size="10" fill="#7d9198">{{ $asPercent ? (int) round($tick).'%' : ($area ? \App\Support\Pulse\PulseFormat::integer((int) round($tick)) : (int) round($tick)) }}</text>
        <line x1="{{ $padL }}" y1="{{ $y }}" x2="{{ $width - ($area ? 8 : 4) }}" y2="{{ $y }}" stroke="#e6eef0"/>
    @endforeach
    @foreach($series as $serie)
        @php
            $pts = [];
            foreach ($serie['values'] as $index => $value) {
                if ($value === null) {
                    continue;
                }
                $x = $padL + ($index / $count) * $innerW;
                $y = $padT + $innerH - (($max - $floor) > 0 ? (($value - $floor) / ($max - $floor)) * $innerH : 0);
                $pts[] = ['x' => round($x, 1), 'y' => round($y, 1), 'v' => $value];
            }
            $coord = implode(' ', array_map(fn ($pt) => $pt['x'].','.$pt['y'], $pts));
            $baseY = round($padT + $innerH, 1);
            $poly = $pts === [] ? '' : $pts[0]['x'].','.$baseY.' '.$coord.' '.$pts[array_key_last($pts)]['x'].','.$baseY;
        @endphp
        @if($pts !== [])
            @if($area)
                <polygon points="{{ $poly }}" fill="#d7eef4"/>
            @endif
            <polyline fill="none" stroke="{{ $serie['color'] }}" stroke-width="2" points="{{ $coord }}"/>
            @if($area)
                @foreach($pts as $pt)
                    <circle cx="{{ $pt['x'] }}" cy="{{ $pt['y'] }}" r="3.2" fill="#fff" stroke="{{ $serie['color'] }}" stroke-width="2"/>
                    @if((int) $pt['v'] > 0)
                        <text x="{{ $pt['x'] }}" y="{{ max(10, $pt['y'] - 8) }}" font-size="9" text-anchor="middle" fill="#5d737a">{{ \App\Support\Pulse\PulseFormat::integer((int) $pt['v']) }}</text>
                    @endif
                @endforeach
            @endif
        @endif
    @endforeach
    @foreach($labels as $index => $label)
        @php $x = $padL + ($index / $count) * $innerW; @endphp
        <text x="{{ $x }}" y="{{ $height - 4 }}" font-size="{{ $area ? 11 : 10 }}" text-anchor="middle" fill="#7d9198">{{ $label }}</text>
    @endforeach
</svg>
@if(empty($area))
<div class="legend">
    @foreach($series as $serie)
        <span><i style="background: {{ $serie['color'] }}"></i>{{ $serie['name'] }}</span>
    @endforeach
</div>
@endif
