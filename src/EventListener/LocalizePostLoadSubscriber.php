<?php

namespace App\EventListener;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class LocalizePostLoadSubscriber
 * @package App\EventListener
 */
class LocalizePostLoadSubscriber implements EventSubscriber
{
    /**
     * @var null|Request
     */
    private $requestStack;

    /**
     * LocalizePostLoadSubscriber constructor.
     * @param RequestStack $requestStack
     */
    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    /**
     * @return array
     */
    public function getSubscribedEvents()
    {
        return ['postLoad'];
    }

    /**
     * @param LifecycleEventArgs $event
     */
    public function postLoad(LifecycleEventArgs $event)
    {
        if (!$this->requestStack) {
            return;
        }

        $entity = $event->getEntity();
        $docReader = new AnnotationReader();
        $reflect = new \ReflectionClass($entity);

        foreach ($reflect->getProperties() as $property) {
            $propertyInfo = $docReader->getPropertyAnnotations($reflect->getProperty($property->getName()));
            if ($propertyType = reset($propertyInfo)) {
                if (isset($propertyType->type) && $propertyType->type == 'localize_string') {
                    $locale = $this->requestStack->getCurrentRequest()->getLocale();
                    $getter = sprintf('get%s', ucfirst($property->getName()));
                    $setter = sprintf('set%s', ucfirst($property->getName()));
                    $value = call_user_func([$entity, $getter]);
                    // show localize string with locale
                    call_user_func([$entity, $setter], isset($value[$locale]) ? $value[$locale] : '');
                }
            }
        }
    }
}
