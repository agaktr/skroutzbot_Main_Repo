<?php

namespace App\Entity;

use App\Repository\ProfileRawDataRepository;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

/**
 * @ORM\Entity(repositoryClass=ProfileRawDataRepository::class)
 */
class ProfileRawData
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
    private $UserProfileUuid;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $ShopItem;

    /**
     * @ORM\Column(type="text")
     */
    private $HTML;

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

    public function getUserProfileUuid()
    {
        return $this->UserProfileUuid;
    }

    public function setUserProfileUuid($UserProfileUuid): self
    {
        $this->UserProfileUuid = $UserProfileUuid;

        return $this;
    }

    public function getShopItem(): ?string
    {
        return $this->ShopItem;
    }

    public function setShopItem(string $ShopItem): self
    {
        $this->ShopItem = $ShopItem;

        return $this;
    }

    public function getHTML(): ?string
    {
        return $this->HTML;
    }

    public function setHTML(string $HTML): self
    {
        $this->HTML = $HTML;

        return $this;
    }
}
