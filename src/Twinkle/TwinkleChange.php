<?php

namespace Sifrious\Elwin\Twinkle;

final readonly class TwinkleChange
{
    public function __construct(public Twinkle $twinkle, public TwinkleTransition $transition) {}
}
