<?php namespace BoundedContext\Laravel\Sourced\Aggregate;

use BoundedContext\Contracts\Business\Invariant\Factory as InvariantFactory;
use BoundedContext\Contracts\Sourced\Aggregate\TypeId\Factory as TypeIdFactory;
use BoundedContext\Contracts\Sourced\Aggregate\State\State;
use BoundedContext\Contracts\Generator;

class Factory implements \BoundedContext\Contracts\Sourced\Aggregate\Factory
{
    protected $typeid_factory;
    protected $invariant_factory;
    protected $id_generator;

    public function __construct(
        TypeIdFactory $typeid_factory, 
        InvariantFactory $invariant_factory,
        Generator\Identifier $id_generator)
    {
        $this->typeid_factory = $typeid_factory;
        $this->invariant_factory = $invariant_factory;
        $this->id_generator = $id_generator;
    }

    public function state(State $state)
    {
        $state_class = get_class($state);

        $state_prefix = substr(
            $state_class,
            0,
            strpos($state_class, "State")
        );

        $aggregate_class = $state_prefix . "Aggregate";

        return new $aggregate_class(
            $this->typeid_factory,
            $this->invariant_factory,
            $this->id_generator,
            $state
        );
    }
}
