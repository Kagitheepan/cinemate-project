<?php

namespace App\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;

#[MongoDB\Document(collection: "cookie_consents")]
class CookieConsent
{
    #[MongoDB\Id]
    private ?string $id = null;

    #[MongoDB\Field(type: "string")]
    private ?string $consentId = null;

    #[MongoDB\Field(type: "string")]
    private ?string $choice = null;

    #[MongoDB\Field(type: "string", nullable: true)]
    private ?string $username = null;

    #[MongoDB\Field(type: "date")]
    private ?\DateTimeInterface $decidedAt = null;

    #[MongoDB\Field(type: "string")]
    private string $policyVersion = '2026-05-18';

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getConsentId(): ?string
    {
        return $this->consentId;
    }

    public function setConsentId(string $consentId): self
    {
        $this->consentId = $consentId;

        return $this;
    }

    public function getChoice(): ?string
    {
        return $this->choice;
    }

    public function setChoice(string $choice): self
    {
        $this->choice = $choice;

        return $this;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(?string $username): self
    {
        $this->username = $username;

        return $this;
    }

    public function getDecidedAt(): ?\DateTimeInterface
    {
        return $this->decidedAt;
    }

    public function setDecidedAt(\DateTimeInterface $decidedAt): self
    {
        $this->decidedAt = $decidedAt;

        return $this;
    }

    public function getPolicyVersion(): string
    {
        return $this->policyVersion;
    }

    public function setPolicyVersion(string $policyVersion): self
    {
        $this->policyVersion = $policyVersion;

        return $this;
    }
}
