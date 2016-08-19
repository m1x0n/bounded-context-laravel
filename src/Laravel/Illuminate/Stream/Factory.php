<?php namespace BoundedContext\Laravel\Illuminate\Stream;

use EventSourced\ValueObject\Contracts\ValueObject\Identifier;
use EventSourced\ValueObject\ValueObject\Integer as Integer_;
use DB;
use App;

class Factory implements \BoundedContext\Contracts\Sourced\Stream\Factory
{
    public function create(
        Identifier $starting_id,
        Integer_ $limit,
        Integer_ $chunk_size
    )
    {
        return App::make(
            \BoundedContext\Laravel\Illuminate\Stream\Stream::class,
            [
                DB::connection(),
                App::make(\BoundedContext\Laravel\Event\Snapshot\Factory::class),
                App::make(\BoundedContext\Laravel\Illuminate\BinaryString\Factory::class),
                $starting_id,
                $limit,
                $chunk_size
            ]
        );
    }
}
