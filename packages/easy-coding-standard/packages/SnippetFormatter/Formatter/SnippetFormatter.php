<?php

declare(strict_types=1);

namespace Symplify\EasyCodingStandard\SnippetFormatter\Formatter;

use Nette\Utils\Strings;
use Symplify\EasyCodingStandard\FixerRunner\Application\FixerFileProcessor;
use Symplify\EasyCodingStandard\SniffRunner\Application\SniffFileProcessor;
use Symplify\EasyCodingStandard\SnippetFormatter\Provider\CurrentParentFileInfoProvider;
use Symplify\EasyCodingStandard\SnippetFormatter\ValueObject\SnippetKind;
use Symplify\EasyCodingStandard\ValueObject\Configuration;
use Symplify\SmartFileSystem\SmartFileInfo;
use Symplify\SmartFileSystem\SmartFileSystem;
use Throwable;

/**
 * @see \Symplify\EasyCodingStandard\Tests\SnippetFormatter\Markdown\MarkdownSnippetFormatterTest
 * @see \Symplify\EasyCodingStandard\Tests\SnippetFormatter\HeredocNowdoc\HereNowDocSnippetFormatterTest
 */
final class SnippetFormatter
{
    /**
     * @see https://regex101.com/r/MJTq5C/1
     * @var string
     */
    private const DECLARE_REGEX = '#(declare\(strict\_types\=1\)\;\n)#ms';

    /**
     * @see https://regex101.com/r/MJTq5C/3
     * @var string
     */
    private const OPENING_TAG_REGEX = '#^\<\?php\n#ms';

    /**
     * @see https://regex101.com/r/MJTq5C/3
     * @var string
     */
    private const OPENING_TAG_HERENOWDOC_REGEX = '#^\<\?php\n#ms';

    /**
     * @var string
     */
    private const CONTENT = 'content';

    /**
     * @var string
     */
    private const OPENING = 'opening';

    /**
     * @var string
     */
    private const CLOSING = 'closing';

    public function __construct(
        private SmartFileSystem $smartFileSystem,
        private FixerFileProcessor $fixerFileProcessor,
        private SniffFileProcessor $sniffFileProcessor,
        private CurrentParentFileInfoProvider $currentParentFileInfoProvider
    ) {
    }

    public function format(
        SmartFileInfo $fileInfo,
        string $snippetRegex,
        string $kind,
        Configuration $configuration
    ): string {
        $this->currentParentFileInfoProvider->setParentFileInfo($fileInfo);

        return Strings::replace($fileInfo->getContents(), $snippetRegex, function ($match) use (
            $kind,
            $configuration
        ): string {
            if (\str_contains($match[self::CONTENT], '-----')) {
                // do nothing
                return $match[self::OPENING] . $match[self::CONTENT] . $match[self::CLOSING];
            }

            return $this->fixContentAndPreserveFormatting($match, $kind, $configuration);
        });
    }

    /**
     * @param string[] $match
     */
    private function fixContentAndPreserveFormatting(array $match, string $kind, Configuration $configuration): string
    {
        return str_replace(PHP_EOL, '', $match[self::OPENING]) . PHP_EOL
            . $this->fixContent($match[self::CONTENT], $kind, $configuration)
            . str_replace(PHP_EOL, '', $match[self::CLOSING]);
    }

    private function fixContent(string $content, string $kind, Configuration $configuration): string
    {
        $temporaryFilePath = $this->createTemporaryFilePath($content);

        if (! \str_starts_with(trim($content), '<?php')) {
            $content = '<?php' . PHP_EOL . $content;
        }

        $fileContent = ltrim($content, PHP_EOL);

        $this->smartFileSystem->dumpFile($temporaryFilePath, $fileContent);
        $temporaryFileInfo = new SmartFileInfo($temporaryFilePath);

        try {
            $this->fixerFileProcessor->processFile($temporaryFileInfo, $configuration);
            $this->sniffFileProcessor->processFile($temporaryFileInfo, $configuration);

            $changedFileContent = $temporaryFileInfo->getContents();
        } catch (Throwable) {
            // Skipped parsed error when processing php temporaryFile
            $changedFileContent = $fileContent;
        } finally {
            // remove temporary temporaryFile
            $this->smartFileSystem->remove($temporaryFilePath);
        }

        $changedFileContent = rtrim($changedFileContent, PHP_EOL) . PHP_EOL;

        if ($kind === SnippetKind::MARKDOWN) {
            return $this->resolveMarkdownFileContent($changedFileContent);
        }

        return Strings::replace($changedFileContent, self::OPENING_TAG_HERENOWDOC_REGEX, '$1');
    }

    /**
     * It does not have any added value and only clutters the output
     */
    private function removeOpeningTagAndStrictTypes(string $content): string
    {
        $content = Strings::replace($content, self::DECLARE_REGEX, '');

        return $this->removeOpeningTag($content);
    }

    private function createTemporaryFilePath(string $content): string
    {
        $key = md5($content);
        $fileName = sprintf('php-code-%s.php', $key);

        return sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'ecs_temp' . DIRECTORY_SEPARATOR . $fileName;
    }

    private function removeOpeningTag(string $fileContent): string
    {
        return Strings::replace($fileContent, self::OPENING_TAG_REGEX, '$1');
    }

    private function resolveMarkdownFileContent(string $fileContent): string
    {
        $fileContent = ltrim($fileContent, PHP_EOL);
        $fileContent = $this->removeOpeningTagAndStrictTypes($fileContent);

        return ltrim($fileContent);
    }
}
