<?php declare(strict_types=1);
namespace MedicalProductManagement\Controller;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use MedicalProductManagement\Core\Content\ProductDocument\ProductDocumentEntity;
use Shopware\Core\Content\Media\MediaEntity;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Shopware\Core\Framework\Uuid\Uuid;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
#[Route(defaults: ['_routeScope' => ['api']])]
final class ProductDocumentController
{
    private const TYPES = ['CE_CERTIFICATE','DECLARATION_OF_CONFORMITY','TECHNICAL_DATASHEET','USER_MANUAL','SAFETY_DATA_SHEET','OTHER'];
    public function __construct(private readonly EntityRepository $documentRepository, private readonly EntityRepository $mediaRepository) {}
    #[Route(path:'/api/_action/medical-product-management/products/{productId}/documents',name:'api.action.medical_product_document.list',methods:['GET'],defaults:['_acl'=>['product:read']])]
    public function list(string $productId, Context $context): JsonResponse
    {
        $this->assertUuid($productId, 'productId');
        $productId = strtolower($productId);
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('productId', strtolower($productId)));
        $criteria->addFilter(new EqualsFilter('productVersionId', Defaults::LIVE_VERSION));
        $criteria->addAssociation('media');
        $criteria->addSorting(new FieldSorting('createdAt'));
        $items = [];
        foreach ($this->documentRepository->search($criteria, $context) as $doc) {
            if (!$doc instanceof ProductDocumentEntity) { continue; }
            $media = $doc->getMedia();
            $private = $doc->isPrivate();
            $items[] = [
                'id'=>$doc->getId(), 'productId'=>$doc->getProductId(), 'mediaId'=>$doc->getMediaId(),
                'title'=>$doc->getTitle(), 'documentType'=>$doc->getDocumentType(), 'isPrivate'=>$private,
                'fileName'=>$media?->getFileNameIncludingExtension(),
                'url'=>(!$private && $media instanceof MediaEntity && !$media->isPrivate()) ? $media->getUrl() : null,
                'createdAt'=>$doc->getCreatedAt()?->format(DATE_ATOM),
            ];
        }
        return new JsonResponse(['data'=>$items]);
    }
    #[Route(path:'/api/_action/medical-product-management/products/{productId}/documents',name:'api.action.medical_product_document.create',methods:['POST'],defaults:['_acl'=>['product:update']])]
    public function create(string $productId, Request $request, Context $context): JsonResponse
    {
        $this->assertUuid($productId, 'productId');
        $payload = $request->toArray();
        $title = $this->requiredString($payload, 'title', 255);
        $type = $this->requiredString($payload, 'documentType', 100);
        $mediaId = $this->requiredString($payload, 'mediaId', 32);
        $private = $payload['isPrivate'] ?? false;
        if (!is_bool($private)) { throw new BadRequestHttpException('isPrivate must be a boolean.'); }
        $this->assertType($type);
        $this->assertMediaPrivacy($mediaId, $private, $context);
        $id = Uuid::randomHex();
        $this->documentRepository->create([['id'=>$id,'productId'=>$productId,'productVersionId'=>Defaults::LIVE_VERSION,'mediaId'=>$mediaId,'title'=>$title,'documentType'=>$type,'isPrivate'=>$private]], $context);
        return new JsonResponse(['success'=>true,'id'=>$id], 201);
    }
    #[Route(path:'/api/_action/medical-product-management/documents/{documentId}',name:'api.action.medical_product_document.update',methods:['PATCH'],defaults:['_acl'=>['product:update']])]
    public function update(string $documentId, Request $request, Context $context): JsonResponse
    {
        $this->assertUuid($documentId, 'documentId');
        $payload = $request->toArray();
        $doc = $this->documentRepository->search(new Criteria([$documentId]), $context)->first();
        if (!$doc instanceof ProductDocumentEntity) { return new JsonResponse(['errors'=>[['detail'=>'Document not found.']]],404); }
        $update = ['id'=>$documentId];
        if (array_key_exists('title',$payload)) { $update['title']=$this->requiredString($payload,'title',255); }
        if (array_key_exists('documentType',$payload)) { $update['documentType']=$this->requiredString($payload,'documentType',100); $this->assertType($update['documentType']); }
        if (array_key_exists('mediaId',$payload) || array_key_exists('isPrivate',$payload)) {
            $mediaId = $payload['mediaId'] ?? $doc->getMediaId();
            $private = $payload['isPrivate'] ?? $doc->isPrivate();
            if (!is_string($mediaId) || !is_bool($private)) { throw new InvalidArgumentException('Invalid mediaId or isPrivate.'); }
            $this->assertMediaPrivacy($mediaId,$private,$context);
            $update['mediaId']=$mediaId; $update['isPrivate']=$private;
        }
        $this->documentRepository->update([$update],$context);
        return new JsonResponse(['success'=>true]);
    }
    #[Route(path:'/api/_action/medical-product-management/documents/{documentId}',name:'api.action.medical_product_document.delete',methods:['DELETE'],defaults:['_acl'=>['product:delete']])]
    public function delete(string $documentId, Context $context): JsonResponse
    {
        $this->assertUuid($documentId,'documentId');
        $this->documentRepository->delete([['id'=>$documentId]],$context);
        return new JsonResponse(['success'=>true]);
    }
    private function assertMediaPrivacy(string $id, bool $private, Context $context): void
    {
        $this->assertUuid($id,'mediaId');
        $media=$this->mediaRepository->search(new Criteria([$id]),$context)->first();
        if (!$media instanceof MediaEntity) { throw new InvalidArgumentException('The selected media file does not exist.'); }
        if ($media->isPrivate() !== $private) {
            throw new InvalidArgumentException($private
                ? 'Private documents must use a Shopware media file marked as private.'
                : 'A private Shopware media file cannot be published as a public product document.');
        }
    }
    private function assertType(string $type): void
    {
        if (!in_array($type,self::TYPES,true)) { throw new InvalidArgumentException('Unsupported medical document type.'); }
    }
    /** @param array<string,mixed> $payload */
    private function requiredString(array $payload,string $key,int $max): string
    {
        $value=$payload[$key]??null;
        if (!is_string($value) || trim($value)==='' || mb_strlen(trim($value))>$max) {
            throw new InvalidArgumentException(sprintf('%s must contain between 1 and %d characters.',$key,$max));
        }
        return trim($value);
    }
    private function assertUuid(string $value,string $field): void
    {
        if (!Uuid::isValid($value)) { throw new InvalidArgumentException(sprintf('%s must be a valid UUID.',$field)); }
    }
}