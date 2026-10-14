<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * A single stubbed remote resource (server, site, certificate, ...) with the
 * exact attribute shape the ploi-core client expects back from the Ploi API.
 */
class ProvisionedResource extends Model
{
    public const STATUS_CREATED = 'created';

    public const STATUS_BUSY = 'busy';

    public const STATUS_ACTIVE = 'active';

    protected $fillable = [
        'type',
        'server_id',
        'site_id',
        'initial_status',
        'payload',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
        ];
    }

    /**
     * Scope to a resource family, optionally within a server/site context.
     *
     * @param  array{server_id?: int|null, site_id?: int|null}  $context
     */
    public function scopeOfType(Builder $query, string $type, array $context = []): Builder
    {
        return $query->where('type', $type)
            ->when(array_key_exists('server_id', $context), fn (Builder $q) => $q->where('server_id', $context['server_id']))
            ->when(array_key_exists('site_id', $context), fn (Builder $q) => $q->where('site_id', $context['site_id']));
    }

    public function remoteStatus(): string
    {
        if ($this->created_at?->addSeconds((int) config('ploi-stub.provision_delay'))->isPast()) {
            return self::STATUS_ACTIVE;
        }

        return $this->initial_status;
    }

    /**
     * The Ploi API `data` envelope for this resource.
     */
    public function toApiArray(): array
    {
        $data = array_merge($this->payload, [
            'id' => $this->id,
            'status' => $this->remoteStatus(),
        ]);

        if ($this->type === 'server') {
            $data['sites_count'] = static::query()->ofType('site', ['server_id' => $this->id])->count();
        }

        return $data;
    }
}
