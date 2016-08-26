<?php namespace BoundedContext\Laravel\Bus;

use BoundedContext\Contracts\Collection\Collection;
use BoundedContext\Contracts\Command\Command;
use BoundedContext\Contracts\Sourced\Log\Command as CommandLog;
use BoundedContext\Contracts\Sourced\Aggregate\Repository as AggregateRepository;
use BoundedContext\Laravel\Player\Collection\Builder as PlayerBuilder;
use DB;

class Dispatcher implements \BoundedContext\Contracts\Bus\Dispatcher
{
    private $connection;
    private $command_log;
    private $aggregate_repository;
    private $player_builder;

    public function __construct(
        AggregateRepository $aggregate_repository,
        CommandLog $command_log,
        PlayerBuilder $player_builder
    )
    {
        $this->connection = DB::connection();
        $this->aggregate_repository = $aggregate_repository;
        $this->command_log = $command_log;
        $this->player_builder = $player_builder;
    }

    protected function run(Command $command)
    {
        $aggregate = $this->aggregate_repository->by($command);

        $aggregate->handle($command);
        
        $this->aggregate_repository->save($aggregate);

        $this->command_log->append($command);
        
        $this->player_builder->all()->get()->play();
    }
    
    public function dispatch(Command $command)
    {
        $this->connection->beginTransaction();
        
        $this->run($command);
        
        $this->connection->commit();
    }

    public function dispatch_collection(Collection $commands)
    {
        $this->connection->beginTransaction();

        foreach ($commands as $command) {
            $this->run($command);
        }

        $this->connection->commit();
    }
}
