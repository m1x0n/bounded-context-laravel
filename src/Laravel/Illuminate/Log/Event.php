<?php namespace BoundedContext\Laravel\Illuminate\Log;

use BoundedContext\Contracts\Event\Snapshot\Factory;
use Illuminate\Database\DatabaseManager;
use BoundedContext\Sourced\Stream\Builder;
use BoundedContext\Laravel\Illuminate\BinaryString;
use BoundedContext\Collection\Collection;

class Event implements \BoundedContext\Contracts\Sourced\Log\Event
{
    private $connection;
    private $snapshot_factory;
    private $binary_string_factory;
    private $table = "event_log";
    private $stream_builder;
    
    public function __construct(
        Factory $snapshot_factory, 
        DatabaseManager $db_manager, 
        Builder $stream_builder,
        BinaryString\Factory $binary_string_factory
    )
    {
        $this->snapshot_factory = $snapshot_factory;
        $this->connection = $db_manager->connection();
        $this->binary_string_factory = $binary_string_factory;
        $this->stream_builder = $stream_builder;
    }
    
    public function append(\BoundedContext\Contracts\Event\Event $event)
    {
        $event_collection = new Collection([$event]);
        $this->append_collection($event_collection);
    }
    
    public function append_collection(\BoundedContext\Contracts\Collection\Collection $events)
    {
        $inserts = [];
        foreach ($events as $event) {
            $snapshot = $this->snapshot_factory->loggable($event);
            $inserts[] = [
                'id' => $this->binary_string_factory->uuid($snapshot->id()),
                'aggregate_id' => $this->binary_string_factory->uuid($event->id()),
                'aggregate_type_id' => $this->binary_string_factory->uuid($event->aggregate_type_id()),
                'snapshot' => json_encode($snapshot->serialize())
            ];
        }
        $this->connection->table($this->table)->insert($inserts);
    }

    public function builder()
    {
        return $this->stream_builder;
    }

    public function reset()
    {
        $this->connection
            ->table($this->table)
            ->delete();
    }
}
