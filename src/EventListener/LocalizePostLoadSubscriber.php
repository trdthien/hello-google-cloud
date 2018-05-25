<?php

namespace App\EventListener;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class LocalizePostLoadSubscriber
 * @package App\EventListener
 */
class LocalizePostLoadSubscriber implements EventSubscriber
{
    private $request;

    /**
     * LocalizePostLoadSubscriber constructor.
     * @param RequestStack $requestStack
     */
    public function __construct(RequestStack $requestStack)
    {
        $this->request = $requestStack->getCurrentRequest();
    }

    public function getSubscribedEvents()
    {
        return array(
            'postLoad'
        );
    }

    public function postLoad(LifecycleEventArgs $event)
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
                    $value = call_user_func([$entity, $getter]);
                    // show localize string with locale
                    call_user_func([$entity, $setter], isset($value[$locale]) ? $value[$locale] : $value);
                }
            }
        }
    }
}