<?php

namespace App\EventListener;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class LocalizeCreateSubscriber
 * @package App\EventListener
 */
class LocalizeCreateSubscriber implements EventSubscriber
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
            'prePersist'
        );
    }

    public function prePersist(LifecycleEventArgs $event)
    {
        $entity = $event->getEntity();
        $locale = $this->request->getLocale();

        $docReader = new AnnotationReader();
        $reflect = new \ReflectionClass($entity);

        $properties = $reflect->getProperties();

        foreach ($properties as $property)
        {
            $propertyInfo = $docReader->getPropertyAnnotations($reflect->getProperty($property->getName()));

            if ($propertyType = reset($propertyInfo)) {
                if (isset($propertyType->type) && $propertyType->type == 'localize_string') {
                    $getter = sprintf('get%s', ucfirst($property->getName()));
                    $setter = sprintf('set%s', ucfirst($property->getName()));

                    $value = [$locale => call_user_func([$entity, $getter])];
                    call_user_func([$entity, $setter], $value);
                }
            }
        }
    }
}