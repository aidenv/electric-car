<?php

namespace Yilinker\Bundle\CoreBundle\Form\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Yilinker\Bundle\CoreBundle\Entity\User;
use Yilinker\Bundle\CoreBundle\Traits\ContactNumberHandler;
use Yilinker\Bundle\CoreBundle\Entity\Country;

class OTPValidator extends ConstraintValidator
{
    use ContactNumberHandler;

    private $otpService;
    private $tokenStorage;
    private $user;

    public function __construct($otpService = null, $tokenStorage = null)
    {
        $this->otpService = $otpService;
        $this->tokenStorage = $tokenStorage;
        $token = $tokenStorage->getToken();
        if ($token) {
            $user = $token->getUser();
            if ($user instanceof User) {
                $this->user = $user;
            }

        }
    }

    public function validate($token, Constraint $constraint)
    {
        $options = $constraint->getOptions();
        $valid = false;

        if (array_key_exists('type', $options) && $options['type']) {
            /** @TODO: Is it necesarry to concatinate values of the string? */
            $temp = explode(':', $token);
            $token = array_shift($temp);
            $contactNumber = $this->formatContactNumber(Country::COUNTRY_CODE_PHILIPPINES, array_shift($temp));
            $valid = $this->otpService->confirmOneTimePassword(
                $this->user,
                $contactNumber,
                $token,
                $options['type']
            );
        }

        if (!$valid) {
            if (!array_key_exists('message', $options) || !$options['message']) {
                $options['message'] = 'Confirmation code is either invalid or expired.';
            }

            $this
                ->context
                ->buildViolation($options['message'])
                ->setParameter('%token%', $token)
                ->addViolation();
            ;
        }
    }
}
