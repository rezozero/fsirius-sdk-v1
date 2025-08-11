<?php

declare(strict_types=1);

namespace RZ\FSirius;

class EventDate
{
    private readonly string $id;
    private readonly string $eventId;
    private readonly bool $enabled;
    private readonly string $name;
    private readonly \DateTime $date;
    private readonly string $place;
    private string $availability;
    private ?\DateTime $ticketingOpening = null;

    /**
     * @param array<string, mixed> $body
     *
     * @throws \Exception
     */
    public function __construct(private readonly array $body)
    {
        $this->id = (string) $this->body['sc'];
        $this->eventId = (string) $this->body['spec'];
        $this->enabled = boolval($this->body['aff'] ?? false);
        $this->name = trim(str_replace('|', '\n', (string) $this->body['titre']));
        $this->date = new \DateTime();
        $this->date->setTimestamp($this->body['date']);
        $this->place = trim((string) $this->body['salle']);
        $this->availability = Client::AVAILABLE_SEATS;
        if ($this->body['ovl'] > -1) {
            $this->ticketingOpening = new \DateTime();
            $this->ticketingOpening->setTimestamp($this->body['ovl']);
        } else {
            $this->ticketingOpening = null;
        }
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getEventId(): string
    {
        return $this->eventId;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDate(): \DateTime
    {
        return $this->date;
    }

    public function getPlace(): ?string
    {
        return $this->place;
    }

    public function getAvailability(): string
    {
        return $this->availability;
    }

    public function setAvailability(string $availability): EventDate
    {
        $this->availability = $availability;

        return $this;
    }

    public function getTicketingOpening(): ?\DateTime
    {
        return $this->ticketingOpening;
    }

    public function getBody(): array
    {
        return $this->body;
    }
}
