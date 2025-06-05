<?php

namespace App\Entity;

use App\Repository\CategorieRepository;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

#[ORM\Entity(repositoryClass: CategorieRepository::class)]
class Categorie
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    private ?string $name = null;

    #[ORM\OneToMany(mappedBy: 'categorie', targetEntity: Question::class)]
    private Collection $questions;

    /**
     * @var Collection<int, QuizResult>
     */
    #[ORM\OneToMany(targetEntity: QuizResult::class, mappedBy: 'categorie')]
    private Collection $Result;

    public function __construct()
    {
        $this->questions = new ArrayCollection();
        $this->Result = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return Collection<int, Question>
     */
    public function getQuestions(): Collection
    {
        return $this->questions;
    }

    public function addQuestion(Question $question): static
    {
        if (!$this->questions->contains($question)) {
            $this->questions->add($question);
            $question->setCategorie($this);
        }
        return $this;
    }

    public function removeQuestion(Question $question): static
    {
        if ($this->questions->removeElement($question)) {
            if ($question->getCategorie() === $this) {
                $question->setCategorie(null);
            }
        }
        return $this;
    }

    /**
     * @return Collection<int, QuizResult>
     */
    public function getResult(): Collection
    {
        return $this->Result;
    }

    public function addResult(QuizResult $result): static
    {
        if (!$this->Result->contains($result)) {
            $this->Result->add($result);
            $result->setCategorie($this);
        }

        return $this;
    }

    public function removeResult(QuizResult $result): static
    {
        if ($this->Result->removeElement($result)) {
            if ($result->getCategorie() === $this) {
                $result->setCategorie(null);
            }
        }

        return $this;
    }
}