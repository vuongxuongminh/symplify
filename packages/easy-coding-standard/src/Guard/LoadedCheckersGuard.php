<?php

declare(strict_types=1);

namespace Symplify\EasyCodingStandard\Guard;

use Symfony\Component\Console\Style\SymfonyStyle;
use Symplify\EasyCodingStandard\Application\FileProcessorCollector;

final class LoadedCheckersGuard
{
    public function __construct(
        private FileProcessorCollector $fileProcessorCollector,
        private SymfonyStyle $symfonyStyle,
    ) {
    }

    public function areSomeCheckersRegistered(): bool
    {
        $fileProcessors = $this->fileProcessorCollector->getFileProcessors();
        foreach ($fileProcessors as $fileProcessor) {
            if ($fileProcessor->getCheckers()) {
                return true;
            }
        }

        return false;
    }

    public function report(): void
    {
        $this->symfonyStyle->error('We could not find any sniffs/fixers rules to run');

        $this->symfonyStyle->writeln('You have few options to add them:');
        $this->symfonyStyle->newLine();

        $this->symfonyStyle->title('Add single rule to "ecs.php"');
        $this->symfonyStyle->writeln('  $services = $containerConfigurator->services();');
        $this->symfonyStyle->writeln('  $services->set(...);');
        $this->symfonyStyle->newLine(2);

        $this->symfonyStyle->title('Add set of rules to "ecs.php"');
        $this->symfonyStyle->writeln('  $containerConfigurator->import(...);');
        $this->symfonyStyle->newLine(2);

        $this->symfonyStyle->title('Missing "ecs.php" in your project? Let ECS create it for you');
        $this->symfonyStyle->writeln('  vendor/bin/ecs init');
        $this->symfonyStyle->newLine();
    }
}
