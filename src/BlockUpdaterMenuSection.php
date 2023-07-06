<?php

declare(strict_types=1);

namespace Gebruederheitz\Wordpress\BlockUpdater;

use Gebruederheitz\Wordpress\AdminPage\Documentation\DocumentationMenu;
use Gebruederheitz\Wordpress\AdminPage\AdminPageSectionInterface;
use Gebruederheitz\Wordpress\AdminPage\AbstractAdminPageSection;

class BlockUpdaterMenuSection extends AbstractAdminPageSection implements
    AdminPageSectionInterface
{
    protected function getDefaultPartial(): string
    {
        return __DIR__ . '/../templates/updater.php';
    }

    public function getTitle(): string
    {
        return 'Runner';
    }
}
