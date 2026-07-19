<?php

namespace Modules\CrmCore\Events;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CrmDomainEvent
{
    use Dispatchable;
    use SerializesModels;

    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        public readonly string $module,
        public readonly string $name,
        public readonly string $entity,
        public readonly int|string|null $entityId = null,
        public readonly ?int $siteId = null,
        public readonly ?int $actorId = null,
        public readonly string $actorName = '',
        public readonly array $payload = [],
        public readonly ?CarbonImmutable $occurredAt = null,
    ) {}

    public function key(): string
    {
        return implode('.', array_filter([
            $this->module,
            $this->entity,
            $this->name,
        ]));
    }

    public function occurredAt(): CarbonImmutable
    {
        return $this->occurredAt ?? CarbonImmutable::now();
    }
}
