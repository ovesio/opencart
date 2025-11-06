<?php

namespace Ovesio;

class QueueHandler
{
    private $model;
    private $api;
    private $options;
    private $log;
    private $debug = [];

    private $request_data = [];

    /**
     * The list of activities that will be processed in order of priority
     */
    private $activity_groups = [
        'generate_content' => [],
        'generate_seo'     => [],
        'translate'        => []
    ];

    public function __construct($model, Client $api, $options = [], $log = null)
    {
        $this->model = $model;
        $this->api = $api;
        $this->options = $options;
        $this->log = $log;
    }

    /**
     * Get option value
     */
    private function getOption($key, $default = null)
    {
        return isset($this->options[$key]) ? $this->options[$key] : $default;
    }

    public function processQueue($params = [])
    {
        $list = $this->model->getCronList($params);

        // setup activity groups
        foreach ($list as $resource => $activities) {
            foreach ($activities as $activity_type => $activity) {
                $this->activity_groups[$activity_type][$resource] = $activity;
            }
        }

        $this->handleGenerateContentQueue();
        $this->handleGenerateSeoQueue();
        $this->handleTranslateQueue();

        $this->activity_groups = array_fill_keys(array_keys($this->activity_groups), []); // reset

        return $list;
    }

    /**
     * Add message to debug
     *
     * @return void
     */
    public function debug($resource, $resource_id, $event_type, $message)
    {
        $this->debug[] = "[{$event_type}] {$resource}: " . implode(',', (array) $resource_id) . " - " . $message;
    }

    /**
     * Show debug messages
     *
     * @return void
     */
    public function showDebug()
    {
        echo "<pre>" . print_r($this->debug, true) . "</pre>";
    }

    /**
     * Decode HTML content
     */
    private function decode($data)
    {
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                $data[$key] = $this->decode($value);
            }
        } else {
            $data = html_entity_decode($data, ENT_COMPAT, 'UTF-8');
        }

        return $data;
    }

    protected function ignoreMoveOnNextEvent($resource_type, $resource_ids, $activity_type, $message = '')
    {
        $default_language = $this->getOption('default_language');
        $resource_ids     = (array) $resource_ids;

        foreach ($resource_ids as $resource_id) {
            $this->model->addList([
                'resource_type' => $resource_type,
                'resource_id'   => $resource_id,
                'lang'          => $default_language,
                'activity_type' => $activity_type,
                'status'        => 'completed',
                'message'       => $message,
                'stale'         => 0,
                'updated_at'    => date('Y-m-d H:i:s')
            ]);
        }
    }

    private function getRefedRequestContents()
    {
        $requests = [];

        foreach ($this->request_data['data'] as $item) {
            $requests[$item['ref']] = $item['content'];
        }

        return $requests;
    }

    /*********************************** GENERATE CONTENT ***********************************/

    protected function handleGenerateContentQueue()
    {
        $default_language                  = $this->getOption('default_language');
        $generate_content_status           = (bool) $this->getOption('generate_content_status');
        $generate_content_include_disabled = array_filter((array) $this->getOption('generate_content_include_disabled', []));
        $generate_content_for              = array_filter((array) $this->getOption('generate_content_for', []));
        $send_stock_0_products             = (bool) $this->getOption('generate_content_include_stock_0');

        if (!$generate_content_status) {
            return;
        }

        $activities = $this->activity_groups['generate_content'];
        $product_activities  = [];
        $category_activities = [];

        foreach ($activities as $resource => $started_activity) {
            if ($started_activity && $started_activity['status'] == 'started') {
                unset($this->activity_groups['generate_seo'][$resource]);
                unset($this->activity_groups['translate'][$resource]);
                continue;
            }

            list($resource_type, $resource_id) = explode('/', $resource);

            if ($resource_type == 'product') {
                $product_activities[$resource_id] = $started_activity;
            } elseif ($resource_type == 'category') {
                $category_activities[$resource_id] = $started_activity;
            }
        }

        if (!empty($product_activities)) {
            if (!empty($generate_content_for['products'])) {
                $this->pushGenerateProductDescriptionRequests($product_activities, !empty($generate_content_include_disabled['products']), $send_stock_0_products);
            }
        }

        if (!empty($category_activities)) {
            if (!empty($generate_content_for['categories'])) {
                $this->pushGenerateCategoryDescriptionRequests($category_activities, !empty($generate_content_include_disabled['categories']));
            }
        }

        if (empty($this->request_data['data'])) {
            return;
        }

        // common conditions
        $_requests = $this->getRefedRequestContents();
        $hash      = $this->getOption('hash');
        $server    = $this->getOption('server_url', '');

        $request = $this->request_data;
        $request['callback_url'] = $server . 'index.php?route=module/ovesio/callback&type=generate_content&hash=' . $hash;
        $request['to'] = $this->getOption('default_language');

        $response = $this->api->generateContent($request);

        // unset further processing for these resources
        foreach ($_requests as $_resource => $item) {
            unset($this->activity_groups['generate_seo'][$_resource]);
            unset($this->activity_groups['translate'][$_resource]);
        }

        if (!empty($response['success'])) {
            foreach ($response['data'] as $item) {
                list($resource_type, $resource_id) = explode('/', $item['ref']);

                $this->model->addList([
                    'resource_type' => $resource_type,
                    'resource_id'   => $resource_id,
                    'lang'          => $default_language,
                    'activity_type' => 'generate_content',
                    'activity_id'   => $item['id'],
                    'hash'          => $hash,
                    'status'        => 'started',
                    'request'       => json_encode($_requests[$item['ref']]),
                    'response'      => json_encode($item),
                    'stale'         => 0,
                    'updated_at'    => date('Y-m-d H:i:s')
                ]);
            }
        } else {
            $this->log->write('Ovesio QueueHandler generate content error: ' . print_r($response, true));
        }
    }

    protected function pushGenerateProductDescriptionRequests($activities, $include_disabled = false, $include_stock_0 = true)
    {
        $product_ids = array_keys($activities);

        $products = $this->model->getProducts($product_ids, $include_disabled, $include_stock_0);

        if (empty($products)) {
            $this->ignoreMoveOnNextEvent('product', $product_ids, 'generate_content', "Not found, disabled or out of stock");
            return;
        }

        // chunk get attributes based on product_id
        $product_attributes = $this->model->getProductsAttributes($product_ids);

        $attribute_ids = [];
        foreach ($product_attributes as $attributes) {
            $attribute_ids = array_merge($attribute_ids, array_keys($attributes));
        }

        $attributes = $this->model->getAttributes($attribute_ids);
        $attributes = array_column($attributes, 'name', 'attribute_id');

        $categories_ids = $this->model->getProductCategories($product_ids);

        foreach ($products as $i => $product) {
            $push = [
                'ref' => 'product/' . $product['product_id'],
                'content' => [
                    'name' => $product['name']
                ]
            ];

            // only if is different from name...usual mistake
            $_description = strip_tags($this->decode($product['description']));
            if (strtolower(preg_replace("/[^A-Za-z0-9 ]/", '', $_description)) != strtolower(preg_replace("/[^A-Za-z0-9 ]/", '', strip_tags($this->decode($product['name']))))) {
                $push['content']['description'] = $product['description'];
            }

            foreach (($categories_ids[$product['product_id']] ?? []) as $category_id) {
                $category_info = $this->model->getCategory($category_id);

                if ($category_info) {
                    $push['content']['categories'][] = ($category_info['path']) ? $category_info['path'] . ' &gt; ' . $category_info['name'] : $category_info['name'];
                }
            }

            foreach (($product_attributes[$product['product_id']] ?? []) as $attribute_id => $attribute_text) {
                $push['content']['additional'][] = $attributes[$attribute_id] . ': ' . $attribute_text;
            }

            // remove description from hash to avoid recreating it everytime
            $_push = $push;
            if (!empty($_push['content']['description'])) {
                unset($_push['content']['description']);
            }

            $hash = md5(json_encode($_push));

            if (!empty($activities[$product['product_id']])) {
                $old_hash = $activities[$product['product_id']]['hash'];

                if ($this->getOption('generate_content_live_update') || $old_hash[$product['product_id']] == $hash) {
                    $this->ignoreMoveOnNextEvent('product', $product['product_id'], 'generate_content', "One time only or the hash did not changed");
                    continue;
                }
            }

            // The description is longer than minimum, send to translation
            if (strlen($_description) > $this->getOption('minimum_product_descrition', 0)) {
                $this->ignoreMoveOnNextEvent('product', $product['product_id'], 'generate_content', "Minimum description length not met");
                continue;
            }

            $this->request_data['data'][] = $push;
        }

        if (!empty($this->request_data['data'])) {
            $this->debug('product', str_replace('product/', '', array_column($this->request_data['data'], 'ref')), 'generate_content', "Prepare data");
        } else {
            $this->ignoreMoveOnNextEvent('product', $product_ids, 'generate_content', "No data left to process");
        }
    }

    protected function pushGenerateCategoryDescriptionRequests($activities, $include_disabled = false)
    {
        $category_ids = array_keys($activities);

        $categories = $this->model->getCategories($category_ids, $include_disabled);

        if (empty($categories)) {
            $this->ignoreMoveOnNextEvent('category', $category_ids, 'generate_content', "Not found or disabled");
            return;
        }

        foreach ($categories as $i => $category) {
            $push = [
                'ref' => 'category/' . $category['category_id'],
                'content' => [
                    'name' => $category['name']
                ]
            ];

            // only if is different from name...usual mistake
            $_description = strip_tags($this->decode($category['description']));
            if (strtolower(preg_replace("/[^A-Za-z0-9 ]/", '', $_description)) != strtolower(preg_replace("/[^A-Za-z0-9 ]/", '', strip_tags($this->decode($category['name']))))) {
                $push['content']['description'] = $category['description'];
            }

            // remove description from hash to avoid recreating it everytime
            $_push = $push;
            if (!empty($_push['content']['description'])) {
                unset($_push['content']['description']);
            }

            $hash = md5(json_encode($_push));

            if (!empty($activities[$category['category_id']])) {
                $old_hash = $activities[$category['category_id']]['hash'];

                if ($this->getOption('create_description_one_time_only') || $old_hash == $hash) {
                    $this->ignoreMoveOnNextEvent('category', $category['category_id'], 'generate_content', "One time only or the hash did not changed");
                    continue;
                }
            }

            // The description is longer than minimum, send to translation
            if (strlen($_description) > $this->getOption('minimum_category_descrition', 0)) {
                $this->ignoreMoveOnNextEvent('category', $category['category_id'], 'generate_content', "Minimum description length not met");
                continue;
            }

            $this->request_data['data'][] = $push;
        }

        if (!empty($this->request_data['data'])) {
            $this->debug('category', str_replace('category/', '', array_column($this->request_data['data'], 'ref')), 'generate_content', "Prepare data");
        } else {
            $this->ignoreMoveOnNextEvent('category', $category_ids, 'generate_content', "No data left to process");
        }
    }

    /*********************************** GENERATE SEO ***********************************/

    protected function handleGenerateSeoQueue()
    {
        $default_language              = $this->getOption('default_language');
        $generate_seo_status           = (bool) $this->getOption('generate_seo_status');
        $generate_seo_include_disabled = array_filter((array) $this->getOption('generate_seo_include_disabled', []));
        $generate_seo_for              = array_filter((array) $this->getOption('generate_seo_for', []));
        $send_stock_0_products         = (bool) $this->getOption('generate_seo_include_stock_0');

        if (!$generate_seo_status) {
            return;
        }

        $activities = $this->activity_groups['generate_seo'];
        $product_activities  = [];
        $category_activities = [];

        foreach ($activities as $resource => $started_activity) {
            if ($started_activity && $started_activity['status'] == 'started') {
                unset($this->activity_groups['generate_seo'][$resource]);
                unset($this->activity_groups['translate'][$resource]);
                continue;
            }

            list($resource_type, $resource_id) = explode('/', $resource);

            if ($resource_type == 'product') {
                $product_activities[$resource_id] = $started_activity;
            } elseif ($resource_type == 'category') {
                $category_activities[$resource_id] = $started_activity;
            }
        }

        if (!empty($product_activities)) {
            if (!empty($generate_seo_for['products'])) {
                $this->pushGenerateProductSeoRequests($product_activities, !empty($generate_seo_include_disabled['products']), $send_stock_0_products);
            }
        }

        if (!empty($category_activities)) {
            if (!empty($generate_seo_for['categories'])) {
                $this->pushGenerateCategorySeoRequests($category_activities, !empty($generate_seo_include_disabled['categories']));
            }
        }

        if (empty($this->request_data['data'])) {
            return;
        }

        // common conditions
        $_requests = $this->getRefedRequestContents();
        $hash      = $this->getOption('hash');
        $server    = $this->getOption('server_url', '');

        $request = $this->request_data;
        $request['callback_url'] = $server . 'index.php?route=module/ovesio/callback&type=metatags&hash=' . $hash;
        $request['to'] = $this->getOption('default_language');

        $response = $this->api->generateSeo($request);

        // unset further processing for these resources
        foreach ($_requests as $_resource => $item) {
            unset($this->activity_groups['generate_seo'][$_resource]);
            unset($this->activity_groups['translate'][$_resource]);
        }

        if (!empty($response['success'])) {
            foreach ($response['data'] as $item) {
                list($resource_type, $resource_id) = explode('/', $item['ref']);

                $this->model->addList([
                    'resource_type' => $resource_type,
                    'resource_id'   => $resource_id,
                    'lang'          => $default_language,
                    'activity_type' => 'generate_seo',
                    'activity_id'   => $item['id'],
                    'hash'          => $hash,
                    'status'        => 'started',
                    'request'       => json_encode($_requests[$item['ref']]),
                    'response'      => json_encode($item),
                    'stale'         => 0,
                    'updated_at'    => date('Y-m-d H:i:s')
                ]);
            }
        } else {
            $this->log->write('Ovesio QueueHandler generate SEO error: ' . print_r($response, true));
        }
    }

    protected function pushGenerateProductSeoRequests($activities, $include_disabled = false, $include_stock_0 = true)
    {
        $product_ids = array_keys($activities);

        $only_for_action = $this->getOption('generate_seo_only_for_action');

        if ($only_for_action) {
            $products = $this->model->getProductsWithDescriptionDependency($product_ids, $include_disabled, $include_stock_0);
        } else {
            $products = $this->model->getProducts($product_ids, $include_disabled, $include_stock_0);
        }

        if (empty($products)) {
            $this->ignoreMoveOnNextEvent('product', $product_ids, 'metatags', "Not found, disabled or out of stock");
            return;
        }

        // chunk get attributes based on product_id
        $product_attributes = $this->model->getProductsAttributes($product_ids);

        $attribute_ids = [];
        foreach ($product_attributes as $attributes) {
            $attribute_ids = array_merge($attribute_ids, array_keys($attributes));
        }

        $attributes = $this->model->getAttributes($attribute_ids);
        $attributes = array_column($attributes, 'name', 'attribute_id');

        $categories_ids = $this->model->getProductCategories($product_ids);

        foreach ($products as $i => $product) {
            $push = [
                'ref' => 'product/' . $product['product_id'],
                'content' => [
                    'name' => $product['name']
                ]
            ];

            // only if is different from name...usual mistake
            $_description = strip_tags($this->decode($product['description']));
            if (trim(strtolower(preg_replace("/[^A-Za-z0-9 ]/", '', $_description))) != trim(strtolower(preg_replace("/[^A-Za-z0-9 ]/", '', strip_tags($this->decode($product['name'])))))) {
                $push['content']['description'] = $product['description'];
            }

            foreach (($categories_ids[$product['product_id']] ?? []) as $category_id) {
                $category_info = $this->model->getCategory($category_id);

                if ($category_info) {
                    $push['content']['categories'][] = ($category_info['path']) ? $category_info['path'] . ' &gt; ' . $category_info['name'] : $category_info['name'];
                }
            }

            foreach (($product_attributes[$product['product_id']] ?? []) as $attribute_id => $attribute_text) {
                $push['content']['additional'][] = $attributes[$attribute_id] . ': ' . $attribute_text;
            }

            $hash = md5(json_encode($push));

            if (!empty($activities[$product['product_id']])) {
                $old_hash = $activities[$product['product_id']]['hash'];

                if ($this->getOption('generate_seo_live_update') || $old_hash[$product['product_id']] == $hash) {
                    $this->ignoreMoveOnNextEvent('product', $product['product_id'], 'metatags', "One time only or the hash did not changed");
                    continue;
                }
            }

            $this->request_data['data'][] = $push;
        }

        if (!empty($this->request_data['data'])) {
            $this->debug('product', str_replace('product/', '', array_column($this->request_data['data'], 'ref')), 'metatags', "Prepare data");
        } else {
            $this->ignoreMoveOnNextEvent('product', $product_ids, 'metatags', "No data left to process");
        }
    }

    protected function pushGenerateCategorySeoRequests($activities, $include_disabled = false)
    {
        $category_ids = array_keys($activities);

        $only_for_action = $this->getOption('generate_seo_only_for_action');

        if ($only_for_action) {
            $categories = $this->model->getCategoriesWithDescriptionDependency($category_ids, $include_disabled);
        } else {
            $categories = $this->model->getCategories($category_ids, $include_disabled);
        }

        if (empty($categories)) {
            $this->ignoreMoveOnNextEvent('category', $category_ids, 'metatags', "Not found or disabled");
            return;
        }

        foreach ($categories as $i => $category) {
            $push = [
                'ref' => 'category/' . $category['category_id'],
                'content' => [
                    'name' => $category['name']
                ]
            ];

            // only if is different from name...usual mistake
            $_description = strip_tags($this->decode($category['description']));
            if (trim(strtolower(preg_replace("/[^A-Za-z0-9 ]/", '', $_description))) != trim(strtolower(preg_replace("/[^A-Za-z0-9 ]/", '', strip_tags($this->decode($category['name'])))))) {
                $push['content']['description'] = $category['description'];
            }

            $hash = md5(json_encode($push));

            if (!empty($activities[$category['category_id']])) {
                $old_hash = $activities[$category['category_id']]['hash'];

                if ($this->getOption('generate_seo_live_update') || $old_hash[$category['category_id']] == $hash) {
                    $this->ignoreMoveOnNextEvent('category', $category['category_id'], 'metatags', "One time only or the hash did not changed");
                    continue;
                }
            }

            $this->request_data['data'][] = $push;
        }

        if (!empty($this->request_data['data'])) {
            $this->debug('category', str_replace('category/', '', array_column($this->request_data['data'], 'ref')), 'metatags', "Prepare data");
        } else {
            $this->ignoreMoveOnNextEvent('category', $category_ids, 'metatags', "No data left to process");
        }
    }

    /*********************************** TRANSLATE ***********************************/

    protected function handleTranslateQueue()
    {
        $default_language           = $this->getOption('default_language');
        $translate_status           = (bool) $this->getOption('translate_status');
        $translate_include_disabled = array_filter((array) $this->getOption('translate_include_disabled', []));
        $send_stock_0_products      = (bool) $this->getOption('translate_include_stock_0');
        $translate_fields           = (array) $this->getOption('translate_fields', []);

        $translate_for = array_filter($translate_fields, function ($item) {
            return array_filter($item);
        });

        if (!$translate_status) {
            return;
        }

        $activities = $this->activity_groups['translate'];
        $product_activities         = [];
        $category_activities        = [];
        $attribute_group_activities = [];
        $attribute_activities       = [];
        $option_activities          = [];

        foreach ($activities as $resource => $started_translate_activities) {
            foreach ($started_translate_activities as $started_activity) {
                if ($started_activity['status'] == 'started') {
                    continue 2; // we do not start new translations until the current translation session on the respective resource is finished
                }
            }

            list($resource_type, $resource_id) = explode('/', $resource);

            if ($resource_type == 'product') {
                $product_activities[$resource_id] = $started_translate_activities;
            } elseif ($resource_type == 'category') {
                $category_activities[$resource_id] = $started_translate_activities;
            } elseif ($resource_type == 'attribute_group') {
                $attribute_group_activities[$resource_id] = $started_translate_activities;
            } elseif ($resource_type == 'attribute') {
                $attribute_activities[$resource_id] = $started_translate_activities;
            } elseif ($resource_type == 'option') {
                $option_activities[$resource_id] = $started_translate_activities;
            }
        }

        if (!empty($attribute_group_activities)) {
            if (!empty($translate_for['attribute_groups'])) {
                $this->pushTranslateAttributeGroupRequests($attribute_group_activities, !empty($translate_include_disabled['attribute_groups']));
            }
        }

        if (!empty($attribute_activities)) {
            if (!empty($translate_for['attribute_groups'])) {
                $this->pushTranslateAttributeRequests($attribute_activities, !empty($translate_include_disabled['attribute_groups']));
            }
        }

        if (!empty($option_activities)) {
            if (!empty($translate_for['options'])) {
                $this->pushTranslateOptionRequests($option_activities, !empty($translate_include_disabled['options']));
            }
        }

        if (!empty($product_activities)) {
            if (!empty($translate_for['products'])) {
                $this->pushTranslateProductRequests($product_activities, !empty($translate_include_disabled['products']), $send_stock_0_products);
            }
        }

        if (!empty($category_activities)) {
            if (!empty($translate_for['categories'])) {
                $this->pushTranslateCategoryRequests($category_activities, !empty($translate_include_disabled['categories']));
            }
        }

        if (empty($this->request_data['data'])) {
            return;
        }

        // common conditions
        $_requests = $this->getRefedRequestContents();
        $hash      = $this->getOption('hash');
        $server    = $this->getOption('server_url', '');

        $request = $this->request_data;
        $request['callback_url'] = $server . 'index.php?route=module/ovesio/callback&type=translate&hash=' . $hash;
        $request['from']         = $this->getOption('default_language', 'en');

        $languages = $this->getOption('language_settings', []);

        foreach ($languages as $language) {
            if (empty($language['translate']) || $language['code'] === $request['from'] || empty($language['translate_from'])) continue;

            $request['to'][] = $language['code'];

            // apply conditions in case the from language is not the default language
            if ($request['from'] != $language['translate_from']) {
                $request['conditions'][$language['code']] = $language['translate_from'];
            }
        }

        $response = $this->api->translate($request);

        // unset further processing for these resources
        foreach ($_requests as $_resource => $item) {
            unset($this->activity_groups['generate_seo'][$_resource]);
            unset($this->activity_groups['translate'][$_resource]);
        }

        if (!empty($response['success'])) {
            foreach ($response['data'] as $item) {
                list($resource_type, $resource_id) = explode('/', $item['ref']);

                foreach ($request['to'] as $lang) {
                    $this->model->addList([
                        'resource_type' => $resource_type,
                        'resource_id'   => $resource_id,
                        'lang'          => $lang,
                        'activity_type' => 'translate',
                        'activity_id'   => $item['id'],
                        'hash'          => $hash,
                        'status'        => 'started',
                        'request'       => json_encode($_requests[$item['ref']]),
                        'response'      => json_encode($item),
                        'stale'         => 0,
                        'updated_at'    => date('Y-m-d H:i:s')
                    ]);
                }
            }
        } else {
            $this->log->write('Ovesio QueueHandler translate error: ' . print_r($response, true));
        }
    }

    protected function pushTranslateCategoryRequests($activities, $include_disabled = false)
    {
        $category_ids = array_keys($activities);

        $translate_fields = (array) $this->getOption('translate_fields', []);

        if (empty($translate_fields['categories'])) {
            return;
        }

        $translate_fields = $translate_fields['categories'];

        $categories = $this->model->getCategories($category_ids, $include_disabled);

        if (empty($categories)) {
            $this->ignoreMoveOnNextEvent('category', $category_ids, 'translate', "Not found or disabled");
            return;
        }

        foreach ($categories as $i => $category) {
            $push = [
                'ref'     => 'category/' . $category['category_id'],
                'content' => []
            ];

            foreach ($translate_fields['categories'] as $key => $send) {
                if (!$send || empty($category[$key])) continue;

                $push['content'][] = [
                    'key'   => $key,
                    'value' => $category[$key]
                ];
            }

            if (!empty($push['content'])) {
                $this->request_data['data'][] = $push;
            }
        }

        if (!empty($this->request_data['data'])) {
            $this->debug('category', str_replace('category/', '', array_column($this->request_data['data'], 'ref')), 'translate', "Prepare data");
        } else {
            $this->ignoreMoveOnNextEvent('category', $category_ids, 'translate', "No data left to process");
        }
    }

    protected function pushTranslateProductRequests($activities, $include_disabled = false, $include_stock_0 = true)
    {
        $product_ids = array_keys($activities);

        $translate_fields = (array) $this->getOption('translate_fields', []);

        if (empty($translate_fields['products'])) {
            return;
        }

        $translate_fields = $translate_fields['products'];

        $products = $this->model->getProducts($product_ids, $include_disabled, $include_stock_0);

        if (empty($products)) {
            $this->ignoreMoveOnNextEvent('product', $product_ids, 'translate', "Not found, disabled or out of stock");
            return;
        }

        $product_attributes = [];
        $attributes         = [];

        if (!empty($translate_fields['attributes'])) {
            // chunk get attributes based on product_id
            $product_attributes = $this->model->getProductsAttributes($product_ids);

            $attribute_ids = [];
            foreach ($product_attributes as $attributes_list) {
                $attribute_ids = array_merge($attribute_ids, array_keys($attributes_list));
            }

            $attributes = $this->model->getAttributes($attribute_ids);
            $attributes = array_column($attributes, 'name', 'attribute_id');
        }

        foreach ($products as $i => $product) {
            $push = [
                'ref'     => 'product/' . $product['product_id'],
                'content' => []
            ];

            foreach ($translate_fields as $key => $send) {
                if (!$send || empty($product[$key])) continue;

                $push['content'][] = [
                    'key'   => $key,
                    'value' => $product[$key]
                ];
            }

            foreach (($product_attributes[$product['product_id']] ?? []) as $attribute_id => $attribute_text) {
                $push['content'][] = [
                    'key'     => 'a-' . $attribute_id,
                    'value'   => $attribute_text,
                    'context' => $attributes[$attribute_id]
                ];
            }

            if (!empty($push['content'])) {
                $this->request_data['data'][] = $push;
            }
        }

        if (!empty($this->request_data['data'])) {
            // Translate large descriptions only with GPT!
            if (!empty($product['description']) && strlen($product['description']) >= 4000) {
                $this->request_data['workflow'] = 7; // TODO: scos de pe modulul principal
            }

            $this->debug('product', str_replace('product/', '', array_column($this->request_data['data'], 'ref')), 'translate', "Prepare data");
        } else {
            $this->ignoreMoveOnNextEvent('product', $product_ids, 'translate', "No data left to process");
        }
    }

    protected function pushTranslateAttributeGroupRequests($activities, $include_disabled = false)
    {
        $attribute_group_ids = array_keys($activities);

        $translate_fields = (array)$this->getOption('translate_fields', []);

        $translate_for = array_filter($translate_fields, function ($item) {
            return array_filter($item);
        });

        if (empty($translate_for['attributes'])) {
            $this->ignoreMoveOnNextEvent('attribute_group', $attribute_group_ids, 'translate', "Groups and Attributes translation is disabled");
            return;
        }

        $attribute_groups = $this->model->getAttributeGroups($attribute_group_ids);
        $attribute_groups = array_column($attribute_groups, null, 'attribute_group_id');

        if (empty($attribute_groups)) {
            $this->ignoreMoveOnNextEvent('attribute_group', $attribute_group_ids, 'translate', "No attribute groups found");
            return;
        }

        $attributes = $this->model->getGroupsAttributes($attribute_group_ids);

        $groups = [];
        foreach ($attribute_group_ids as $attribute_group_id) {
            if (!isset($attribute_groups[$attribute_group_id])) continue;

            $groups[$attribute_group_id] = [
                'ref' => 'attribute_group/' . $attribute_group_id,
                'content' => [
                    [
                        'key'   => 'ag-' . $attribute_group_id,
                        'value' => $attribute_groups[$attribute_group_id]['name']
                    ]
                ]
            ];
        }

        foreach ($attributes as $attribute) {
            if (!isset($groups[$attribute['attribute_group_id']])) continue;

            $groups[$attribute['attribute_group_id']]['content'][] = [
                'key'     => 'a-' . $attribute['attribute_id'],
                'context' => $attribute_groups[$attribute['attribute_group_id']]['name'],
                'value'   => $attribute['name'],
            ];
        }

        $this->request_data['data'] = array_merge($this->request_data['data'] ?? [], array_values($groups));

        if (!empty($this->request_data['data'])) {
            $this->debug('attribute_group', str_replace('attribute_group/', '', array_column($this->request_data['data'], 'ref')), 'translate', "Prepare data");
        } else {
            $this->ignoreMoveOnNextEvent('attribute_group', $attribute_group_ids, 'translate', "No data left to process");
        }
    }

    protected function pushTranslateAttributeRequests($activities, $include_disabled = false)
    {
        $attribute_ids = array_keys($activities);

        $translate_fields = (array)$this->getOption('translate_fields', []);

        $translate_for = array_filter($translate_fields, function ($item) {
            return array_filter($item);
        });

        if (empty($translate_for['attributes'])) {
            $this->ignoreMoveOnNextEvent('attribute', $attribute_ids, 'translate', "Groups and Attributes translation is disabled");
            return;
        }

        $attribute_groups = $this->model->getAttributeGroups();
        if (empty($attribute_groups)) {
            $this->ignoreMoveOnNextEvent('attribute', $attribute_ids, 'translate', "No attribute groups found");
            return;
        }

        $attribute_groups = array_column($attribute_groups, null, 'attribute_group_id');

        $attributes          = $this->model->getAttributes($attribute_ids);
        $attribute_group_ids = array_column($attributes, 'attribute_group_id');
        $attributes          = $this->model->getGroupsAttributes($attribute_group_ids);

        $groups = [];
        foreach ($attribute_group_ids as $attribute_group_id) {
            if (!isset($attribute_groups[$attribute_group_id])) continue;

            $groups[$attribute_group_id] = [
                'ref' => 'attribute_group/' . $attribute_group_id,
                'content' => [
                    [
                        'key'   => 'ag-' . $attribute_group_id,
                        'value' => $attribute_groups[$attribute_group_id]['name']
                    ]
                ]
            ];
        }

        foreach ($attributes as $attribute) {
            if (!isset($groups[$attribute['attribute_group_id']])) continue;

            $groups[$attribute['attribute_group_id']]['content'][] = [
                'key'     => 'a-' . $attribute['attribute_id'],
                'context' => $attribute_groups[$attribute['attribute_group_id']]['name'],
                'value'   => $attribute['name'],
            ];
        }

        $this->request_data['data'] = array_merge($this->request_data['data'] ?? [], array_values($groups));

        if (!empty($this->request_data['data'])) {
            $this->debug('attribute', str_replace('attribute_group/', '', array_column($this->request_data['data'], 'ref')), 'translate', "Prepare data");
        } else {
            $this->ignoreMoveOnNextEvent('attribute', $attribute_ids, 'translate', "No data left to process");
        }
    }

    public function pushTranslateOptionRequests($activities, $include_disabled = false)
    {
        $option_ids = array_keys($activities);

        $translate_fields = (array)$this->getOption('translate_fields', []);

        $translate_for = array_filter($translate_fields, function ($item) {
            return array_filter($item);
        });

        if (empty($translate_for['options'])) {
            $this->ignoreMoveOnNextEvent('option', $option_ids, 'translate', "Options translation is disabled");
            return;
        }

        $options = $this->model->getOptions($option_ids);
        if (empty($options)) {
            $this->ignoreMoveOnNextEvent('option', $option_ids, 'translate', "No options found");
            return;
        }

        $option_values = $this->model->getOptionValues($option_ids);

        $_option_values = [];
        foreach ($option_values as $option_value) {
            $_option_values[$option_value['option_id']][] = $option_value;
        }

        $option_values = $_option_values;
        unset($_option_values);

        foreach ($options as $option) {
            $push = [
                'ref'     => 'option/' . $option['option_id'],
                'content' => []
            ];

            $push['content'][] = [
                'key'   => 'o-' . $option['option_id'],
                'value' => $option['name'],
            ];

            foreach (($option_values[$option['option_id']] ?? []) as $option_value) {
                $push['content'][] = [
                    'key'     => 'ov-' . $option_value['option_value_id'],
                    'context' => $option['name'],
                    'value'   => $option_value['name'],
                ];
            }

            if (!empty($push['content'])) {
                $this->request_data['data'][] = $push;
            }
        }

        if (!empty($this->request_data['data'])) {
            $this->debug('option', str_replace('option/', '', array_column($this->request_data['data'], 'ref')), 'translate', "Prepare data");
        } else {
            $this->ignoreMoveOnNextEvent('option', $option_ids, 'translate', "No data left to process");
        }
    }

    public function syncActivityStatus($activity_id)
    {
        $activity = $this->model->getActivityById($activity_id);

        $payload = null;

        if ($activity['activity_type'] == 'generate_content') {
            $response = $this->api->getGenerateContentStatus($activity['activity_id']);
            $payload = $response['data'] ?? null;
        } elseif ($activity['activity_type'] == 'generate_seo') {
            $response = $this->api->getGenerateSeoStatus($activity['activity_id']);
            $payload = $response['data'] ?? null;
        } elseif ($activity['activity_type'] == 'translate') {
            $response = $this->api->getTranslateStatus($activity['activity_id']);

            foreach ($response['data']['data'] as $item) {
                if ($item['to'] == $activity['lang']) {
                    $payload = $item;
                    break;
                }
            }
        }

        if ($payload && $payload['status'] == 'completed') {
            $this->triggerCallback($activity['activity_type'], $payload);
        }

        $activity = $this->model->getActivityById($activity_id);

        return $activity;
    }

    private function triggerCallback($type, $data)
    {
        // make a curl POST to self server, without SSL verification
        $server = $this->getOption('server_url', '');
        $url = $server . 'index.php?route=module/ovesio/callback&type=' . $type . '&hash=' . $this->getOption('hash');

        $ch = curl_init($url);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $response = curl_exec($ch);
        curl_close($ch);

        return $response;
    }
}
