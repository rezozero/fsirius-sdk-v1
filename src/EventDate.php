<?php

declare(strict_types=1);

namespace RZ\FSirius;

class EventDate
{
    private array $body;
    private string $id;
    private string $eventId;
    private bool $enabled;
    private string $name;
    private \DateTime $date;
    private string $place;
    private string $availability;
    private ?\DateTime $ticketingOpening = null;

    /**
     * @param array<string, mixed> $body
     *
     * @throws \Exception
     */
    public function __construct(array $body)
    {
        $this->body = $body;
        $this->id = (string) $body['sc'];
        $this->eventId = (string) $body['spec'];
        $this->enabled = boolval($body['aff'] ?? false);
        $this->name = trim(str_replace('|', '\n', (string) $body['titre']));
        $this->date = new \DateTime();
        $this->date->setTimestamp($body['date']);
        $this->place = trim($body['salle']);
        $this->availability = Client::AVAILABLE_SEATS;
        if ($body['ovl'] > -1) {
            $this->ticketingOpening = new \DateTime();
            $this->ticketingOpening->setTimestamp($body['ovl']);
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
