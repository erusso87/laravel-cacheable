<?php

namespace Cacheable;

use \Cache;
use Closure;
use InvalidArgumentException;

/**
 * User: ezequiel.russo
 * Date: 6/10/16
 * Time: 14:57
 */
trait CacheableTrait
{
    /**
     * Hashing algorithm for the key.
     * @var string
     */
    protected $hash_algo = 'sha256';

    /**
     * Return a cached object or store the result of the closure with an autogenerated key.
     *
     * @param \Closure $function
     *
     * @return mixed
     */
    protected function remember(Closure $function)
    {
        $previous_call = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 2)[1];

        $data = [
            'class'    => $previous_call['class'],
            'function' => $previous_call['function'],
            'args'     => $previous_call['args'],
            'commit'   => config('cache.commit'),
        ];
        $key  = $this->generateCacheKey($data);

        return Cache::remember($key, $this->getTTL(), $function);
    }

    /**
     * Allow a per-class config of TTL or use the application default value.
     *
     * @return float|int
     */
    protected function getTTL()
    {
        return Cache::getDefaultCacheTime();
    }

    /**
     * Stop the execution if there is a Closure in the argument list.
     *
     * @param $arguments
     */
    protected function checkArguments($arguments)
    {
        foreach ($arguments as $arg) {
            if ($arg instanceof Closure) throw new InvalidArgumentException('Closure can\'t be serialized');
        }
    }

    /**
     * Generate the cache key based on: Class, Function, Arguments, Git commit.
     * Using serialize allow the caching of custom objects.
     *
     * @param $previous_call
     *
     * @return mixed
     */
    protected function generateCacheKey($data)
    {
        $this->checkArguments($data['args']);

        $key = hash($this->hash_algo, serialize([
                                                    $data['class'],
                                                    $data['function'],
                                                    $data['args'],
                                                    $data['commit'],
                                                ]));

        return $key;
    }
}