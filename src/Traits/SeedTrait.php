<?php

namespace Datashaman\PHPCheck\Traits;

trait SeedTrait
{
    protected $seed;

    public function getSeed()
    {
        return $this->seed;
    }

    public function setSeed(int $seed = null)
    {
        if (is_null($seed)) {
            [$usec, $sec] = explode(' ', microtime());
            $seed = (int) ($sec + $usec * 1000000);
        }

        $this->seed = $seed;
        mt_srand($seed);
    }
}
