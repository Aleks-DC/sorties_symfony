<?php

namespace App\Entity;

use App\Repository\LieuRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LieuRepository::class)]
class Lieu
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $Nom = null;

    #[ORM\Column(length: 255)]
    private ?string $Rue = null;

    #[ORM\Column]
    private ?float $Latitude = null;

    #[ORM\Column]
    private ?float $Longitude = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setNom(string $nom): static
    {
        $this->nom = $nom;

        return $this;
    }

    public function getRue(): ?string
    {
        return $this->Rue;
    }

    public function setRue(string $Rue): static
    {
        $this->Rue = $Rue;

        return $this;
    }

    public function getLatitude(): ?float
    {
        return $this->Latitude;
    }

    public function setLatitude(float $Latitude): static
    {
        $this->Latitude = $Latitude;

        return $this;
    }

    public function getLongitude(): ?float
    {
        return $this->Longitude;
    }

    public function setLongitude(float $Longitude): static
    {
        $this->Longitude = $Longitude;

        return $this;
    }
}
