<?php

namespace App\Entity;

use App\Repository\ProductRepository;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

/**
 * @ORM\Entity(repositoryClass=ProductRepository::class)
 */
class Product
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
     * @ORM\Column(type="string", length=100)
     */
    private $Name;

    /**
     * @ORM\Column(type="integer")
     */
    private $ExternalId;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $Url;

    /**
     * @ORM\Column(type="text")
     */
    private $Specs;

    /**
     * @ORM\Column(type="float")
     */
    private $Rating;

    /**
     * @ORM\Column(type="integer")
     */
    private $Updated;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $Photo;

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

    public function getName(): ?string
    {
        return $this->Name;
    }

    public function setName(string $Name): self
    {
        $this->Name = $Name;

        return $this;
    }

    public function getExternalId(): ?int
    {
        return $this->ExternalId;
    }

    public function setExternalId(int $ExternalId): self
    {
        $this->ExternalId = $ExternalId;

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

    public function getSpecs(): ?string
    {
        return $this->Specs;
    }

    public function setSpecs(string $Specs): self
    {
        $this->Specs = $Specs;

        return $this;
    }

    public function getRating(): ?float
    {
        return $this->Rating;
    }

    public function setRating(float $Rating): self
    {
        $this->Rating = $Rating;

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

    public function getPhoto(): ?string
    {
        return $this->Photo;
    }

    public function setPhoto(string $Photo): self
    {
        $this->Photo = $Photo;

        return $this;
    }
}
