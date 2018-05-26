<?php

namespace App\EventListener;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class LocalizeUpdateSubscriber
 * @package App\EventListener
 */
class LocalizeUpdateSubscriber implements EventSubscriber
{
    /**
     * @var null|Request
     */
    private $request;

    /**
     * LocalizeCreateSubscriber constructor.
     * @param RequestStack $requestStack
     */
    public function __construct(RequestStack $requestStack)
    {
        $this->request = $requestStack->getCurrentRequest();
    }

    /**
     * @return array
     */
    public function getSubscribedEvents()
    {
        return ['preUpdate'];
    }

    /**
     * @param LifecycleEventArgs $event
     */
    public function preUpdate(LifecycleEventArgs $event)
    {
        $entity = $event->getEntity();
        $docReader = new AnnotationReader();
        $reflect = new \ReflectionClass($entity);

        $changeSet = $event->getEntityManager()->getUnitOfWork()->getEntityChangeSet($entity);

        if (empty($changeSet)) {
            return;
        }

        foreach ($reflect->getProperties() as $property) {
            $propertyInfo = $docReader->getPropertyAnnotations($reflect->getProperty($property->getName()));
            if ($propertyType = reset($propertyInfo)) {
                if (isset($propertyType->type) && $propertyType->type == 'localize_string') {
                    $locale = $this->request->getLocale();
                    $changeByName = $changeSet[$property->getName()];
                    $oldValue = $changeByName[0];
                    $newValue = array_merge($oldValue, [$locale => $changeByName[1]]);
                    $setter = sprintf('set%s', ucfirst($property->getName()));
                    // override entity localize value
                    call_user_func([$entity, $setter], $newValue);
                }
            }
        }
    }
}
