<?php declare(strict_types=1);

namespace MedicalProductManagement\Service;

use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\Framework\Context;

class ProductCustomFieldInstaller
{
    private const FIELD_SET_ID = '7f3b0a7d2e8c4f4e9a1b6c5d8e0f1234';

    public function __construct(
        private readonly EntityRepository $customFieldSetRepository
    ) {
    }

    public function install(Context $context): void
    {
        $this->customFieldSetRepository->upsert([
            [
                'id' => self::FIELD_SET_ID,
                'name' => 'medical_product_management',
                'config' => [
                    'label' => [
                        'en-GB' => 'Medical product information',
                        'de-DE' => 'Medizinische Produktinformationen',
                        'fr-FR' => 'Informations produit médicales',
                    ],
                ],
                'relations' => [
                    ['entityName' => 'product'],
                ],
                'customFields' => [
                    $this->field('medical_reference', 'text', 1, 'Medical reference', 'Medizinische Referenz', 'Référence médicale'),
                    $this->field('manufacturer_reference', 'text', 2, 'Manufacturer reference', 'Herstellerreferenz', 'Référence fabricant'),
                    $this->field('package_quantity', 'int', 3, 'Units per package', 'Einheiten pro Verpackung', 'Unités par emballage'),
                    $this->field('package_unit', 'text', 4, 'Package unit', 'Verpackungseinheit', 'Unité de conditionnement'),
                    $this->field('minimum_order_quantity', 'float', 5, 'Minimum order quantity', 'Mindestbestellmenge', 'Quantité minimale de commande'),
                    $this->field('storage_conditions', 'text', 6, 'Storage conditions', 'Lagerbedingungen', 'Conditions de stockage'),
                    $this->field('requires_special_handling', 'bool', 7, 'Requires special handling', 'Erfordert besondere Handhabung', 'Manipulation spéciale requise'),
                ],
            ],
        ], $context);
    }

    /**
     * @return array<string, mixed>
     */
    private function field(
        string $name,
        string $type,
        int $position,
        string $englishLabel,
        string $germanLabel,
        string $frenchLabel
    ): array {
        return [
            'id' => Uuid::fromString(sprintf('7f3b0a7d-2e8c-4f4e-9a1b-%012d', $position)),
            'name' => $name,
            'type' => $type,
            'config' => [
                'label' => [
                    'en-GB' => $englishLabel,
                    'de-DE' => $germanLabel,
                    'fr-FR' => $frenchLabel,
                ],
                'customFieldPosition' => $position,
            ],
        ];
    }
}