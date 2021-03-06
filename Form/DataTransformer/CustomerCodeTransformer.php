<?php

namespace Librinfo\CRMBundle\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;
use Librinfo\CRMBundle\Entity\Organism;

class CustomerCodeTransformer implements DataTransformerInterface
{
    /**
     * @param  string|null $code
     * @return string|null
     */
    public function transform($code)
    {
        return $code;
    }

    /**
     * Automatically add prefix and leading zeros when user submits only digits
     * @param  string|null $code
     * @return string|null
     */
    public function reverseTransform($code)
    {
        $code = trim($code);
        if (preg_match('/^[0-9]+$/', $code))
          return sprintf('%s%0'.Organism::CC_LENGTH.'d', Organism::CC_PREFIX, (int)$code);
        return $code;
    }
}
