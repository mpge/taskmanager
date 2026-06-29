<?php

namespace App\Support;

use App\Enums\InsightType;

final readonly class Insight
{
    public function __construct(
        public InsightType $type,
        public string $title,
        public string $message,
    ) {}

    /**
     * @return array{type: string, tone: string, title: string, message: string}
     */
    public function toArray(): array
    {
        return [
            'type' => $this->type->value,
            'tone' => $this->type->tone(),
            'title' => $this->title,
            'message' => $this->message,
        ];
    }
}
