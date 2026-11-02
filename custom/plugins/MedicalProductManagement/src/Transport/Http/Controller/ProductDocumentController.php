<?php declare(strict_types=1);

namespace MedicalProductManagement\Transport\Http\Controller;

use MedicalProductManagement\Infrastructure\Persistence\Shopware\ProductDocument\ProductDocumentEntity;
use MedicalProductManagement\Domain\ProductDocuments\Policy\ProductDocumentPrivacyPolicy;
use League\Flysystem\UnableToReadFile;
use Shopware\Core\Content\Media\File\DownloadResponseGenerator;
use Shopware\Core\Content\Media\MediaEntity;
use Shopware\Core\Content\Media\MediaException;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Shopware\Core\Framework\Uuid\Uuid;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;

#[Route(defaults: ['_routeScope' => ['api']])]
final class ProductDocumentController
{
    private const TYPES = [
        'CE_CERTIFICATE',
        'DECLARATION_OF_CONFORMITY',
        'TECHNICAL_DATASHEET',
        'USER_MANUAL',
        'SAFETY_DATA_SHEET',
        'OTHER',
    ];

    public function __construct(
        private readonly EntityRepository $documentRepository,
        private readonly EntityRepository $mediaRepository,
        private readonly ProductDocumentPrivacyPolicy $privacyPolicy,
        private readonly DownloadResponseGenerator $downloadResponseGenerator
    ) {
    }

    #[Route(
        path: '/api/_action/medical-product-management/products/{productId}/documents',
        name: 'api.action.medical_product_document.list',
        methods: ['GET'],
        defaults: ['_acl' => ['product:read']]
    )]
    public function list(string $productId, Context $context): JsonResponse
    {
        $this->assertUuid($productId, 'productId');
        $productId = strtolower($productId);

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('productId', $productId));
        $criteria->addFilter(new EqualsFilter('productVersionId', Defaults::LIVE_VERSION));
        $criteria->addAssociation('media');
        $criteria->addSorting(new FieldSorting('createdAt'));

        $items = [];
        foreach ($this->documentRepository->search($criteria, $context) as $document) {
            if (!$document instanceof ProductDocumentEntity) {
                continue;
            }

            $media = $document->getMedia();
            $isPrivate = $document->isPrivate();

            $items[] = [
                'id' => $document->getId(),
                'productId' => $document->getProductId(),
                'mediaId' => $document->getMediaId(),
                'title' => $document->getTitle(),
                'documentType' => $document->getDocumentType(),
                'isPrivate' => $isPrivate,
                'fileName' => $media?->getFileNameIncludingExtension(),
                'url' => $media instanceof MediaEntity
                    ? $this->privacyPolicy->resolvePublicUrl($isPrivate, $media->isPrivate(), $media->getUrl())
                    : null,
                'createdAt' => $document->getCreatedAt()?->format(DATE_ATOM),
            ];
        }

        return new JsonResponse(['data' => $items]);
    }

    /**
     * Streams a private Shopware media file only after both the product-document
     * and media ACL checks have passed. The core generator selects the private
     * filesystem and handles the configured download strategy.
     */
    #[Route(
        path: '/api/_action/medical-product-management/documents/{documentId}/download',
        name: 'api.action.medical_product_document.download_private',
        methods: ['GET'],
        defaults: ['_acl' => ['product:read', 'media:read']]
    )]
    public function downloadPrivate(string $documentId, Context $context): Response
    {
        $this->assertUuid($documentId, 'documentId');
        $documentId = strtolower($documentId);

        $criteria = new Criteria([$documentId]);
        $criteria->addAssociation('media');
        $document = $this->documentRepository->search($criteria, $context)->first();

        if (!$document instanceof ProductDocumentEntity || !$document->isPrivate()) {
            return new JsonResponse(['errors' => [['detail' => 'Document not found.']]], 404);
        }

        $media = $document->getMedia();
        if (!$media instanceof MediaEntity || !$media->isPrivate()) {
            return new JsonResponse(['errors' => [['detail' => 'Private document file not found.']]], 404);
        }

        try {
            $response = $this->downloadResponseGenerator->getResponseByContext($media, $context);
        } catch (MediaException|UnableToReadFile) {
            return new JsonResponse(['errors' => [['detail' => 'Document file not found.']]], 404);
        }

        $response->headers->set('Cache-Control', 'private, no-store, no-cache, must-revalidate');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        return $response;
    }

    #[Route(
        path: '/api/_action/medical-product-management/products/{productId}/documents',
        name: 'api.action.medical_product_document.create',
        methods: ['POST'],
        defaults: ['_acl' => ['product:update']]
    )]
    public function create(string $productId, Request $request, Context $context): JsonResponse
    {
        $this->assertUuid($productId, 'productId');
        $productId = strtolower($productId);

        $payload = $request->toArray();
        $title = $this->requiredString($payload, 'title', 255);
        $type = $this->requiredString($payload, 'documentType', 100);
        $mediaId = $this->requiredString($payload, 'mediaId', 32);
        $isPrivate = $payload['isPrivate'] ?? false;

        if (!is_bool($isPrivate)) {
            throw new BadRequestHttpException('isPrivate must be a boolean.');
        }

        $this->assertType($type);
        $this->assertMediaPrivacy($mediaId, $isPrivate, $context);

        $id = Uuid::randomHex();
        $this->documentRepository->create([
            [
                'id' => $id,
                'productId' => $productId,
                'productVersionId' => Defaults::LIVE_VERSION,
                'mediaId' => $mediaId,
                'title' => $title,
                'documentType' => $type,
                'isPrivate' => $isPrivate,
            ],
        ], $context);

        return new JsonResponse(['success' => true, 'id' => $id], 201);
    }

    #[Route(
        path: '/api/_action/medical-product-management/documents/{documentId}',
        name: 'api.action.medical_product_document.update',
        methods: ['PATCH'],
        defaults: ['_acl' => ['product:update']]
    )]
    public function update(string $documentId, Request $request, Context $context): JsonResponse
    {
        $this->assertUuid($documentId, 'documentId');
        $documentId = strtolower($documentId);

        $payload = $request->toArray();
        $document = $this->documentRepository->search(new Criteria([$documentId]), $context)->first();

        if (!$document instanceof ProductDocumentEntity) {
            return new JsonResponse(['errors' => [['detail' => 'Document not found.']]], 404);
        }

        $update = ['id' => $documentId];

        if (array_key_exists('title', $payload)) {
            $update['title'] = $this->requiredString($payload, 'title', 255);
        }

        if (array_key_exists('documentType', $payload)) {
            $type = $this->requiredString($payload, 'documentType', 100);
            $this->assertType($type);
            $update['documentType'] = $type;
        }

        if (array_key_exists('mediaId', $payload) || array_key_exists('isPrivate', $payload)) {
            $mediaId = $payload['mediaId'] ?? $document->getMediaId();
            $isPrivate = $payload['isPrivate'] ?? $document->isPrivate();

            if (!is_string($mediaId) || !is_bool($isPrivate)) {
                throw new BadRequestHttpException('Invalid mediaId or isPrivate.');
            }

            $this->assertMediaPrivacy($mediaId, $isPrivate, $context);
            $update['mediaId'] = strtolower($mediaId);
            $update['isPrivate'] = $isPrivate;
        }

        $this->documentRepository->update([$update], $context);

        return new JsonResponse(['success' => true]);
    }

    #[Route(
        path: '/api/_action/medical-product-management/documents/{documentId}',
        name: 'api.action.medical_product_document.delete',
        methods: ['DELETE'],
        defaults: ['_acl' => ['product:delete']]
    )]
    public function delete(string $documentId, Context $context): JsonResponse
    {
        $this->assertUuid($documentId, 'documentId');
        $documentId = strtolower($documentId);

        $document = $this->documentRepository->search(new Criteria([$documentId]), $context)->first();
        if (!$document instanceof ProductDocumentEntity) {
            return new JsonResponse(['errors' => [['detail' => 'Document not found.']]], 404);
        }

        $this->documentRepository->delete([['id' => $documentId]], $context);

        return new JsonResponse(['success' => true]);
    }

    private function assertMediaPrivacy(string $mediaId, bool $documentIsPrivate, Context $context): void
    {
        $this->assertUuid($mediaId, 'mediaId');
        $media = $this->mediaRepository->search(new Criteria([strtolower($mediaId)]), $context)->first();

        if (!$media instanceof MediaEntity) {
            throw new BadRequestHttpException('The selected media file does not exist.');
        }

        if ($media->isPrivate() !== $documentIsPrivate) {
            throw new BadRequestHttpException(
                $documentIsPrivate
                    ? 'Private documents must use a Shopware media file marked as private.'
                    : 'A private Shopware media file cannot be published as a public product document.'
            );
        }
    }

    private function assertType(string $type): void
    {
        if (!in_array($type, self::TYPES, true)) {
            throw new BadRequestHttpException('Unsupported medical document type.');
        }
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function requiredString(array $payload, string $key, int $maxLength): string
    {
        $value = $payload[$key] ?? null;
        if (!is_string($value) || trim($value) === '' || mb_strlen(trim($value)) > $maxLength) {
            throw new BadRequestHttpException(sprintf(
                '%s must contain between 1 and %d characters.',
                $key,
                $maxLength
            ));
        }

        return trim($value);
    }

    private function assertUuid(string $value, string $field): void
    {
        if (!Uuid::isValid($value)) {
            throw new BadRequestHttpException(sprintf('%s must be a valid UUID.', $field));
        }
    }
}
