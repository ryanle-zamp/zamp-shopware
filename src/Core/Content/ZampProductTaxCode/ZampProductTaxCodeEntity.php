<?php

namespace ZampTax\Core\Content\ZampProductTaxCode;

use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;

class ZampProductTaxCodeEntity extends Entity
{
    use EntityIdTrait;

	/**
	 * @var string
	 */

    protected $productId;

	/**
	 * @var string
	 */

    protected $productTaxCode;

    public function getProductId(): ?string
    {
        return $this->productId;
    }

    public function setProductId(?string $productId): void
    {
        $this->productId = $productId;
    }

    public function getProductTaxCode(): ?string
    {
        return $this->productTaxCode;
    }

    public function setProductTaxCode(?string $productTaxCode): void
    {
        $this->productTaxCode = $productTaxCode;
    }

    

}