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

namespace SuperBrave\GdprBundle\Annotation;

use Doctrine\Common\Annotations\Annotation;

/**
 * Annotation to flag an entity field to be exported
 * to conform with the GDPR right of portability.
 *
 * @Annotation
 * @Annotation\Target({"PROPERTY"})
 *
 * @author Niels Nijens <nn@superbrave.nl>
 */
class Export
{
    /**
     * Use given name instead of column name in the exported data.
     *
     * @var string
     */
    public $fieldName;
}