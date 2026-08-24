<?php

namespace App\Services;

class LocalServiceResponse
{
    public function __construct(private mixed $payload, private int $statusCode = 200) {}

    public function successful(): bool { return $this->statusCode >= 200 && $this->statusCode < 300; }
    public function status(): int { return $this->statusCode; }
    public function json(): mixed { return $this->payload; }
}
