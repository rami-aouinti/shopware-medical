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
    protected bool $isPrivate = false;
    protected ?ProductEntity $product = null;
    protected ?MediaEntity $media = null;
    public function getProductId(): string { return $this->productId; }
    public function setProductId(string $value): void { $this->productId = $value; }
    public function getProductVersionId(): string { return $this->productVersionId; }
    public function setProductVersionId(string $value): void { $this->productVersionId = $value; }
    public function getMediaId(): string { return $this->mediaId; }
    public function setMediaId(string $value): void { $this->mediaId = $value; }
    public function getDocumentType(): string { return $this->documentType; }
    public function setDocumentType(string $value): void { $this->documentType = $value; }
    public function getTitle(): string { return $this->title; }
    public function setTitle(string $value): void { $this->title = $value; }
    public function isPrivate(): bool { return $this->isPrivate; }
    public function setIsPrivate(bool $value): void { $this->isPrivate = $value; }
    public function getProduct(): ?ProductEntity { return $this->product; }
    public function setProduct(?ProductEntity $value): void { $this->product = $value; }
    public function getMedia(): ?MediaEntity { return $this->media; }
    public function setMedia(?MediaEntity $value): void { $this->media = $value; }
}