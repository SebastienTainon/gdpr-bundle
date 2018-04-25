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

namespace SuperBrave\GdprBundle\Tests\Serializer\Normalizer;

use Doctrine\Common\Collections\ArrayCollection;
use ReflectionClass;
use SuperBrave\GdprBundle\Annotation\AnnotationReader;
use SuperBrave\GdprBundle\Annotation\Export;
use SuperBrave\GdprBundle\Serializer\Normalizer\AnnotationNormalizer;
use SuperBrave\GdprBundle\Tests\AnnotatedMock;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Serializer;

/**
 * AnnotationNormalizerTest.
 *
 * @author Niels Nijens <nn@superbrave.nl>
 */
class AnnotationNormalizerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * The AnnotationNormalizer instance being tested.
     *
     * @var AnnotationNormalizer
     */
    private $normalizer;

    /**
     * The mock AnnotationReader instance.
     *
     * @var PHPUnit_Framework_MockObject_MockObject
     */
    private $annotationReaderMock;

    /**
     * The mock property accessor instance.
     *
     * @var PHPUnit_Framework_MockObject_MockObject
     */
    private $propertyAccessorMock;

    /**
     * Creates a new AnnotationNormalizer instance for testing.
     *
     * @return void
     */
    public function setUp()
    {
        $this->annotationReaderMock = $this->getMockBuilder(AnnotationReader::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->propertyAccessorMock = $this->getMockBuilder(PropertyAccessorInterface::class)
            ->getMock();

        $this->normalizer = new AnnotationNormalizer(
            $this->annotationReaderMock,
            Export::class,
            $this->propertyAccessorMock
        );
    }

    /**
     * Tests if constructing a new AnnotationNormalizer sets the instance properties.
     *
     * @return void
     */
    public function testConstruct()
    {
        $this->assertAttributeSame($this->annotationReaderMock, 'annotationReader', $this->normalizer);
        $this->assertAttributeSame(Export::class, 'annotationName', $this->normalizer);
        $this->assertAttributeSame($this->propertyAccessorMock, 'propertyAccessor', $this->normalizer);
    }

    /**
     * Tests if AnnotationNormalizer::supportsNormalization returns false
     * when the data is not an object.
     *
     * @return void
     */
    public function testSupportsNormalizationReturnsFalseWhenDataIsNotAnObject()
    {
        $this->annotationReaderMock->expects($this->never())
            ->method('getPropertiesWithAnnotation');

        $this->assertFalse($this->normalizer->supportsNormalization('no object'));
    }

    /**
     * Tests if AnnotationNormalizer::supportsNormalization returns false
     * when the AnnotationReader does not return annotation instances.
     *
     * @return void
     */
    public function testSupportsNormalizationReturnsFalseWhenAnnotationReaderDoesNotReturnAnnotations()
    {
        $this->annotationReaderMock->expects($this->once())
            ->method('getPropertiesWithAnnotation')
            ->with(
                $this->isInstanceOf(ReflectionClass::class),
                Export::class
            )
            ->willReturn(array());

        $this->assertFalse($this->normalizer->supportsNormalization(new AnnotatedMock()));
    }

    /**
     * Tests if AnnotationNormalizer::supportsNormalization returns true
     * when the AnnotationReader returns annotation instances.
     *
     * @return void
     */
    public function testSupportsNormalizationReturnsTrueWhenAnnotationReaderReturnsAnnotations()
    {
        $this->annotationReaderMock->expects($this->once())
            ->method('getPropertiesWithAnnotation')
            ->with(
                $this->isInstanceOf(ReflectionClass::class),
                Export::class
            )
            ->willReturn(array(
                'foo' => new Export(),
            ));

        $this->assertTrue($this->normalizer->supportsNormalization(new AnnotatedMock()));
    }

    /**
     * Tests if AnnotationNormalizer::normalize returns the expected array of a AnnotatedMock instance.
     *
     * @return void
     */
    public function testNormalize()
    {
        $annotationReader = new AnnotationReader();
        $propertyAccessor = PropertyAccess::createPropertyAccessor();

        $normalizer = new AnnotationNormalizer($annotationReader, Export::class, $propertyAccessor);

        $annotatedMock = new AnnotatedMock();

        $this->assertEquals(
            array(
                'foo' => 'bar',
                'baz' => 1,
                'qux' => array(),
                'quuxs' => new ArrayCollection(),
                'annotatedPropertyWithoutMethod' => 'Yes',
            ),
            $normalizer->normalize($annotatedMock)
        );
    }

    /**
     * Tests if AnnotationNormalizer::normalize returns the expected normalized data
     * for serialization through the Serializer.
     *
     * @return void
     */
    public function testNormalizeThroughSerializer()
    {
        $annotationReader = new AnnotationReader();
        $propertyAccessor = PropertyAccess::createPropertyAccessor();

        $normalizer = new AnnotationNormalizer($annotationReader, Export::class, $propertyAccessor);
        $encoder = new XmlEncoder('mock');

        $serializer = new Serializer(
            array($normalizer),
            array($encoder)
        );

        $data = new AnnotatedMock(new AnnotatedMock());

        $this->assertStringEqualsFile(
            __DIR__.'/../../Resources/xml/annotation_normalizer_result.xml',
            $serializer->serialize($data, 'xml')
        );
    }
}