<?php namespace BoundedContext\Player\Collection;

use BoundedContext\Contracts\Collection\Collection;
use BoundedContext\Contracts\Player\Progress;
use BoundedContext\Contracts\Player\Repository;

class Player implements \BoundedContext\Contracts\Player\Player
{
    protected $player_repository;
    protected $player_classes;
    protected $progress;

    public function __construct(
        Repository $player_repository,
        Collection $player_classes,
        Progress $progress
    ) {
        $this->player_repository = $player_repository;
        $this->player_classes = $player_classes;
        $this->progress = $progress;
    }

    public function reset()
    {
        foreach ($this->player_classes as $player_class) {
            $player = $this->player_repository->get($player_class);
            $player->reset();
            $this->player_repository->save($player);
        }
    }

    public function play($limit = 1000)
    {
        $this->progress->start($this->count());

        foreach ($this->player_classes as $player_class) {
            $player = $this->player_repository->get($player_class);
            $player->play($limit);
            $this->player_repository->save($player);
            $this->progress->advance();
        }

        $this->progress->finish();
    }

    public function snapshot()
    {
        throw new \Exception("Collection Player Snapshots are not supported.");
    }

    public static function version()
    {
        throw new \Exception("Collection Player versions are not supported.");
    }

    public function count()
    {
        return $this->player_classes->count()->value();
    }
}
