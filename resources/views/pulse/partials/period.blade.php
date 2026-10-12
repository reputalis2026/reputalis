<nav class="periods" aria-label="Periodo">
    @foreach([['acumulado', 'Acumulado'], ['hoy', 'Hoy'], ['7', '7 días'], ['30', '30 días']] as [$key, $label])
        <a href="?periodo={{ $key }}" class="{{ (string) $period === (string) $key ? 'is-on' : '' }}">{{ $label }}</a>
    @endforeach
</nav>
