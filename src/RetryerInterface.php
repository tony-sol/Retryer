<?php

declare(strict_types=1);

namespace Retryer;

interface RetryerInterface
{
    /**
     * Action you want to execute
     *
     * @param \Closure $action Action you want to execute.
     *
     * @return static
     */
    public function do(\Closure $action): RetryerInterface;

    /**
     * Execute your action N times
     *
     * @param integer $times Number of times to execute your action.
     *
     * @throws Exception\InvalidRetryTimesException
     *
     * @return static
     */
    public function times(int $times): RetryerInterface;

    /**
     * With delay of T ms between iterations
     *
     * @param integer $delay Microseconds between iterations.
     *
     * @throws Exception\InvalidDelayException
     *
     * @return static
     */
    public function withDelay(int $delay): RetryerInterface;

    /**
     * With breaking execution exceptions
     *
     * @param class-string[] $allowedExceptions Exceptions that will not be thrown.
     *
     * @return static
     */
    public function withBreakingExceptions(array $allowedExceptions): RetryerInterface;

    /**
     * Should retryer use exponential backoff policy
     *
     * @param boolean $isEnabled Pass false to disable exponential backoff policy.
     *
     * @throws Exception\MutuallyExclusiveBackoffPolicyException
     *
     * @return static
     */
    public function useExponentialBackoff(bool $isEnabled = true): RetryerInterface;

    /**
     * Should retryer use linear backoff policy with multiplier
     *
     * @param float $linearBackoffMultiplier Should be positive, default is 1.
     *
     * @throws Exception\MutuallyExclusiveBackoffPolicyException
     *
     * @return static
     */
    public function useLinearBackoffMultiplier(float $linearBackoffMultiplier): RetryerInterface;

    /**
     * Throw exception on last try if fails
     *
     * @return static
     */
    public function throwExceptionOnLastTry(): RetryerInterface;

    /**
     * Start execution
     *
     * @throws \Throwable
     *
     * @return mixed
     */
    public function execute(): mixed;
}
