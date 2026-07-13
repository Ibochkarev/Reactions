<?php

namespace Reactions\Console;

use MODX\Revolution\modX;
use Reactions\Reactions;

abstract class AbstractCommand
{
    public function __construct(
        protected readonly modX $modx,
        protected readonly Reactions $reactions,
        /** @var array<string, mixed> */
        protected readonly array $options = [],
    ) {
    }

    abstract public function execute(): int;

    protected function writeln(string $message = ''): void
    {
        fwrite(STDOUT, $message . PHP_EOL);
    }

    protected function writelnError(string $message): void
    {
        fwrite(STDERR, $message . PHP_EOL);
    }

    protected function hasOption(string $name): bool
    {
        return array_key_exists($name, $this->options) && $this->options[$name] !== false;
    }

    protected function getOption(string $name, mixed $default = null): mixed
    {
        if (!array_key_exists($name, $this->options)) {
            return $default;
        }

        $value = $this->options[$name];

        return $value === false ? $default : $value;
    }

    protected function getIntOption(string $name, int $default = 0): int
    {
        $value = $this->getOption($name);

        return is_numeric($value) ? (int) $value : $default;
    }

    protected function getStringOption(string $name, string $default = ''): string
    {
        $value = $this->getOption($name);

        return is_string($value) ? $value : (is_scalar($value) ? (string) $value : $default);
    }

    protected function getBoolOption(string $name, bool $default = false): bool
    {
        if (!$this->hasOption($name)) {
            return $default;
        }

        $value = $this->getOption($name);

        if (is_bool($value)) {
            return $value;
        }

        if (is_string($value)) {
            return !in_array(strtolower($value), ['0', 'false', 'no', 'off'], true);
        }

        return (bool) $value;
    }

    /**
     * @param list<string> $required
     */
    protected function requireOptions(array $required): bool
    {
        foreach ($required as $name) {
            if (!$this->hasOption($name) || $this->getStringOption($name) === '') {
                $this->writelnError("Missing required option: --{$name}");

                return false;
            }
        }

        return true;
    }
}
