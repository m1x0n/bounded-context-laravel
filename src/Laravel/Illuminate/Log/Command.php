<?php namespace BoundedContext\Laravel\Illuminate\Log;

use BoundedContext\Contracts\Event\Snapshot\Factory;
use Illuminate\Database\DatabaseManager;
use BoundedContext\Laravel\Illuminate\BinaryString;

class Command implements \BoundedContext\Contracts\Sourced\Log\Command
{
    private $connection;
    private $binary_string_factory;
    private $snapshot_factory;
    private $table;
    
    public function __construct(
        Factory $snapshot_factory, 
        DatabaseManager $db_manager,
        BinaryString\Factory $binary_string_factory
    )
    {
        $this->snapshot_factory = $snapshot_factory;
        $this->connection = $db_manager->connection();
        $this->binary_string_factory = $binary_string_factory;
        $this->table = "command_log";
    }
    
    public function append(\BoundedContext\Contracts\Command\Command $command)
    {
        $snapshot = $this->snapshot_factory->command($command);
        $this->connection->table($this->table)->insert([
            'id' => $this->binary_string_factory->uuid($snapshot->id()),
            'snapshot' => json_encode($snapshot->schema()->data_tree())
        ]);
    }

    public function reset()
    {
        $this->connection
            ->table($this->table)
            ->delete();
    }
}
