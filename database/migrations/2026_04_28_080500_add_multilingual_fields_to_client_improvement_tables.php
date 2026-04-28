<?php

use App\Models\ClientImprovementConfig;
use App\Models\ClientImprovementOption;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('client_improvement_configs', function (Blueprint $table) {
            $table->string('default_locale', 2)->default(ClientImprovementConfig::DEFAULT_LOCALE);
            $table->string('title_es')->nullable();
            $table->string('title_pt')->nullable();
            $table->string('title_en')->nullable();
            $table->string('survey_question_text_es')->nullable();
            $table->string('survey_question_text_pt')->nullable();
            $table->string('survey_question_text_en')->nullable();
        });

        Schema::table('client_improvement_options', function (Blueprint $table) {
            $table->string('label_es')->nullable();
            $table->string('label_pt')->nullable();
            $table->string('label_en')->nullable();
        });

        $defaultTitles = ClientImprovementConfig::defaultTitles();
        $defaultQuestions = ClientImprovementConfig::defaultSurveyQuestionTexts();

        DB::table('client_improvement_configs')
            ->orderBy('id')
            ->chunk(100, function ($configs) use ($defaultTitles, $defaultQuestions): void {
                foreach ($configs as $config) {
                    $legacyTitle = trim((string) ($config->title ?? ''));
                    $legacyQuestion = trim((string) ($config->survey_question_text ?? ''));

                    DB::table('client_improvement_configs')
                        ->where('id', $config->id)
                        ->update([
                            'default_locale' => ClientImprovementConfig::DEFAULT_LOCALE,
                            'title_es' => $legacyTitle !== '' ? $legacyTitle : $defaultTitles['es'],
                            'title_pt' => $this->translatedOrOriginal($legacyTitle, $defaultTitles['es'], $defaultTitles['pt']),
                            'title_en' => $this->translatedOrOriginal($legacyTitle, $defaultTitles['es'], $defaultTitles['en']),
                            'survey_question_text_es' => $legacyQuestion !== '' ? $legacyQuestion : $defaultQuestions['es'],
                            'survey_question_text_pt' => $this->translatedOrOriginal($legacyQuestion, $defaultQuestions['es'], $defaultQuestions['pt']),
                            'survey_question_text_en' => $this->translatedOrOriginal($legacyQuestion, $defaultQuestions['es'], $defaultQuestions['en']),
                            'updated_at' => now(),
                        ]);
                }
            });

        $defaultLabels = ClientImprovementOption::defaultLabels();

        DB::table('client_improvement_options')
            ->orderBy('id')
            ->chunk(100, function ($options) use ($defaultLabels): void {
                foreach ($options as $option) {
                    $legacyLabel = trim((string) ($option->label ?? ''));
                    $translated = $this->defaultOptionTranslation($legacyLabel, $defaultLabels);

                    DB::table('client_improvement_options')
                        ->where('id', $option->id)
                        ->update([
                            'label_es' => $legacyLabel,
                            'label_pt' => $translated['pt'],
                            'label_en' => $translated['en'],
                            'updated_at' => now(),
                        ]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('client_improvement_options', function (Blueprint $table) {
            $table->dropColumn(['label_es', 'label_pt', 'label_en']);
        });

        Schema::table('client_improvement_configs', function (Blueprint $table) {
            $table->dropColumn([
                'default_locale',
                'title_es',
                'title_pt',
                'title_en',
                'survey_question_text_es',
                'survey_question_text_pt',
                'survey_question_text_en',
            ]);
        });
    }

    private function translatedOrOriginal(string $legacyValue, string $defaultSpanish, string $translatedDefault): string
    {
        if ($legacyValue === '' || $legacyValue === $defaultSpanish) {
            return $translatedDefault;
        }

        return $legacyValue;
    }

    /**
     * @param  array<int, array{es: string, pt: string, en: string}>  $defaultLabels
     * @return array{pt: string, en: string}
     */
    private function defaultOptionTranslation(string $legacyLabel, array $defaultLabels): array
    {
        foreach ($defaultLabels as $defaultLabel) {
            if ($legacyLabel === $defaultLabel['es']) {
                return [
                    'pt' => $defaultLabel['pt'],
                    'en' => $defaultLabel['en'],
                ];
            }
        }

        return [
            'pt' => $legacyLabel,
            'en' => $legacyLabel,
        ];
    }
};
