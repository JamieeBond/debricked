<?php

namespace App\Validator;

use App\Util\DebrickedApiUtil;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

/**
 * Validates that the file format is supported by Debricked.
 */
class SupportedFileValidator extends ConstraintValidator
{
    /**
     * @var DebrickedApiUtil
     */
    private DebrickedApiUtil $apiUtil;

    /**
     * @param DebrickedApiUtil $apiUtil
     */
    public function __construct(DebrickedApiUtil $apiUtil)
    {
        $this->apiUtil = $apiUtil;
    }

    /**
     * @param $value
     * @param Constraint $constraint
     * @return void
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function validate($value, Constraint $constraint)
    {
        /* @var SupportedFile $constraint */

        if (!is_array($value)) {
            return;
        }

        if (0 === (count($value))) {
            return;
        }

        $supportedFiles = $this->apiUtil->getSupportedFormats();

        foreach ($value as $file) {
            $notSupported = true;
            if (!$file instanceof UploadedFile) {
                continue;
            }
            $fileName = $file->getClientOriginalName();
            foreach ($supportedFiles as $supportedFile) {
                if (preg_match('/' . $supportedFile . '/', $fileName)) {
                    $notSupported = false;
                    continue;
                }
            }
            if ($notSupported) {
                $this->context
                    ->buildViolation($constraint->message)
                    ->setParameter('{{ filename }}', $fileName)
                    ->addViolation()
                ;
            }

        }
    }


}
