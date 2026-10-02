<?php

declare(strict_types=1);

namespace IPMax\Exception;

use UnexpectedValueException;

final class DecodeException extends UnexpectedValueException implements IPMaxException {}
