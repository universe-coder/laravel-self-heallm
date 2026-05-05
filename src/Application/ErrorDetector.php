<?php

declare(strict_types=1);

namespace SelfHealLM\Application;

use Illuminate\Filesystem\Filesystem;
use SelfHealLM\Domain\Error\ErrorContext;

final class ErrorDetector
{
    public function __construct(private readonly Filesystem $files)
    {
    }

    public function detectFromLog(string $logPath, int $maxLines, int $maxFileChars = 12000): ?ErrorContext
    {
        if (!$this->files->exists($logPath)) {
            return null;
        }

        $lines = preg_split('/\R/', (string) $this->files->get($logPath)) ?: [];
        $tail = array_slice($lines, -$maxLines);
        $tail = array_values(array_filter($tail, static fn (string $line): bool => trim($line) !== ''));

        for ($i = count($tail) - 1; $i >= 0; $i--) {
            $line = $tail[$i];

            if (!str_contains($line, 'ERROR') && !str_contains($line, 'exception')) {
                continue;
            }

            $message = $line;
            $filePath = '';
            $lineNumber = 0;

            if (preg_match('/ in ([^ ]+) on line (\d+)/', $line, $matches) === 1) {
                $filePath = $matches[1];
                $lineNumber = (int) $matches[2];
            }

            $snippet = $this->extractSnippet($filePath, $lineNumber);
            $fileContent = $this->extractFileContent($filePath, $maxFileChars);
            return new ErrorContext($message, $filePath, $lineNumber, [], $snippet, $fileContent, $line);
        }

        return null;
    }

    private function extractSnippet(string $filePath, int $line): string
    {
        if ($filePath === '' || $line < 1 || !$this->files->exists($filePath)) {
            return '';
        }

        $content = preg_split('/\R/', (string) $this->files->get($filePath)) ?: [];
        $offset = max(0, $line - 4);
        $slice = array_slice($content, $offset, 7, true);
        $snippet = [];

        foreach ($slice as $index => $codeLine) {
            $snippet[] = sprintf('%d: %s', $index + 1, $codeLine);
        }

        return implode(PHP_EOL, $snippet);
    }

    private function extractFileContent(string $filePath, int $maxFileChars): string
    {
        if ($filePath === '' || !$this->files->exists($filePath)) {
            return '';
        }

        $content = (string) $this->files->get($filePath);

        if ($maxFileChars > 0 && strlen($content) > $maxFileChars) {
            return substr($content, 0, $maxFileChars);
        }

        return $content;
    }
}
