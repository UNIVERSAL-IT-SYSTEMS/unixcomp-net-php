<?php

namespace unixcomp_net\query_cache;

/**
 * Интерфейс движка кэша запросов
 */
interface EngineInterface {
  public function set($query, $result);
  public function get($query);
  public function clear();
}

/**
 * Реализация движка кэша на базе SQLite
 */
class EngineSQLite implements EngineInterface {
  protected $_db;

  public function __construct($db_file_path) {    
    $this->_db = new SQLite3($db_file_path, SQLITE3_OPEN_CREATE | SQLITE3_OPEN_READWRITE);
    $this->_dbPrepare();
  }

  /**
   * Помещает в кэш запрос и результат
   */
  public function set($query, $result, $query_time = NULL) {
    $query_hash = md5($query);

    $insert_sql = <<<SQL
INSERT OR IGNORE INTO query_cache (
  query_hash,
  cache_datetime,
  query_time,
  query,
  result
)
VALUES ('%s', %d, '%F', '%s', '%s');
SQL;
    $q = sprintf($insert_sql,
      md5($query),
      time(),
      $query_time,
      SQLite3::escapeString($query),
      SQLite3::escapeString($this->_packQueryResult($result))
    );

    return $this->_db->exec($q);
  }

  /**
   * Запрашивает результат запроса из кэша
   */
  public function get($query) {
    $q = sprintf("SELECT result FROM query_cache WHERE query_hash = '%s' LIMIT 1", md5($query));
    if ($result = $this->_db->querySingle($q)) {
      return $this->_unpackQueryResult($result);
    }
    else {
      return FALSE;
    }
  }

  /**
   * Очищает хранилище кэша
   */
  public function clear() {
    $q = sprintf("DELETE FROM query_cache");
    $this->_db->exec($q);
  }

  /**
   * Готовит базу данных
   */
  protected function _dbPrepare() {
    $init_sql = <<<SQL
CREATE TABLE IF NOT EXISTS query_cache (
  id INTEGER PRIMARY KEY,
  query_hash TEXT NOT NULL,
  cache_datetime INTEGER NOT NULL,
  query_time REAL,
  query TEXT NOT NULL,
  result TEXT NOT NULL,
  hit_counter INTEGER NOT NULL DEFAULT 0,
  hit_datetime TEXT NOT NULL DEFAULT ''
);
CREATE UNIQUE INDEX IF NOT EXISTS "query_hash_uniq" on query_cache (query_hash ASC);
CREATE INDEX IF NOT EXISTS "cache_datetime_idx" on query_cache (cache_datetime ASC);
CREATE INDEX IF NOT EXISTS "query_time_idx" on query_cache (query_time ASC);
SQL;
    $this->_db->exec($init_sql);
    $this->_db->exec("PRAGMA synchronous = 1;");
  }

  /**
   * Упаковывает результат
   */
  protected function _packQueryResult($result) {
    return serialize($result);
  }

  /**
   * Распаковывает результат
   */
  protected function _unpackQueryResult($result) {
    return unserialize($result);
  }
}

/**
 * Базовый класс реализации кэша запросов
 */
abstract class Base {
  protected $_engine;

  public function __construct(QueryCacheEngineInterface $engine) {
    $this->_engine = $engine;
  }

  public function set($query, $result, $query_time = NULL) {
    return $this->_engine->set($query, $result, $query_time);
  }

  public function get($query) {
    return $this->_engine->get($query);
  }

  public function clear() {
    return $this->_engine->clear();
  }
}
