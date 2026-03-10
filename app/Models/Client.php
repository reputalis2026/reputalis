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
            'is_active' => 'boolean',
            'fecha_inicio_alta' => 'date',
            'fecha_fin' => 'date',
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
}
