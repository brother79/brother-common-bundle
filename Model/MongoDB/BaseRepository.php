<?php
/**
 * Created by PhpStorm.
 * User: Андрей
 * Date: 22.10.2014
 * Time: 11:48
 */

namespace Brother\CommonBundle\Model\MongoDB;


use App\Document\Sol\News;
use Brother\CommonBundle\AppDebug;
use Brother\CommonBundle\Cache\BrotherCacheProvider;
use Brother\CommonBundle\Route\AppRouteAction;
use DateTime;
use Doctrine\Common\Cache\MemcacheCache;
use Doctrine\Common\Cache\PredisCache;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Mapping;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\MongoDBException;
use Doctrine\ODM\MongoDB\Repository\DocumentRepository;
use Doctrine\ODM\MongoDB\UnitOfWork;
use Exception;
use MongoCursor;
use MongoCursorTimeoutException;
use MongoDate;
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;
use MongoDB\Collection;
use MongoException;

class BaseRepository extends DocumentRepository
{

    /**
     * Буфер для хранения текущих данных
     *
     * @var array
     */
    protected $cacheBuffer = [];

    protected $cache = null;

    /**
     * @var BrotherCacheProvider|MemcacheCache|PredisCache
     */
    protected $cacheManager = null;

    protected $cachePrefix = null;

    /**
     * @param DocumentManager $dm
     * @param UnitOfWork $uow
     * @param ClassMetadata $class
     */
    public function __construct(DocumentManager $dm, UnitOfWork $uow, ClassMetadata $class)
    {
        parent::__construct($dm, $uow, $class);
        $this->cachePrefix = substr($this->getDocumentName(), strrpos($this->getDocumentName(), '\\') + 1) . ':';
    }

    /**
     * @param string $name
     *
     * @return BrotherCacheProvider
     * @throws Exception
     */
    protected function getCacheManager($name = 'brother_cache')
    {
        if (!$this->cacheManager) {
            $this->cacheManager = AppRouteAction::getContainer($name);
            $this->cache = $this->cacheManager;
        }
        if (!$this->cacheManager) {
            throw new Exception('Добавить в контроллер AppRouteAction::setContainer($this->container);', 10300);
        }
        return $this->cacheManager;
    }

    /**
     * @param     $id
     * @param int $lifetime
     *
     * @return bool|mixed|null|string
     */
    public function getById($id, $lifetime = 7776000)
    {
        return $this->findById($id, $lifetime);
//        if ($id == null) {
//            $object = null;
//        } else {
//            $object = $this->tryFetchFromCache($id);
//            if (!$object || is_string($object) || is_numeric($object)) {
//                try {
//                    $object = $this->findOneLogged(array('_id' => new \MongoDB\BSON\ObjectId((string)$id)));
//                    $this->saveCache($id, $object, $lifetime);
//                } catch (\MongoException $e) {
//                    $object = null;
//                }
//            }
//        }
//        return $object;
    }

    /**
     * @param $id
     *
     * @return bool|mixed|string
     * @throws Exception
     */
    public function tryFetchFromCache($id)
    {
        $t = memory_get_usage();
        $key = $this->generateCacheKey($id);
        if (!empty($this->cacheBuffer[$key])) {
            return $this->cacheBuffer[$key];
        }
        if (!$object = $this->getCacheManager()->fetch($key)) {
            return null;
        }
        $this->cacheBuffer[$key] = $object;
        if (is_object($object)) {
            $this->getDocumentManager()->getUnitOfWork()->registerManaged($object, $object->getId(), []);
        } elseif (is_array($object)) {
            foreach ($object as $item) {
                if (is_object($item)) {
                    $this->getDocumentManager()->getUnitOfWork()->registerManaged($item, $item->getId(), []);
                }
            }
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
        return $object;
//        return $this->getDocumentManager()->merge($object);
    }

    /**
     * @param $id
     *
     * @return string
     */
    public function generateCacheKey($id)
    {
        return $this->cachePrefix . (string)$id;
    }

    /**
     * @param      $row
     * @param null $model
     * @param null $fields
     *
     * @return mixed|null
     * @throws MongoCursorTimeoutException
     */
    public function loadFromArray($row, $model = null, $fields = null)
    {
        if ($row == null || isset($row['$err'])) {
            return $model;
        }
        if ($model == null) {
            $class = $this->getClassName();
            $model = new $class();
        }
        if ($row instanceof MongoCursor) {
            $row->timeout(10000);
        }
        foreach ($row as $name => $value) {
            /** @var UTCDateTime $value */
            if ($fields != null && array_search($name, $fields) === false) {
                continue;
            }
            if (is_object($value)) {
                switch (get_class($value)) {
                    case 'MongoDB\BSON\ObjectId':
                        break;
                    case 'MongoDB\BSON\UTCDateTime':
                        $value = $value->toDateTime();
                        break;
                    case 'MongoDate':
                        /* @var $value MongoDate */
                        $d = new DateTime();
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
        $this->getDocumentManager()->getUnitOfWork()->registerManaged($model, $model->getId(), []);
        return $model;
    }

    /**
     * @param array $options
     *
     * @param array $query
     *
     * @return Collection
     * @throws MongoDBException
     */
    public function getMongoCollection($options = [], $query = [])
    {
        if (isset($options['collection'])) {
            return $options['collection'];
        }
        if (isset($options['collectionName'])) {
            $collectionName = $options['collectionName'];
            return $this->getCollection($collectionName);
        }
        return $this->getCollection();
    }

    /**
     * @param null $documentName
     *
     * @return Collection
     * @throws MongoDBException
     */
    public function getCollection($documentName = null)
    {
        return $this->getDocumentManager()->getDocumentCollection($documentName ?: $this->getDocumentName());
    }

    /**
     * @param $id
     * @param $object
     * @param $lifetime
     *
     * @throws Exception
     */
    public function saveCache($id, $object, $lifetime)
    {
        $key = $this->generateCacheKey($id);
        $this->cacheBuffer[$key] = $object;
        $this->getCacheManager()->save($key, $object, $lifetime);
    }

    /**
     * @param     $slug
     * @param int $lifetime
     *
     * @return bool|mixed|null|string
     * @throws MongoDBException
     * @throws Exception
     */
    public function findBySlug($slug, $lifetime = 2592000)
    {
        $object = $this->tryFetchFromCache($slug);
        if ($object == null || is_numeric($object) && $object == -1) {
            $object = $this->findOneLogged(['slug' => $slug], []);
            if ($object == null && strpos($slug, '_') === false) {
                try {
                    $object = $this->findById($slug, $lifetime);
                } catch (Exception $e) {
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
    public function findById($id, $lifetime = 86400)
    {
        try {
            if (preg_match('/^[\dabcdef]+$/i', (string)$id)) {
                return $this->doFindById($id, ['_id' => new ObjectId((string)$id)], $lifetime);
            } else {
                return null;
            }
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * @param $id
     * @param $query
     * @param $lifetime
     *
     * @return array|bool|mixed|string|null
     * @throws MongoDBException
     */
    protected function doFindById($id, $query, $lifetime)
    {
        if (is_array($lifetime)) {
            $options = $lifetime;
            $lifetime = isset($options['lifetime']) ? $options['lifetime'] : null;
        } else {
            $options = [];
        }
        if ($id == null) {
            return null;
        }
        $object = $this->tryFetchFromCache('__:' . $id);
        if (is_object($object)) {
            $this->getDocumentManager()->getUnitOfWork()->registerManaged($object, $object->getId(), []);
        }

        if (!$object || is_string($object) || is_numeric($object)) {
            try {
                $object = $this->findOneLogged($query, $options);
                $this->saveCache('__:' . $id, $object, $lifetime);
            } catch (MongoException $e) {
                return null;
            }
        }
        return $object;

    }


    /**
     * Кэширует запрос id по кэшу
     *
     * @param       $id
     * @param       $query
     * @param       $lifetime
     *
     * @param array $options
     *
     * @return bool|mixed|null|string
     * @throws MongoDBException
     * @throws Exception
     */
    protected function doFindIdById($id, $query, $lifetime, $options = [])
    {
        if ($id == null) {
            return null;
        }
        if ($object = $this->tryFetchFromCache($id)) {
            if (is_string($object) && !is_numeric($object)) {
                return $object;
            }
        }
        try {
            $object = $this->findOneLogged($query, $options);
            /* @var $object News */
            if ($object) {
                $this->saveCache($object->getId(), $object, $lifetime);
                $this->saveCache($id, $object->getId(), $lifetime);
                return $object->getId();
            }
            return null;
        } catch (MongoException $e) {
            return null;
        }
    }

    /**
     * @param       $ids
     * @param array $options
     *
     * @return array
     * @throws Exception
     */
    public function findByIds($ids, $options = [])
    {
//        try {
        $keys = [];
        foreach ($ids as $id) {
            if ($id) {
                $keys[$id] = $this->generateCacheKey('__:' . $id);
            }
        }
        $r = [];
        foreach ($keys as $k => $key) {
            if (!empty($this->cacheBuffer[$key])) {
                $r[$k] = $this->cacheBuffer[$key];
                unset($keys[$k]);
            }
        }
        $rr = $this->getCacheManager()->fetchMultiple(array_values($keys));
        foreach ($rr as $k => $item) {
            /** @var DocumentInterface $item */
            $this->cacheBuffer[$k] = $item;
            $id = $item && is_object($item) ? (string)$item->getId() : null;
            if ($id && isset($keys[$id])) {
                unset($keys[$id]);
                $r[$id] = $item;
                if (is_object($item)) {
                    $this->getDocumentManager()->getUnitOfWork()->registerManaged($item, $item->getId(), []);
                }
            }
            $ki = array_search($k, $keys);
            if ($ki !== false) {
                unset($keys[$ki]);
            }
        }
        foreach ($keys as $k => $key) {
            $r[$k] = $this->findById($k, $options);
        }
        $result = [];
        foreach ($ids as $id) {
            if ($id && isset($r[$id])) {
                $result[] = $r[$id];
            }
        }
        return $result;
//        }catch (\Exception $e) {
//            AppDebug::_dx($e->getTraceAsString());
//            return $result;
//        }
    }

    /**
     * @param       $query
     * @param array $options
     *
     * @return int
     * @throws MongoDBException
     */
    protected function doCountByParams($query, $options = [])
    {
        $collection = $this->getMongoCollection($options, $query);
        $this->mongoLog([
            'collection' => $collection->getCollectionName(),
            'find' => true,
            'query' => $query,
            'fields' => ['_id'],
        ]);
        $r = $collection->countDocuments($query);
        AppDebug::mongoLogEnd();
        return $r;
    }

    /**
     * @param       $query
     * @param       $sort
     * @param int $limit
     * @param int $skip
     * @param array $options
     *
     * @return array
     * @throws MongoDBException
     */
    protected function doFindByParams($query, $sort, $limit = 1000, $skip = 0, &$options = [])
    {
        $collection = $this->getMongoCollection($options, $query);
        $this->mongoLog([
            'collection' => $collection->getCollectionName(),
            'find' => true,
            'query' => $query,
            'fields' => ['_id'],
//                'sort' => $sort,
            'limit' => $limit,
            'skip' => $skip
        ]);
//        $field = isset($options['idField']) ? $options['idField'] : '_id';
        $r = $collection->find($query, ['sort' => $sort, 'skip' => $skip, 'limit' => $limit]);
//        AppDebug::_dx([$skip, $limit, $query, $sort, $collection->getCollectionName(), $r, $r->toArray()]);
//        $r = $collection->find($query, ['_id' => 1, $field => 1], ['sort' => $sort])->skip($skip)->limit($limit);
        AppDebug::mongoLogEnd();
        return $r->toArray();
    }

    /**
     * @param       $query
     * @param       $sort
     * @param int $limit
     * @param int $skip
     * @param array $options
     *
     * @return array
     * @throws Exception
     */
    public function findByCache($query, $sort, $limit = 1000, $skip = 0, $options = [])
    {
        if (isset($options['key'])) {
            if (empty($options['controlled'])) {
                $key = $options['key'] . '_' . md5(serialize($query)) . '_' . md5(serialize($sort)) . '_' . $limit . '_' . $skip;
                if (!isset($options['controlled'])) {
                    AppDebug::_dx($options);
                }
            } else {
                $key = $options['key'];
            }
        } else {
            $key = null;
        }
        $lifeTimeMain = isset($options['lifetime_main']) ? $options['lifetime_main'] : 3600;
//        $lifeTimeDetails = isset($options['lifetime_details']) ? $options['lifetime_details'] : 86400;
        $idField = isset($options['idField']) ? $options['idField'] : '_id';
        if (!$key) {
            if (!isset($options['controlled'])) {
                AppDebug::_dx([$query, $options]);
            }
            $r = $this->doFindByParams($query, $sort, $limit, $skip, $options);
        } elseif (!$r = $this->tryFetchFromCache($key)) {
            /** @var MongoCursor $r */
            $r = $this->doFindByParams($query, $sort, $limit, $skip, $options);
            if ($r) {
                if ($r instanceof MongoCursor) {
                    $r = iterator_to_array($r);
                }
                $r = array_map(function ($a) use ($idField) {
                    if ('_id' != $idField && isset($a[$idField])) {
                        return [$idField => (string)$a[$idField]];
                    }
                    return ['_id' => (string)$a['_id']];
                }, $r);
            }
            $this->getCacheManager()->save($this->generateCacheKey($key), $r ? $r : -1, $lifeTimeMain);
        }
        if (is_numeric($r) && -1 == $r) {
            return [];
        }

        $ids = [];
        if ($r) {
            foreach ($r as $row) {
                $ids[] = (string)(isset($row[$idField]) ? $row[$idField] : $row['_id']);
            }
        }
        $result = $this->findByIds($ids, $options);
        if (count($result) == 0 && count($ids) > 0) {
            $this->getCacheManager()->delete($this->generateCacheKey($key));
        }
        return $result;
    }

    /**
     * @param       $query
     * @param array $options
     *
     * @return array|bool|int|mixed|string|null
     * @throws Exception
     */
    public function countByCache($query, $options = [])
    {
        if (isset($options['key'])) {
            if (empty($options['controlled'])) {
                $key = $options['key'] . '_count_' . md5(serialize($query));
                if (!isset($options['controlled'])) {
                    AppDebug::_dx($options);
                }
            } else {
                $key = $options['key'];
            }
        } else {
            $key = null;
        }
        $lifeTimeMain = isset($options['lifetime_main']) ? $options['lifetime_main'] : 86400;
        if (!$key) {
            if (!isset($options['controlled'])) {
                AppDebug::_dx([$query, $options]);
            }
            $r = $this->doCountByParams($query, $options);
        } elseif (!$r = $this->tryFetchFromCache($key)) {
            $r = $this->doCountByParams($query, $options);
            $this->getCacheManager()->save($this->generateCacheKey($key), $r ? $r : -1, $lifeTimeMain);
        }
        return $r;
    }

    /**
     * @param $r MongoCursor|array
     *
     * @throws Exception
     */
    public function clearCursorCache($r)
    {
        if (isset($r['_id'])) {
            $this->clearCache((string)$r['_id']);
        } elseif ($r) {
            foreach ($r as $row) {
                $this->clearCache((string)$row['_id']);
            }
        }
    }

    /**
     * @param $id
     *
     * @throws Exception
     */
    public function clearCache($id)
    {
        $key = $this->generateCacheKey($id);
        if (!empty($this->cacheBuffer[$key])) {
            unset($this->cacheBuffer[$key]);
        }
        $this->getCacheManager()->delete($key);

        $key = $this->generateCacheKey('__:' . $id);
        if (!empty($this->cacheBuffer[$key])) {
            unset($this->cacheBuffer[$key]);
        }
        $this->getCacheManager()->delete($key);
    }

    /**
     * @param      $c MongoCursor
     * @param null $fields
     *
     * @return array
     * @throws MongoCursorTimeoutException
     */
    public function loadFromCursor($c, $fields = null)
    {
        $r = [];
        foreach ($c as $row) {
            $r[] = $this->loadFromArray($row, null, $fields);
        }
        return $r;
    }

    /**
     * @param $id
     * @param $field
     *
     * @throws MongoDBException
     */
    public function removeField($id, $field)
    {
        $this->getMongoCollection()->updateOne(['_id' => new ObjectId((string)$id)], ['$unset' => [$field => true]]);
    }

    /**
     * @param array $criteria
     * @param array|null $sort
     * @param null $limit
     * @param null $skip
     *
     * @return array
     */
    public function findBy(array $criteria, array $sort = null, $limit = null, $skip = null): array
    {
        if (isset($criteria['id'][0])) {
            $criteria['id'] = ['$in' => $criteria['id']];
        }
        return parent::findBy($criteria, $sort, $limit, $skip);
    }

    /**
     * @param $query
     *
     * @return array|bool|mixed|string|null
     * @throws Exception
     */
    public function getIdByQuery($query)
    {
        $key = md5(json_encode($query));
        return $this->tryFetchFromCache($key);
    }

    /**
     * @param $query
     * @param $id
     * @param $lifeTime
     *
     * @throws Exception
     */
    public function setIdByQuery($query, $id, $lifeTime)
    {
        $key = md5(json_encode($query));
        $this->getCacheManager()->save($this->generateCacheKey($key), $id, $lifeTime);
    }

    /**
     * @param       $query
     * @param array $options
     *
     * @return array|bool|mixed|string|null
     * @throws MongoDBException
     */
    public function getOneByCacheId($query, $options = [])
    {
        $id = $this->doFindIdById(md5(json_encode($query)), $query, 86400, $options);
        if ($id) {
            return $this->findById($id, $options);
        } else {
            return null;
        }
    }

    /**
     * @param       $query
     * @param array $options
     *
     * @return null
     * @throws MongoDBException
     */
    protected function findOneLogged($query, $options = [])
    {
        $collection = $this->getMongoCollection($options, $query);
        $this->mongoLog([
            'collection' => $collection->getCollectionName(),
            'findOne' => true,
            'query' => $query,
            'fields' => null
        ]);
        $r = $this->loadFromArray($collection->findOne($query));
        if (is_object($r)) {
            $this->getDocumentManager()->getUnitOfWork()->registerManaged($r, $r->getId(), []);
        }
        AppDebug::mongoLogEnd();
        return $r;
    }

    public function mongoLog($log)
    {

        AppDebug::mongoLog($log);

    }

    /**
     * @param       $query
     * @param array $options
     *
     * @return MongoCursor
     * @throws MongoDBException
     */
    public function mongoCount($query, $options = [])
    {
        $collection = $this->getMongoCollection($options, $query);
        $this->mongoLog([
            'collection' => $collection->getCollectionName(),
            'find' => true,
            'query' => $query,
            'fields' => ['_id'],
        ]);
        /** @var MongoCursor $r */
        $r = $collection->count($query);
        AppDebug::mongoLogEnd();
        return $r;
    }
} 