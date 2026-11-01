<?php declare(strict_types=1);

namespace MedicalCore\Service;

final class ProjectInfoProvider
{
    /**
     * @return array<string, string>
     */
    public function getProjectInfo(): array
    {
        return [
            'name' => 'MedicalSupply B2B Commerce',
            'warehouse_strategy' => 'multi-warehouse',
            'order_approval' => 'conditional',
            'erp_mode' => 'simulated-api',
        ];
    }
}
