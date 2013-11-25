<?php
/**
 * @author Carsten Brandt <mail@cebe.cc>
 */

namespace yii\elasticsearch;


use Guzzle\Http\Exception\ClientErrorResponseException;
use yii\base\Component;
use yii\db\Exception;
use yii\helpers\Json;

// camelCase vs. _
// http://www.elasticsearch.org/guide/en/elasticsearch/reference/current/common-options.html#_result_casing


/**
 * Class Command
 *
 * http://www.elasticsearch.org/guide/en/elasticsearch/reference/current/glossary.html
 *
 */
class Command extends Component
{
	/**
	 * @var Connection
	 */
	public $db;
	/**
	 * @var string|array the indexes to execute the query on. Defaults to null meaning all indexes
	 * @see http://www.elasticsearch.org/guide/en/elasticsearch/reference/current/search.html#search-multi-index
	 */
	public $index;
	/**
	 * @var string|array the types to execute the query on. Defaults to null meaning all types
	 */
	public $type;
	/**
	 * @var array list of arrays or json strings that become parts of a query
	 */
	public $queryParts;

	public $options = [];

	/**
	 * @param array $options
	 * @return mixed
	 */
	public function search($options = [])
	{
		$query = $this->queryParts;
		if (empty($query)) {
			$query = '{}';
		}
		if (is_array($query)) {
			$query = Json::encode($query);
		}
		$url = [
			$this->index !== null ? $this->index : '_all',
			$this->type !== null ? $this->type : '_all',
			'_search'
		];
		return $this->db->get($url, array_merge($this->options, $options), $query)['hits'];
	}

	/**
	 * Inserts a document into an index
	 * @param string $index
	 * @param string $type
	 * @param string|array $data json string or array of data to store
	 * @param null $id the documents id. If not specified Id will be automatically choosen
	 * @param array $options
	 * @return mixed
	 * @see http://www.elasticsearch.org/guide/en/elasticsearch/reference/current/docs-index_.html
	 */
	public function insert($index, $type, $data, $id = null, $options = [])
	{
		$body = is_array($data) ? Json::encode($data) : $data;

		if ($id !== null) {
			return $this->db->put([$index, $type, $id], $options, $body);
		} else {
			return $this->db->post([$index, $type], $options, $body);
		}
	}

	/**
	 * gets a document from the index
	 * @param $index
	 * @param $type
	 * @param $id
	 * @param array $options
	 * @return mixed
	 * @see http://www.elasticsearch.org/guide/en/elasticsearch/reference/current/docs-get.html
	 */
	public function get($index, $type, $id, $options = [])
	{
		return $this->db->get([$index, $type, $id], $options, null, [200, 404]);
	}

	/**
	 * gets multiple documents from the index
	 *
	 * TODO allow specifying type and index + fields
	 * @param $index
	 * @param $type
	 * @param $id
	 * @param array $options
	 * @return mixed
	 * @see http://www.elasticsearch.org/guide/en/elasticsearch/reference/current/docs-multi-get.html
	 */
	public function mget($index, $type, $ids, $options = [])
	{
		$body = Json::encode(['ids' => array_values($ids)]);
		return $this->db->get([$index, $type, '_mget'], $options, $body);
	}

	/**
	 * gets a documents _source from the index (>=v0.90.1)
	 * @param $index
	 * @param $type
	 * @param $id
	 * @return mixed
	 * @see http://www.elasticsearch.org/guide/en/elasticsearch/reference/current/docs-get.html#_source
	 */
	public function getSource($index, $type, $id)
	{
		return $this->db->get([$index, $type, $id]);
	}

	/**
	 * gets a document from the index
	 * @param $index
	 * @param $type
	 * @param $id
	 * @return mixed
	 * @see http://www.elasticsearch.org/guide/en/elasticsearch/reference/current/docs-get.html
	 */
	public function exists($index, $type, $id)
	{
		return $this->db->head([$index, $type, $id]);
	}

	/**
	 * deletes a document from the index
	 * @param $index
	 * @param $type
	 * @param $id
	 * @param array $options
	 * @return mixed
	 * @see http://www.elasticsearch.org/guide/en/elasticsearch/reference/current/docs-delete.html
	 */
	public function delete($index, $type, $id, $options = [])
	{
		return $this->db->delete([$index, $type, $id], $options);
	}

	/**
	 * updates a document
	 * @param $index
	 * @param $type
	 * @param $id
	 * @param array $options
	 * @return mixed
	 * @see http://www.elasticsearch.org/guide/en/elasticsearch/reference/current/docs-update.html
	 */
	public function update($index, $type, $id, $data, $options = [])
	{
		// TODO
//		return $this->db->delete([$index, $type, $id], $options);
	}

	// TODO bulk http://www.elasticsearch.org/guide/en/elasticsearch/reference/current/docs-bulk.html

	/**
	 * @see http://www.elasticsearch.org/guide/en/elasticsearch/reference/current/indices-create-index.html
	 */
	public function createIndex($index, $configuration = null)
	{
		$body = $configuration !== null ? Json::encode($configuration) : null;
		return $this->db->put([$index], $body);
	}

	/**
	 * @see http://www.elasticsearch.org/guide/en/elasticsearch/reference/current/indices-delete-index.html
	 */
	public function deleteIndex($index)
	{
		return $this->db->delete([$index]);
	}

	/**
	 * @see http://www.elasticsearch.org/guide/en/elasticsearch/reference/current/indices-delete-index.html
	 */
	public function deleteAllIndexes()
	{
		return $this->db->delete(['_all']);
	}

	/**
	 * @see http://www.elasticsearch.org/guide/en/elasticsearch/reference/current/indices-exists.html
	 */
	public function indexExists($index)
	{
		return $this->db->head([$index]);
	}

	/**
	 * @see http://www.elasticsearch.org/guide/en/elasticsearch/reference/current/indices-types-exists.html
	 */
	public function typeExists($index, $type)
	{
		return $this->db->head([$index, $type]);
	}

	// TODO http://www.elasticsearch.org/guide/en/elasticsearch/reference/current/indices-aliases.html

	// TODO http://www.elasticsearch.org/guide/en/elasticsearch/reference/current/indices-update-settings.html
	// TODO http://www.elasticsearch.org/guide/en/elasticsearch/reference/current/indices-get-settings.html

	// TODO http://www.elasticsearch.org/guide/en/elasticsearch/reference/current/indices-warmers.html

	/**
	 * @see http://www.elasticsearch.org/guide/en/elasticsearch/reference/current/indices-open-close.html
	 */
	public function openIndex($index)
	{
		return $this->db->post([$index, '_open']);
	}

	/**
	 * @see http://www.elasticsearch.org/guide/en/elasticsearch/reference/current/indices-open-close.html
	 */
	public function closeIndex($index)
	{
		return $this->db->post([$index, '_close']);
	}

	/**
	 * @see http://www.elasticsearch.org/guide/en/elasticsearch/reference/current/indices-status.html
	 */
	public function getIndexStatus($index = '_all')
	{
		return $this->db->get([$index, '_status']);
	}

	// TODO http://www.elasticsearch.org/guide/en/elasticsearch/reference/current/indices-stats.html
	// http://www.elasticsearch.org/guide/en/elasticsearch/reference/current/indices-segments.html

	/**
	 * @see http://www.elasticsearch.org/guide/en/elasticsearch/reference/current/indices-clearcache.html
	 */
	public function clearIndexCache($index)
	{
		return $this->db->post([$index, '_cache', 'clear']);
	}

	/**
	 * @see http://www.elasticsearch.org/guide/en/elasticsearch/reference/current/indices-flush.html
	 */
	public function flushIndex($index = '_all')
	{
		return $this->db->post([$index, '_flush']);
	}

	/**
	 * @see http://www.elasticsearch.org/guide/en/elasticsearch/reference/current/indices-refresh.html
	 */
	public function refreshIndex($index)
	{
		return $this->db->post([$index, '_refresh']);
	}

	// TODO http://www.elasticsearch.org/guide/en/elasticsearch/reference/current/indices-optimize.html

	// TODO http://www.elasticsearch.org/guide/en/elasticsearch/reference/current/indices-gateway-snapshot.html

	/**
	 * http://www.elasticsearch.org/guide/en/elasticsearch/reference/current/indices-put-mapping.html
	 */
	public function setMapping($index, $type, $mapping)
	{
		$body = $mapping !== null ? Json::encode($mapping) : null;
		return $this->db->put([$index, $type, '_mapping'], $body);
	}

	/**
	 * http://www.elasticsearch.org/guide/en/elasticsearch/reference/current/indices-get-mapping.html
	 */
	public function getMapping($index = '_all', $type = '_all')
	{
		return $this->db->get([$index, $type, '_mapping']);
	}

	/**
	 * http://www.elasticsearch.org/guide/en/elasticsearch/reference/current/indices-put-mapping.html
	 */
	public function deleteMapping($index, $type)
	{
		return $this->db->delete([$index, $type]);
	}

	/**
	 * @see http://www.elasticsearch.org/guide/en/elasticsearch/reference/current/indices-get-field-mapping.html
	 */
	public function getFieldMapping($index, $type = '_all')
	{
		return $this->db->put([$index, $type, '_mapping']);
	}

	/**
	 * @see http://www.elasticsearch.org/guide/en/elasticsearch/reference/current/indices-analyze.html
	 */
	public function analyze($options, $index = null)
	{
		// TODO implement
//		return $this->db->put([$index]);
	}

	/**
	 * @see http://www.elasticsearch.org/guide/en/elasticsearch/reference/current/indices-templates.html
	 */
	public function createTemplate($name, $pattern, $settings, $mappings, $order = 0)
	{
		$body = Json::encode([
			'template' => $pattern,
			'order' => $order,
			'settings' => (object) $settings,
			'mappings' => (object) $mappings,
		]);
		return $this->db->put(['_template', $name], $body);

	}

	/**
	 * @see http://www.elasticsearch.org/guide/en/elasticsearch/reference/current/indices-templates.html
	 */
	public function deleteTemplate($name)
	{
		return $this->db->delete(['_template', $name]);

	}

	/**
	 * @see http://www.elasticsearch.org/guide/en/elasticsearch/reference/current/indices-templates.html
	 */
	public function getTemplate($name)
	{
		return $this->db->get(['_template', $name]);
	}
}