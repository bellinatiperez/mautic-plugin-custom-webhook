<?php

namespace MauticPlugin\MauticCustomWebhookBundle\Event;

use Mautic\LeadBundle\Entity\Lead;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Evento disparado antes de fazer uma requisição de webhook personalizado
 */
class CustomWebhookRequestEvent extends Event
{
    public function __construct(
        private Lead $contact,
        private string $url,
        private array $headers,
        private array $payload,
        private string $method = 'POST'
    ) {
    }

    public function getContact(): Lead
    {
        return $this->contact;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function setUrl(string $url): void
    {
        $this->url = $url;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function setHeaders(array $headers): void
    {
        $this->headers = $headers;
    }

    public function getPayload(): array
    {
        return $this->payload;
    }

    public function setPayload(array $payload): void
    {
        $this->payload = $payload;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function setMethod(string $method): void
    {
        $this->method = $method;
    }
}