<?php namespace BoundedContext\Laravel\Illuminate\Stream;

use BoundedContext\Contracts\ValueObject\Identifier;

use BoundedContext\Collection\Collection;
use BoundedContext\Schema\Schema;
use BoundedContext\Sourced\Stream\AbstractStream;
use BoundedContext\ValueObject\Integer as Integer_;

use BoundedContext\Laravel\Event\Snapshot\Factory as EventSnapshotFactory;
use Illuminate\Database\ConnectionInterface;

class Stream extends AbstractStream implements \BoundedContext\Contracts\Sourced\Stream\Stream
{
    protected $connection;
    protected $log_table = 'event_log';

    protected $starting_id;
    protected $last_id;
    protected $order;

    public function __construct(
        ConnectionInterface $connection,
        EventSnapshotFactory $event_snapshot_factory,
        Identifier $starting_id,
        Integer_  $limit,
        Integer_ $chunk_size
    )
    {
        $this->connection = $connection;

        $this->starting_id = $starting_id;        
        
        $this->configure_order();

        parent::__construct(
            $event_snapshot_factory,
            $limit,
            $chunk_size
        );
    }
    
    private function configure_order()
    {
        if ($this->starting_id->is_null()) {
            $this->order = -1;
        } else {
            $this->order = $this->get_order_of_id($this->starting_id);
        }
    }
    
    private function get_order_of_id(Identifier $id) 
    {
        $row = $this->connection
            ->table($this->log_table)
            ->select("$this->log_table.order")
            ->where("id", "=", $this->uuid_to_binary($id))
            ->first();
        if (!$row) {
            throw new \Exception("Cannot find log entry for ID '".$id->serialize()."'");
        }
        return $row->order;
    }
    
    private function uuid_to_binary(Uuid $uuid)
    {
        $hex = str_replace("-", "", $uuid->value());
        return hex2bin($hex);
    }

    public function reset()
    {
        $this->configure_order();
        
        parent::reset();
    }

    private function get_next_chunk()
    {
        $query = $this->connection
            ->table($this->log_table)
            ->select("$this->log_table.snapshot")
            ->where("order", ">", $this->order)
            ->orderBy("$this->stream_table.id")
            ->limit($this->chunk_size->serialize());

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
