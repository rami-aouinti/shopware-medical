<?php declare(strict_types=1);

namespace MedicalInventory\Command;

use MedicalInventory\Core\Content\Warehouse\WarehouseEntity;
use MedicalInventory\Service\WarehouseService;
use MedicalInventory\Service\WarehouseStockService;
use RuntimeException;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\Currency\CurrencyEntity;
use Shopware\Core\System\Tax\TaxEntity;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'medical:inventory:demo-data',
    description: 'Create an idempotent medical-supplies catalogue and warehouse stock demo',
)]
final class WarehouseDemoDataCommand extends Command
{
    /**
     * @var list<array{code: string, name: string, countryCode: string}>
     */
    private const WAREHOUSES = [
        ['code' => 'DE-KOELN', 'name' => 'Köln Central Warehouse', 'countryCode' => 'DE'],
        ['code' => 'FR-PARIS_01', 'name' => 'Paris Warehouse', 'countryCode' => 'FR'],
    ];

    /**
     * @var list<array{
     *     productNumber: string,
     *     name: string,
     *     description: string,
     *     grossPrice: float,
     *     stock: array<string, int>
     * }>
     */
    private const PRODUCTS = [
        [
            'productNumber' => 'MED-GLOVE-NITRILE-M',
            'name' => 'Nitrile examination gloves - size M',
            'description' => 'Powder-free disposable nitrile examination gloves, size M, box of 100.',
            'grossPrice' => 12.90,
            'stock' => ['DE-KOELN' => 120, 'FR-PARIS_01' => 60],
        ],
        [
            'productNumber' => 'MED-MASK-FFP2',
            'name' => 'FFP2 protective masks',
            'description' => 'FFP2 protective masks for professional medical environments, pack of 20.',
            'grossPrice' => 8.50,
            'stock' => ['DE-KOELN' => 300, 'FR-PARIS_01' => 120],
        ],
        [
            'productNumber' => 'MED-DISINF-1L',
            'name' => 'Hand disinfectant 1 litre',
            'description' => 'Professional hand disinfectant, 1 litre bottle.',
            'grossPrice' => 5.90,
            'stock' => ['DE-KOELN' => 80, 'FR-PARIS_01' => 40],
        ],
        [
            'productNumber' => 'MED-GAUZE-10X10',
            'name' => 'Sterile gauze compresses 10 x 10 cm',
            'description' => 'Sterile gauze compresses, 10 x 10 cm, pack of 100.',
            'grossPrice' => 6.75,
            'stock' => ['DE-KOELN' => 150, 'FR-PARIS_01' => 90],
        ],
        [
            'productNumber' => 'MED-SYRINGE-5ML',
            'name' => 'Disposable syringe 5 ml',
            'description' => 'Single-use sterile 5 ml syringes, pack of 100.',
            'grossPrice' => 14.50,
            'stock' => ['DE-KOELN' => 200, 'FR-PARIS_01' => 100],
        ],
    ];

    public function __construct(
        private readonly WarehouseService $warehouseService,
        private readonly WarehouseStockService $stockService,
        private readonly EntityRepository $productRepository,
        private readonly EntityRepository $taxRepository,
        private readonly EntityRepository $currencyRepository,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $context = Context::createDefaultContext();

        $currency = $this->getEuroCurrency($context);
        $tax = $this->getPreferredTax($context);

        $warehouses = [];
        foreach (self::WAREHOUSES as $definition) {
            $warehouses[$definition['code']] = $this->ensureWarehouse($definition, $context);
        }

        $rows = [];

        foreach (self::PRODUCTS as $definition) {
            $totalStock = array_sum($definition['stock']);
            [$product, $created] = $this->ensureProduct(
                $definition,
                $totalStock,
                $tax,
                $currency,
                $context
            );

            foreach ($definition['stock'] as $warehouseCode => $quantity) {
                if (!isset($warehouses[$warehouseCode])) {
                    throw new RuntimeException(sprintf(
                        'Demo warehouse "%s" is not configured.',
                        $warehouseCode
                    ));
                }

                $existingStock = $this->stockService->getStock($warehouseCode, $product->getId(), $context);

                if ($existingStock !== null) {
                    $rows[] = [
                        $definition['productNumber'],
                        $warehouseCode,
                        $existingStock->getQuantity(),
                        'preserved',
                    ];

                    continue;
                }

                $stock = $this->stockService->setStock(
                    $warehouseCode,
                    $product->getId(),
                    $quantity,
                    $context
                );

                $rows[] = [
                    $definition['productNumber'],
                    $warehouseCode,
                    $stock->getQuantity(),
                    'created',
                ];
            }

            $io->text(sprintf(
                '%s product %s',
                $created ? 'Created' : 'Reused',
                $definition['productNumber']
            ));
        }

        $io->table(
            ['Product number', 'Warehouse', 'Physical quantity', 'Action'],
            $rows
        );

        $io->success(sprintf(
            'Demo catalogue ready: %d products, %d warehouses. Existing records were preserved. '
            . 'Currency: %s; tax: %s%%.',
            count(self::PRODUCTS),
            count($warehouses),
            $currency->getIsoCode(),
            $tax->getTaxRate()
        ));

        return Command::SUCCESS;
    }

    /**
     * @param array{code: string, name: string, countryCode: string} $definition
     */
    private function ensureWarehouse(array $definition, Context $context): WarehouseEntity
    {
        $warehouse = $this->warehouseService->findByCode($definition['code'], $context);

        if ($warehouse !== null) {
            return $warehouse;
        }

        $this->warehouseService->create(
            $definition['code'],
            $definition['name'],
            $definition['countryCode'],
            $context
        );

        $warehouse = $this->warehouseService->findByCode($definition['code'], $context);

        if ($warehouse === null) {
            throw new RuntimeException(sprintf(
                'Warehouse "%s" could not be created.',
                $definition['code']
            ));
        }

        return $warehouse;
    }

    /**
     * @param array{
     *     productNumber: string,
     *     name: string,
     *     description: string,
     *     grossPrice: float,
     *     stock: array<string, int>
     * } $definition
     * @return array{0: ProductEntity, 1: bool}
     */
    private function ensureProduct(
        array $definition,
        int $totalStock,
        TaxEntity $tax,
        CurrencyEntity $currency,
        Context $context,
    ): array {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('productNumber', $definition['productNumber']));
        $criteria->setLimit(1);

        $existingProduct = $this->productRepository->search($criteria, $context)->first();

        if ($existingProduct instanceof ProductEntity) {
            return [$existingProduct, false];
        }

        $productId = Uuid::randomHex();
        $netPrice = round($definition['grossPrice'] / (1 + ($tax->getTaxRate() / 100)), 2);

        $this->productRepository->create([
            [
                'id' => $productId,
                'productNumber' => $definition['productNumber'],
                'name' => $definition['name'],
                'description' => $definition['description'],
                'taxId' => $tax->getId(),
                'price' => [[
                    'currencyId' => $currency->getId(),
                    'gross' => $definition['grossPrice'],
                    'net' => $netPrice,
                    'linked' => true,
                ]],
                'stock' => $totalStock,
                'active' => true,
            ],
        ], $context);

        $product = $this->productRepository->search(new Criteria([$productId]), $context)->first();

        if (!$product instanceof ProductEntity) {
            throw new RuntimeException(sprintf(
                'Demo product "%s" could not be loaded after creation.',
                $definition['productNumber']
            ));
        }

        return [$product, true];
    }

    private function getEuroCurrency(Context $context): CurrencyEntity
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('isoCode', 'EUR'));
        $criteria->setLimit(1);

        $currency = $this->currencyRepository->search($criteria, $context)->first();

        if (!$currency instanceof CurrencyEntity) {
            throw new RuntimeException('EUR currency is required to seed MedicalInventory demo data.');
        }

        return $currency;
    }

    private function getPreferredTax(Context $context): TaxEntity
    {
        $taxes = $this->taxRepository->search(new Criteria(), $context)->getEntities();

        foreach ($taxes as $tax) {
            if ($tax instanceof TaxEntity && abs($tax->getTaxRate() - 19.0) < 0.001) {
                return $tax;
            }
        }

        $fallback = $taxes->first();

        if (!$fallback instanceof TaxEntity) {
            throw new RuntimeException('At least one Shopware tax rule is required to seed demo products.');
        }

        return $fallback;
    }
}
