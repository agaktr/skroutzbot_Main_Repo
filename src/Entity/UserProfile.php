<?php

namespace App\Entity;

use App\Repository\UserProfileRepository;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

/**
 * @ORM\Entity(repositoryClass=UserProfileRepository::class)
 */
class UserProfile
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
    private $UserUuid;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $CsvUrl;

    /**
     * @ORM\Column(type="integer")
     */
    private $ItemsNumber;

    /**
     * @ORM\Column(type="array")
     */
    private $Products = [];

    /**
     * @ORM\Column(type="integer")
     */
    private $ItemsProcessed;

    /**
     * @ORM\Column(type="boolean")
     */
    private $IsDone;

    /**
     * @ORM\Column(type="array")
     */
    private $Competitors = [];

    /**
     * @ORM\Column(type="string", length=100)
     */
    private $Name;

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

    public function getUserUuid()
    {
        return $this->UserUuid;
    }

    public function setUserUuid($UserUuid): self
    {
        $this->UserUuid = $UserUuid;

        return $this;
    }

    public function getCsvUrl(): ?string
    {
        return $this->CsvUrl;
    }

    public function setCsvUrl(string $CsvUrl): self
    {
        $this->CsvUrl = $CsvUrl;

        return $this;
    }

    public function getItemsNumber(): ?int
    {
        return $this->ItemsNumber;
    }

    public function setItemsNumber(int $ItemsNumber): self
    {
        $this->ItemsNumber = $ItemsNumber;

        return $this;
    }

    public function getProducts(): ?array
    {
        return $this->Products;
    }

    public function setProducts(array $Products): self
    {
        $this->Products = $Products;

        return $this;
    }

    public function getItemsProcessed(): ?int
    {
        return $this->ItemsProcessed;
    }

    public function setItemsProcessed(int $ItemsProcessed): self
    {
        $this->ItemsProcessed = $ItemsProcessed;

        return $this;
    }

    public function getIsDone(): ?bool
    {
        return $this->IsDone;
    }

    public function setIsDone(bool $IsDone): self
    {
        $this->IsDone = $IsDone;

        return $this;
    }

    public function getCompetitors(): ?array
    {
        return $this->Competitors;
    }

    public function setCompetitors(array $Competitors): self
    {
        $this->Competitors = $Competitors;

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
}
