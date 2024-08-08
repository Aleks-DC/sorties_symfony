<?php

namespace App\Entity;

use App\Repository\EtatRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EtatRepository::class)]
class Etat
{
    const ETAT_CREEE = 'Créée';
    const ETAT_OUVERTE = 'Ouverte';
    const ETAT_CLOTUREE = 'Clôturée';
    const ETAT_EN_COURS = 'Activité en cours';
    const ETAT_PASSEE = 'Passée';
    const ETAT_ANNULEE = 'Annulée';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $libelle = null;

    /**
     * @var Collection<int, Sortie>
     */
    #[ORM\OneToMany(targetEntity: Sortie::class, mappedBy: 'etats')]
    private Collection $sorties;

    public function __construct()
    {
        $this->sorties = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLibelle(): ?string
    {
        return $this->libelle;
    }

    public function setLibelle(string $libelle): static
    {
        $this->libelle = $libelle;

        return $this;
    }

    /**
     * @return Collection<int, Sortie>
     */
    public function getSorties(): Collection
    {
        return $this->sorties;
    }

    public function addSortie(Sortie $sorties): static
    {
        if (!$this->sorties->contains($sorties)) {
            $this->sorties->add($sorties);
            $sorties->setEtats($this);
        }

        return $this;
    }

    public function removeSortie(Sortie $sorties): static
    {
        if ($this->sorties->removeElement($sorties)) {
            // set the owning side to null (unless already changed)
            if ($sorties->getEtats() === $this) {
                $sorties->setEtats(null);
            }
        }

        return $this;
    }
}
