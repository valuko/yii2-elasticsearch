Elasticsearch Query and ActiveRecord for Yii 2
==============================================

This extension provides the [elasticsearch](http://www.elasticsearch.org/) integration for the Yii2 framework.
It includes basic querying/search support and also implements the `ActiveRecord` pattern that allows you to store active
records in elasticsearch.

To use this extension, you have to configure the Connection class in your application configuration:

```php
return [
	//....
	'components' => [
        'elasticsearch' => [
            'class' => 'yii\elasticsearch\Connection',
            'hosts' => [
                ['http_address' => '127.0.0.1:9200'],
                // configure more hosts if you have a cluster
            ],
        ],
	]
];
```


Installation
------------

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```
php composer.phar require yiisoft/yii2-elasticsearch "*"
```

or add

```json
"yiisoft/yii2-elasticsearch": "*"
```

to the require section of your composer.json.


Using the Query
---------------

TBD

Using the ActiveRecord
----------------------

For general information on how to use yii's ActiveRecord please refer to the [guide](https://github.com/yiisoft/yii2/blob/master/docs/guide/active-record.md).

For defining an elasticsearch ActiveRecord class your record class needs to extend from `yii\elasticsearch\ActiveRecord` and
implement at least the `attributes()` method to define the attributes of the record.
The primary key (the `_id` field in elasticsearch terms) is represented by `getId()` and `setId()` and can not be changed.
The primary key is not part of the attributes.

 primary key can be defined via [[primaryKey()]] which defaults to `id` if not specified.
The primaryKey needs to be part of the attributes so make sure you have an `id` attribute defined if you do
not specify your own primary key.

The following is an example model called `Customer`:

```php
class Customer extends \yii\elasticsearch\ActiveRecord
{
    /**
     * @return array the list of attributes for this record
     */
    public function attributes()
    {
        return ['id', 'name', 'address', 'registration_date'];
    }

    /**
     * @return ActiveRelation defines a relation to the Order record (can be in other database, e.g. redis or sql)
     */
    public function getOrders()
    {
        return $this->hasMany(Order::className(), ['customer_id' => 'id'])->orderBy('id');
    }

    /**
     * Defines a scope that modifies the `$query` to return only active(status = 1) customers
     */
    public static function active($query)
    {
        $query->andWhere(array('status' => 1));
    }
}
```

You may override [[index()]] and [[type()]] to define the index and type this record represents.

The general usage of elasticsearch ActiveRecord is very similar to the database ActiveRecord as described in the
[guide](https://github.com/yiisoft/yii2/blob/master/docs/guide/active-record.md).
It supports the same interface and features except the following limitations and additions(*!*):

- As elasticsearch does not support SQL, the query API does not support `join()`, `groupBy()`, `having()` and `union()`.
  Sorting, limit, offset and conditional where are all supported.
- `from()` does not select the tables, but the [index](http://www.elasticsearch.org/guide/en/elasticsearch/reference/current/glossary.html#glossary-index)
  and [type](http://www.elasticsearch.org/guide/en/elasticsearch/reference/current/glossary.html#glossary-type) to query against.
- `select()` has been replaced with `fields()` which basically does the same but `fields` is more elasticsearch terminology.
  It defines the fields to retrieve from a document.
- `via`-relations can not be defined via a table as there are not tables in elasticsearch. You can only define relations via other records.
- As elasticsearch is a data storage and search engine there is of course support added for search your records.
  TBD ...
- It is also possible to define relations from elasticsearch ActiveRecords to normal ActiveRecord classes and vice versa.

Elasticsearch separates primary key from attributes. You need to set the `id` property of the record to set its primary key.

Usage example:

```php
$customer = new Customer();
$customer->id = 1;
$customer->attributes = ['name' => 'test'];
$customer->save();

$customer = Customer::get(1); // get a record by pk
$customers = Customer::get([1,2,3]); // get a records multiple by pk
$customer = Customer::find()->where(['name' => 'test'])->one(); // find by query
$customer = Customer::find()->active()->all(); // find all by query (using the `active` scope)
```