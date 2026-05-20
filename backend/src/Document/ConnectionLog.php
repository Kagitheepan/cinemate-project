<?php

namespace App\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;

#[MongoDB\Document(collection: "connection_logs")]
class ConnectionLog
{
    #[MongoDB\Id]
    private ?string $id = null;

    #[MongoDB\Field(type: "string")]
    private ?string $username = null;

    #[MongoDB\Field(type: "date")]
    private ?\DateTimeInterface $connectedAt = null;

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(string $username): self
    {
        $this->username = $username;

        return $this;
    }

    public function getConnectedAt(): ?\DateTimeInterface
    {
        return $this->connectedAt;
    }

    public function setConnectedAt(\DateTimeInterface $connectedAt): self
    {
        $this->connectedAt = $connectedAt;

        return $this;
    }
}
