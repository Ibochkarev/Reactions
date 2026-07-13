<?php

namespace Reactions\Dto;

use Reactions\Enum\ReactionAction;

readonly class ReactionResult
{
    /**
     * @param array<string, int> $counts
     * @param list<string> $userReactions
     */
    public function __construct(
        public ReactionAction $action,
        public array $counts,
        public int $total,
        public array $userReactions,
        public string $typeName = '',
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'action' => $this->action->value,
            'counts' => $this->counts,
            'total' => $this->total,
            'user_reaction' => $this->userReactions,
            'type' => $this->typeName,
        ];
    }
}
