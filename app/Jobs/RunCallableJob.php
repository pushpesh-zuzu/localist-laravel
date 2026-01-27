<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Laravel\SerializableClosure\SerializableClosure;

class RunCallableJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

     public $timeout = 1200;

    // 🔁 Retry max 3 times if failed
     public $tries = 5;


    protected $callable;

    /**
     * Create a new job instance.
     *
     * @param callable $callable
     */
    public function __construct(callable $callable)
    {
        // Use Laravel's SerializableClosure
        $this->callable = serialize(new SerializableClosure($callable));
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        $callable = unserialize($this->callable)->getClosure();
        $callable();
    }
}
