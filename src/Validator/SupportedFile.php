<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;
use Attribute;

/**
 * @Annotation
 * @Target({"PROPERTY", "METHOD", "ANNOTATION"})
 */
#[\Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class SupportedFile extends Constraint
{
    public string $message = '"{{ filename }}" is not supported.';
}
