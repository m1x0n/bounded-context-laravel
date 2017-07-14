<?php namespace BoundedContext\Laravel\Illuminate\Stream;

use BoundedContext\Sourced\Stream\SnapshotStream;
use BoundedContext\Sourced\Stream\UpgradedStream;
use EventSourced\ValueObject\Contracts\ValueObject\Identifier;
use EventSourced\ValueObject\ValueObject\Integer as Integer_;
use DB;
use App;
use BoundedContext\Laravel\Illuminate\Stream\Stream as LogStream;

// Actor: TODO: Use injectable dependencies, instead of magic "App::make" bullshit
class Factory implements \BoundedContext\Contracts\Sourced\Stream\Factory
{
    public function create(
        Identifier $starting_id,
        Integer_ $limit,
        Integer_ $chunk_size
    )
    {
        $log_stream = new LogStream(
            DB::connection(),
            App::make(\BoundedContext\Laravel\Illuminate\BinaryString\Factory::class),
            $starting_id,
            $limit,
            $chunk_size
        );

        $upgrader = App::make(\BoundedContext\Sourced\Stream\Upgrader::class);

        $upgraded_stream = new UpgradedStream($log_stream, $upgrader);

        $snapshot_transformer = App::make(\BoundedContext\Contracts\Event\Snapshot\Transformer::class);

        return new SnapshotStream($snapshot_transformer, $upgraded_stream);
    }
}
