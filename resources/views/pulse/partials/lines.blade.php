@php
    $asPercent = $asPercent ?? false;
    $area = $area ?? false;
    $zoom = $zoom ?? false;
    $legendTop = $legendTop ?? false;
    $inspect = $inspect ?? false;
    $labels = $chart['labels'] ?? [];
    $series = $chart['series'] ?? [];
    $width = $legendTop ? 360 : ($area ? 360 : 320);
    $height = $legendTop ? 210 : ($area ? 188 : 150);
    $padL = $legendTop ? 36 : ($area ? 42 : 28);
    $padR = $legendTop ? 22 : ($area ? 14 : 8);
    $padT = $legendTop ? 14 : ($area ? 16 : 8);
    $padB = $legendTop ? 24 : 22;
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
    } elseif ($area || $legendTop) {
        $target = max($max * 1.12, $legendTop ? 5 : 4);
        $rough = $target / 4;
        $pow = 10 ** floor(log10(max($rough, 1)));
        $frac = $rough / $pow;
        $niceFrac = $frac <= 1 ? 1 : ($frac <= 2 ? 2 : ($frac <= 2.5 ? 2.5 : ($frac <= 5 ? 5 : 10)));
        $step = $legendTop ? ($target <= 20 ? 5 : ($target <= 50 ? 10 : 20)) : $niceFrac * $pow;
        $max = max($step, ceil($target / $step) * $step);
        $ticks = [];
        for ($tick = 0; $tick <= $max + ($step / 10); $tick += $step) {
            $ticks[] = $tick;
        }
    } else {
        $max = max($max, 5);
        $ticks = [0, $max / 2, $max];
    }
    if ($area && $max >= 1000) {
        $padL = 52;
    }
    $count = max(1, count($labels) - 1);
    $innerW = $width - $padL - $padR;
    $innerH = $height - $padB - $padT;
    $tickLabel = function (float $tick) use ($asPercent, $area, $legendTop): string {
        if ($asPercent) {
            return $legendTop ? ((int) round($tick)).' %' : ((int) round($tick)).'%';
        }

        return $area ? \App\Support\Pulse\PulseFormat::integer((int) round($tick)) : (string) (int) round($tick);
    };
    $nudge = [];
    if ($legendTop) {
        $buckets = [];
        foreach ($series as $si => $serie) {
            foreach ($serie['values'] as $index => $value) {
                if ($value === null) {
                    continue;
                }
                $y = $padT + $innerH - (($max - $floor) > 0 ? (($value - $floor) / ($max - $floor)) * $innerH : 0);
                $buckets[$index][] = ['key' => $si.'-'.$index, 'si' => $si, 'y' => $y];
            }
        }
        $maxOffset = 0.0;
        foreach ($buckets as $points) {
            usort($points, fn ($a, $b) => $a['y'] <=> $b['y'] ?: $a['si'] <=> $b['si']);
            $cluster = [];
            $apply = function () use (&$cluster, &$nudge, &$maxOffset) {
                $size = count($cluster);
                if ($size >= 2) {
                    $spread = $size >= 4 ? 12.0 : ($size === 3 ? 20.0 : 28.0);
                    foreach ($cluster as $i => $point) {
                        $dx = ($i - (($size - 1) / 2)) * $spread;
                        $nudge[$point['key']] = $dx;
                        $maxOffset = max($maxOffset, abs($dx));
                    }
                }
                $cluster = [];
            };
            foreach ($points as $point) {
                if ($cluster !== [] && abs($point['y'] - $cluster[array_key_last($cluster)]['y']) > 12) {
                    $apply();
                }
                $cluster[] = $point;
            }
            $apply();
        }
        $half = (int) ceil($maxOffset);
        if ($half > 0) {
            $padL += $half;
            $padR += $half;
            $innerW = $width - $padL - $padR;
        }
    }
@endphp
@if($legendTop)
    <div class="legend is-top">
        @foreach($series as $serie)
            <span><i class="mk mk-{{ $serie['marker'] ?? 'circle' }}" style="color: {{ $serie['color'] }}"></i>{{ $serie['name'] }}</span>
        @endforeach
    </div>
@endif
<svg class="line-chart{{ $area ? ' is-area' : '' }}{{ $legendTop ? ' is-markers' : '' }}" viewBox="0 0 {{ $width }} {{ $height }}" role="img">
    @foreach($ticks as $tick)
        @php $y = $padT + $innerH - (($max - $floor) > 0 ? (($tick - $floor) / ($max - $floor)) * $innerH : 0); @endphp
        <text x="0" y="{{ $y + 3 }}" font-size="10" fill="#7d9198">{{ $tickLabel($tick) }}</text>
        <line x1="{{ $padL }}" y1="{{ $y }}" x2="{{ $legendTop ? ($width - $padR) : ($width - 8) }}" y2="{{ $y }}" stroke="#e6eef0"/>
    @endforeach
    @foreach($series as $si => $serie)
        @php
            $pts = [];
            foreach ($serie['values'] as $index => $value) {
                if ($value === null) {
                    continue;
                }
                $dx = $nudge[$si.'-'.$index] ?? 0;
                $x = $padL + ($index / $count) * $innerW + $dx;
                $y = $padT + $innerH - (($max - $floor) > 0 ? (($value - $floor) / ($max - $floor)) * $innerH : 0);
                $pts[] = ['x' => round($x, 1), 'y' => round($y, 1), 'v' => $value, 'i' => $index, 'dx' => $dx];
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
            @if($legendTop)
                @php $marker = $serie['marker'] ?? 'circle'; @endphp
                @foreach($pts as $pt)
                    @if($marker === 'square')
                        <rect x="{{ $pt['x'] - 4 }}" y="{{ $pt['y'] - 4 }}" width="8" height="8" fill="{{ $serie['color'] }}"/>
                    @elseif($marker === 'diamond')
                        <rect x="{{ $pt['x'] - 3.5 }}" y="{{ $pt['y'] - 3.5 }}" width="7" height="7" fill="{{ $serie['color'] }}" transform="rotate(45 {{ $pt['x'] }} {{ $pt['y'] }})"/>
                    @elseif($marker === 'triangle')
                        <polygon points="{{ $pt['x'] }},{{ $pt['y'] - 5 }} {{ $pt['x'] - 5 }},{{ $pt['y'] + 4 }} {{ $pt['x'] + 5 }},{{ $pt['y'] + 4 }}" fill="{{ $serie['color'] }}"/>
                    @else
                        <circle cx="{{ $pt['x'] }}" cy="{{ $pt['y'] }}" r="4" fill="{{ $serie['color'] }}"/>
                    @endif
                @endforeach
            @endif
            @if($inspect)
                @foreach($pts as $pt)
                    <circle class="line-hit" cx="{{ $pt['x'] }}" cy="{{ $pt['y'] }}" r="{{ abs($pt['dx'] ?? 0) > 0 ? 11 : 9 }}" fill="transparent"
                        data-name="{{ $serie['name'] }}"
                        data-when="{{ $labels[$pt['i']] ?? '' }}"
                        data-value="{{ str_replace('.', ',', (string) $pt['v']) }} %"/>
                @endforeach
            @endif
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
@if(empty($area) && empty($legendTop))
<div class="legend">
    @foreach($series as $serie)
        <span><i style="background: {{ $serie['color'] }}"></i>{{ $serie['name'] }}</span>
    @endforeach
</div>
@endif
