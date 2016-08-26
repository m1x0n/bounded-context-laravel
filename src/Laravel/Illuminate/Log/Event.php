<?php namespace BoundedContext\Laravel\Illuminate\Log;

use BoundedContext\Contracts\Event\Snapshot\Factory;
use Illuminate\Database\DatabaseManager;
use BoundedContext\Sourced\Stream\Builder;
use BoundedContext\Laravel\Illuminate\BinaryString;
use BoundedContext\Contracts\Sourced\Aggregate\Aggregate;
use EventSourced\ValueObject\ValueObject\Integer;
use EventSourced\ValueObject\ValueObject\Uuid;
use BoundedContext\Contracts\Event\Snapshot\Snapshot;
use BoundedContext\Event\AggregateType;

class Event implements \BoundedContext\Contracts\Sourced\Log\Event
{
    private $connection;
    private $snapshot_factory;
    private $binary_string_factory;
    private $stream_builder;
    private $log_table;

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
        $this->log_table = config('logs.event_log.table_name');
    }

    public function append_aggregate_events(Aggregate $aggregate)
    {
        if (count($aggregate->changes()->count()) == 0) {
            return;
        }

        $state = $aggregate->state();

        $this->lock_rows($state->aggregate_id(), $state->aggregate_type());

        $loaded_log_version = $state->version()->subtract($aggregate->changes()->count());
        $current_log_version = $this->log_version($state->aggregate_id(), $state->aggregate_type());

        if (!$loaded_log_version->equals($current_log_version)) {
            $this->unlock_rows($state->aggregate_id(), $state->aggregate_type());
            throw new \Exception("Aggregate has already been updated during this transation by another thread.");
        }

        $this->store_events($aggregate);

        $this->unlock_rows($state->aggregate_id(), $state->aggregate_type());
    }
        
    private static $appended_events = [];
   
    private function store_events(Aggregate $aggregate)
    {
        $events = $aggregate->changes();
        $state = $aggregate->state();

        $binary_aggregate_id = $this->binary_string_factory->uuid($state->aggregate_id());

        $inserts = [];
        foreach ($events as $event) {
            $snapshot = $this->snapshot_factory->event($event);
            $encoded_snapshot = $this->encode_snapshot($snapshot);
            $inserts[] = [
                'id' => $this->binary_string_factory->uuid($event->id()),
                'aggregate_id' => $binary_aggregate_id,
                'aggregate_type' => $state->aggregate_type()->value(),
                'snapshot' => $encoded_snapshot
            ];
            static::$appended_events[] = $encoded_snapshot;
        }
        $this->connection->table($this->log_table)->insert($inserts);
    }

    private function encode_snapshot(Snapshot $snapshot)
    {
        return json_encode([
            'id' => $snapshot->id()->value(),
            'type' => $snapshot->type()->value(),
            'aggregate_id' => $snapshot->aggregate_id()->value(),
            'aggregate_type' => $snapshot->aggregate_type()->value(),
            'command_id' => $snapshot->command_id()->value(),
            'version' => $snapshot->version()->value(),
            'occurred_at' => $snapshot->occurred_at()->value(),
            'event' => $snapshot->schema()->data_tree()
        ]);
    }

    private function log_version($aggregate_id, $aggregate_type)
    {
        $binary_aggregate_id = $this->binary_string_factory->uuid($aggregate_id);

        $query = $this->connection
            ->table($this->log_table)
            ->selectRaw("COUNT(*) as version")
            ->where("aggregate_id", $binary_aggregate_id)
            ->where("aggregate_type", $aggregate_type->value());

        $row = $query->first();

        return new Integer($row->version);
    }

    private function lock_rows(Uuid $id, AggregateType $type)
    {
        $this->connection->raw(
            "SELECT GET_LOCK(:lockid, 1)",
            ['lockid'=> $this->lock_id($id, $type)]
        );
    }

    private function unlock_rows(Uuid $id, AggregateType $type)
    {
        $this->connection->raw(
            "SELECT RELEASE_LOCK(:lockid)",
            ['lockid'=> $this->lock_id($id, $type)]
        );
    }

    private function lock_id(Uuid $id, AggregateType $type)
    {
        return $id->value().'-'.$type->value();
    }

    public function builder()
    {
        return $this->stream_builder;
    }

    public function reset()
    {
        $this->connection
            ->table($this->log_table)
            ->delete();
    }

    public function get_appended_events()
    {
        $events = static::$appended_events;
        static::$appended_events = [];
        return $events;
    }
}
