<?php declare(strict_types=1);
namespace MedicalProductManagement\Tests\Unit\Service;
use MedicalProductManagement\Service\ProductCustomFieldValidator;
use PHPUnit\Framework\TestCase;
class ProductCustomFieldValidatorTest extends TestCase
{
    private ProductCustomFieldValidator $validator;
    protected function setUp(): void { $this->validator = new ProductCustomFieldValidator(); }
    public function testAcceptsValidValues(): void
    {
        self::assertSame([], $this->validator->validate(['package_quantity'=>10,'package_unit'=>'box','minimum_order_quantity'=>20.0]));
    }
    public function testAllowsPartialCustomFieldUpdates(): void
    {
        self::assertSame([], $this->validator->validate(['package_quantity'=>12]));
    }
    public function testRejectsInvalidPackageQuantity(): void
    {
        foreach ([0,-1,1.5,'5'] as $value) {
            self::assertContains('MEDICAL_PACKAGE_QUANTITY_INVALID',array_column($this->validator->validate(['package_quantity'=>$value]),'code'));
        }
    }
    public function testRejectsInvalidMinimumOrderQuantity(): void
    {
        foreach ([0,-2,'abc',INF,NAN] as $value) {
            self::assertContains('MEDICAL_MINIMUM_ORDER_QUANTITY_INVALID',array_column($this->validator->validate(['minimum_order_quantity'=>$value]),'code'));
        }
    }
    public function testRejectsBlankOrLongPackageUnit(): void
    {
        foreach ([' ',str_repeat('x',33)] as $value) {
            self::assertContains('MEDICAL_PACKAGE_UNIT_INVALID',array_column($this->validator->validate(['package_unit'=>$value]),'code'));
        }
    }
}