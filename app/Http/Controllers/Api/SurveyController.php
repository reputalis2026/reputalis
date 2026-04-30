<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreSurveyRequest;
use App\Models\Client;
use App\Models\ClientImprovementConfig;
use App\Models\ClientImprovementOption;
use App\Models\CsatSurvey;
use App\Models\Employee;
use App\Models\ImprovementReason;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class SurveyController extends Controller
{
    /**
     * Registrar una encuesta CSAT (endpoint público para pruebas).
     */
    public function store(StoreSurveyRequest $request): JsonResponse
    {
        $client = Client::where('code', $request->input('client_code'))
            ->where('is_active', true)
            ->first();

        if (! $client) {
            return response()->json(['message' => 'Cliente no encontrado o inactivo.'], 404);
        }

        $employee = null;
        if (filled($request->employee_code)) {
            $employee = Employee::where('client_id', $client->id)
                ->where('name', $request->employee_code)
                ->first();
        }

        $client->loadMissing('improvementConfig');
        $score = (int) $request->input('score');
        $positiveScoresUsed = $client->improvementConfig?->positiveScores()
            ?? ClientImprovementConfig::defaultPositiveScores();
        $isPositiveScore = $client->improvementConfig?->isPositiveScore($score)
            ?? in_array($score, ClientImprovementConfig::defaultPositiveScores(), true);
        $improvementReason = null;
        $improvementOptionId = null;

        if (! $isPositiveScore) {
            if (filled($request->improvement_option_id)) {
                $option = ClientImprovementOption::with('clientImprovementConfig')
                    ->find($request->input('improvement_option_id'));
                if (! $option || $option->clientImprovementConfig->client_id !== $client->id) {
                    return response()->json([
                        'message' => 'La opción seleccionada no es válida para este cliente.',
                        'errors' => ['improvement_option_id' => ['Opción no válida.']],
                    ], 422);
                }
                $improvementOptionId = $option->id;
            } elseif (filled($request->improvement_reason_code)) {
                $improvementReason = ImprovementReason::where('code', $request->input('improvement_reason_code'))
                    ->where('is_active', true)
                    ->first();
                if (! $improvementReason) {
                    return response()->json([
                        'message' => 'El motivo de mejora no existe o está inactivo.',
                        'errors' => ['improvement_reason_code' => ['No existe un motivo activo con el código indicado.']],
                    ], 422);
                }
            }
        }

        $deviceHash = $request->input('device_hash');
        if (filled($deviceHash)) {
            $countLast24h = CsatSurvey::where('client_id', $client->id)
                ->where('device_hash', $deviceHash)
                ->where('created_at', '>=', now()->subHours(24))
                ->count();
            if ($countLast24h >= 10) {
                Log::info('Survey rate limit by device', [
                    'client_id' => $client->id,
                    'device_hash' => substr($deviceHash, 0, 8),
                ]);
                return response()->json([
                    'message' => 'Demasiadas encuestas desde este dispositivo. Intente de nuevo más tarde.',
                ], 429);
            }
        }

        $survey = CsatSurvey::create([
            'client_id' => $client->id,
            'employee_id' => $employee?->id,
            'score' => $score,
            'improvementreason_id' => $improvementReason?->id,
            'improvement_option_id' => $improvementOptionId,
            'positive_scores_used' => $positiveScoresUsed,
            'locale_used' => $request->input('locale_used'),
            'device_hash' => $deviceHash,
        ]);

        Log::info('Survey created', [
            'survey_id' => $survey->id,
            'client_id' => $client->id,
            'score' => $score,
        ]);

        return response()->json([
            'success' => true,
            'survey_id' => $survey->id,
        ], 201);
    }
}
