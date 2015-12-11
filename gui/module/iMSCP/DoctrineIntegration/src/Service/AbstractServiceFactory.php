<?php
/*
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the MIT license. For more information, see
 * <http://www.doctrine-project.org>.
 */

namespace iMSCP\DoctrineIntegration\Service;

use Zend\ServiceManager\AbstractFactoryInterface;
use Zend\ServiceManager\Exception\ServiceNotFoundException;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * Class AbstractServiceFactory
 *
 * Abstract service factory capable of instantiating services whose names match the
 * pattern doctrine_integration.$serviceType.$serviceName
 *
 * @license MIT
 * @link http://www.doctrine-project.org/
 * @author Marco Pivetta <ocramius@gmail.com>
 * @package iMSCP\DoctrineIntegration\Service
 */
class AbstractServiceFactory implements AbstractFactoryInterface
{
    /**
     * @var array|bool last mapping result
     */
    protected $lastMappingResult;

    /**
     * {@inheritdoc}
     */
    public function canCreateServiceWithName(ServiceLocatorInterface $serviceLocator, $name, $requestedName)
    {
        if (!($mappingResult = $this->getFactoryMapping($serviceLocator, $requestedName))) {
            return false;
        }

        $this->lastMappingResult = $mappingResult;
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function createServiceWithName(ServiceLocatorInterface $serviceLocator, $name, $requestedName)
    {
        $lastMappingResult = $this->lastMappingResult;
        unset($this->lastMappingResult);

        if (!$lastMappingResult) {
            throw new ServiceNotFoundException();
        }

        /** @var AbstractFactory $factory */
        $factory = new $lastMappingResult['factory']($lastMappingResult['name']);
        return $factory->createService($serviceLocator);
    }

    /**
     * Get mapping data for the given service
     *
     * @param ServiceLocatorInterface $serviceLocator
     * @param string $name Service name
     * @return null|array
     */
    private function getFactoryMapping(ServiceLocatorInterface $serviceLocator, $name)
    {
        $matches = [];

        if (!preg_match('/^doctrine_integration\.(?<type>[a-z0-9_]+)\.(?<name>[a-z0-9_]+)$/', $name, $matches)) {
            return null;
        }

        $config = $serviceLocator->get('Config');
        $type = $matches['type'];
        $name = $matches['name'];

        if (
            !isset($config['doctrine_integration_factories'][$type]) ||
            !isset($config['doctrine_integration'][$type][$name])
        ) {
            return null;
        }

        return [
            'name' => $name,
            'factory' => $config['doctrine_integration_factories'][$type],
        ];
    }
}
