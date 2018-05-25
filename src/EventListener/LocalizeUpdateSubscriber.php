<?php

namespace App\EventListener;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class LocalizeUpdateSubscriber
 * @package App\EventListener
 */
class LocalizeUpdateSubscriber implements EventSubscriber
{
    private $request;

    /**
     * LocalizeCreateSubscriber constructor.
     * @param RequestStack $requestStack
     */
    public function __construct(RequestStack $requestStack)
    {
        $this->request = $requestStack->getCurrentRequest();
    }

    public function getSubscribedEvents()
    {
        return array(
            'preUpdate'
        );
    }

    public function preUpdate(LifecycleEventArgs $event)
    {
        $entity = $event->getEntity();

        $locale = $this->request->getLocale();

        $docReader = new AnnotationReader();
        $reflect = new \ReflectionClass($entity);

        $changeSet = $event->getEntityManager()->getUnitOfWork()->getEntityChangeSet($entity);

        if (empty($changeSet)) {
            return;
        }

        $properties = $reflect->getProperties();

        foreach ($properties as $property)
        {
            $propertyInfo = $docReader->getPropertyAnnotations($reflect->getProperty($property->getName()));

            if ($propertyType = reset($propertyInfo)) {
                if (isset($propertyType->type) && $propertyType->type == 'localize_string') {

                    $changeByName = $changeSet[$property->getName()];

                    $oldValue = $changeByName[0];
                    $newValue = array_merge($oldValue, [$locale => $changeByName[1]]);

                    $setter = sprintf('set%s', ucfirst($property->getName()));

                    call_user_func([$entity, $setter], $newValue);
                }
            }
        }
    }
}