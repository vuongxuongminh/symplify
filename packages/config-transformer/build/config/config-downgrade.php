<?php

declare(strict_types=1);

use PhpParser\Parser;
use PhpParser\PrettyPrinterAbstract;
use Rector\Core\Configuration\Option;
use Rector\DowngradePhp72\Rector\ClassMethod\DowngradeParameterTypeWideningRector;
use Rector\Set\ValueObject\DowngradeLevelSetList;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\StyleInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $containerConfigurator): void {
    $containerConfigurator->import(DowngradeLevelSetList::DOWN_TO_PHP_71);

    $services = $containerConfigurator->services();
    $services->set(DowngradeParameterTypeWideningRector::class)
        ->call('configure', [[
            DowngradeParameterTypeWideningRector::SAFE_TYPES => [
                OutputInterface::class,
                StyleInterface::class,
                // phpstan
                Parser::class,
                PrettyPrinterAbstract::class,
            ],
            DowngradeParameterTypeWideningRector::SAFE_TYPES_TO_METHODS => [
                ContainerInterface::class => ['setParameter', 'getParameter', 'hasParameter'],
            ],
        ]]);

    $parameters = $containerConfigurator->parameters();
    $parameters->set(Option::SKIP, [
        '*/Tests/*',
        '*/tests/*',
        __DIR__ . '/../../tests',
        # missing "optional" dependency and never used here
        '*/symfony/framework-bundle/KernelBrowser.php',
        '*/symfony/http-kernel/HttpKernelBrowser.php',
        '*/symfony/cache/*',
        // fails on DOMCaster
        '*/symfony/var-dumper/*',
        '*/symfony/var-exporter/*',
        '*/symfony/error-handler/*',
        '*/symfony/event-dispatcher/*',
        '*/symfony/event-dispatcher-contracts/*',
        '*/symfony/http-foundation/*',
    ]);
};
