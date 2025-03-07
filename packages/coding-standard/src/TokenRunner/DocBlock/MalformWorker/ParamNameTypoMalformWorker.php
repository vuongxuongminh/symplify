<?php

declare(strict_types=1);

namespace Symplify\CodingStandard\TokenRunner\DocBlock\MalformWorker;

use Nette\Utils\Strings;
use PhpCsFixer\DocBlock\Annotation;
use PhpCsFixer\DocBlock\DocBlock;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;
use Symplify\CodingStandard\TokenAnalyzer\DocblockRelatedParamNamesResolver;
use Symplify\CodingStandard\TokenRunner\Contract\DocBlock\MalformWorkerInterface;

final class ParamNameTypoMalformWorker implements MalformWorkerInterface
{
    /**
     * @var string
     * @see https://regex101.com/r/5szHlw/1
     */
    private const PARAM_NAME_REGEX = '#@param(.*?)(?<paramName>\$\w+)#';

    public function __construct(
        private DocblockRelatedParamNamesResolver $docblockRelatedParamNamesResolver
    ) {
    }

    /**
     * @param Tokens<Token> $tokens
     */
    public function work(string $docContent, Tokens $tokens, int $position): string
    {
        $argumentNames = $this->docblockRelatedParamNamesResolver->resolve($tokens, $position);
        if ($argumentNames === []) {
            return $docContent;
        }

        $paramNames = $this->getParamNames($docContent);

        // remove correct params
        foreach ($argumentNames as $key => $argumentName) {
            if (in_array($argumentName, $paramNames, true)) {
                $paramPosition = array_search($argumentName, $paramNames, true);
                unset($paramNames[$paramPosition]);
                unset($argumentNames[$key]);
            }
        }

        // nothing to edit, all arguments are correct or there are no more @param annotations
        if ($argumentNames === []) {
            return $docContent;
        }

        if ($paramNames === []) {
            return $docContent;
        }

        return $this->fixTypos($argumentNames, $paramNames, $docContent);
    }

    /**
     * @return string[]
     */
    private function getParamNames(string $docContent): array
    {
        $paramAnnotations = $this->getAnnotationsOfType($docContent, 'param');

        $paramNames = [];
        foreach ($paramAnnotations as $paramAnnotation) {
            $match = Strings::match($paramAnnotation->getContent(), self::PARAM_NAME_REGEX);
            if (isset($match['paramName'])) {
                $paramNames[] = $match['paramName'];
            }
        }

        return $paramNames;
    }

    /**
     * @return Annotation[]
     */
    private function getAnnotationsOfType(string $docContent, string $type): array
    {
        $docBlock = new DocBlock($docContent);

        return $docBlock->getAnnotationsOfType($type);
    }

    /**
     * @param string[] $argumentNames
     * @param string[] $paramNames
     */
    private function fixTypos(array $argumentNames, array $paramNames, string $docContent): string
    {
        foreach ($argumentNames as $key => $argumentName) {
            // 1. the same position
            if (! isset($paramNames[$key])) {
                continue;
            }

            $typoName = $paramNames[$key];
            $replacePattern = '#@param(.*?)' . preg_quote($typoName, '#') . '#';

            $docContent = Strings::replace($docContent, $replacePattern, '@param$1' . $argumentName);
        }

        return $docContent;
    }
}
