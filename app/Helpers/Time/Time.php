<?php


namespace App\Helpers\Time;

use InvalidArgumentException;

class Time
{
    private int $seconds;

    public function __construct(int $seconds)
    {
        if ($seconds < 0) {
            throw new InvalidArgumentException('Seconds must be positive int or zero');
        }

        $this->seconds = $seconds;
    }

    public function __toString()
    {
        return $this->toString();
    }

    public function toString()
    {
        $time = '';

        if ($hours = $this->toHours()) {
            $time .= sprintf('%dh ', $hours);
        }

        if ($minutes = $this->toMinutes()) {
            $time .= sprintf('%dm ', $minutes);
        }

        $time .= sprintf('%ds', $this->toSeconds());

        return trim($time);
    }

    public function toHours(): int
    {
        return (int)gmdate('H', $this->seconds);
    }

    public function toMinutes(): int
    {
        return (int)gmdate('m', $this->seconds);
    }

    public function toSeconds(): int
    {
        return (int)gmdate('s', $this->seconds);
    }

    public function toDecimalFormat(): string
    {
        return (string)round($this->seconds / 3600, 2);
    }

    public function getSeconds(): int
    {
        return $this->seconds;
    }

    /**
     * @param int $seconds
     * @return static
     */
    public function addSeconds(int $seconds)
    {
        return new static($seconds + $this->getSeconds());
    }

    public function addTime(Time $time): self
    {
        return $this->addSeconds($time->getSeconds());
    }
}
