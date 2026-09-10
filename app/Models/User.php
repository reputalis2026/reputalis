<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Support\ClientImagePaths;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasAvatar;
use Filament\Models\Contracts\HasName;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements FilamentUser, HasAvatar, HasName
{
    public $incrementing = false;
    protected $keyType = 'string';

    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'id',
        'name',
        'fullname',
        'username',
        'dni',
        'email',
        'admin_email',
        'password',
        'role',
        'client_id',
        'email_verified_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function getFilamentName(): string
    {
        return $this->username ?: $this->email;
    }

    public function getFilamentAvatarUrl(): ?string
    {
        if (! $this->isClientOwner()) {
            return null;
        }

        $logo = $this->ownedClient?->logo;

        if (! filled($logo)) {
            return null;
        }

        return ClientImagePaths::publicUrl($logo);
    }

    /**
     * Permite el acceso al panel de administracion a usuarios con rol valido.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        if ($panel->getId() !== 'admin') {
            return false;
        }

        if (! in_array($this->role, [self::ROLE_SUPERADMIN, self::ROLE_CLIENTE, self::ROLE_DISTRIBUIDOR], true)) {
            return false;
        }

        // Para cliente y distribuidor, solo permitir acceso si su cliente/distribuidor asociado esta activo.
        if (in_array($this->role, [self::ROLE_CLIENTE, self::ROLE_DISTRIBUIDOR], true)) {
            $client = $this->ownedClient;

            if (! $client || ! $client->is_active) {
                return false;
            }
        }

        return true;
    }

    /**
     * Roles de usuario posibles dentro del sistema.
     */
    public const ROLE_SUPERADMIN = 'superadmin';
    public const ROLE_CLIENTE = 'cliente';
    public const ROLE_DISTRIBUIDOR = 'distribuidor';

    /**
     * Relacion con el cliente al que pertenece el usuario, si aplica.
     */
    public function client()
    {
        return $this->belongsTo(\App\Models\Client::class, 'client_id');
    }

    /**
     * Determina si el usuario es Super Admin.
     */
    public function isSuperAdmin(): bool
    {
        return $this->role === self::ROLE_SUPERADMIN;
    }

    /**
     * Determina si el usuario es propietario de cliente (rol cliente).
     */
    public function isClientOwner(): bool
    {
        return $this->role === self::ROLE_CLIENTE;
    }

    /**
     * Determina si el usuario tiene rol distribuidor.
     */
    public function isDistributor(): bool
    {
        return $this->role === self::ROLE_DISTRIBUIDOR;
    }

    /**
     * Relacion con el cliente que administra (como propietario).
     * Un usuario puede ser propietario de un cliente.
     */
    public function ownedClient()
    {
        return $this->hasOne(\App\Models\Client::class, 'owner_id');
    }

    /**
     * Clientes creados por este usuario.
     */
    public function createdClients()
    {
        return $this->hasMany(\App\Models\Client::class, 'created_by');
    }

    /**
     * Mensajes del panel recibidos por este usuario (bandeja de notificaciones/mensajes).
     */
    public function receivedPanelMessages()
    {
        return $this->hasMany(\App\Models\PanelMessageRecipient::class, 'user_id');
    }

    /**
     * Busca un usuario por identificador de login (usuario o email).
     * Insensible a mayusculas y recorta espacios.
     */
    public static function findByIdentifier(string $identifier): ?self
    {
        $value = trim($identifier);
        if ($value === '') {
            return null;
        }

        return static::query()
            ->whereRaw('LOWER(TRIM(email)) = ?', [strtolower($value)])
            ->first();
    }
}
