<?php

declare(strict_types=1);

namespace Retryer;

class Retryer implements RetryerInterface
{
    protected const DEFAULT_LINEAR_MULTIPLIER = 1.0;

    protected  $action;

    protected  $times = 1;

    protected  $delay = 0;


    protected  $breakingExceptions = [];

    protected  $isExponentialBackoff = false;

    protected  $linearBackoffMultiplier = self::DEFAULT_LINEAR_MULTIPLIER;

    protected  $isThrowExceptionOnLastTry = false;

    /**
     * Empty constructor
     */
    public function __construct()
    {
        $this->action = static function (): void {
        };
    }

    public function do( $action): self
    {
        $this->action = $action;
        return $this;
    }


    public function times( $times): self
    {
        if ($times <= 0) {
            throw new Exception\InvalidRetryTimesException("Tried to set {$times} retry times");
        }
        $this->times = $times;
        return $this;
    }

    public function withDelay( $delay): self
    {
        if ($delay < 0) {
            throw new Exception\InvalidDelayException("Tried to set {$delay} as delay");
        }
        $this->delay = $delay;
        return $this;
    }

    public function withBreakingExceptions( $breakingExceptions): self
    {
        $this->breakingExceptions = $breakingExceptions;
        return $this;
    }

    public function useExponentialBackoff( $isEnabled = true): self
    {
        if ($isEnabled && ($this->linearBackoffMultiplier !== static::DEFAULT_LINEAR_MULTIPLIER)) {
            throw new Exception\MutuallyExclusiveBackoffPolicyException(
                'Tried to use exponential backoff policy with linear multiplier != 1'
            );
        }
        $this->isExponentialBackoff = $isEnabled;
        return $this;
    }

    public function useLinearBackoffMultiplier( $linearBackoffMultiplier)
    {
        if ($this->isExponentialBackoff && ($linearBackoffMultiplier !== static::DEFAULT_LINEAR_MULTIPLIER)) {
            throw new Exception\MutuallyExclusiveBackoffPolicyException(
                'Tried to use linear backoff with exponential enabled'
            );
        }
        $this->linearBackoffMultiplier = $linearBackoffMultiplier;
        return $this;
    }

    public function throwExceptionOnLastTry()
    {
        $this->isThrowExceptionOnLastTry = true;
        return $this;
    }

    public function execute()
    {
        $result = null;
        for ($iteration = 1; $iteration <= $this->times; $iteration++) {
            try {
                return ($this->action)();
            } catch (\Throwable $throwable) {
                $this->checkThrowable($throwable, $iteration);
                $this->delay($iteration);
            }
        }
        return $result;
    }

    protected function checkThrowable(\Throwable $throwable, int $iteration): void
    {
        $isThrowExceptionOnLastTry = $this->isThrowExceptionOnLastTry && $iteration >= $this->times;
        $isBreakingException       = \in_array(\get_class($throwable), $this->breakingExceptions, true);
        if ($isThrowExceptionOnLastTry || $isBreakingException) {
            throw $throwable;
        }
    }

    protected function delay(int $iteration): void
    {
        if ($iteration >= $this->times) {
            return;
        }
        if ($this->isExponentialBackoff) {
            $delay = $this->applyExponentialBackoff($iteration);
        } elseif ($this->linearBackoffMultiplier !== static::DEFAULT_LINEAR_MULTIPLIER) {
            $delay = $this->applyLinearBackoff();
        } else {
            $delay = $this->delay;
        }
        if ($delay > 0) {
            \usleep($delay);
        }
    }

    private function applyExponentialBackoff(int $iteration): int
    {
        return ($this->delay << $iteration);
    }

    private function applyLinearBackoff(): int
    {
        return (int)($this->delay * $this->linearBackoffMultiplier);
    }
}
