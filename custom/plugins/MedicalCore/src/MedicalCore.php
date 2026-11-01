<?php declare(strict_types=1);

namespace MedicalCore;

use Shopware\Core\Framework\Plugin;
use Shopware\Core\Framework\Plugin\Context\ActivateContext;
use Shopware\Core\Framework\Plugin\Context\DeactivateContext;
use Shopware\Core\Framework\Plugin\Context\InstallContext;
use Shopware\Core\Framework\Plugin\Context\UninstallContext;
use Shopware\Core\Framework\Plugin\Context\UpdateContext;

class MedicalCore extends Plugin
{
    public function install(InstallContext $installContext): void
    {
        error_log('[MedicalCore] install() called.');
    }

    public function postInstall(InstallContext $installContext): void
    {
        error_log('[MedicalCore] postInstall() called.');
    }

    public function activate(ActivateContext $activateContext): void
    {
        error_log('[MedicalCore] activate() called.');
    }

    public function deactivate(DeactivateContext $deactivateContext): void
    {
        error_log('[MedicalCore] deactivate() called.');
    }

    public function update(UpdateContext $updateContext): void
    {
        error_log('[MedicalCore] update() called.');
    }

    public function postUpdate(UpdateContext $updateContext): void
    {
        error_log('[MedicalCore] postUpdate() called.');
    }

    public function uninstall(UninstallContext $uninstallContext): void
    {
        error_log(sprintf(
            '[MedicalCore] uninstall() called. keepUserData=%s',
            $uninstallContext->keepUserData() ? 'true' : 'false'
        ));

        // We intentionally do not delete data in this learning exercise.
    }
}
