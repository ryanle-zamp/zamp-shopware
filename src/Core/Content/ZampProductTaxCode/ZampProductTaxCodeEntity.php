<?php

namespace ZampTax\Core\Content\ZampProductTaxCode;

use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;

/**
 * Entity representing a product tax code mapping for Zamp Tax integration
 */
class ZampProductTaxCodeEntity extends Entity
{
    use EntityIdTrait;

    /**
     * The product ID associated with this tax code
     * 
     * @var string|null
     */
    protected $productId;

    /**
     * The Zamp tax code for this product
     * 
     * @var string|null
     */
    protected $productTaxCode;

    /**
     * Gets the product ID
     * 
     * @return string|null
     */
    public function getProductId(): ?string
    {
        return $this->productId;
    }

    /**
     * Sets the product ID
     * 
     * @param string|null $productId The product ID to set
     */
    public function setProductId(?string $productId): void
    {
        this->productId = $productId;
    }

    /**
     * Gets the product tax code
     * 
     * @return string|null
     */
    public function getProductTaxCode(): ?string
    {
        return $this->productTaxCode;
    }

    /**
     * Sets the product tax code
     * 
     * @param string|null $productTaxCode The product tax code to set
     */
    public function setProductTaxCode(?string $productTaxCode): void
    {
        $this->productTaxCode = $productTaxCode;
    }
}