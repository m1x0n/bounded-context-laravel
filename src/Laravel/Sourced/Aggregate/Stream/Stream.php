<?php namespace BoundedContext\Laravel\Sourced\Aggregate\Stream;

use BoundedContext\Contracts\ValueObject\Identifier;
use BoundedContext\Laravel\Event\Snapshot\Factory as EventSnapshotFactory;
use BoundedContext\Sourced\Stream\AbstractStream;
use BoundedContext\ValueObject\Integer as Integer_;
use Illuminate\Database\ConnectionInterface;
use BoundedContext\Laravel\Illuminate\BinaryString;

class Stream extends AbstractStream implements \BoundedContext\Contracts\Sourced\Stream\Stream
{
    private $connection;
    private $binary_string_factory;

    private $aggregate_id;
    private $aggregate_type_id;

    private $starting_offset;
    private $current_offset;

    public function __construct(
        ConnectionInterface $connection,
        EventSnapshotFactory $event_snapshot_factory,
        BinaryString\Factory $binary_string_factory,
        Identifier $aggregate_id,
        Identifier $aggregate_type_id,
        Integer_ $starting_offset,
        Integer_  $limit,
        Integer_ $chunk_size
    )
    {
        $this->connection = $connection;
        $this->binary_string_factory = $binary_string_factory;

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

    protected function get_next_chunk()
    {
        $query = $this->connection
            ->table($this->log_table)
            ->select("snapshot")
            ->where(
                "aggregate_id",
                $this->binary_string_factory->uuid($this->aggregate_id)
            )
            ->where(
                "aggregate_type_id",
                $this->binary_string_factory->uuid($this->aggregate_type_id)
            )
            ->orderBy("order")
            ->limit($this->chunk_size->serialize())
            ->offset($this->current_offset->serialize());
                
        $rows = $query->get();

        return $rows;
    }

    protected function set_offset(array $event_snapshot_rows)
    {
        $this->current_offset = $this->current_offset->add(
            new Integer_(count($event_snapshot_rows))
        );
    }
}
