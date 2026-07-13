<?php

namespace Reactions\Service;

class TrendingCalculator
{
    private const EPOCH = 1134028003;

    public function score(int $score, int $createdAt): float
    {
        $sign = $score <=> 0;
        $order = log10(max(abs($score), 1));
        $age = ($createdAt - self::EPOCH) / 45000;

        return round($sign * $order + $age, 6);
    }
}
