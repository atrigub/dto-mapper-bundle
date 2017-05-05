<?php

namespace NSM\Bundle\DTOMapperBundle\ApiDoc\Parser;

use NSM\Bundle\DTOMapperBundle\ApiDoc\ApiDocAwareInterface;
use NSM\Mapper\Mapper;

/**
 * Class DTOParser
 *
 * @package App\Bundle\BaseBundle\ApiDoc\Parser
 */
class DTOParser
{
    const INTEGER = 'integer';
    const FLOAT = 'float';
    const STRING = 'string';
    const BOOLEAN = 'boolean';
    const COLLECTION = 'collection';
    const DATE = 'date';
    const DATETIME = 'datetime';

    /**
     * @var Mapper
     */
    private $mapper;

    /** @var array */
    private $typeMap = [
        'integer' => self::INTEGER,
        'string' => self::STRING,
        'boolean' => self::BOOLEAN,
        'float' => self::FLOAT,
        'array' => self::COLLECTION,
        'date' => self::DATE,
        'datetime' => self::DATETIME,
    ];

    /**
     * DTOParser constructor.
     *
     * @param Mapper $mapper
     */
    public function __construct(Mapper $mapper)
    {
        $this->mapper = $mapper;
    }

    /**
     * {@inheritdoc}
     */
    public function supports(array $item): bool
    {
        return
            !is_null($item['class'])
            && $this->mapper->hasDestinationType($item['class'])
            || is_subclass_of($item['class'], ApiDocAwareInterface::class);
    }

    /**
     * {@inheritdoc}
     */
    public function parse(array $item): array
    {
        $class = new \ReflectionClass($item['class']);
        $props = array_keys($class->getDefaultProperties());

        $result = [];
        foreach ($props as $propName) {
            $prop = new \ReflectionProperty($item['class'], $propName);
            $propertyNameNormalized = strtolower(preg_replace('/[A-Z]/', '_\\0', $prop->getName()));
            $annotations = AnnotationParser::getAnnotations($prop->getDocComment());

            if (!array_key_exists('api', $annotations)) {
                continue;
            }

            $dataType = $this->buildDataType($annotations['api'], $propName);

            if (false === $dataType['primitive'] && isset($dataType['class'])) {
                $visited[] = $dataType['class'];
                $children = $this->parse($dataType);

                if ($dataType['inline']) {
                    $result = array_merge($result, $children);
                } else {
                    $dataType['children'] = $children;
                }
            }

            $result[$propertyNameNormalized] = $dataType;
        }

        return $result;
    }

    /**
     * @param array  $annotation
     * @param string $field
     *
     * @return array
     */
    public function buildDataType($annotation, $field): array
    {
        $type = $annotation['type'] ?? null;

        $dataType = [
            'required' => array_key_exists('required', $annotation) ? $annotation['required'] : false,
            'description' => array_key_exists('description', $annotation) ? $annotation['description'] : '',
            'readonly' => array_key_exists('readonly', $annotation) ? $annotation['readonly'] : false,
            'primitive' => $this->isPrimitive($type),
        ];

        if (!$this->isPrimitive($type) && class_exists($type)) {
            $exp = explode("\\", $type);
            $dataType = array_merge(
                $dataType,
                [
                    'normalized' => sprintf("object (%s)", end($exp)),
                    'class' => $type,
                    'subType' => $type,
                    'actualType' => DataTypes::MODEL,
                    'inline' => false,
                ]
            );
        }

        if ($this->isPrimitive($type)) {
            $dataType['dataType'] = array_key_exists('type', $annotation) ? $this->typeMap[$annotation['type']] : null;
        } elseif ($type == 'custom') {
            $dataType['dataType'] = sprintf('custom handler result for (%s)', $field);
        }

        return $dataType;
    }

    /**
     * @param string $type
     *
     * @return bool
     */
    private function isPrimitive($type): bool
    {
        return in_array($type, ['boolean', 'integer', 'string', 'float', 'double', 'array', 'DateTime']);
    }
}
