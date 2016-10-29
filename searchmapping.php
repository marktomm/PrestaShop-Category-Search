<?php 

if (!defined('_CAN_LOAD_FILES_'))
	exit;

require_once(_PS_MODULE_DIR_.'/blocksearchcategories/utils/Log.php');

class CategorySearchMapping extends ObjectModel
{
    public $id_category;
    public $id_lang;
    public $word;
    
    const PS_SM_SEARCH_MAX_WORD_LENGTH = 25;
    
    public static $definition = array(
        'table' => 'sm_category_search_mapping',
        'primary' => 'id_sm_category_search_mapping',
        'fields' => array(
            'id_category' => array(
                'type' => self::TYPE_INT,
                'validate' => 'isInt', 
                'required' => true
            ),
            'id_lang' => array(
                'type' => self::TYPE_INT,
                'validate' => 'isInt', 
                'required' => true
            ),
            'word' => array(
                'type' => self::TYPE_STRING,
                'validate' => 'isString',
                'size' => self::PS_SM_SEARCH_MAX_WORD_LENGTH,
                'required' => true
            )
        ) 
    );
    
    public function getFields()
    {
        parent::validateFields();
            
        if (isset($this->id)) {
            $fields['id_sm_category_search_mapping'] = intval($this->id);    
        }
        $fields['id_category'] = intval($this->id_category);
        $fields['id_lang'] = intval($this->id_lang);
        $fields['word'] = strval($this->word);
        
        return $fields;
    }
    
    public static function deleteAll()
    {
        $table = _DB_PREFIX_.'sm_category_search_mapping';
        return Db::getInstance()->delete($table);
    }
    
    public static function createDbTable()
    {
        $sql = 'CREATE TABLE `'._DB_PREFIX_.'sm_category_search_mapping` (
                    `id_sm_category_search_mapping` int(10) unsigned NOT NULL AUTO_INCREMENT,
                    `id_category` int(10) NOT NULL,
                    `id_lang` int(10) NOT NULL,
                    `word` varchar('.self::PS_SM_SEARCH_MAX_WORD_LENGTH.') NOT NULL,
                    CONSTRAINT uk_cat_word UNIQUE (`id_category`, `word`, `id_lang`),
                    PRIMARY KEY  (`id_sm_category_search_mapping`)
                ) ENGINE='._MYSQL_ENGINE_.' CHARSET=utf8;';
                
        if(!Db::getInstance()->Execute(trim($sql))) {
            return false;
        }
        
        return true;
    }
    
    public static function deleteDbTable()
    {
        return Db::getInstance()->Execute('DROP TABLE IF EXISTS `'._DB_PREFIX_.'sm_category_search_mapping`');
    }
    
    public static function regenerateSearchIndex() 
    {    
        $output = '';
        $index = 0;
        foreach(Language::getIDs() as $lang_id) {
            $categoriesNameDesc = self::getCategoriesNameAndDesc($lang_id);
            
            foreach($categoriesNameDesc as $cat) {
                $nameAry = self::sanitize($cat['name'], $lang_id);
                $caegoryAry = self::sanitize($cat['description'], $lang_id);
                $wordAry = array_unique(array_merge($nameAry, $caegoryAry));
                
                foreach($wordAry as $word) {
                    $output .= Logger::log('idx: '.($index++).' Add word: '.$word.' '.' category: '.$cat['id_category'].' lang: '.$lang_id);
                    $mapping = new CategorySearchMapping();
                    $mapping->id_category = $cat['id_category'];
                    $mapping->id_lang = $lang_id;
                    $mapping->word = $word;
                    $mapping->add();
                }
            }
        }
        
        return $output;
    }
    
    // Rewrite to get active
    public static function getCategoriesNameAndDesc($id_lang)
    {
        $res = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS('
                    SELECT c.`id_category`, cl.`name`, cl.`description`
                    FROM `'._DB_PREFIX_.'category` c
                    LEFT JOIN `'._DB_PREFIX_.'category_lang` cl ON (c.`id_category` = cl.`id_category`'.Shop::addSqlRestrictionOnLang('cl').')
                    '.Shop::addSqlAssociation('category', 'c').'
                    WHERE cl.`id_lang` = '.(int)$id_lang.'
                    AND c.`active` = 1
                    AND c.`id_category` != '.Configuration::get('PS_ROOT_CATEGORY').'
                    GROUP BY c.id_category
                    ORDER BY c.`id_category`, category_shop.`position`');
                    
        foreach($res as $key => $cat) {
            $category = new Category($cat['id_category']);
            $parents = $category->getParentsCategories($id_lang);
            foreach($parents as $prnt) {
                if(0 == $prnt['active']) {
                    Logger::log('unsetting: '.$cat['name']);
                    unset($res[$key]);
                }
            }
        }
        
        return $res;
    }
    
    public static function sanitize($value, $id_lang)
    {
        $sanitized = Search::sanitize($value, $id_lang);
        $sanitizedAry = explode(' ', $sanitized);
        return array_filter($sanitizedAry, function($var){return strlen($var) >= (int)Configuration::get('PS_SEARCH_MINWORDLEN');});
    }
    
    public static function find($id_lang, $expr, $limit = null, Context $context = null)
    {
        if (!$context) {
            $context = Context::getContext();
        }
        $db = Db::getInstance(_PS_USE_SQL_SLAVE_);
        
        // $words = explode(' ', Search::sanitize($expr, $id_lang, false, $context->language->iso_code));
        $words = explode(' ', Search::sanitize($expr, $id_lang, false));
        $limit = ( (int)$limit ? $limit : 10 );
        $categoryWeight = array();
        
        foreach ($words as $key => $word) {
            if (!empty($word) && strlen($word) >= (int)Configuration::get('PS_SEARCH_MINWORDLEN')) {
                Logger::log('search for: '.$word);
                $word = Search::sanitize($word, $id_lang);
                $start_search = '%';
                $end_search = '%';
                
                $query = 'SELECT `id_category`
                      FROM '._DB_PREFIX_.'sm_category_search_mapping
	                  WHERE `id_lang` = '.(int)$id_lang.'
                      AND `word` LIKE
    				  \''.$start_search.pSQL(Tools::substr($word, 0, self::PS_SM_SEARCH_MAX_WORD_LENGTH)).$end_search.'\'';
                    
                Logger::log('query: '.$query);
                      
                foreach( $db->executeS($query, true, false) as $row ) {
                    if(array_key_exists($row['id_category'], $categoryWeight)) {
                        $categoryWeight[$row['id_category']]++;
                    } else {
                        $categoryWeight[$row['id_category']] = 1;
                    }
                    Logger::log('got category: '.$row['id_category']);
                }
            } else {
                unset($words[$key]);
            }
        }
        
        Logger::log('limit: '.$limit);
        arsort($categoryWeight);
        $categoryWeight = array_slice($categoryWeight, 0, $limit, true);

        if(defined('_PM_CAT_LOG') && _PM_CAT_LOG) {
            foreach($categoryWeight as $key => $cw) {
                Logger::log('category: '.$key.' weight: '.$cw);
            }
        }
        
        return $categoryWeight;
    }
}