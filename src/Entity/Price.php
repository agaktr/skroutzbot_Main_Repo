<?php

namespace App\Entity;

use App\Repository\PriceRepository;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

/**
 * @ORM\Entity(repositoryClass=PriceRepository::class)
 */
class Price
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * The internal primary identity key.
     *
     * @var UuidInterface
     *
     * @ORM\Column(type="uuid", unique=true)
     */
    private $uuid;

    /**
     * @ORM\Column(type="uuid")
     */
    private $ProductUuid;

    /**
     * @ORM\Column(type="float")
     */
    private $NetPrice;

    /**
     * @ORM\Column(type="float")
     */
    private $ShippingCost;

    /**
     * @ORM\Column(type="float")
     */
    private $PaymentCost;

    /**
     * @ORM\Column(type="float")
     */
    private $FinalPrice;

    /**
     * @ORM\Column(type="uuid")
     */
    private $ShopUuid;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $Url;

    /**
     * @ORM\Column(type="integer")
     */
    private $Updated;

    public function __construct()
    {

        $this->uuid = Uuid::uuid4()->toString();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUuid()
    {
        return $this->uuid;
    }

    public function setUuid($uuid): self
    {
        $this->uuid = $uuid;

        return $this;
    }

    public function getProductUuid()
    {
        return $this->ProductUuid;
    }

    public function setProductUuid($ProductUuid): self
    {
        $this->ProductUuid = $ProductUuid;

        return $this;
    }

    public function getNetPrice(): ?float
    {
        return $this->NetPrice;
    }

    public function setNetPrice(float $NetPrice): self
    {
        $this->NetPrice = $NetPrice;

        return $this;
    }

    public function getShippingCost(): ?float
    {
        return $this->ShippingCost;
    }

    public function setShippingCost(float $ShippingCost): self
    {
        $this->ShippingCost = $ShippingCost;

        return $this;
    }

    public function getPaymentCost(): ?float
    {
        return $this->PaymentCost;
    }

    public function setPaymentCost(float $PaymentCost): self
    {
        $this->PaymentCost = $PaymentCost;

        return $this;
    }

    public function getFinalPrice(): ?float
    {
        return $this->FinalPrice;
    }

    public function setFinalPrice(float $FinalPrice): self
    {
        $this->FinalPrice = $FinalPrice;

        return $this;
    }

    public function getShopUuid()
    {
        return $this->ShopUuid;
    }

    public function setShopUuid($ShopUuid): self
    {
        $this->ShopUuid = $ShopUuid;

        return $this;
    }

    public function getUrl(): ?string
    {
        return $this->Url;
    }

    public function setUrl(string $Url): self
    {
        $this->Url = $Url;

        return $this;
    }

    public function getUpdated(): ?int
    {
        return $this->Updated;
    }

    public function setUpdated(int $Updated): self
    {
        $this->Updated = $Updated;

        return $this;
    }
}
