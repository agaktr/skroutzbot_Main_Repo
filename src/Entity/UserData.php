<?php

namespace App\Entity;

use App\Repository\UserDataRepository;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

/**
 * @ORM\Entity(repositoryClass=UserDataRepository::class)
 */
class UserData
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
    private $ApiKey;

    /**
     * @ORM\Column(type="boolean")
     */
    private $Active;

    /**
     * @ORM\Column(type="integer")
     */
    private $MaxRequests;

    /**
     * @ORM\Column(type="integer")
     */
    private $CurrentRequests;

    /**
     * @ORM\Column(type="integer")
     */
    private $Updated;

    public function __construct()
    {

        $this->uuid = Uuid::uuid4()->toString();
        $this->ApiKey = bin2hex(random_bytes(60));
        $this->Updated = time();
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

    public function getApiKey(): ?string
    {
        return $this->ApiKey;
    }

    public function setApiKey(string $ApiKey): self
    {
        $this->ApiKey = $ApiKey;

        return $this;
    }

    public function getActive(): ?bool
    {
        return $this->Active;
    }

    public function setActive(bool $Active): self
    {
        $this->Active = $Active;

        return $this;
    }

    public function getMaxRequests(): ?int
    {
        return $this->MaxRequests;
    }

    public function setMaxRequests(int $MaxRequests): self
    {
        $this->MaxRequests = $MaxRequests;

        return $this;
    }

    public function getCurrentRequests(): ?int
    {
        return $this->CurrentRequests;
    }

    public function setCurrentRequests(int $CurrentRequests): self
    {
        $this->CurrentRequests = $CurrentRequests;

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
