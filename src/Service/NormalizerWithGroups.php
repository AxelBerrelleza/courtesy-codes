<?php

namespace App\Service;

use Symfony\Component\Serializer\Context\Normalizer\ObjectNormalizerContextBuilder;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class NormalizerWithGroups
{
    public function __construct(protected NormalizerInterface $normalizer) {}

    public function normalize(
        mixed $data,
        array|string|null $groups = null,
        string $format = 'array'
    ): array {
        return $this->normalizer->normalize(
            $data,
            format: $format,
            context: (new ObjectNormalizerContextBuilder())
                ->withGroups($groups)
                ->toArray()
        );
    }
}
