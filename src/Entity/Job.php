<?php

namespace App\Entity;

use App\Repository\JobRepository;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

/**
 * @ORM\Entity(repositoryClass=JobRepository::class)
 */
class Job
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
     * @ORM\Column(type="string", length=100)
     */
    private $Mode;

    /**
     * @ORM\Column(type="array")
     */
    private $Competitors = [];

    /**
     * @ORM\Column(type="float")
     */
    private $LowestPrice;

    /**
     * @ORM\Column(type="float")
     */
    private $Increment;

    /**
     * @ORM\Column(type="integer")
     */
    private $Created;

    /**
     * @ORM\Column(type="integer")
     */
    private $LastRun;

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

    public function getMode(): ?string
    {
        return $this->Mode;
    }

    public function setMode(string $Mode): self
    {
        $this->Mode = $Mode;

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

    public function getLowestPrice(): ?float
    {
        return $this->LowestPrice;
    }

    public function setLowestPrice(float $LowestPrice): self
    {
        $this->LowestPrice = $LowestPrice;

        return $this;
    }

    public function getIncrement(): ?float
    {
        return $this->Increment;
    }

    public function setIncrement(float $Increment): self
    {
        $this->Increment = $Increment;

        return $this;
    }

    public function getCreated(): ?int
    {
        return $this->Created;
    }

    public function setCreated(int $Created): self
    {
        $this->Created = $Created;

        return $this;
    }

    public function getLastRun(): ?int
    {
        return $this->LastRun;
    }

    public function setLastRun(int $LastRun): self
    {
        $this->LastRun = $LastRun;

        return $this;
    }
}
