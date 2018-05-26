<?php

namespace App\EventListener;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class LocalizeCreateSubscriber
 * @package App\EventListener
 */
class LocalizeCreateSubscriber implements EventSubscriber
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
        return ['prePersist'];
    }

    /**
     * @param LifecycleEventArgs $event
     */
    public function prePersist(LifecycleEventArgs $event)
    {
        $entity = $event->getEntity();
        $docReader = new AnnotationReader();
        $reflect = new \ReflectionClass($entity);
        $properties = $reflect->getProperties();

        foreach ($properties as $property) {
            $propertyInfo = $docReader->getPropertyAnnotations($reflect->getProperty($property->getName()));

            if ($propertyType = reset($propertyInfo)) {
                if (isset($propertyType->type) && $propertyType->type == 'localize_string') {
                    $locale = $this->request->getLocale();
                    $getter = sprintf('get%s', ucfirst($property->getName()));
                    $setter = sprintf('set%s', ucfirst($property->getName()));

                    $value = [$locale => call_user_func([$entity, $getter])];
                    call_user_func([$entity, $setter], $value);
                }
            }
        }
    }
}