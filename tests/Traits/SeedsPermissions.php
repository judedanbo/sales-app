<?php

namespace Tests\Traits;

use Database\Seeders\RolesAndPermissionsSeeder;

trait SeedsPermissions
{
    protected function seedPermissions(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    protected function setUpTraits(): void
    {
        parent::setUpTraits();

        $uses = array_flip(class_uses_recursive(static::class));

        if (isset($uses[SeedsPermissions::class])) {
            $this->seedPermissions();
        }
    }
}
