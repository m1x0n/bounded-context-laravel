<?php namespace BoundedContext\Laravel\Sourced\Aggregate;

use BoundedContext\Contracts\Business\Invariant\Factory as InvariantFactory;
use BoundedContext\Contracts\Sourced\Aggregate\Type\Factory as TypeFactory;
use BoundedContext\Contracts\Sourced\Aggregate\State\State;
use BoundedContext\Contracts\Generator;
use BoundedContext\Laravel\Sourced\Aggregate\Projection;

class Factory implements \BoundedContext\Contracts\Sourced\Aggregate\Factory
{
    protected $type_factory;
    protected $invariant_factory;
    protected $id_generator;
    protected $projection_factory;
    protected $locker;

    public function __construct(
        TypeFactory $type_factory,
        InvariantFactory $invariant_factory,
        Generator\Identifier $id_generator,
        Projection\Factory $projection_factory,
        Locker $locker
    )
    {
        $this->type_factory = $type_factory;
        $this->invariant_factory = $invariant_factory;
        $this->id_generator = $id_generator;
        $this->projection_factory = $projection_factory;
        $this->locker = $locker;
    }

    public function state(State $state)
    {
        $this->locker->lock($state->aggregate_id(), $state->aggregate_type());

        $state_class = get_class($state);

        $state_prefix = substr(
            $state_class,
            0,
            strpos($state_class, "Projector")
        );

        $aggregate_class = $state_prefix . "Aggregate";

        return new $aggregate_class(
            $this->type_factory,
            $this->invariant_factory,
            $this->id_generator,
            $this->projection_factory,
            $state
        );
    }
}
