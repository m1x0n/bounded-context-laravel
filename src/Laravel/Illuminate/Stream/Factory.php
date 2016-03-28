<?php namespace BoundedContext\Laravel\Illuminate\Stream;

use EventSourced\ValueObject\Contracts\ValueObject\Identifier;
use EventSourced\ValueObject\ValueObject\Integer as Integer_;
use Illuminate\Contracts\Foundation\Application;

class Factory implements \BoundedContext\Contracts\Sourced\Stream\Factory
{
    private $application;

    public function __construct(Application $application)
    {
        $this->application = $application;
    }

    public function create(
        Identifier $starting_id,
        Integer_ $limit,
        Integer_ $chunk_size
    )
    {
        return $this->application->make(
            'BoundedContext\Laravel\Illuminate\Stream\Stream',
            [
                $this->application->make('db')->connection(),
                $this->application->make('BoundedContext\Laravel\Event\Snapshot\Factory'),
                $this->application->make(\BoundedContext\Laravel\Illuminate\BinaryString\Factory::class),
                $starting_id,
                $limit,
                $chunk_size
            ]
        );
    }
}
