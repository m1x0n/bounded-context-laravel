<?php namespace BoundedContext\Laravel\Illuminate\Stream;

use BoundedContext\Contracts\ValueObject\Identifier;

use BoundedContext\Collection\Collection;
use BoundedContext\Schema\Schema;
use BoundedContext\Sourced\Stream\AbstractStream;
use BoundedContext\ValueObject\Integer as Integer_;

use BoundedContext\Laravel\Event\Snapshot\Factory as EventSnapshotFactory;
use Illuminate\Database\ConnectionInterface;
use BoundedContext\Laravel\Illuminate\BinaryString;

class Stream extends AbstractStream implements \BoundedContext\Contracts\Sourced\Stream\Stream
{
    protected $connection;
    protected $binary_string_factory;
    protected $log_table = 'event_log';

    protected $starting_id;
    protected $last_id;
    protected $order;

    public function __construct(
        ConnectionInterface $connection,
        EventSnapshotFactory $event_snapshot_factory,
        BinaryString\Factory $binary_string_factory,
        Identifier $starting_id,
        Integer_  $limit,
        Integer_ $chunk_size
    )
    {
        $this->connection = $connection;
        $this->binary_string_factory = $binary_string_factory;

        $this->starting_id = $starting_id;  
        
        $this->reset();
        
        parent::__construct(
            $event_snapshot_factory,
            $limit,
            $chunk_size
        );
    }

    public function reset()
    {
        if ($this->starting_id->is_null()) {
            $this->order = -1;
        } else {
            $this->order = null;
        }
        
        parent::reset();
    }

    private function get_next_chunk()
    {
        $query = $this->connection
            ->table($this->log_table)
            ->select("snapshot", "order")
            ->orderBy("order")
            ->limit($this->chunk_size->serialize());
        
        if (is_null($this->order)) {
            $query = $query->whereRaw("
                `order` >
                    (
                        SELECT `order` FROM `$this->log_table`
                        WHERE id = '".$this->binary_string_factory->uuid($this->starting_id)."'
                    )
                "
            );
        } else {
            $query = $query->where("order", ">", $this->order);
        }

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
            $this->order = $event_snapshot_schema->order;
        }
    }
}
