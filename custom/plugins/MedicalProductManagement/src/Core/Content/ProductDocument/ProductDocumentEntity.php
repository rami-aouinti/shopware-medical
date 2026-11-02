<?php declare(strict_types=1);

namespace MedicalProductManagement\Core\Content\ProductDocument;

use Shopware\Core\Content\Media\MediaEntity;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;

class ProductDocumentEntity extends Entity
{
    protected string $productId;
    protected string $productVersionId;
    protected string $mediaId;
    protected string $documentType;
    protected string $title;
    protected ?ProductEntity $product = null;
    protected ?MediaEntity $media = null;

    public function getProductId(): string { return $this->productId; }
    public function setProductId(string $productId): void { $this->productId = $productId; }
    public function getProductVersionId(): string { return $this->productVersionId; }
    public function setProductVersionId(string $productVersionId): void { $this->productVersionId = $productVersionId; }
    public function getMediaId(): string { return $this->mediaId; }
    public function setMediaId(string $mediaId): void { $this->mediaId = $mediaId; }
    public function getDocumentType(): string { return $this->documentType; }
    public function setDocumentType(string $documentType): void { $this->documentType = $documentType; }
    public function getTitle(): string { return $this->title; }
    public function setTitle(string $title): void { $this->title = $title; }
    public function getProduct(): ?ProductEntity { return $this->product; }
    public function setProduct(?ProductEntity $product): void { $this->product = $product; }
    public function getMedia(): ?MediaEntity { return $this->media; }
    public function setMedia(?MediaEntity $media): void { $this->media = $media; }
}