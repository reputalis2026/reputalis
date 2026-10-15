<?php

namespace App\Support\Pulse;

use App\Models\Client;
use App\Models\CsatSurvey;
use Carbon\Carbon;

/**
 * Avisos informativos de El Pulso: cambios de nota e hitos de volumen.
 * No son alertas.
 */
final class PulseNotifications
{
    private const TIMEZONE = 'Europe/Madrid';

    /**
     * @param  array<int, string>  $readIds
     * @return array{
     *   items: array<int, array<string, mixed>>,
     *   unread: int,
     *   internal_unread: int,
     *   google_unread: int
     * }
     */
    public function forClient(Client $client, array $readIds, bool $unreadOnly = false): array
    {
        $surveys = CsatSurvey::query()
            ->where('client_id', $client->id)
            ->orderBy('created_at')
            ->get(['score', 'created_at'])
            ->map(fn ($row): array => [
                'at' => $row->created_at->copy()->timezone(self::TIMEZONE),
                'score' => (int) $row->score,
            ])
            ->all();

        $google = $client->externalReputationSnapshots()
            ->reorder()
            ->orderBy('captured_at')
            ->get(['captured_at', 'snapshot_date', 'rating', 'reviews_total'])
            ->map(function ($row): ?array {
                $at = $row->captured_at ?? $row->snapshot_date;
                if ($at === null) {
                    return null;
                }

                return [
                    'at' => $at->copy()->timezone(self::TIMEZONE),
                    'rating' => $row->rating !== null ? (float) $row->rating : null,
                    'reviews' => (int) $row->reviews_total,
                ];
            })
            ->filter()
            ->values()
            ->all();

        return $this->present($this->build($surveys, $google), $readIds, $unreadOnly);
    }

    /**
     * @param  list<array{at: Carbon, score: int}>  $surveys
     * @param  list<array{at: Carbon, rating: float|null, reviews: int}>  $google
     * @return list<array{id: string, tone: string, text: string, at: Carbon}>
     */
    public function build(array $surveys, array $google): array
    {
        $items = array_merge(
            $this->internalRatingChanges($surveys),
            $this->surveyMilestones($surveys),
            $this->googleRatingChanges($google),
            $this->reviewMilestones($google),
        );

        usort($items, fn (array $a, array $b): int => $b['at']->getTimestamp() <=> $a['at']->getTimestamp());

        return array_slice($items, 0, 40);
    }

    /**
     * @param  list<array{id: string, tone: string, text: string, at: Carbon}>  $items
     * @param  array<int, string>  $readIds
     * @return array{items: array<int, array<string, mixed>>, unread: int, internal_unread: int, google_unread: int}
     */
    public function present(array $items, array $readIds, bool $unreadOnly = false): array
    {
        $read = array_fill_keys($readIds, true);
        $rows = [];
        $unread = 0;
        $internalUnread = 0;
        $googleUnread = 0;

        foreach ($items as $item) {
            $isUnread = ! isset($read[$item['id']]);
            if ($isUnread) {
                $unread++;
                if ($item['tone'] === 'google') {
                    $googleUnread++;
                } else {
                    $internalUnread++;
                }
            }
            if ($unreadOnly && ! $isUnread) {
                continue;
            }
            $rows[] = [
                'id' => $item['id'],
                'tone' => $item['tone'],
                'text' => $item['text'],
                'date' => PulseFormat::dayMonth($item['at']),
                'unread' => $isUnread,
            ];
        }

        return [
            'items' => $rows,
            'unread' => $unread,
            'internal_unread' => $internalUnread,
            'google_unread' => $googleUnread,
        ];
    }

    /**
     * @param  list<array{at: Carbon, score: int}>  $surveys
     * @return list<array{id: string, tone: string, text: string, at: Carbon}>
     */
    private function internalRatingChanges(array $surveys): array
    {
        $sum = 0;
        $count = 0;
        $previous = null;
        $byDay = [];

        foreach ($surveys as $survey) {
            $count++;
            $sum += $survey['score'];
            $shown = round($sum / $count, 2);
            if ($previous !== null && abs($shown - $previous) >= 0.005) {
                $day = $survey['at']->format('Y-m-d');
                if (! isset($byDay[$day])) {
                    $byDay[$day] = [
                        'at' => $survey['at'],
                        'from' => $previous,
                        'to' => $shown,
                    ];
                } else {
                    $byDay[$day]['at'] = $survey['at'];
                    $byDay[$day]['to'] = $shown;
                }
            }
            $previous = $shown;
        }

        $items = [];
        foreach ($byDay as $day => $change) {
            if (abs($change['to'] - $change['from']) < 0.005) {
                continue;
            }
            $items[] = [
                'id' => 'interna-nota-'.$day,
                'tone' => 'interna',
                'text' => $this->ratingSentence('Tu valoración interna', $change['from'], $change['to']),
                'at' => $change['at'],
            ];
        }

        return $items;
    }

    /**
     * @param  list<array{at: Carbon, score: int}>  $surveys
     * @return list<array{id: string, tone: string, text: string, at: Carbon}>
     */
    private function surveyMilestones(array $surveys): array
    {
        $items = [];
        $count = 0;
        foreach ($surveys as $survey) {
            $count++;
            if (! in_array($count, $this->thresholds(), true)) {
                continue;
            }
            $items[] = [
                'id' => 'interna-encuestas-'.$count,
                'tone' => 'interna',
                'text' => 'Has alcanzado las '.PulseFormat::integer($count).' encuestas.',
                'at' => $survey['at'],
            ];
        }

        return $items;
    }

    /**
     * @param  list<array{at: Carbon, rating: float|null, reviews: int}>  $google
     * @return list<array{id: string, tone: string, text: string, at: Carbon}>
     */
    private function googleRatingChanges(array $google): array
    {
        $previous = null;
        $byDay = [];
        foreach ($google as $point) {
            if ($point['rating'] === null) {
                continue;
            }
            $shown = round($point['rating'], 2);
            if ($previous !== null && abs($shown - $previous) >= 0.005) {
                $day = $point['at']->format('Y-m-d');
                if (! isset($byDay[$day])) {
                    $byDay[$day] = [
                        'at' => $point['at'],
                        'from' => $previous,
                        'to' => $shown,
                    ];
                } else {
                    $byDay[$day]['at'] = $point['at'];
                    $byDay[$day]['to'] = $shown;
                }
            }
            $previous = $shown;
        }

        $items = [];
        foreach ($byDay as $day => $change) {
            if (abs($change['to'] - $change['from']) < 0.005) {
                continue;
            }
            $items[] = [
                'id' => 'google-nota-'.$day,
                'tone' => 'google',
                'text' => $this->ratingSentence('Tu valoración de Google', $change['from'], $change['to']),
                'at' => $change['at'],
            ];
        }

        return $items;
    }

    /**
     * @param  list<array{at: Carbon, rating: float|null, reviews: int}>  $google
     * @return list<array{id: string, tone: string, text: string, at: Carbon}>
     */
    private function reviewMilestones(array $google): array
    {
        $items = [];
        $previous = null;
        foreach ($google as $point) {
            $reviews = $point['reviews'];
            if ($previous !== null) {
                foreach ($this->thresholds() as $threshold) {
                    if ($previous >= $threshold || $reviews < $threshold) {
                        continue;
                    }
                    $items[] = [
                        'id' => 'google-resenas-'.$threshold,
                        'tone' => 'google',
                        'text' => 'Has alcanzado las '.PulseFormat::integer($threshold).' reseñas en Google.',
                        'at' => $point['at'],
                    ];
                }
            }
            $previous = $reviews;
        }

        return $items;
    }

    private function ratingSentence(string $subject, float $from, float $to): string
    {
        $verb = $to > $from ? 'ha subido' : 'ha bajado';

        return $subject.' '.$verb.' de '.PulseFormat::decimal($from, 2).' a '.PulseFormat::decimal($to, 2).'.';
    }

    /**
     * @return list<int>
     */
    private function thresholds(): array
    {
        return [10, 25, 50, 100, 150, 200, 250, 300, 400, 500, 750, 1000, 1500, 2000, 3000, 5000, 7500, 10000, 15000, 20000, 25000, 30000];
    }
}
