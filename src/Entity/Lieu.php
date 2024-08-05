<?php

namespace App\Entity;

use App\Repository\LieuRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
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

    #[ORM\ManyToOne(inversedBy: 'lieu')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Ville $villes = null;

    /**
     * @var Collection<int, Sortie>
     */
    #[ORM\OneToMany(targetEntity: Sortie::class, mappedBy: 'lieux')]
    private Collection $sorties;

    public function __construct()
    {
        $this->sorties = new ArrayCollection();
    }

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

    public function getVilles(): ?Ville
    {
        return $this->villes;
    }

    public function setVilles(?Ville $villes): static
    {
        $this->villes = $villes;

        return $this;
    }

    /**
     * @return Collection<int, Sortie>
     */
    public function getSortie(): Collection
    {
        return $this->sorties;
    }

    public function addSortie(Sortie $sorties): static
    {
        if (!$this->sorties->contains($sorties)) {
            $this->sorties->add($sorties);
            $sorties->setLieux($this);
        }

        return $this;
    }

    public function removeSortie(Sortie $sorties): static
    {
        if ($this->sorties->removeElement($sorties)) {
            // set the owning side to null (unless already changed)
            if ($sorties->getLieux() === $this) {
                $sorties->setLieux(null);
            }
        }

        return $this;
    }
}
