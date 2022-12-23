<?php

declare(strict_types=1);

namespace Retryer;

class Retryer implements RetryerInterface
{
    protected const DEFAULT_LINEAR_MULTIPLIER = 1.0;

    protected \Closure $action;

    protected int $times = 1;

    protected int $delay = 0;

    /**
     * List of Exceptions which can break retry loop
     *
     * @var string[]
     */
    protected array $breakingExceptions = [];

    protected bool $isExponentialBackoff = false;

    protected float $linearBackoffMultiplier = self::DEFAULT_LINEAR_MULTIPLIER;

    protected bool $isThrowExceptionOnLastTry = false;

    /**
     * Empty constructor
     */
    public function __construct()
    {
        $this->action = static function (): void {
        };
    }

    /**
     * {@inheritdoc}
     *
     * @param \Closure $action Action you want to execute.
     *
     * @return static
     */
    public function do(\Closure $action): self
    {
        $this->action = $action;
        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @param integer $times Number of times to execute your action.
     *
     * @throws Exception\InvalidRetryTimesException
     *
     * @return static
     */
    public function times(int $times): self
    {
        if ($times <= 0) {
            throw new Exception\InvalidRetryTimesException("Tried to set {$times} retry times");
        }
        $this->times = $times;
        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @param integer $delay Microseconds between iterations.
     *
     * @throws Exception\InvalidDelayException
     *
     * @return static
     */
    public function withDelay(int $delay): self
    {
        if ($delay < 0) {
            throw new Exception\InvalidDelayException("Tried to set {$delay} as delay");
        }
        $this->delay = $delay;
        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @param class-string[] $breakingExceptions Exceptions that will not be thrown.
     *
     * @return static
     */
    public function withBreakingExceptions(array $breakingExceptions): self
    {
        $this->breakingExceptions = $breakingExceptions;
        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @param boolean $isEnabled Pass false to disable exponential backoff policy.
     *
     * @throws Exception\MutuallyExclusiveBackoffPolicyException
     *
     * @return static
     */
    public function useExponentialBackoff(bool $isEnabled = true): self
    {
        if ($isEnabled && ($this->linearBackoffMultiplier !== static::DEFAULT_LINEAR_MULTIPLIER)) {
            throw new Exception\MutuallyExclusiveBackoffPolicyException(
                'Tried to use exponential backoff policy with linear multiplier != 1'
            );
        }
        $this->isExponentialBackoff = $isEnabled;
        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @param float $linearBackoffMultiplier Should be positive, default is 1.
     *
     * @throws Exception\MutuallyExclusiveBackoffPolicyException
     *
     * @return static
     */
    public function useLinearBackoffMultiplier(float $linearBackoffMultiplier): self
    {
        if ($this->isExponentialBackoff && ($linearBackoffMultiplier !== static::DEFAULT_LINEAR_MULTIPLIER)) {
            throw new Exception\MutuallyExclusiveBackoffPolicyException(
                'Tried to use linear backoff with exponential enabled'
            );
        }
        $this->linearBackoffMultiplier = $linearBackoffMultiplier;
        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @return static
     */
    public function throwExceptionOnLastTry(): self
    {
        $this->isThrowExceptionOnLastTry = true;
        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @throws \Throwable
     *
     * @return mixed
     */
    public function execute(): mixed
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

    /**
     * Check should throwable be thrown
     *
     * @param \Throwable $throwable Catched exception.
     * @param integer    $iteration Number of current iteration.
     *
     * @throws \Throwable
     *
     * @return void
     */
    protected function checkThrowable(\Throwable $throwable, int $iteration): void
    {
        $isThrowExceptionOnLastTry = $this->isThrowExceptionOnLastTry && $iteration >= $this->times;
        $isBreakingException       = \in_array(\get_class($throwable), $this->breakingExceptions, true);
        if ($isThrowExceptionOnLastTry || $isBreakingException) {
            throw $throwable;
        }
    }

    /**
     * Sleep between iterations depends on backoff policy
     *
     * @param integer $iteration Current iteration number.
     *
     * @return void
     */
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

    /**
     * Modify delay by exponential backoff policy
     *
     * @param integer $iteration Current iteration number.
     *
     * @return integer
     */
    private function applyExponentialBackoff(int $iteration): int
    {
        return ($this->delay << $iteration);
    }

    /**
     * Modify delay by linear backoff policy
     *
     * @return integer
     */
    private function applyLinearBackoff(): int
    {
        return (int)($this->delay * $this->linearBackoffMultiplier);
    }
}
