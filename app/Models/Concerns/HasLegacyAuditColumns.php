<?php

namespace App\Models\Concerns;

trait HasLegacyAuditColumns
{
    public function getCreatedAtColumn(): ?string
    {
        return 'createddate';
    }

    public function getUpdatedAtColumn(): ?string
    {
        return 'updateddate';
    }

    protected static function bootHasLegacyAuditColumns(): void
    {
        static::creating(function ($model) {
            $actor = static::legacyAuditActor();
            if ($actor !== null) {
                $model->createdby ??= $actor;
                $model->updatedby ??= $actor;
            }
        });

        static::updating(function ($model) {
            $actor = static::legacyAuditActor();
            if ($actor !== null) {
                $model->updatedby = $actor;
            }
        });
    }

    protected static function legacyAuditActor(): ?string
    {
        $user = auth()->user();

        if (! $user) {
            return null;
        }

        return $user->name ?: $user->email;
    }
}
