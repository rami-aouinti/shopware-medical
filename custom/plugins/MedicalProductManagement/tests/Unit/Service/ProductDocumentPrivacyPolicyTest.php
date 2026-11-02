<?php declare(strict_types=1);

namespace MedicalProductManagement\Tests\Unit\Service;

use MedicalProductManagement\Service\ProductDocumentPrivacyPolicy;
use PHPUnit\Framework\TestCase;

class ProductDocumentPrivacyPolicyTest extends TestCase
{
    private ProductDocumentPrivacyPolicy $policy;

    protected function setUp(): void
    {
        $this->policy = new ProductDocumentPrivacyPolicy();
    }

    public function testPublicDocumentWithPublicMediaReturnsUrl(): void
    {
        self::assertSame(
            'https://cdn.example.test/manual.pdf',
            $this->policy->resolvePublicUrl(false, false, 'https://cdn.example.test/manual.pdf')
        );
    }

    public function testPrivateDocumentNeverReturnsUrl(): void
    {
        self::assertNull(
            $this->policy->resolvePublicUrl(true, false, 'https://cdn.example.test/private.pdf')
        );
    }

    public function testPrivateMediaNeverReturnsUrlEvenIfDocumentFlagIsPublic(): void
    {
        self::assertNull(
            $this->policy->resolvePublicUrl(false, true, 'https://cdn.example.test/private.pdf')
        );
    }

    public function testMissingMediaUrlStaysNull(): void
    {
        self::assertNull($this->policy->resolvePublicUrl(false, false, null));
    }
}
