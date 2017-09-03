<?php

namespace BoundedContext\Contracts\Player;

interface Progress
{
    public function start($max = null);

    public function advance($step = 1);

    public function finish();
}
