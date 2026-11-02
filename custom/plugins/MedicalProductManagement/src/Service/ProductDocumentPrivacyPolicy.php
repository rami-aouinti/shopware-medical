<?php declare(strict_types=1);

namespace MedicalProductManagement\Service;

final class ProductDocumentPrivacyPolicy
{
    public function resolvePublicUrl(
        bool $documentIsPrivate,
        bool $mediaIsPrivate,
        ?string $mediaUrl
    ): ?string {
        if ($documentIsPrivate || $mediaIsPrivate) {
            return null;
        }

        return $mediaUrl;
    }
}
