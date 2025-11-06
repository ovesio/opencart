<?php

require_once(modification($_SERVER['DOCUMENT_ROOT'] . '/catalog/model/module/ovesio.php'));

class ControllerModuleOvesioCallback extends Controller
{
    private $output = [];
    private $module_key = 'ovesio';

    public function __construct($registry) {
        parent::__construct($registry);

        /**
         * Changes needed for v3
         */
        if(version_compare(VERSION, '3.0.0.0') >= 0) {
            $this->module_key = 'module_ovesio';
        }

        $this->model = new ModelModuleOvesio($registry);
    }

    public function index()
    {
        if (!$this->config->get($this->module_key . '_status')) {
            return $this->setOutput(['error' => 'Module is disabled']);
        }

        $hash = isset($this->request->get['hash']) ? $this->request->get['hash'] : false;
        if (!$hash || $hash !== $this->config->get($this->module_key . '_hash')) {
            if (ENVIRONMENT != 'development') {
                return $this->setOutput(['error' => 'Invalid Hash!']);
            }
        }

        try {
            $this->handle();
        } catch(\Exception $e) {
            $this->setOutput(array_merge($this->output, [
                'error' => $e->getMessage()
            ]));
        }
    }

    protected function handle()
    {
        // Takes raw data from the request
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);
        $data = $this->request->clean($data);

        if (!$this->config->get($this->module_key . '_status')) {
            return $this->setOutput(['error' => 'Module is disabled']);
        }

        if (empty($data)) {
            throw new Exception('No data received');
        }

        if (empty($data['content'])) {
            throw new Exception('Data received has empty content');
        }

        list($resource, $identifier) = explode('/', $data['ref']);
        $ovesio_language_code = $data['to'];

        $status = 0;
        if (empty($this->request->get['type'])) {
            throw new Exception('Wrong request');
        }

        $activity_type = $this->request->get['type'];
        if ($activity_type == 'generate_content') {
            $status = $this->config->get($this->module_key . '_content_status');
        } elseif ($activity_type == 'generate_seo') {
            $status = $this->config->get($this->module_key . '_generate_seo_status');
        } elseif ($activity_type == 'translate') {
            $status = $this->config->get($this->module_key . '_translation_status');
        }

        if (empty($activity_type)) {
            throw new Exception('Data received has empty type');
        }

        if(in_array($resource, ['product', 'category', 'attribute_group', 'option']) && !$status) {
            return $this->setOutput(['error' => 'This operation is disabled!']);
        }

        if (empty($identifier)) {
            throw new Exception('Identifier cannot be empty');
        }

        $this->load->library('ovesio');

        $language_id  = $this->ovesio->getDefaultLanguageId();
        $language_settings = $this->config->get($this->module_key . '_language_settings');
        foreach($language_settings as $match_language_id => $lang) {
            if(!empty($lang['code']) && $lang['code'] == $ovesio_language_code) {
                $language_id = $match_language_id;
                break;
            }
        }

        $query = $this->db->query("SELECT language_id FROM " . DB_PREFIX . "language WHERE language_id = '" . $language_id . "'");
        if (!$query->row) {
            throw new Exception('Language id "' . $ovesio_language_code . '" not found');
        }

        $data['language_id'] = $query->row['language_id'];

        $method = $activity_type . '_' . $resource;
        if (! method_exists($this, $method)) {
            throw new Exception('Method "' . $method . '" not found, wrong response type');
        }

        try {
            $this->{$method}($identifier, $data);
        } catch (Throwable $e) {
            // stop updating list
            throw new Exception('Error processing ' . $method . ': ' . $e->getMessage() . ' ' . $e->getFile() . ':' . $e->getLine());
        }

        // Update log table
        list($resource, $resource_id) = explode('/', $data['ref']);

        $this->model->addList([
            'resource_type' => $resource,
            'resource_id'   => $resource_id,
            'activity_type' => $activity_type,
            'lang'          => $ovesio_language_code,
            'status'        => 'completed',
            'response'      => json_encode($data['content']),
        ]);

        // $resource, $resource_id
        $queue_handler = $this->ovesio->buildQueueHandler();

        $queue_handler->processQueue([
            'resource_type' => $resource,
            'resource_id'   => $resource_id,
        ]);

        $this->setOutput(array_merge($this->output, [
            'success' => true
        ]));
    }

    protected function generate_description_product($product_id, $data)
    {
        $product_description['description'] = $data['content']['description'];

        $this->model->updateProductDescription($product_id, $data['language_id'], $product_description);

        $this->seoProduct($product_id, $data['language_id'], $product_description);
    }

    protected function generate_description_category($category_id, $data)
    {
        $category_description['description'] = $data['content']['description'];

        $this->model->updateCategoryDescription($category_id, $data['language_id'], $category_description);

        $this->seoCategory($category_id, $data['language_id'], $category_description);
    }

    protected function translate_product($product_id, $data)
    {
        $translate_fields = $this->config->get($this->module_key . '_translate_fields');

        $product_description = [];
        $attribute_values = [];

        foreach ($data['content'] as $item) {
            // ? if order matters
            if (strpos($item['key'], 'a-') === 0) {
                $attribute_values[str_replace('a-', '', $item['key'])] = $item['value'];
            }
            elseif (!empty($translate_fields['product'][$item['key']])) {
                $product_description[str_replace('p-', '', $item['key'])] = $item['value'];
            }
            elseif (!isset($translate_fields['product'][$item['key']])) {
                $this->output['warnings'][] = 'Unknown key "' . $item['key'] . '"';
            }
        }

        if (!empty($product_description)) {
            $this->model->updateProductDescription($product_id, $data['language_id'], $product_description);
        }

        foreach ($attribute_values as $attribute_id => $text) {
            $this->model->updateAttributeValueDescription($product_id, $attribute_id, $data['language_id'], $text);
        }

        if (!empty($product_description)) {
            $this->seoProduct($product_id, $data['language_id'], $product_description);
        }
    }

    protected function translate_category($category_id, $data)
    {
        $translate_fields = $this->config->get($this->module_key . '_translate_fields');

        $category_description = [];

        foreach ($data['content'] as $item) {
            if (!empty($translate_fields['category'][$item['key']])) {
                $category_description[$item['key']] = $item['value'];
            }
            elseif (!isset($translate_fields['category'][$item['key']])) {
                $this->output['warnings'][] = 'Unknown key "' . $item['key'] . '"';
            }
        }

        if (!empty($category_description)) {
            $this->model->updateCategoryDescription($category_id, $data['language_id'], $category_description);

            $this->seoCategory($category_id, $data['language_id'], $category_description);
        }
    }

    protected function translate_attribute_group($attribute_group_id, $data)
    {
        foreach ($data['content'] as $item) {
            if (strpos($item['key'], 'ag-') === 0) {
                $attribute_group_id = str_replace('ag-', '', $item['key']);
                $this->model->updateAttributeGroupDescription($attribute_group_id, $data['language_id'], $item['value']);
            }
            elseif (strpos($item['key'], 'a-') === 0) {
                $attribute_id = str_replace('a-', '', $item['key']);
                $this->model->updateAttributeDescription($attribute_id, $data['language_id'], $item['value']);
            }
            else {
                $this->output['warnings'][] = 'Unknown key "' . $item['key'] . '"';
            }
        }
    }

    protected function translate_option($option_id, $data)
    {
        foreach ($data['content'] as $item) {
            if (strpos($item['key'], 'o-') === 0) {
                $option_id = str_replace('o-', '', $item['key']);
                $this->model->updateOptionDescription($option_id, $data['language_id'], $item['value']);
            }
            elseif (strpos($item['key'], 'ov-') === 0) {
                $option_value_id = str_replace('ov-', '', $item['key']);
                $this->model->updateOptionValueDescription($option_value_id, $data['language_id'], $item['value']);
            }
            else {
                $this->output['warnings'][] = 'Unknown key "' . $item['key'] . '"';
            }
        }
    }

    protected function metatags_product($product_id, $data)
    {
        //seo_h1, seo_h2, seo_h3, meta_title, meta_description, meta_keywords

        $language_id = $data['language_id'];
        $seo = $this->model->getProductForSeo($product_id, $language_id);
        $content = $this->populate_compatibility_content($data['content']);

        $metatags = [];
        foreach ($content as $key => $value) {
            if(isset($seo['product_description'][$language_id][$key])) {
                $metatags[$key] = $value;
            }
        }

        $this->model->updateProductDescription($product_id, $language_id, $metatags);
    }

    protected function metatags_category($category_id, $data)
    {
        //seo_h1, seo_h2, seo_h3, meta_title, meta_description, meta_keywords

        $language_id = $data['language_id'];
        $seo = $this->model->getCategoryForSeo($category_id, $language_id);
        $content = $this->populate_compatibility_content($data['content']);

        $metatags = [];
        foreach ($content as $key => $value) {
            if(isset($seo['category_description'][$language_id][$key])) {
                $metatags[$key] = $value;
            }
        }

        $this->model->updateCategoryDescription($category_id, $language_id, $metatags);
    }

    protected function populate_compatibility_content($content)
    {
        // General Mappings
        $content['meta_keyword'] = $content['meta_keywords'];

        // Complete SEO module mappings
        $content['image_title'] = $content['seo_h1'];
        $content['image_alt'] = $content['seo_h2'];
        $content['seo_keyword'] = $content['meta_keywords'];

        // SEO Mega KIT PLUS mappings
        //$content['meta_title_ag'] = $data['meta_title'];
        $content['smp_h1_title'] = $content['seo_h1'];
        $content['smp_alt_images'] = $content['seo_h1'];
        $content['smp_title_images'] = $content['seo_h2'];

        return $content;
    }

    /**
     * Internal SEO methods - compatible with Complete SEO module
     *
     */
    private function seoProduct($product_id, $language_id, $product_description)
    {
        if (1 || !$this->config->get('module_seo_enabled')) {
            return;
        }

        $data = $this->model->getProductForSeo($product_id, $language_id);
        $data['product_description'][$language_id] = array_merge($data['product_description'][$language_id], $product_description);

        // discard not translated fields to re-compose them with seo based on new translation
        $translate_fields = $this->config->get($this->module_key . '_translate_fields');
        foreach ($data['product_description'][$language_id] as $field => $value) {
            if (in_array($field, ['product_id', 'language_id', 'seo_keyword'])) continue;

            if (empty($translate_fields['product'][$field])) {
                $data['product_description'][$language_id][$field] = '';
            }
        }

        $this->load->controller('module/complete_seo/event/product/after_model_product_edit', 'editProduct', [$product_id, $data], $product_id, true);
    }

    private function seoCategory($category_id, $language_id, $category_description)
    {
        if (1 || !$this->config->get('module_seo_enabled')) {
            return;
        }

        $data = $this->model->getCategoryForSeo($category_id, $language_id);
        $data['category_description'][$language_id] = array_merge($data['category_description'][$language_id], $category_description);

        // discard not translated fields to re-compose them with seo based on new translation
        $translate_fields = $this->config->get($this->module_key . '_translate_fields');
        foreach ($data['category_description'][$language_id] as $field => $value) {
            if (in_array($field, ['category_id', 'language_id', '_seo_keyword'])) continue;

            if (empty($translate_fields['category'][$field])) {
                $data['category_description'][$language_id][$field] = '';
            }
        }

        $this->load->controller('module/complete_seo/event/category/after_model_category_edit', 'editCategory', [$category_id, $data], $category_id, true);
    }

    /**
     * Custom response
     */
    private function setOutput($response)
    {
        if(is_array($response))
        {
            $response = json_encode($response);

            $this->response->addHeader('Content-Type: application/json');
        }

        $this->response->setOutput($response);
    }
}