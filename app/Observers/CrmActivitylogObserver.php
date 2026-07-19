<?php

namespace App\Observers;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Spatie\Activitylog\Facades\CauserResolver;

class CrmActivitylogObserver
{
    public function created(Model $model): void
    {
        $this->record($model, 'created');
    }

    public function updated(Model $model): void
    {
        $changes = $this->changedAttributes($model);

        if ($changes === []) {
            return;
        }

        $this->record($model, 'updated', [
            'attributes' => $changes,
            'old' => $this->oldAttributes($model, array_keys($changes)),
        ]);
    }

    public function deleted(Model $model): void
    {
        $this->record($model, 'deleted', [
            'old' => $this->sanitize($model->getRawOriginal()),
        ]);
    }

    public function restored(Model $model): void
    {
        $this->record($model, 'restored');
    }

    /**
     * @param  array<string, mixed>  $properties
     */
    private function record(Model $model, string $event, array $properties = []): void
    {
        if (! config('activitylog.enabled', true) || ! config('crm.audit.enabled', true)) {
            return;
        }

        if ($properties === []) {
            $properties['attributes'] = $this->sanitize($model->getAttributes());
        }

        $properties['ip'] = request()?->ip();
        $properties['url'] = request()?->fullUrl();

        $causer = auth()->user();

        if ($causer instanceof User) {
            CauserResolver::setCauser($causer);
        }

        activity('crm')
            ->event($event)
            ->performedOn($model)
            ->withProperties($properties)
            ->log(class_basename($model).'.'.$event);
    }

    /**
     * @return array<string, mixed>
     */
    private function changedAttributes(Model $model): array
    {
        return $this->sanitize(Arr::except($model->getChanges(), ['updated_at']));
    }

    /**
     * @param  array<int, string>  $keys
     * @return array<string, mixed>
     */
    private function oldAttributes(Model $model, array $keys): array
    {
        return $this->sanitize(Arr::only($model->getRawOriginal(), $keys));
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private function sanitize(array $attributes): array
    {
        return Arr::except($attributes, [
            'password',
            'remember_token',
            'token',
            'auth_token',
            'public_key',
            'private_key',
        ]);
    }
}
