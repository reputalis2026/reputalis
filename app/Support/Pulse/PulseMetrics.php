<?php

namespace App\Support\Pulse;

use App\Models\Client;
use App\Models\ClientExternalReputationAlert;
use App\Models\ClientExternalReputationSnapshot;
use App\Models\ClientImprovementOption;
use App\Models\CsatSurvey;
use App\Models\Employee;
use App\Support\ExternalReputation\RatingProjection;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class PulseMetrics
{
    public const PERIODS = ['acumulado', 'hoy', '7', '30'];

    /**
     * @return array{0: Carbon, 1: Carbon}|null
     */
    public function bounds(string $period): ?array
    {
        $now = Carbon::now($this->timezone());

        return match ($period) {
            'hoy' => [$now->copy()->startOfDay(), $now->copy()->endOfDay()],
            '7' => [$now->copy()->subDays(6)->startOfDay(), $now->copy()->endOfDay()],
            '30' => [$now->copy()->subDays(29)->startOfDay(), $now->copy()->endOfDay()],
            default => null,
        };
    }

    /**
     * @return array<string, mixed>
     */
    public function home(Client $client, array $readSurveyIds = []): array
    {
        $internal = $this->internalSummary($client->id, 'acumulado');
        $today = $this->internalSummary($client->id, 'hoy');
        $external = $this->externalSummary($client, 'acumulado');
        if (is_array($external)) {
            $external['reviews_today'] = $this->reviewsAddedToday($client);
        }
        $counts = $this->counts($client, $readSurveyIds);

        return [
            'internal' => $internal,
            'today' => $today,
            'external' => $external,
            'alerts' => $counts['alerts'],
            'notifications' => $counts['notifications'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function internalSummary(string $clientId, string $period, ?string $employeeId = null): array
    {
        $counts = $this->scoreCounts($clientId, $period, $employeeId);
        $total = array_sum($counts);
        if ($total === 0) {
            return [
                'empty' => true,
                'total' => 0,
                'average' => null,
                'average_label' => null,
                'positive_pct' => null,
                'positive_label' => null,
                'distribution' => $this->distribution($counts),
                'incidents' => 0,
            ];
        }

        $weighted = 0;
        foreach ($counts as $score => $count) {
            $weighted += $score * $count;
        }
        $average = $weighted / $total;
        $positive = $counts[4] + $counts[5];
        $positivePct = ($positive / $total) * 100;

        return [
            'empty' => false,
            'total' => $total,
            'average' => $average,
            'average_label' => PulseFormat::decimal($average, 2),
            'positive_pct' => $positivePct,
            'positive_label' => PulseFormat::percent($positivePct, 1),
            'distribution' => $this->distribution($counts),
            'incidents' => $counts[1] + $counts[2] + $counts[3],
        ];
    }

    /**
     * Media y encuestas acumuladas al cierre de cada uno de los últimos 6 meses.
     *
     * @return array<int, array{label: string, average: float|null, total: int}>
     */
    public function internalMonths(string $clientId, ?string $employeeId = null): array
    {
        $points = [];
        foreach ($this->monthEnds() as $month) {
            $counts = $this->scoreCountsUntil($clientId, $month['until'], $employeeId);
            $total = array_sum($counts);
            $average = null;
            if ($total > 0) {
                $weighted = 0;
                foreach ($counts as $score => $count) {
                    $weighted += $score * $count;
                }
                $average = $weighted / $total;
            }
            $points[] = [
                'label' => $month['label'],
                'average' => $average,
                'total' => $total,
            ];
        }

        return $points;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function externalSummary(Client $client, string $period): ?array
    {
        $snapshot = $this->snapshotForPeriod($client, 'acumulado');
        if (! $snapshot) {
            return null;
        }

        $stars = $snapshot->starsBreakdown();
        $raw = $snapshot->calculated_rating !== null
            ? (float) $snapshot->calculated_rating
            : RatingProjection::calculatedRating($stars);
        $google = $snapshot->rating !== null ? (float) $snapshot->rating : null;
        $progress = $raw !== null ? RatingProjection::objectiveProgress($raw) : null;
        $target = $progress['to'] ?? null;
        $needed = $target !== null
            ? RatingProjection::fiveStarsNeededForTarget($stars, (float) $target)
            : null;
        $captured = $snapshot->captured_at?->timezone($this->timezone());
        $reviews = (int) $snapshot->reviews_total;
        $distribution = $this->distribution($stars);
        if ($period !== 'acumulado') {
            $delta = $this->periodReviewDelta($client, $period);
            $reviews = $delta['reviews'];
            $distribution = $this->distribution($delta['stars']);
        }

        return [
            'empty' => false,
            'google' => $google,
            'google_label' => PulseFormat::decimal($google, 1),
            'real' => $raw,
            'real_label' => PulseFormat::decimal($raw, 2),
            'reviews' => $reviews,
            'distribution' => $distribution,
            'target' => $target,
            'target_label' => $target !== null ? PulseFormat::decimal((float) $target, 1) : null,
            'progress' => (int) ($progress['percent'] ?? 0),
            'from_label' => isset($progress['from']) ? PulseFormat::decimal((float) $progress['from'], 1) : null,
            'needed' => $needed,
            'captured' => $captured,
            'updated_label' => $this->updatedLabel($captured),
            'error' => null,
        ];
    }

    /**
     * @return array<int, array{label: string, real: float|null, google: float|null, reviews: int|null}>
     */
    public function externalMonths(Client $client): array
    {
        $snapshots = $client->externalReputationSnapshots()
            ->reorder()
            ->orderBy('captured_at')
            ->get(['captured_at', 'rating', 'reviews_total', 'calculated_rating', 'stars_1', 'stars_2', 'stars_3', 'stars_4', 'stars_5']);

        $points = [];
        foreach ($this->monthEnds() as $month) {
            $match = $snapshots
                ->filter(fn (ClientExternalReputationSnapshot $row): bool => $row->captured_at !== null
                    && $row->captured_at->timezone($this->timezone())->lessThanOrEqualTo($month['until']))
                ->last();
            $real = null;
            $google = null;
            $reviews = null;
            if ($match) {
                $real = $match->calculated_rating !== null
                    ? (float) $match->calculated_rating
                    : RatingProjection::calculatedRating($match->starsBreakdown());
                $google = $match->rating !== null ? (float) $match->rating : null;
                $reviews = (int) $match->reviews_total;
            }
            $points[] = [
                'label' => $month['label'],
                'real' => $real,
                'google' => $google,
                'reviews' => $reviews,
            ];
        }

        return $points;
    }

    public function externalError(Client $client): ?string
    {
        if ($client->latestExternalReputationSnapshot() !== null) {
            return null;
        }

        $error = trim((string) $client->external_reputation_last_error);

        return $error !== '' ? $error : null;
    }

    /**
     * @return array{ranked: array<int, array<string, mixed>>, unranked: array<int, array<string, mixed>>, total_ranked: int}
     */
    public function team(Client $client): array
    {
        $min = max(1, (int) config('elpulso.min_sample', 10));
        $rows = $this->employeeAggregates($client->id);
        $employees = Employee::query()
            ->where('client_id', $client->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        $ranked = [];
        $unranked = [];
        foreach ($employees as $employee) {
            $stats = $rows->get($employee->id);
            $surveys = (int) ($stats->surveys ?? 0);
            $average = $surveys > 0 ? ((float) $stats->weighted) / $surveys : null;
            $item = [
                'id' => (string) $employee->id,
                'name' => (string) $employee->name,
                'surveys' => $surveys,
                'average' => $average,
                'average_label' => PulseFormat::decimal($average, 2),
                'incidents' => (int) ($stats->incidents ?? 0),
            ];
            if ($surveys >= $min && $average !== null) {
                $ranked[] = $item;
            } else {
                $unranked[] = $item;
            }
        }

        usort($ranked, function (array $a, array $b): int {
            $byScore = $b['average'] <=> $a['average'];
            if ($byScore !== 0) {
                return $byScore;
            }

            return $b['surveys'] <=> $a['surveys'];
        });

        foreach ($ranked as $index => &$item) {
            $item['position'] = $index + 1;
        }
        unset($item);

        return [
            'ranked' => $ranked,
            'unranked' => $unranked,
            'total_ranked' => count($ranked),
            'min_sample' => $min,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function employee(Client $client, string $employeeId, string $period): ?array
    {
        $employee = Employee::query()
            ->where('client_id', $client->id)
            ->whereKey($employeeId)
            ->first();
        if (! $employee) {
            return null;
        }

        $team = $this->team($client);
        $position = null;
        foreach ($team['ranked'] as $row) {
            if ($row['id'] === (string) $employee->id) {
                $position = $row['position'];
                break;
            }
        }

        $summary = $this->internalSummary($client->id, $period, (string) $employee->id);
        $months = $this->internalMonths($client->id, (string) $employee->id);

        return [
            'id' => (string) $employee->id,
            'name' => (string) $employee->name,
            'position' => $position,
            'ranked_total' => $team['total_ranked'],
            'summary' => $summary,
            'months' => $months,
            'points' => $this->employeePoints($client, (string) $employee->id),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function improvements(Client $client, string $period): array
    {
        $summary = $this->internalSummary($client->id, $period);
        $options = $this->options($client);
        $counts = $this->optionCounts($client->id, $period);
        $incidents = (int) $counts->sum();
        $withPoint = $this->surveysWithPoint($client->id, $period);
        $rows = [];
        $palette = ['#c4a35a', '#c46b8a', '#7a6bb5', '#1f8f8a', '#d17a45', '#4c7fd1'];
        foreach ($options as $index => $option) {
            $times = (int) ($counts[$option->id] ?? 0);
            $share = $incidents > 0 ? ($times / $incidents) * 100 : 0.0;
            $rows[] = [
                'id' => (string) $option->id,
                'label' => $option->labelForLocale('es'),
                'times' => $times,
                'share' => $share,
                'share_label' => PulseFormat::percent($share, 1),
                'color' => $palette[$index % count($palette)],
            ];
        }
        usort($rows, fn (array $a, array $b): int => $b['times'] <=> $a['times']);

        $mentioned = $summary['total'] > 0 ? ($withPoint / $summary['total']) * 100 : null;

        return [
            'empty' => $summary['empty'] && $options->isEmpty(),
            'surveys' => $summary['total'],
            'with_point' => $withPoint,
            'with_point_label' => $summary['total'] > 0 ? PulseFormat::percent($mentioned, 1) : null,
            'rows' => $rows,
            'incidents' => $incidents,
            'series' => $this->improvementSeries($client, $options, $palette),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function improvementDetail(Client $client, string $optionId, string $period): ?array
    {
        $option = $this->options($client)->firstWhere('id', $optionId);
        if (! $option) {
            return null;
        }

        $summary = $this->internalSummary($client->id, $period);
        $times = (int) ($this->optionCounts($client->id, $period)[$optionId] ?? 0);
        $share = $summary['total'] > 0 ? ($times / $summary['total']) * 100 : null;
        $months = $this->optionMonthRates($client->id, $optionId);
        $first = null;
        $last = null;
        foreach ($months as $point) {
            if ($point['rate'] === null) {
                continue;
            }
            $first ??= $point['rate'];
            $last = $point['rate'];
        }
        $trend = null;
        if ($first !== null && $last !== null) {
            $trend = $last < $first - 0.05 ? 'favorable' : ($last > $first + 0.05 ? 'desfavorable' : 'estable');
        }

        return [
            'id' => (string) $option->id,
            'label' => $option->labelForLocale('es'),
            'times' => $times,
            'surveys' => $summary['total'],
            'share' => $share,
            'share_label' => PulseFormat::percent($share, 1),
            'empty' => $summary['empty'],
            'months' => $months,
            'trend' => $trend,
            'from_label' => PulseFormat::percent($first, 1),
            'to_label' => PulseFormat::percent($last, 1),
            'by_employee' => $this->optionByEmployee($client, $optionId),
        ];
    }

    /**
     * @param  array<int, string>  $readSurveyIds
     * @return array{items: array<int, array<string, mixed>>, unread: int, internal_unread: int, google_unread: int}
     */
    public function alerts(Client $client, array $readSurveyIds, bool $unreadOnly): array
    {
        $items = [];
        $googleUnread = 0;
        $alerts = $client->externalReputationAlerts()->orderByDesc('detected_at')->limit(40)->get();
        foreach ($alerts as $alert) {
            $unread = $alert->isUnread();
            if ($unread) {
                $googleUnread++;
            }
            if ($unreadOnly && ! $unread) {
                continue;
            }
            $items[] = $this->googleAlertItem($alert, $client);
        }

        $internalUnread = (int) $this->internalAlertQuery($client)
            ->when($readSurveyIds !== [], fn ($query) => $query->whereNotIn('id', $readSurveyIds))
            ->count();
        $surveys = $this->internalAlertQuery($client)
            ->with('improvementOption')
            ->orderByDesc('created_at')
            ->limit(40)
            ->get();
        foreach ($surveys as $survey) {
            $unread = ! in_array((string) $survey->id, $readSurveyIds, true);
            if ($unreadOnly && ! $unread) {
                continue;
            }
            $label = $survey->improvementOption?->labelForLocale('es');
            $items[] = [
                'kind' => 'internal',
                'id' => (string) $survey->id,
                'tone' => 'interna',
                'title' => $label !== null && $label !== '' ? $label : 'Valoración de '.$survey->score.'★',
                'date' => PulseFormat::dayMonth($survey->created_at->timezone($this->timezone())),
                'sort' => $survey->created_at->getTimestamp(),
                'unread' => $unread,
                'body' => 'Valoración interna de '.$survey->score.'★.',
                'link' => null,
                'read_url' => url('/pulse/'.$client->code.'/alertas/encuesta/'.$survey->id.'/leida'),
            ];
        }

        usort($items, fn (array $a, array $b): int => $b['sort'] <=> $a['sort']);

        return [
            'items' => $items,
            'unread' => $googleUnread + $internalUnread,
            'internal_unread' => $internalUnread,
            'google_unread' => $googleUnread,
        ];
    }

    /**
     * @return array{alerts: int, notifications: int}
     */
    public function counts(Client $client, array $readSurveyIds = []): array
    {
        $google = (int) $client->externalReputationAlerts()->unread()->count();
        $internal = (int) $this->internalAlertQuery($client)
            ->when($readSurveyIds !== [], fn ($query) => $query->whereNotIn('id', $readSurveyIds))
            ->count();

        return [
            'alerts' => $google + $internal,
            'notifications' => 0,
        ];
    }

    /**
     * @param  array<int, int>  $counts
     * @return array<int, array{score: int, count: int, height: float}>
     */
    private function distribution(array $counts): array
    {
        $max = max(1, ...array_values($counts));
        $bars = [];
        foreach ([1, 2, 3, 4, 5] as $score) {
            $count = (int) ($counts[$score] ?? 0);
            $bars[] = [
                'score' => $score,
                'count' => $count,
                'height' => $count > 0 ? max(12, ($count / $max) * 100) : 6,
            ];
        }

        return $bars;
    }

    /**
     * @return array<int, int>
     */
    private function scoreCounts(string $clientId, string $period, ?string $employeeId = null): array
    {
        $query = CsatSurvey::query()->where('client_id', $clientId);
        if ($employeeId !== null) {
            $query->where('employee_id', $employeeId);
        }
        $window = $this->queryWindow($this->bounds($period));
        if ($window !== null) {
            $query->whereBetween('created_at', $window);
        }

        return $this->pluckScores($query);
    }

    /**
     * @return array<int, int>
     */
    private function scoreCountsUntil(string $clientId, Carbon $until, ?string $employeeId = null): array
    {
        $query = CsatSurvey::query()
            ->where('client_id', $clientId)
            ->where('created_at', '<=', $until->copy()->timezone(config('app.timezone')));
        if ($employeeId !== null) {
            $query->where('employee_id', $employeeId);
        }

        return $this->pluckScores($query);
    }

    /**
     * @return array<int, int>
     */
    private function pluckScores($query): array
    {
        $rows = $query
            ->selectRaw('score, COUNT(*) as aggregate')
            ->groupBy('score')
            ->pluck('aggregate', 'score');

        $counts = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0];
        foreach ($rows as $score => $aggregate) {
            $score = (int) $score;
            if (isset($counts[$score])) {
                $counts[$score] = (int) $aggregate;
            }
        }

        return $counts;
    }

    private function reviewsAddedToday(Client $client): int
    {
        return $this->periodReviewDelta($client, 'hoy')['reviews'];
    }

    /**
     * Reseñas y estrellas nuevas entre el inicio del periodo y la última captura.
     *
     * @return array{reviews: int, stars: array<int, int>}
     */
    private function periodReviewDelta(Client $client, string $period): array
    {
        $empty = ['reviews' => 0, 'stars' => [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0]];
        $window = $this->queryWindow($this->bounds($period));
        if ($window === null) {
            return $empty;
        }

        $latest = $client->externalReputationSnapshots()
            ->whereBetween('captured_at', $window)
            ->orderByDesc('captured_at')
            ->first();
        if ($latest === null) {
            return $empty;
        }

        $previous = $client->externalReputationSnapshots()
            ->where('captured_at', '<', $window[0])
            ->orderByDesc('captured_at')
            ->first();
        if ($previous === null) {
            return [
                'reviews' => (int) $latest->reviews_total,
                'stars' => $latest->starsBreakdown(),
            ];
        }

        $stars = [];
        $current = $latest->starsBreakdown();
        $before = $previous->starsBreakdown();
        foreach ([1, 2, 3, 4, 5] as $score) {
            $stars[$score] = max(0, $current[$score] - $before[$score]);
        }

        return [
            'reviews' => max(0, (int) $latest->reviews_total - (int) $previous->reviews_total),
            'stars' => $stars,
        ];
    }

    private function snapshotForPeriod(Client $client, string $period): ?ClientExternalReputationSnapshot
    {
        $query = $client->externalReputationSnapshots()->orderByDesc('captured_at');
        $window = $this->queryWindow($this->bounds($period));
        if ($window !== null) {
            $query->whereBetween('captured_at', $window);
        }

        return $query->first();
    }

    private function updatedLabel(?Carbon $captured): ?string
    {
        if ($captured === null) {
            return null;
        }

        $time = $captured->format('H:i');
        $today = Carbon::now($this->timezone())->isSameDay($captured);

        return $today
            ? 'Actualizado hoy, '.$time
            : 'Actualizado el '.PulseFormat::dayMonth($captured).', '.$time;
    }

    private function internalAlertQuery(Client $client)
    {
        $from = Carbon::now($this->timezone())->subDays(29)->startOfDay()->timezone((string) config('app.timezone'));

        return CsatSurvey::query()
            ->where('client_id', $client->id)
            ->whereIn('score', [1, 2, 3])
            ->where('created_at', '>=', $from);
    }

    /**
     * @return Collection<string, object>
     */
    private function employeeAggregates(string $clientId): Collection
    {
        return CsatSurvey::query()
            ->where('client_id', $clientId)
            ->whereNotNull('employee_id')
            ->select([
                'employee_id',
                DB::raw('COUNT(*) as surveys'),
                DB::raw('SUM(score) as weighted'),
                DB::raw('SUM(CASE WHEN score IN (1, 2, 3) THEN 1 ELSE 0 END) as incidents'),
            ])
            ->groupBy('employee_id')
            ->get()
            ->keyBy('employee_id');
    }

    /**
     * @return array<int, array{label: string, times: int, share: float, share_label: string|null}>
     */
    private function employeePoints(Client $client, string $employeeId): array
    {
        $until = Carbon::now($this->timezone())->endOfDay();
        $from = Carbon::now($this->timezone())->startOfMonth()->subMonths(5)->startOfDay();
        $window = $this->queryWindow([$from, $until]);
        $rows = CsatSurvey::query()
            ->where('client_id', $client->id)
            ->where('employee_id', $employeeId)
            ->whereNotNull('improvement_option_id')
            ->whereBetween('created_at', $window)
            ->selectRaw('improvement_option_id, COUNT(*) as aggregate')
            ->groupBy('improvement_option_id')
            ->pluck('aggregate', 'improvement_option_id');
        $total = (int) $rows->sum();
        $options = $this->options($client)->keyBy('id');
        $points = [];
        foreach ($rows as $id => $times) {
            $option = $options->get($id);
            if (! $option) {
                continue;
            }
            $share = $total > 0 ? ((int) $times / $total) * 100 : 0.0;
            $points[] = [
                'label' => $option->labelForLocale('es'),
                'times' => (int) $times,
                'share' => $share,
                'share_label' => PulseFormat::percent($share, 1),
            ];
        }
        usort($points, fn (array $a, array $b): int => $b['times'] <=> $a['times']);

        return $points;
    }

    /**
     * @return Collection<int, ClientImprovementOption>
     */
    private function options(Client $client): Collection
    {
        $client->loadMissing('improvementConfig.options');
        $options = $client->improvementConfig?->options ?? collect();

        return $options
            ->filter(fn (ClientImprovementOption $option): bool => (bool) $option->is_active && $option->labelForLocale('es') !== '')
            ->values();
    }

    /**
     * @return Collection<string, int>
     */
    private function optionCounts(string $clientId, string $period): Collection
    {
        $query = CsatSurvey::query()
            ->where('client_id', $clientId)
            ->whereNotNull('improvement_option_id');
        $window = $this->queryWindow($this->bounds($period));
        if ($window !== null) {
            $query->whereBetween('created_at', $window);
        }

        return $query
            ->selectRaw('improvement_option_id, COUNT(*) as aggregate')
            ->groupBy('improvement_option_id')
            ->pluck('aggregate', 'improvement_option_id')
            ->map(fn ($count): int => (int) $count);
    }

    private function surveysWithPoint(string $clientId, string $period): int
    {
        $query = CsatSurvey::query()
            ->where('client_id', $clientId)
            ->whereNotNull('improvement_option_id');
        $window = $this->queryWindow($this->bounds($period));
        if ($window !== null) {
            $query->whereBetween('created_at', $window);
        }

        return (int) $query->count();
    }

    /**
     * @param  Collection<int, ClientImprovementOption>  $options
     * @param  array<int, string>  $palette
     * @return array{labels: array<int, string>, series: array<int, array<string, mixed>>}
     */
    private function improvementSeries(Client $client, Collection $options, array $palette): array
    {
        $labels = [];
        $buckets = [];
        foreach ($this->monthEnds() as $month) {
            $labels[] = $month['label'];
            $start = $month['until']->copy()->startOfMonth();
            $window = $this->queryWindow([$start, $month['until']]);
            $surveys = (int) CsatSurvey::query()
                ->where('client_id', $client->id)
                ->whereBetween('created_at', $window)
                ->count();
            $counts = CsatSurvey::query()
                ->where('client_id', $client->id)
                ->whereNotNull('improvement_option_id')
                ->whereBetween('created_at', $window)
                ->selectRaw('improvement_option_id, COUNT(*) as aggregate')
                ->groupBy('improvement_option_id')
                ->pluck('aggregate', 'improvement_option_id');
            $buckets[] = ['surveys' => $surveys, 'counts' => $counts];
        }

        $series = [];
        foreach ($options as $index => $option) {
            $values = [];
            foreach ($buckets as $bucket) {
                $surveys = $bucket['surveys'];
                $times = (int) ($bucket['counts'][$option->id] ?? 0);
                $values[] = $surveys > 0 ? round(($times / $surveys) * 100, 1) : null;
            }
            $series[] = [
                'name' => $option->labelForLocale('es'),
                'color' => $palette[$index % count($palette)],
                'values' => $values,
            ];
        }

        return ['labels' => $labels, 'series' => $series];
    }

    /**
     * @return array<int, array{label: string, rate: float|null, label_rate: string|null}>
     */
    private function optionMonthRates(string $clientId, string $optionId): array
    {
        $points = [];
        foreach ($this->monthEnds() as $month) {
            $start = $month['until']->copy()->startOfMonth();
            $window = $this->queryWindow([$start, $month['until']]);
            $surveys = (int) CsatSurvey::query()
                ->where('client_id', $clientId)
                ->whereBetween('created_at', $window)
                ->count();
            $times = (int) CsatSurvey::query()
                ->where('client_id', $clientId)
                ->where('improvement_option_id', $optionId)
                ->whereBetween('created_at', $window)
                ->count();
            $rate = $surveys > 0 ? ($times / $surveys) * 100 : null;
            $points[] = [
                'label' => $month['label'],
                'rate' => $rate,
                'label_rate' => PulseFormat::percent($rate, 1),
            ];
        }

        return $points;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function optionByEmployee(Client $client, string $optionId): array
    {
        $months = $this->monthEnds();
        $from = $months[0]['until']->copy()->startOfMonth();
        $until = $months[array_key_last($months)]['until'];
        $window = $this->queryWindow([$from, $until]);
        $rows = CsatSurvey::query()
            ->where('client_id', $client->id)
            ->where('improvement_option_id', $optionId)
            ->whereNotNull('employee_id')
            ->whereBetween('created_at', $window)
            ->get(['employee_id', 'created_at']);
        $employees = Employee::query()
            ->where('client_id', $client->id)
            ->whereIn('id', $rows->pluck('employee_id')->unique()->all())
            ->pluck('name', 'id');
        $grid = [];
        foreach ($rows as $row) {
            $id = (string) $row->employee_id;
            $grid[$id] ??= array_fill(0, count($months), 0);
            $created = $row->created_at->timezone($this->timezone());
            foreach ($months as $index => $month) {
                if ($created->isSameMonth($month['until'])) {
                    $grid[$id][$index]++;
                    break;
                }
            }
        }

        $grand = array_sum(array_map('array_sum', $grid));
        $table = [];
        foreach ($grid as $id => $cells) {
            $total = array_sum($cells);
            $table[] = [
                'name' => (string) ($employees[$id] ?? ''),
                'cells' => $cells,
                'total' => $total,
                'share_label' => PulseFormat::percent($grand > 0 ? ($total / $grand) * 100 : 0, 1),
            ];
        }
        usort($table, fn (array $a, array $b): int => $b['total'] <=> $a['total']);

        return [
            'labels' => array_column($months, 'label'),
            'rows' => $table,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function googleAlertItem(ClientExternalReputationAlert $alert, Client $client): array
    {
        $detected = $alert->detected_at?->timezone($this->timezone()) ?? Carbon::now($this->timezone());
        $title = 'Aviso de Google';
        $body = 'Hay un cambio en las reseñas de Google.';
        if ((int) $alert->delta_stars_1 > 0) {
            $n = (int) $alert->delta_stars_1;
            $title = $n === 1 ? 'Nueva reseña de 1★' : $n.' nuevas reseñas de 1★';
            $body = $title.' que requiere tu atención.';
        } elseif ((int) $alert->delta_stars_2 > 0) {
            $n = (int) $alert->delta_stars_2;
            $title = $n === 1 ? 'Nueva reseña de 2★' : $n.' nuevas reseñas de 2★';
            $body = $title.' que requiere tu atención.';
        } elseif ($alert->isTotalDropAnomaly()) {
            $title = 'Bajada del total de reseñas';
            $body = 'El total de reseñas ha cambiado entre dos lecturas.';
        }

        $link = filled($client->google_place_id)
            ? 'https://search.google.com/local/reviews?placeid='.rawurlencode((string) $client->google_place_id)
            : null;

        return [
            'kind' => 'google',
            'id' => (string) $alert->id,
            'tone' => 'google',
            'title' => $title,
            'date' => PulseFormat::dayMonth($detected),
            'sort' => $detected->getTimestamp(),
            'unread' => $alert->isUnread(),
            'body' => $body,
            'link' => $link,
            'read_url' => url('/pulse/'.$client->code.'/alertas/'.$alert->id.'/leida'),
        ];
    }

    /**
     * @return array<int, array{label: string, until: Carbon}>
     */
    private function monthEnds(): array
    {
        $cursor = Carbon::now($this->timezone())->startOfMonth();
        $months = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = $cursor->copy()->subMonths($i);
            $until = $month->copy()->endOfMonth();
            $now = Carbon::now($this->timezone());
            if ($until->greaterThan($now)) {
                $until = $now->copy();
            }
            $months[] = [
                'label' => PulseFormat::monthShort($month),
                'until' => $until,
            ];
        }

        return $months;
    }

    /**
     * @param  array{0: Carbon, 1: Carbon}|null  $bounds
     * @return array{0: Carbon, 1: Carbon}|null
     */
    private function queryWindow(?array $bounds): ?array
    {
        if ($bounds === null) {
            return null;
        }

        $tz = (string) config('app.timezone');

        return [
            $bounds[0]->copy()->timezone($tz),
            $bounds[1]->copy()->timezone($tz),
        ];
    }

    private function timezone(): string
    {
        return (string) config('elpulso.timezone', 'Europe/Madrid');
    }
}
