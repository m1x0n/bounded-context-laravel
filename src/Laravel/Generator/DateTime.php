<?php namespace BoundedContext\Laravel\Generator;

class DateTime implements \BoundedContext\Contracts\Generator\DateTime
{
    public function now()
    {
        $datetime = date("Y-m-d H:i:s");
        return new \EventSourced\ValueObject\ValueObject\DateTime($datetime);
    }

    public function string($datetime)
    {
        return new \EventSourced\ValueObject\ValueObject\DateTime($datetime);
    }

    public function generate()
    {
        return $this->now();
    }
}
