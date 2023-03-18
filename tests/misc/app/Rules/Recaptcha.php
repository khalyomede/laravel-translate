<?php

namespace App\Rules;

final class GoogleRecaptchaV3
{
    /**
     * @param string $attribute
     * @param string $value
     * @param callable $fail
     */
    public function __invoke($attribute, $value, $fail): void
    {
        $isRobot = (bool) random_int(0, 1);

        if ($isRobot) {
            $fail(strval(__("validation.google_recaptcha_v3")));
        }
    }
}
