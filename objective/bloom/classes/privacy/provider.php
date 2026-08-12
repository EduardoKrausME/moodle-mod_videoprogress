<?php

namespace videoprogressobjective_bloom\privacy;

use core_privacy\local\metadata\null_provider;

/**
 * Declares that the Bloom objective type does not store personal data.
 */
class provider implements null_provider {
    /**
     * Returns the localized privacy reason for this objective type.
     *
     * @return string Localized privacy reason.
     */
    public static function get_reason(): string {
        return "privacy:metadata";
    }
}
