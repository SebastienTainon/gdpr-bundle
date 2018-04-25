<?php
/**
 * This file is part of the GDPR bundle.
 *
 * @category  Bundle
 * @package   Gdpr
 * @author    SuperBrave <info@superbrave.nl>
 * @copyright 2018 SuperBrave <info@superbrave.nl>
 * @license   https://github.com/superbrave/gdpr-bundle/blob/master/LICENSE MIT
 * @link      https://www.superbrave.nl/
 */

namespace SuperBrave\GdprBundle\Serializer\Normalizer;

use ReflectionClass;
use ReflectionProperty;
use SuperBrave\GdprBundle\Annotation\AnnotationReader;
use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Normalizes object data based on the specified property annotation.
 *
 * @author Niels Nijens <nn@superbrave.nl>
 */
class AnnotationNormalizer implements NormalizerInterface
{
    /**
     * The AnnotationReader instance.
     *
     * @var AnnotationReader
     */
    private $annotationReader;

    /**
     * The FQCN of the annotation class.
     *
     * @var string
     */
    private $annotationName;

    /**
     * The property accessor instance.
     *
     * @var PropertyAccessorInterface
     */
    private $propertyAccessor;

    /**
     * Constructs a new AnnotationNormalizer instance.
     *
     * @param AnnotationReader          $annotationReader The AnnotationReader instance
     * @param string                    $annotationName   The FQCN of the annotation class
     * @param PropertyAccessorInterface $propertyAccessor The property accessor instance
     */
    public function __construct(
        AnnotationReader $annotationReader,
        $annotationName,
        PropertyAccessorInterface $propertyAccessor
    ) {
        $this->annotationReader = $annotationReader;
        $this->annotationName = $annotationName;
        $this->propertyAccessor = $propertyAccessor;
    }

    /**
     * Checks whether the given class is supported for normalization by this normalizer.
     *
     * @param mixed  $data   Data to normalize
     * @param string $format The format being (de-)serialized from or into
     *
     * @return bool
     */
    public function supportsNormalization($data, $format = null)
    {
        if (is_object($data) === false) {
            return false;
        }

        $propertyAnnotations = $this->annotationReader->getPropertiesWithAnnotation(
            new ReflectionClass($data),
            $this->annotationName
        );

        return count($propertyAnnotations) > 0;
    }

    /**
     * Normalizes an object into a set of arrays/scalars.
     *
     * @param object $object  Object to normalize
     * @param string $format  Format the normalization result will be encoded as
     * @param array  $context Context options for the normalizer
     *
     * @return array|string|int|float|bool
     */
    public function normalize($object, $format = null, array $context = array())
    {
        $normalizedData = array();
        $propertyAnnotations = $this->annotationReader->getPropertiesWithAnnotation(
            new ReflectionClass($object),
            $this->annotationName
        );

        foreach ($propertyAnnotations as $propertyName => $propertyAnnotation) {
            $propertyValue = $this->getPropertyValue($object, $propertyName);
            if (property_exists($propertyAnnotation, 'alias') && isset($propertyAnnotation->alias)) {
                $propertyName = $propertyAnnotation->alias;
            }

            $normalizedData[$propertyName] = $this->getMappedPropertyValue($propertyAnnotation, $propertyValue);
        }

        return $normalizedData;
    }

    /**
     * Returns the value of specified property through the getter of the object.
     *
     * @param object $object       The object being normalized
     * @param string $propertyName The property name where the value gotten from
     *
     * @return mixed
     */
    private function getPropertyValue($object, $propertyName)
    {
        try {
            $propertyData = $this->propertyAccessor->getValue($object, $propertyName);
        } catch (NoSuchPropertyException $exception) {
            $reflectionProperty = new ReflectionProperty($object, $propertyName);
            $reflectionProperty->setAccessible(true);

            $propertyData = $reflectionProperty->getValue($object);
        }

        return $propertyData;
    }

    /**
     * Returns the mapped value of a property or the original value when no value map is configured on the annotation.
     *
     * @param object $annotation    The annotation instance
     * @param mixed  $propertyValue The value of the property retrieved from the object
     *
     * @return mixed
     */
    private function getMappedPropertyValue($annotation, $propertyValue)
    {
        if (is_scalar($propertyValue) === false) {
            return $propertyValue;
        }

        if (property_exists($annotation, 'valueMap') === false || isset($annotation->valueMap) === false) {
            return $propertyValue;
        }

        $mappedPropertyValue = null;
        if (isset($annotation->valueMap[$propertyValue])) {
            $mappedPropertyValue = $annotation->valueMap[$propertyValue];
        }

        return $mappedPropertyValue;
    }
}