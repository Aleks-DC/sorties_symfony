<?php

namespace App\Entity;

use App\Repository\ParticipantRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ParticipantRepository::class)]
class Participant 
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $nom = null;

    #[ORM\Column(length: 255)]
    private ?string $prenom = null;

    #[ORM\Column]
    #[Assert\Regex(
        pattern: '/^\+?[0-9]{7,15}$/',
        message: 'Le numéro de téléphone doit contenir entre 7 et 15 chiffres.'
    )]
    private ?string $telephone = null;

    #[ORM\Column(length: 255)]
    #[Assert\Email]
    private ?string $mail = null;

    #[ORM\Column(length: 255)]
    private ?string $motDePasse = null;

    #[ORM\Column]
    private ?bool $administrateur = null;

    #[ORM\Column]
    private ?bool $actif = null;

    #[ORM\Column(length: 255)]
    private ?string $pseudo = null;

    #[ORM\ManyToOne(inversedBy: 'participants')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Campus $campusAffilie = null;

    /**
     * @var Collection<int, Sortie>
     */
    #[ORM\OneToMany(targetEntity: Sortie::class, mappedBy: 'participant')]
    private Collection $sortiesOrganisees;

    /**
     * @var Collection<int, Sortie>
     */
    #[ORM\ManyToMany(targetEntity: Sortie::class, mappedBy: 'estInscrit')]
    private Collection $sortiesPrevues;

    public function __construct()
    {
        $this->sortiesOrganisees = new ArrayCollection();
        $this->sortiesPrevues = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): static
    {
        $this->nom = $nom;

        return $this;
    }

    public function getPrenom(): ?string
    {
        return $this->prenom;
    }

    public function setPrenom(string $prenom): static
    {
        $this->prenom = $prenom;

        return $this;
    }

    public function getTelephone(): ?string
    {
        return $this->telephone;
    }

    public function setTelephone(string $telephone): static
    {
        $this->telephone = $telephone;

        return $this;
    }

    public function getMail(): ?string
    {
        return $this->mail;
    }

    public function setMail(string $mail): static
    {
        $this->mail = $mail;

        return $this;
    }

    public function getMotDePasse(): ?string
    {
        return $this->motDePasse;
    }

    public function setMotDePasse(string $motDePasse): static
    {
        $this->motDePasse = $motDePasse;

        return $this;
    }

    public function isAdministrateur(): ?bool
    {
        return $this->administrateur;
    }

    public function setAdministrateur(bool $administrateur): static
    {
        $this->administrateur = $administrateur;

        return $this;
    }

    public function isActif(): ?bool
    {
        return $this->actif;
    }

    public function setActif(bool $actif): static
    {
        $this->actif = $actif;

        return $this;
    }

    public function getPseudo(): ?string
    {
        return $this->pseudo;
    }

    public function setPseudo(string $pseudo): static
    {
        $this->pseudo = $pseudo;

        return $this;
    }

    public function getCampus(): ?Campus
    {
        return $this->campusAffilie;
    }

    public function setCampus(?Campus $campusAffilie): static
    {
        $this->campusAffilie = $campusAffilie;

        return $this;
    }

    /**
     * @return Collection<int, Sortie>
     */
    public function getSorties(): Collection
    {
        return $this->sortiesOrganisees;
    }

    public function addSortie(Sortie $sorties): static
    {
        if (!$this->sortiesOrganisees->contains($sorties)) {
            $this->sortiesOrganisees->add($sorties);
            $sorties->setParticipant($this);
        }

        return $this;
    }

    public function removeSortie(Sortie $sorties): static
    {
        if ($this->sortiesOrganisees->removeElement($sorties)) {
            // set the owning side to null (unless already changed)
            if ($sorties->getParticipant() === $this) {
                $sorties->setParticipant(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Sortie>
     */
    public function getSortiesPrevues(): Collection
    {
        return $this->sortiesPrevues;
    }

    public function addSortiesPrevue(Sortie $sortiesPrevue): static
    {
        if (!$this->sortiesPrevues->contains($sortiesPrevue)) {
            $this->sortiesPrevues->add($sortiesPrevue);
            $sortiesPrevue->addEstInscrit($this);
        }

        return $this;
    }

    public function removeSortiesPrevue(Sortie $sortiesPrevue): static
    {
        if ($this->sortiesPrevues->removeElement($sortiesPrevue)) {
            $sortiesPrevue->removeEstInscrit($this);
        }

        return $this;
    }
}
