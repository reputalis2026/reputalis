<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Client extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'clients';

    public $incrementing = false;
    protected $keyType = 'string';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'id',
        'code',
        'namecommercial',
        'nif',
        'razon_social',
        'calle',
        'pais',
        'codigo_postal',
        'ciudad',
        'sector',
        'telefono_negocio',
        'telefono_cliente',
        'owner_id',
        'created_by',
        'is_active',
        'fecha_inicio_alta',
        'fecha_fin',
        'logo',
        'google_place_id',
        'google_id',
        'external_reputation_last_synced_at',
        'external_reputation_last_error',
        'last_call_at',
        'next_call_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'id' => 'string',
            'owner_id' => 'string',
            'created_by' => 'string',
            'google_place_id' => 'string',
            'google_id' => 'string',
            'external_reputation_last_synced_at' => 'datetime',
            'is_active' => 'boolean',
            'fecha_inicio_alta' => 'date',
            'fecha_fin' => 'date',
            'last_call_at' => 'datetime',
            'next_call_at' => 'datetime',
        ];
    }

    /**
     * Relación con el propietario del cliente.
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /**
     * Relación con el usuario que creó el cliente.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Relación con los usuarios que pertenecen a este cliente.
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'client_id');
    }

    /**
     * Relación con los empleados del cliente.
     */
    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class, 'client_id');
    }

    /**
     * Relación con las encuestas CSAT del cliente.
     */
    public function csatSurveys(): HasMany
    {
        return $this->hasMany(CsatSurvey::class, 'client_id');
    }

    /**
     * Relación con los tokens NFC del cliente.
     */
    public function nfcTokens(): HasMany
    {
        return $this->hasMany(NfcToken::class, 'client_id');
    }

    /**
     * Etiquetas personalizadas de motivos de mejora para este cliente.
     */
    public function improvementReasonLabels(): HasMany
    {
        return $this->hasMany(ClientImprovementReasonLabel::class, 'client_id');
    }

    /**
     * Configuración única de puntos de mejora del cliente (título + lista de respuestas).
     */
    public function improvementConfig(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(ClientImprovementConfig::class, 'client_id');
    }

    /**
     * Instantáneas de reputación externa (Google vía Outscraper).
     */
    public function externalReputationSnapshots(): HasMany
    {
        return $this->hasMany(ClientExternalReputationSnapshot::class, 'client_id')
            ->orderByDesc('captured_at');
    }

    /**
     * Alertas de reputación externa (p. ej. subida de 1★/2★ entre escaneos).
     */
    public function externalReputationAlerts(): HasMany
    {
        return $this->hasMany(ClientExternalReputationAlert::class, 'client_id')
            ->orderByDesc('detected_at');
    }

    /**
     * Último snapshot de reputación externa, o el último del día indicado.
     */
    public function latestExternalReputationSnapshot(?\DateTimeInterface $onDate = null): ?ClientExternalReputationSnapshot
    {
        $query = $this->externalReputationSnapshots();

        if ($onDate !== null) {
            $query->whereDate('snapshot_date', $onDate->format('Y-m-d'));
        }

        return $query->orderByDesc('captured_at')->first();
    }

    /**
     * Historial de llamadas del cliente.
     */
    public function calls(): HasMany
    {
        return $this->hasMany(ClientCall::class, 'client_id')
            ->orderByDesc('called_at');
    }

    /**
     * Normaliza Place ID o URL de Google Maps (misma lógica que la encuesta).
     */
    public static function normalizeGooglePlaceId(?string $value): ?string
    {
        return ClientImprovementConfig::normalizeGooglePlaceId($value);
    }

    /**
     * URL de writereview en Google Maps a partir del Place ID del cliente.
     */
    public function googleReviewUrl(): ?string
    {
        $placeId = self::normalizeGooglePlaceId($this->google_place_id);

        return $placeId !== null
            ? 'https://search.google.com/local/writereview?placeid=' . rawurlencode($placeId)
            : null;
    }
}
