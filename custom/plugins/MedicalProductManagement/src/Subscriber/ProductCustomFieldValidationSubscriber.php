<?php declare(strict_types=1);

namespace MedicalProductManagement\\Subscriber;

use MedicalProductManagement\\Service\\ProductCustomFieldValidator;
use Shopware\\Core\\Content\\Product\\Aggregate\\ProductTranslation\\ProductTranslationDefinition;
use Shopware\\Core\\Content\\Product\\ProductDefinition;
use Shopware\\Core\\Framework\\DataAbstractionLayer\\Write\\Validation\\PreWriteValidationEvent;
use Shopware\\Core\\Framework\\Validation\\WriteConstraintViolationException;
use Symfony\\Component\\EventDispatcher\\EventSubscriberInterface;
use Symfony\\Component\\Validator\\ConstraintViolation;
use Symfony\\Component\\Validator\\ConstraintViolationList;

final class ProductCustomFieldValidationSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly ProductCustomFieldValidator $validator
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [PreWriteValidationEvent::class => 'validate'];
    }

    public function validate(PreWriteValidationEvent $event): void
    {
        $violations = new ConstraintViolationList();
        $entityNames = [
            ProductDefinition::ENTITY_NAME,
            ProductTranslationDefinition::ENTITY_NAME,
        ];

        foreach ($entityNames as $entityName) {
            foreach ($event->getCommandsForEntity($entityName) as $command) {
                $payload = $command->getPayload();
                $customFields = $payload['custom_fields'] ?? $payload['customFields'] ?? null;

                if (!is_array($customFields)) {
                    continue;
                }

                foreach ($this->validator->validate($customFields) as $error) {
                    $violations->add(new ConstraintViolation(
                        $error['message'],
                        $error['message'],
                        [],
                        null,
                        $command->getPath() . '/customFields/' . $error['field'],
                        $customFields[$error['field']] ?? null,
                        null,
                        $error['code']
                    ));
                }
            }
        }

        if ($violations->count() > 0) {
            $event->getExceptions()->add(new WriteConstraintViolationException($violations));
        }
    }
}
