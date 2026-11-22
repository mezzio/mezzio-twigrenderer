<?php

declare(strict_types=1);

namespace Mezzio\Template;

use Stringable;

use function class_exists;

if (! class_exists(TemplatePath::class)) {
/**
 * @deprecated This class was removed in mezzio/template v3.0.0, this is a polyfill for upgrade compatibility.
 * It will be removed in mezzio/mezzio-twigrenderer v3.0.0.
 *
 * @final
 */
    class TemplatePath implements Stringable
    {
        public function __construct(protected string $path, protected ?string $namespace = null)
        {
        }

        /**
         * Casts to string by returning the path only.
         */
        public function __toString(): string
        {
            return $this->path;
        }

        /**
         * Get the namespace
         */
        public function getNamespace(): ?string
        {
            return $this->namespace;
        }

        /**
         * Get the path
         */
        public function getPath(): string
        {
            return $this->path;
        }
    }
}
