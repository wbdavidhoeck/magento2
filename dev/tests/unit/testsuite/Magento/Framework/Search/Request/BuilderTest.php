<?php
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * @copyright   Copyright (c) 2014 X.commerce, Inc. (http://www.magentocommerce.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
namespace Magento\Framework\Search\Request;

use Magento\TestFramework\Helper\ObjectManager;

class BuilderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Magento\Framework\Search\Request\Builder
     */
    private $requestBuilder;

    /**
     * @var \Magento\Framework\ObjectManagerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $objectManager;

    /**
     * @var \Magento\Framework\Search\Request\Config|\PHPUnit_Framework_MockObject_MockObject
     */
    private $config;

    /**
     * @var \Magento\Framework\Search\Request\Mapper|\PHPUnit_Framework_MockObject_MockObject
     */
    private $requestMapper;

    /**
     * @var \Magento\Framework\Search\Request|\PHPUnit_Framework_MockObject_MockObject
     */
    private $request;

    /**
     * @var \Magento\Framework\Search\Request\Binder|\PHPUnit_Framework_MockObject_MockObject
     */
    private $binder;

    /**
     * @var \Magento\Framework\Search\Request\Cleaner|\PHPUnit_Framework_MockObject_MockObject
     */
    private $cleaner;

    protected function setUp()
    {
        $helper = new ObjectManager($this);

        $this->config = $this->getMockBuilder('Magento\Framework\Search\Request\Config')
            ->setMethods(['get'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->objectManager = $this->getMock('Magento\Framework\ObjectManagerInterface');

        $this->requestMapper = $this->getMockBuilder('Magento\Framework\Search\Request\Mapper')
            ->setMethods(['getRootQuery', 'getBuckets'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->request = $this->getMockBuilder('Magento\Framework\Search\Request')
            ->disableOriginalConstructor()
            ->getMock();

        $this->binder = $this->getMockBuilder('Magento\Framework\Search\Request\Binder')
            ->setMethods(['bind'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->cleaner = $this->getMockBuilder('Magento\Framework\Search\Request\Cleaner')
            ->setMethods(['clean'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->requestBuilder = $helper->getObject(
            'Magento\Framework\Search\Request\Builder',
            [
                'config' => $this->config,
                'objectManager' => $this->objectManager,
                'binder' => $this->binder,
                'cleaner' => $this->cleaner
            ]
        );
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testCreateInvalidArgumentExceptionNotDefined()
    {
        $this->requestBuilder->create();
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Request name 'rn' doesn't exist.
     */
    public function testCreateInvalidArgumentException()
    {
        $requestName = 'rn';

        $this->requestBuilder->setRequestName($requestName);
        $this->config->expects($this->once())->method('get')->with($this->equalTo($requestName))->willReturn(null);

        $this->requestBuilder->create();
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testCreate()
    {
        $data = [
            'dimensions' => [
                'scope' => [
                    'name' => 'scope',
                    'value' => 'default',
                ],
            ],
            'queries' => [
                'one_match_filters' => [
                    'name' => 'one_match_filters',
                    'boost' => '2',
                    'queryReference' => [
                        [
                            'clause' => 'must',
                            'ref' => 'fulltext_search_query',
                        ],
                        [
                            'clause' => 'must',
                            'ref' => 'fulltext_search_query2',
                        ],
                    ],
                    'type' => 'boolQuery',
                ],
                'fulltext_search_query' => [
                    'name' => 'fulltext_search_query',
                    'boost' => '5',
                    'value' => '$fulltext_search_query$',
                    'match' => [
                        [
                            'field' => 'data_index',
                            'boost' => '2',
                        ],
                    ],
                    'type' => 'matchQuery',
                ],
                'fulltext_search_query2' => [
                    'name' => 'fulltext_search_query2',
                    'filterReference' => [
                        [
                            'ref' => 'pid',
                        ],
                    ],
                    'type' => 'filteredQuery',
                ],
            ],
            'filters' => [
                'pid' => [
                    'name' => 'pid',
                    'filterReference' => [
                        [
                            'clause' => 'should',
                            'ref' => 'pidm',
                        ],
                        [
                            'clause' => 'should',
                            'ref' => 'pidsh',
                        ],
                    ],
                    'type' => 'boolFilter',
                ],
                'pidm' => [
                    'name' => 'pidm',
                    'field' => 'product_id',
                    'type' => 'rangeFilter',
                    'from' => '$pidm_from$',
                    'to' => '$pidm_to$'
                ],
                'pidsh' => [
                    'name' => 'pidsh',
                    'field' => 'product_id',
                    'type' => 'termFilter',
                    'value' => '$pidsh$'
                ],
            ],
            'from' => '10',
            'size' => '10',
            'query' => 'one_match_filters',
            'index' => 'catalogsearch_fulltext',
            'aggregations' => [],
        ];
        $requestName = 'rn';
        $this->requestBuilder->bind('fulltext_search_query', 'socks');
        $this->requestBuilder->bind('pidsh', 4);
        $this->requestBuilder->bind('pidm_from', 1);
        $this->requestBuilder->bind('pidm_to', 3);
        $this->requestBuilder->setRequestName($requestName);
        $this->requestBuilder->setSize(10);
        $this->requestBuilder->setFrom(10);
        $this->requestBuilder->bindDimension('scope', 'default');
        $this->binder->expects($this->once())->method('bind')->willReturn($data);
        $this->cleaner->expects($this->once())->method('clean')->willReturn($data);
        $this->requestMapper->expects($this->once())->method('getRootQuery')->willReturn([]);
        $this->objectManager->expects($this->at(0))->method('create')->willReturn($this->requestMapper);
        $this->objectManager->expects($this->at(2))->method('create')->willReturn($this->request);
        $this->config->expects($this->once())->method('get')->with($this->equalTo($requestName))->willReturn($data);
        $result = $this->requestBuilder->create();
        $this->assertInstanceOf('\Magento\Framework\Search\Request', $result);
    }
}