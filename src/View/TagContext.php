<?php

declare(strict_types=1);

namespace Gears\Framework\View;

use Gears\Framework\View\Exception\RenderingException;

final class TagContext
{
    /** Tag html attributes */
    public array $attrs = [];

    public string $innerHTML = '';

    /** Whether tag is self-closing */
    public bool $isVoid;

    /** Locale setting which is useful for date/time formatting and other special rendering cases */
    public string $locale;

    /** Template file path */
    public string $filePath = '';

    /** Tag position line in template file */
    public int $linepos;

    /** Tag position column in template file */
    public int $column;

    /** @var string Tag name */
    public string $name;

    /** @var array All template variables */
    private array $vars;

    private bool $debugMode;

    public function __construct(array $node, Template $t)
    {
        $this->name = $node['tag'];
        $this->attrs = $node['attrs'] ?? [];
        $this->isVoid = $node['void'];
        $this->linepos = $node['tag_pos'][0];
        $this->column = $node['tag_pos'][1];
        $this->vars = $t->getVars();
        $this->locale = $t->getLocale();
        $this->debugMode = $t->isDebugMode();
        $this->filePath = $t->getFilePath();
    }

    /** Get template variable. In case of string it is safely escaped */
    public function v(string $name): mixed
    {
        $this->assertVarExists($name);
        return is_string($v = $this->vars[$name] ?? null)
            ? htmlspecialchars($v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
            : $v;
    }

    /** Get unescaped template variable */
    public function raw(string $name): mixed
    {
        $this->assertVarExists($name);
        return $this->vars[$name] ?? null;
    }

    private function assertVarExists(string $name): void
    {
        if ($this->debugMode && !array_key_exists($name, $this->vars)) {
            throw new RenderingException(sprintf('Missing template variable "%s"', $name), $this);
        }
    }
}