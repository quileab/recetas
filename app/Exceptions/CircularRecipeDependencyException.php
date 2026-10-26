<?php

namespace App\Exceptions;

use RuntimeException;

class CircularRecipeDependencyException extends RuntimeException
{
    public static function detected(): self
    {
        return new self('Se detectó una dependencia circular entre recetas. Revisa las preparaciones utilizadas.');
    }
}
