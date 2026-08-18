<?php

declare(strict_types=1);

namespace KitchenManager\Modules\BusinessProfile\Repositories;

use KitchenManager\Modules\BusinessProfile\DTOs\BusinessProfileDTO;

class BusinessProfileRepository
{
    public const OPTION_KEY = 'km_business_profile_settings';

    public function get(): BusinessProfileDTO
    {
        $raw = get_option(self::OPTION_KEY, []);
        if (!is_array($raw)) {
            $raw = [];
        }

        return BusinessProfileDTO::fromArray($raw);
    }

    public function save(BusinessProfileDTO $dto): bool
    {
        return update_option(self::OPTION_KEY, $dto->toArray(), false);
    }

    public function isSetupCompleted(): bool
    {
        $profile = $this->get();
        return $profile->setupCompleted;
    }
}