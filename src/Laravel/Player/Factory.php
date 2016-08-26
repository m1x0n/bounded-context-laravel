<?php namespace BoundedContext\Laravel\Player;

use BoundedContext\Map\Map;
use BoundedContext\Contracts\Player\Snapshot\Snapshot;
use BoundedContext\Contracts\Projection\Projection;
use BoundedContext\Contracts\Sourced\Log\Event as EventLog;
use App;

class Factory implements \BoundedContext\Contracts\Player\Factory
{
    private $players_map;
    private $event_log;

    public function __construct(Map $players_map, EventLog $event_log)
    {
        $this->players_map = $players_map;
        $this->event_log = $event_log;
    }

    private function get_implementation_by_interface($interface_class)
    {
        return str_replace('Projector', 'Projection', $interface_class);
    }

    public function snapshot(Snapshot $snapshot)
    {
        $player_class = $this->players_map->get_class($snapshot->id());

        $reflection = new \ReflectionClass($player_class);
        $parameters = $reflection->getConstructor()->getParameters();

        $args = [];
        
        foreach ($parameters as $parameter) {
            $parameter_name = $parameter->getName();
            $parameter_contract = $parameter->getClass()->name;

            if ($parameter_contract === Projection::class) {
                $args[$parameter_name] = App::make(
                    $this->get_implementation_by_interface($player_class)
                );
            } elseif ($parameter_contract === Snapshot::class) {
                $args[$parameter_name] = $snapshot;
            } elseif ($parameter_contract === EventLog::class) {
                $args[$parameter_name] = $this->event_log;
            } else {
                $args[$parameter_name] = App::make($parameter_contract);
            }
        }

        return $reflection->newInstanceArgs($args);
    }
}
