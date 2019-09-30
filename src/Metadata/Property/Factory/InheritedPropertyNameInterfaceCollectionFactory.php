<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace ApiPlatform\Core\Metadata\Property\Factory;

use ApiPlatform\Core\Metadata\Property\PropertyNameCollection;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceNameCollectionFactoryInterface;

/**
 * Creates a property name collection from eventual child inherited properties.
 *
 * @author Antoine Bluchet <soyuka@gmail.com>
 */
final class InheritedPropertyNameInterfaceCollectionFactory implements PropertyNameCollectionFactoryInterface
{
    private $resourceNameCollectionFactory;
    private $decorated;
    private $resourceMetadata;

    public function __construct(ResourceNameCollectionFactoryInterface $resourceNameCollectionFactory, ResourceMetadataFactoryInterface $resourceMetadata, PropertyNameCollectionFactoryInterface $decorated = null)
    {
        $this->resourceNameCollectionFactory = $resourceNameCollectionFactory;
        $this->decorated = $decorated;
        $this->resourceMetadata = $resourceMetadata;
    }

    /**
     * {@inheritdoc}
     */
    public function create(string $resourceClass, array $options = []): PropertyNameCollection
    {
        $propertyNames = [];

        $resourceMetadata = $this->resourceMetadata->create($resourceClass);
        // Inherited from parent
        if ($this->decorated) {
            // InheritedPropertyNameCollectionFactory doesnt work for interfaces
            if (!$this->decorated instanceof InheritedPropertyNameCollectionFactory || !$resourceMetadata->isInterface()) {
                foreach ($this->decorated->create($resourceClass, $options) as $propertyName) {
                    $propertyNames[$propertyName] = (string) $propertyName;
                }
            }
        }

        if (!$resourceMetadata->isInterface()) {
            return new PropertyNameCollection(array_values($propertyNames));
        }

        foreach ($this->resourceNameCollectionFactory->create() as $knownResourceClass) {
            if ($resourceClass === $knownResourceClass) {
                continue;
            }

            if (is_subclass_of($resourceClass, $knownResourceClass)) {
                foreach ($this->create($knownResourceClass) as $propertyName) {
                    $propertyNames[$propertyName] = $propertyName;
                }
            }
        }

        return new PropertyNameCollection(array_values($propertyNames));
    }
}
