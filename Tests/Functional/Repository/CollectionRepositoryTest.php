<?php
/**
 * (c) Kitodo. Key to digital objects e.V. <contact@kitodo.org>
 *
 * This file is part of the Kitodo and TYPO3 projects.
 *
 * @license GNU General Public License version 3 or later.
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace Kitodo\Dlf\Tests\Functional\Repository;

use TYPO3\CMS\Extbase\Persistence\Generic\QueryResult;
use Kitodo\Dlf\Domain\Repository\CollectionRepository;
use Kitodo\Dlf\Tests\Functional\FunctionalTestCase;

class CollectionRepositoryTest extends FunctionalTestCase
{
    /**
     * @var CollectionRepository
     */
    protected $collectionRepository;

    public function setUp(): void
    {
        parent::setUp();

        $this->collectionRepository = $this->initializeRepository(
            CollectionRepository::class,
            20000
        );

        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/Repository/collections.csv');
    }

    /**
     *
     * @group find
     */
    public function canFindAllByUids(): void
    {
        $collections = $this->collectionRepository->findAllByUids([1101, 1102]);
        $this->assertNotNull($collections);
        $this->assertInstanceOf(QueryResult::class, $collections);

        $collectionsByLabel = [];
        foreach ($collections as $collection) {
            $collectionsByLabel[$collection->getLabel()] = $collection;
        }

        $this->assertArrayHasKey('Musik', $collectionsByLabel);
        $this->assertArrayHasKey('Collection with single document', $collectionsByLabel);
    }

    /**
     * @test
     * @group find
     */
    public function canGetCollectionForMetadata(): void
    {
        $collections = $this->collectionRepository->getCollectionForMetadata("20000");
        $this->assertNotNull($collections);
        $this->assertInstanceOf(QueryResult::class, $collections);

        $collectionsByLabel = [];
        foreach ($collections as $collection) {
            $collectionsByLabel[$collection->getLabel()] = $collection;
        }

        $this->assertArrayHasKey('Musik', $collectionsByLabel);
        $this->assertArrayHasKey('Collection with single document', $collectionsByLabel);
        $this->assertArrayHasKey('Geschichte', $collectionsByLabel);
        $this->assertArrayHasKey('Bildende Kunst', $collectionsByLabel);
    }

    /**
     * @param $settings
     * @return array
     */
    protected function findCollectionsBySettings($settings): array
    {
        $collections = $this->collectionRepository->findCollectionsBySettings($settings);
        $this->assertNotNull($collections);
        $this->assertInstanceOf(QueryResult::class, $collections);

        $collectionsByLabel = [];
        foreach ($collections as $collection) {
            $collectionsByLabel[$collection->getLabel()] = $collection;
        }

        return $collectionsByLabel;
    }

    /**
     * @test
     * @group find
     */
    public function canFindCollectionsBySettings(): void
    {
        $collectionsByLabel = $this->findCollectionsBySettings(['collections' => '1101, 1102']);
        $this->assertCount(2, $collectionsByLabel);
        $this->assertArrayHasKey('Collection with single document', $collectionsByLabel);
        $this->assertArrayHasKey('Musik', $collectionsByLabel);

        $collectionsByLabel = $this->findCollectionsBySettings(
            [
                'index_name' => ['Geschichte', 'collection-with-single-document'],
                'show_userdefined' => true
            ]
        );
        $this->assertCount(2, $collectionsByLabel);
        $this->assertArrayHasKey('Geschichte', $collectionsByLabel);
        $this->assertArrayHasKey('Collection with single document', $collectionsByLabel);

        $collectionsByLabel = $this->findCollectionsBySettings(['show_userdefined' => true]);
        $this->assertCount(4, $collectionsByLabel);
        $this->assertArrayHasKey('Musik', $collectionsByLabel);
        $this->assertArrayHasKey('Collection with single document', $collectionsByLabel);
        $this->assertArrayHasKey('Geschichte', $collectionsByLabel);
        $this->assertArrayHasKey('Bildende Kunst', $collectionsByLabel);
        $this->assertEquals(
            'Bildende Kunst, Collection with single document, Geschichte, Musik',
            implode(', ', array_keys($collectionsByLabel))
        );

        $collectionsByLabel = $this->findCollectionsBySettings(['show_userdefined' => false]);
        $this->assertCount(2, $collectionsByLabel);
        $this->assertArrayHasKey('Musik', $collectionsByLabel);
        $this->assertArrayHasKey('Collection with single document', $collectionsByLabel);

        $collectionsByLabel = $this->findCollectionsBySettings(['hideEmptyOaiNames' => true]);
        $this->assertCount(2, $collectionsByLabel);
        $this->assertArrayHasKey('Musik', $collectionsByLabel);
        $this->assertArrayHasKey('Collection with single document', $collectionsByLabel);

        $collectionsByLabel = $this->findCollectionsBySettings(
            [
                'hideEmptyOaiNames' => true,
                'show_userdefined' => true
            ]
        );
        $this->assertCount(3, $collectionsByLabel);
        $this->assertArrayHasKey('Musik', $collectionsByLabel);
        $this->assertArrayHasKey('Collection with single document', $collectionsByLabel);
        $this->assertArrayHasKey('Geschichte', $collectionsByLabel);

        $collectionsByLabel = $this->findCollectionsBySettings(
            [
                'hideEmptyOaiNames' => false,
                'show_userdefined' => true
            ]
        );
        $this->assertCount(4, $collectionsByLabel);
        $this->assertArrayHasKey('Musik', $collectionsByLabel);
        $this->assertArrayHasKey('Collection with single document', $collectionsByLabel);
        $this->assertArrayHasKey('Geschichte', $collectionsByLabel);
        $this->assertArrayHasKey('Bildende Kunst', $collectionsByLabel);

        $collectionsByLabel = $this->findCollectionsBySettings(
            [
                'collections' => '1101, 1102, 1103, 1104',
                'show_userdefined' => true,
                'hideEmptyOaiNames' => false,
                'index_name' => ['Geschichte', 'collection-with-single-document']
            ]
        );

        $this->assertCount(2, $collectionsByLabel);
        $this->assertArrayHasKey('Collection with single document', $collectionsByLabel);
        $this->assertArrayHasKey('Geschichte', $collectionsByLabel);
    }

    /**
     * @test
     * @group find
     */
    public function canGetIndexNameForSolr(): void
    {
        $indexName = $this->collectionRepository->getIndexNameForSolr(
            ['show_userdefined' => true, 'storagePid' => '20000'], 'history'
        );
        $result = $indexName->fetchAllAssociative();
        $this->assertEquals(1, $indexName->rowCount());
        $this->assertEquals('Geschichte', $result[0]['index_name']);
        $this->assertEquals('*:*', $result[0]['index_query']);
        $this->assertEquals('1103', $result[0]['uid']);

        $indexName = $this->collectionRepository->getIndexNameForSolr(
            ['show_userdefined' => false, 'storagePid' => '20000'], 'history'
        );
        $this->assertEquals(0, $indexName->rowCount());

        $indexName = $this->collectionRepository->getIndexNameForSolr(
            ['show_userdefined' => false, 'storagePid' => '20000'], 'collection-with-single-document'
        );
        $this->assertEquals(1, $indexName->rowCount());
        $this->assertEquals('collection-with-single-document', $indexName->fetchOne());
    }
}
