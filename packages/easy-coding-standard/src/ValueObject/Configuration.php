<?php

declare(strict_types=1);

namespace Symplify\EasyCodingStandard\ValueObject;

use Symplify\EasyCodingStandard\Console\Output\ConsoleOutputFormatter;

final class Configuration
{
    /**
     * @param string[] $sources
     */
    public function __construct(
        private bool $isFixer = false,
        private bool $shouldClearCache = false,
        private bool $showProgressBar = true,
        private bool $showErrorTable = true,
        private array $sources = [],
        private string $outputFormat = ConsoleOutputFormatter::NAME,
        private bool $isParallel = false,
        private ?string $config = null,
        private string | null $parallelPort = null,
        private string | null $parallelIdentifier = null,
    ) {
    }

    public function isFixer(): bool
    {
        return $this->isFixer;
    }

    public function shouldClearCache(): bool
    {
        return $this->shouldClearCache;
    }

    public function shouldShowProgressBar(): bool
    {
        return $this->showProgressBar;
    }

    public function shouldShowErrorTable(): bool
    {
        return $this->showErrorTable;
    }

    /**
     * @return string[]
     */
    public function getSources(): array
    {
        return $this->sources;
    }

    public function getOutputFormat(): string
    {
        return $this->outputFormat;
    }

    public function isParallel(): bool
    {
        return $this->isParallel;
    }

    public function getConfig(): ?string
    {
        return $this->config;
    }

    public function getParallelPort(): ?string
    {
        return $this->parallelPort;
    }

    public function getParallelIdentifier(): ?string
    {
        return $this->parallelIdentifier;
    }
}
