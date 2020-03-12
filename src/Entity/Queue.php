<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\QueueRepository")
 */
class Queue
{
    const ADDED_BY_DOWNLOAD_SINGLE_SONG = 1; // kada korisnik doda u queue download jedne pjesme onda tom queue postavljamo runIndex = ADDED_BY_DOWNLOAD_SINGLE_SONG

    const ADDED_BY_DOWNLOAD_PLAYLIST = 2; // kada korisnik doda u queue download playliste onda tom queue postavljamo runIndex = ADDED_BY_DOWNLOAD_PLAYLIST

    const ADDED_BY_CHANNEL_SUBSCRIBE = 3; // kada korisnik subscribea neki kanal i inicijalno dodavanje pjesama u queue označavamo sa runIndex = ADDED_BY_CHANNEL_SUBSCRIBE

    const ADDED_BY_CHANNEL_SUBSCRIBE_CRONJOB = 4; // cronjob se pokrene svako malo i za svaki subscribeani channel skidamo nove pjesme koje pronađemo i njih označavamo sa = ADDED_BY_CHANNEL_SUBSCRIBE_CRONJOB
    
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="text", length=10000)
     */
    private $youtubeInfo;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $url;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $location;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\User", inversedBy="queues")
     * @ORM\JoinColumn(nullable=false)
     */
    private $user;

    /**
     * @ORM\Column(type="boolean")
     */
    private $finished;

    /**
     * @ORM\Column(type="datetime")
     */
    private $createdAt;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $finishedAt;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $videoId;

    /**
     * @ORM\Column(type="integer")
     */
    private $status;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $thumb;

    /**
     * @ORM\Column(type="integer")
     */
    private $runIndex;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getYoutubeInfo(): ?string
    {
        return $this->youtubeInfo;
    }

    public function setYoutubeInfo(string $youtubeInfo): self
    {
        $this->youtubeInfo = $youtubeInfo;

        return $this;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(string $url): self
    {
        $this->url = $url;

        return $this;
    }

    public function getLocation(): ?string
    {
        return $this->location;
    }

    public function setLocation(string $location): self
    {
        $this->location = $location;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getFinished(): ?bool
    {
        return $this->finished;
    }

    public function setFinished(bool $finished): self
    {
        $this->finished = $finished;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getFinishedAt(): ?\DateTimeInterface
    {
        return $this->finishedAt;
    }

    public function setFinishedAt(\DateTimeInterface $finishedAt): self
    {
        $this->finishedAt = $finishedAt;

        return $this;
    }

    public function getVideoId(): ?string
    {
        return $this->videoId;
    }

    public function setVideoId(string $videoId): self
    {
        $this->videoId = $videoId;

        return $this;
    }

    public function getStatus(): ?int
    {
        return $this->status;
    }

    public function setStatus(int $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getThumb(): ?string
    {
        return $this->thumb;
    }

    public function setThumb(string $thumb): self
    {
        $this->thumb = $thumb;

        return $this;
    }

    public function getRunIndex(): ?int
    {
        return $this->runIndex;
    }

    public function setRunIndex(int $runIndex): self
    {
        $this->runIndex = $runIndex;

        return $this;
    }
}
