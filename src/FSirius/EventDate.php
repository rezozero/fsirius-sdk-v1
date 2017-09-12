<?php

namespace RZ\FSirius;

/**
 * Class EventDate.
 *
 * @package RZ\FSirius
 */
class EventDate
{
    /**
     * @var array
     */
    private $body;
    /**
     * @var string
     */
    private $id;
    /**
     * @var string
     */
    private $eventId;
    /**
     * @var boolean
     */
    private $enabled;
    /**
     * @var string
     */
    private $name;
    /**
     * @var \DateTime
     */
    private $date;
    /**
     * @var string
     */
    private $place;

    /**
     * @var string
     */
    private $availability;

    /**
     * EventDate constructor.
     * @param array $body
     */
    public function __construct(array $body)
    {
        $this->body = $body;
        $this->id = $body['sc'];
        $this->eventId = $body['spec'];
        $this->enabled = $body['aff'];
        $this->name = trim($body['titre']);
        $this->date = new \DateTime();
        $this->date->setTimestamp($body['date']);
        $this->place = trim($body['salle']);
        $this->availability = Client::AVAILABLE_SEATS;
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getEventId()
    {
        return $this->eventId;
    }

    /**
     * @return bool
     */
    public function isEnabled()
    {
        return $this->enabled;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return \DateTime
     */
    public function getDate(): \DateTime
    {
        return $this->date;
    }

    /**
     * @return string
     */
    public function getPlace()
    {
        return $this->place;
    }

    /**
     * @return string
     */
    public function getAvailability(): string
    {
        return $this->availability;
    }

    /**
     * @param string $availability
     * @return EventDate
     */
    public function setAvailability(string $availability): EventDate
    {
        $this->availability = $availability;
        return $this;
    }
}
