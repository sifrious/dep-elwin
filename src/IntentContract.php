<?php
declare(strict_types=1);
namespace Sifrious\Elwin;

interface IntentContract
{
    public function supersededBy(Intent $replacement): Intent;
}
