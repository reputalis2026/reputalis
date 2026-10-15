<?php

namespace Tests\Unit;

use App\Support\Pulse\PulseNotifications;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

class PulseNotificationsTest extends TestCase
{
    public function test_builds_rating_changes_and_milestones(): void
    {
        $notes = new PulseNotifications;
        $day = Carbon::parse('2026-09-07 10:00:00', 'Europe/Madrid');
        $surveys = [];
        for ($i = 0; $i < 10; $i++) {
            $surveys[] = ['at' => $day->copy()->addMinutes($i), 'score' => 5];
        }
        $surveys[] = ['at' => $day->copy()->addDay(), 'score' => 1];

        $items = $notes->build($surveys, [
            ['at' => $day->copy(), 'rating' => 4.2, 'reviews' => 80],
            ['at' => $day->copy()->addDays(2), 'rating' => 4.5, 'reviews' => 110],
        ]);

        $texts = array_column($items, 'text');
        $this->assertContains('Has alcanzado las 10 encuestas.', $texts);
        $this->assertContains('Tu valoración interna ha bajado de 5,00 a 4,64.', $texts);
        $this->assertNotContains('Has alcanzado las 50 reseñas en Google.', $texts);
        $this->assertContains('Has alcanzado las 100 reseñas en Google.', $texts);
        $this->assertContains('Tu valoración de Google ha subido de 4,20 a 4,50.', $texts);
    }

    public function test_present_splits_unread_by_origin(): void
    {
        $notes = new PulseNotifications;
        $at = Carbon::parse('2026-09-07 10:00:00', 'Europe/Madrid');
        $built = [
            ['id' => 'a', 'tone' => 'interna', 'text' => 'Una', 'at' => $at],
            ['id' => 'b', 'tone' => 'google', 'text' => 'Dos', 'at' => $at],
        ];

        $all = $notes->present($built, ['a']);
        $this->assertSame(1, $all['unread']);
        $this->assertSame(0, $all['internal_unread']);
        $this->assertSame(1, $all['google_unread']);
        $this->assertCount(2, $all['items']);

        $unread = $notes->present($built, ['a'], true);
        $this->assertCount(1, $unread['items']);
        $this->assertSame('b', $unread['items'][0]['id']);
    }
}
