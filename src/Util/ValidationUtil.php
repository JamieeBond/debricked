<?php

namespace App\Util;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Normalizer\ConstraintViolationListNormalizer;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Serializer\Exception\ExceptionInterface;

/**
 * Containing functions relating to validation.
 */
class ValidationUtil
{
    /**
     * @var ValidatorInterface
     */
    private ValidatorInterface $validator;
    
    /**
     * @param ValidatorInterface $validator
     */
    public function __construct(ValidatorInterface $validator)
    {
        $this->validator = $validator;
    }

    /**
     * Validate constraints and return a json response.
     *
     * @param mixed $value
     * @return JsonResponse|null
     * @throws ExceptionInterface
     */
    public function validateConstraintsResponse(mixed $value): ?JsonResponse
    {
        $violations = $this->validator->validate($value);

        if (0 === count($violations)) {
            return null;
        }

        $normalizer = new ConstraintViolationListNormalizer();

        $violations = $normalizer->normalize($violations);

        return new JsonResponse(
            $violations,
            Response::HTTP_BAD_REQUEST
        );
    }
}