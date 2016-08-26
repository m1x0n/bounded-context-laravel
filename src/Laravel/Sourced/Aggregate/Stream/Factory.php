<?php namespace BoundedContext\Laravel\Sourced\Aggregate\Stream;

use EventSourced\ValueObject\Contracts\ValueObject\Identifier;
use EventSourced\ValueObject\ValueObject\Integer as Integer_;
use App;
use DB;

class Factory implements \BoundedContext\Contracts\Sourced\Aggregate\Stream\Factory
{
    public function create(
        Identifier $aggregate_id,
        Identifier $aggregate_type,
        Integer_ $starting_offset,
        Integer_ $limit,
        Integer_ $chunk_size
    )
    {        
        return App::make(
            \BoundedContext\Laravel\Sourced\Aggregate\Stream\Stream::class,
            [
                DB::connection(),
                App::make(\BoundedContext\Laravel\Event\Snapshot\Factory::class),
                App::make(\BoundedContext\Laravel\Illuminate\BinaryString\Factory::class),
                $aggregate_id,
                $aggregate_type,
                $starting_offset,
                $limit,
                $chunk_size
            ]
        );
    }
}
