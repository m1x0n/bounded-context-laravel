<?php namespace BoundedContext\Laravel\Sourced\Aggregate\Stream;

use BoundedContext\Collection\Collection;
use BoundedContext\Contracts\ValueObject\Identifier;
use BoundedContext\Laravel\Event\Snapshot\Factory as EventSnapshotFactory;
use BoundedContext\Schema\Schema;
use BoundedContext\Sourced\Stream\AbstractStream;
use BoundedContext\ValueObject\Integer as Integer_;
use Illuminate\Database\ConnectionInterface;
use BoundedContext\Laravel\ValueObject\Uuid;

class Stream extends AbstractStream implements \BoundedContext\Contracts\Sourced\Stream\Stream
{
    protected $connection;
    protected $log_table = 'event_log';

    protected $aggregate_id;
    protected $aggregate_type_id;

    protected $starting_offset;
    protected $current_offset;

    public function __construct(
        ConnectionInterface $connection,
        EventSnapshotFactory $event_snapshot_factory,
        Identifier $aggregate_id,
        Identifier $aggregate_type_id,
        Integer_ $starting_offset,
        Integer_  $limit,
        Integer_ $chunk_size
    )
    {
        $this->connection = $connection;

        $this->aggregate_id = $aggregate_id;
        $this->aggregate_type_id = $aggregate_type_id;        
        
        $this->starting_offset = $starting_offset;
        $this->current_offset = $starting_offset;

        parent::__construct(
            $event_snapshot_factory,
            $limit,
            $chunk_size
        );
    }

    public function reset()
    {
        $this->current_offset = $this->starting_offset;

        parent::reset();
    }

    private function get_next_chunk()
    {
        $query = $this->connection
            ->table($this->log_table)
            ->select("snapshot")
            ->where(
                "aggregate_id",
                $this->uuid_to_binary($this->aggregate_id)
            )
            ->where(
                "aggregate_type_id",
                $this->uuid_to_binary($this->aggregate_type_id)
            )
            ->orderBy("order")
            ->limit($this->chunk_size->serialize())
            ->offset($this->current_offset->serialize());
                
        $rows = $query->get();

        return $rows;
    }

    protected function fetch()
    {
        $this->event_snapshots = new Collection();

        $event_snapshot_schemas = $this->get_next_chunk();

        foreach ($event_snapshot_schemas as $event_snapshot_schema) {
            $event_snapshot = $this->event_snapshot_factory->schema(
                new Schema(
                    json_decode(
                        $event_snapshot_schema->snapshot,
                        true
                    )
                )
            );
            $this->event_snapshots->append($event_snapshot);
        }

        $this->current_offset = $this->current_offset->add(
            $this->event_snapshots->count()
        );
    }
    
    private function uuid_to_binary(Uuid $uuid)
    {
        $hex = str_replace("-", "", $uuid->value());
        return hex2bin($hex);
    }
}
