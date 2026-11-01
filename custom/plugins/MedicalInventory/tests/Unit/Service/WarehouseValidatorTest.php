<?php declare(strict_types=1);

namespace MedicalInventory\Tests\Unit\Service;

use InvalidArgumentException;
use MedicalInventory\Service\WarehouseValidator;
use PHPUnit\Framework\TestCase;

final class WarehouseValidatorTest extends TestCase
{
    private WarehouseValidator $validator;

    #[\Override]
    protected function setUp(): void
    {
        $this->validator = new WarehouseValidator();
    }

    public function testItNormalizesWarehouseCodeAndCountryCode(): void
    {
        self::assertSame(
            [
                'code' => 'DE-KOELN',
                'name' => 'Köln Central Warehouse',
                'countryCode' => 'DE',
            ],
            $this->validator->validate(' de-koeln ', ' Köln Central Warehouse ', ' de ')
        );
    }

    public function testItAcceptsUnderscoresAndDigitsInWarehouseCodes(): void
    {
        self::assertSame(
            'FR-PARIS_01',
            $this->validator->validate('fr-paris_01', 'Paris Warehouse', 'fr')['code']
        );
    }

    public function testItRejectsWarehouseCodesThatAreTooShort(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->validator->validate('A', 'Warehouse', 'DE');
    }

    public function testItRejectsWarehouseCodesWithUnsupportedCharacters(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->validator->validate('DE KOELN', 'Warehouse', 'DE');
    }

    public function testItRequiresAName(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->validator->validate('DE-KOELN', '   ', 'DE');
    }

    public function testItRejectsCountryCodesThatAreNotTwoLetters(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->validator->validate('DE-KOELN', 'Warehouse', 'DEU');
    }

    public function testItRejectsNamesLongerThanTheDatabaseColumn(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->validator->validate('DE-KOELN', str_repeat('A', 256), 'DE');
    }
}
