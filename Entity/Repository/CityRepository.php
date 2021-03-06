<?php

namespace Librinfo\CRMBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;

class CityRepository extends EntityRepository
{
    /**
     * @param string $field              the field to search by ("zip" or "city")
     * @param string $term               the search term
     * @param int $page                  1 based page index
     * @param int $items_per_page        max nb of items to retrieve
     * @param string|null $country_code  restrict the search to this country code (if not null)
     * @return array                     associative array with: items, total nb of items, last page
     */
    public function getAddressAutocompleteItems($field, $term, $page = 1, $items_per_page = 10, $country_code = null)
    {
        if ( !in_array($field, ['zip', 'city']) )
            return [];

        if ($page < 1)
            $page = 1; // 1 based index
        if ($items_per_page == 0)
            $items_per_page = 1; // avoid division by zero

        // Count results (before pagination)
        $query =  $this->createQueryBuilder('c')
            ->andWhere("c.$field LIKE :pattern")
            ->setParameter('pattern', '%'.$term.'%')
        ;
        if ($country_code)
            $query->andWhere('c.country_code = :country_code')->setParameter('country_code', $country_code);
        $countQuery = clone($query);
        $count = $countQuery->select('count(DISTINCT c.id) as cnt')
            ->getQuery()
            ->getSingleScalarResult()
        ;

        $lastPage = (int)ceil($count / $items_per_page);

        if ($page > $lastPage)
            $items = [];
        else {
            // get ids (with pagination and properly sorted)
            $idsQuery = clone($query);
            $idsQuery->select('c.id')
                ->setMaxResults($items_per_page)
                ->setFirstResult(($page-1) * $items_per_page)
            ;
            $orderBy = [
                'zip' => ['zip', 'city'],
                'city' => ['city'],
            ];
            foreach($orderBy[$field] as $ord)
                $idsQuery->addOrderBy("c.$ord");

            $ids = $idsQuery->getQuery()->getScalarResult();

            // get items
            $finalQuery = $this->createQueryBuilder('c')
                    ->andWhere('c.id IN (:ids)')->setParameter('ids', $ids);
            foreach($orderBy[$field] as $ord)
                $finalQuery->addOrderBy("c.$ord");
            $items = $finalQuery->getQuery()->getArrayResult();
        }

        return [
            'items' => $items,
            'count' => $count,
            'last_page' => $lastPage,
        ];
    }
}