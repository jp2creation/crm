<?php

namespace Modules\CrmTapisRomus\Services;

class TapisRomusModuleService
{
    /**
     * @return array<string, string>
     */
    public function payload(): array
    {
        return [
            'slug' => 'tapis-romus',
            'title' => 'Tapis ROMUS',
            'section' => 'applications-crm',
        ];
    }
}
