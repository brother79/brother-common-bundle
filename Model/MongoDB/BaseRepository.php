<?php
/**
 * Created by PhpStorm.
 * User: Андрей
 * Date: 22.10.2014
 * Time: 11:48
 */

namespace Brother\CommonBundle\Model\MongoDB;


use Brother\CommonBundle\AppDebug;
use Doctrine\Common\Cache\ArrayCache;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\DocumentRepository;
use Doctrine\ODM\MongoDB\UnitOfWork;
use Doctrine\ODM\MongoDB\Mapping;

class BaseRepository extends DocumentRepository {
    protected $buffer = array();

    /**
     * @var \Doctrine\Common\Cache\MemcacheCache|\Snc\RedisBundle\Doctrine\Cache\RedisCache
     */
    protected $cache = null;

    /**
     * @param DocumentManager       $dm
     * @param UnitOfWork            $uow
     * @param Mapping\ClassMetadata $class
     */
    public function __construct(DocumentManager $dm, UnitOfWork $uow, Mapping\ClassMetadata $class) {
        parent::__construct($dm, $uow, $class); // TODO: Change the autogenerated stub
        $cache = $dm->getConfiguration()->getMetadataCacheImpl();
        $this->cache = $cache ?: new ArrayCache();
    }

    /**
     * @param     $id
     * @param int $lifetime
     *
     * @return bool|mixed|null|string
     */
    public function getById($id, $lifetime = 7776000) {
        AppDebug::startWatch(__METHOD__);
        if ($id == null) {
            $object = null;
        } else {
            $object = $this->tryFetchFromCache($id, false);
            if (!$object || is_string($object) || is_numeric($object)) {
                try {
                    $object = $this->findOneLogged(array('_id' => new \MongoId((string)$id)));
                    $this->saveCache($id, $object, $lifetime);
                } catch (\MongoException $e) {
                    $object = null;
                }
            }
        }
        AppDebug::stopWatch(__METHOD__);
        return $object;
    }

    /**
     * @param $id
     *
     * @return bool|mixed|string
     */
    protected function tryFetchFromCache($id) {
        AppDebug::startWatch(__METHOD__);
        $t = memory_get_usage();
        $key = $this->generateCacheKey($id);
        if (!empty($this->buffer[$key])) {
            AppDebug::stopWatch(__METHOD__);
            return $this->buffer[$key];
        }
        if (!$object = $this->cache->fetch($key)) {
            AppDebug::stopWatch(__METHOD__);
            return null;
        }
        $d = memory_get_usage() - $t;
        if ($d > 20000000) {
            if (is_object($object)) {
                AppDebug::_d(get_class($object), $d);
            } elseif (is_array($object)) {
                AppDebug::_dx(count($object), $d);
            } else {
                AppDebug::_dx($object, $d);
            }
        }
        AppDebug::stopWatch(__METHOD__);
        return $object;
//        return $this->getDocumentManager()->merge($object);
    }

    /**
     * @param $id
     *
     * @return string
     */
    public function generateCacheKey($id) {
        return substr($this->getDocumentName(), strrpos($this->getDocumentName(), '\\') + 1) . '_' . (string)$id;
    }

    public function loadFromArray($row, $model = null, $fields = null) {
        if ($row == null) {
            return $model;
        }
        if ($model == null) {
            $class = $this->getClassName();
            $model = new $class();
        }
        if ($row instanceOf \MongoCursor) {
            $row->timeout(10000);
        }
        foreach ($row as $name => $value) {
            if ($fields != null && array_search($name, $fields) === false) {
                continue;
            }
            if (is_object($value)) {
                switch (get_class($value)) {
                    case 'MongoId':
                        break;
                    case 'MongoDate':
                        /* @var $value \MongoDate */
                        $d = new \DateTime();
                        $d->setTimestamp($value->sec);
                        $value = $d;
                        break;
                    default:
                        AppDebug::_dx(get_class($value));
                        break;
                }
            }
            if (!is_array($value) || empty($value['$ref'])) {
                $setter = 'set' . implode('', array_map('ucfirst', explode('_', trim($name, '_'))));
                $model->$setter($value);
            }
        }
        return $model;
    }

    /**
     * @return \MongoCollection
     */
    public function getMongoCollection() {
        return $this->getCollection()->getMongoCollection();
    }

    /**
     * @return \Doctrine\MongoDB\Collection
     */
    public function getCollection() {
        return $this->getDocumentManager()->getDocumentCollection($this->getDocumentName());
    }

    protected function saveCache($id, $object, $lifetime) {
        $key = $this->generateCacheKey($id);
        $this->buffer[$key] = $object;
        $this->cache->save($key, $object, $lifetime);
    }

    public function findBySlug($slug, $lifetime = 86400) {
        AppDebug::startWatch(__METHOD__);
        $object = $this->tryFetchFromCache($slug);
        if ($object == null || is_numeric($object) && $object == -1) {
            $collection = $this->getMongoCollection();
            $object = $this->findOneLogged(array('slug' => $slug));
            if ($object == null) {
                try {
                    $object = $this->findById($slug, $lifetime);
                } catch (\Exception $e) {
                    $object = null;
                }
            }
            if ($object && $slug != $object->getId()) {
                $this->saveCache($slug, $object->getId(), $lifetime);
            }
        } else {
            if (is_object($object)) {
                $object = $this->findById($object->getId());
            } else {
                $object = $this->findById($object);
            }
        }
        AppDebug::stopWatch(__METHOD__);
        return $object;
    }

    /**
     * Кэширует объект по ID
     *
     * @param     $id
     * @param int $lifetime
     *
     * @return bool|mixed|null|string
     */
    public function findById($id, $lifetime = 86400) {
        try {
            return $this->doFindById($id, array('_id' => new \MongoId((string)$id)), $lifetime);
        } catch (\Exception $e) {
            return null;
        }
    }

    protected function doFindById($id, $query, $lifetime) {
        if ($id == null) {
            return null;
        }
        $object = $this->tryFetchFromCache($id, false);
        if (!$object || is_string($object) || is_numeric($object)) {
            try {
                $object = $this->findOneLogged($query);
                $this->saveCache($id, $object, $lifetime);
            } catch (\MongoException $e) {
                return null;
            }
        }
        return $object;

    }


    /**
     * Кэширует запрос id по кэшу
     *
     * @param $id
     * @param $query
     * @param $lifetime
     *
     * @return bool|mixed|null|string
     */
    protected function doFindIdById($id, $query, $lifetime) {
        if ($id == null) {
            return null;
        }
        if ($object = $this->tryFetchFromCache($id, false)) {
            if (is_string($object) && !is_numeric($object)) {
                return $object;
            }
        }
        try {
            $object = $this->findOneLogged($query);
            /* @var $object \Sol\NewsBundle\Document\News */
            if ($object) {
                $this->saveCache($object->getId(), $object, $lifetime);
                $this->saveCache($id, $object->getId(), $lifetime);
                return $object->getId();
            }
            return null;
        } catch (\MongoException $e) {
            return null;
        }
    }

    public function findByIds($ids) {
        $r = array();
        foreach ($ids as $id) {
            if ($id && $entity = $this->findById($id)) {
                $r[] = $entity;
            }
        }
        return $r;
    }

    public function findByCache($query, $sort, $limit = 1000, $skip = 0, $options = array()) {
        AppDebug::startWatch(__METHOD__);

        $key = isset($options['key']) ? $options['key'] . '_' .
            md5(serialize($query)) . '_' . md5(serialize($sort)) . '_' . $limit . '_' . $skip : null;
        $lifeTimeMain = isset($options['lifetime_main']) ? $options['lifetime_main'] : 3600;
        $lifeTimeDetails = isset($options['lifetime_details']) ? $options['lifetime_details'] : 86400;
        if (!$key) {
            AppDebug::_dx($query);
            AppDebug::mongoLog(array(
                'collection' => $this->getCollection()->getName(),
                'find' => true,
                'query' => $query,
                'fields' => array('_id'),
//                'sort' => $sort,
                'limit' => $limit,
                'skip' => $skip
            ));
            $r = $this->getMongoCollection()
                ->find($query, array('_id'))
                ->sort($sort)->limit($limit)->skip($skip);
        } elseif (!$r = $this->tryFetchFromCache($key)) {
            AppDebug::mongoLog(array(
                'collection' => $this->getCollection()->getName(),
                'find' => true,
                'query' => $query,
                'fields' => array('_id'),
//                'sort' => $sort,
                'limit' => $limit,
                'skip' => $skip
            ));
            $r = iterator_to_array(
                $this->getMongoCollection()
                    ->find($query, array('_id'))
                    ->sort($sort)->limit($limit)->skip($skip)
            );
            $this->cache->save($this->generateCacheKey($key), $r ? $r : -1, $lifeTimeMain);
        }
        if (is_numeric($r) && -1 == $r) {
            AppDebug::stopWatch(__METHOD__);
            return array();
        }

        $result = array();
        foreach ($r as $row) {
            $entity = $this->findById((string)$row['_id'], $lifeTimeDetails);
            if ($entity) {
                $result[] = $entity;
            }
        }
        AppDebug::stopWatch(__METHOD__);
        return $result;
    }

    /**
     * @param $r \MongoCursor|array
     */
    public function clearCursorCache($r) {
        if (isset($r['_id'])) {
            $this->clearCache((string)$r['_id']);
        } elseif ($r) {
            foreach ($r as $row) {
                $this->clearCache((string)$row['_id']);
            }
        }
    }

    /**
     * @param      $id
     * @param null $slug
     */
    public function clearCache($id) {
        $key = $this->generateCacheKey($id);
        if (!empty($this->buffer[$key])) {
            unset($this->buffer[$key]);
        }
        $this->cache->delete($key);
    }

    /**
     * @param      $c \MongoCursor
     * @param null $fields
     *
     * @return array
     */
    public function loadFromCursor($c, $fields = null) {
        $r = array();
        foreach ($c as $row) {
            $r[] = $this->loadFromArray($row, null, $fields);
        }
        return $r;
    }

    public function removeField($id, $field) {
        $this->getMongoCollection()->update(
            array('_id' => new \MongoId((string)$id)),
            array('$unset' => array($field => true))
        );
    }

    public function findBy(array $criteria, array $sort = null, $limit = null, $skip = null) {
        if (isset($criteria['id'][0])) {
            $criteria['id'] = array('$in' => $criteria['id']);
        }
        return parent::findBy($criteria, $sort, $limit, $skip); // TODO: Change the autogenerated stub
    }

    public function getIdByQuery($query) {
        $key = md5(json_encode($query));
        return $this->tryFetchFromCache($key, false);
    }

    public function setIdByQuery($query, $id, $lifeTime) {
        $key = md5(json_encode($query));
        $this->cache->save($this->generateCacheKey($key), $id, $lifeTime);
    }

    public function getOneByCacheId($query) {
        $id = $this->doFindIdById(md5(json_encode($query)), $query, 86400);
        if ($id) {
            return $this->findById($id);
        } else {
            return null;
        }
    }

    private function findOneLogged($query) {
        AppDebug::mongoLog(array(
            'collection' => $this->getCollection()->getName(),
            'findOne' => true,
            'query' => $query,
            'fields' => null
        ));
        return $this->loadFromArray($this->getMongoCollection()->findOne($query));
    }
} 