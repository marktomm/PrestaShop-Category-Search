<?php

if (!defined('_PS_VERSION_'))
	exit;
    
require_once(_PS_MODULE_DIR_.'/blocksearchcategories/searchmapping.php');

class BlockSearchCategories extends Module
{
    const DB_SW = 'sm_category_search_word';
    const DB_SI = 'sm_category_search_index';
    const NAME = 'blocksearchcategories';
    
	public function __construct()
	{
		$this->name = self::NAME;
		$this->tab = 'front_office_features';
		$this->version = '1.0.0';
		$this->author = 'Mark Tomm';
		$this->need_instance = 0;
        $this->bootstrap = true;

		parent::__construct();

		$this->displayName = $this->l('Quick Category search block');
		$this->description = $this->l('Adds a quick category search field to your website.');
		$this->ps_versions_compliancy = array('min' => '1.6', 'max' => '1.6.99.99');
	}

	public function install()
	{
		if (!parent::install() || !$this->registerHook('top') || !$this->registerHook('header') || !$this->registerHook('displaySearch'))
			return false;
                
        if( !$this->dbInstall() ) {
            $this->uninstall();
            return false;
        }
        
        Configuration::updateValue('SM_CATEGORIES_LIMIT', 10);

		return true;
	}
    
    public function uninstall() 
    {
        CategorySearchMapping::deleteDbTable();
        
        Configuration::deleteByName('SM_CATEGORIES_LIMIT');
        
        return parent::uninstall();
    }
    
    protected function dbInstall() 
    {
        return CategorySearchMapping::createDbTable();
    }
    
    protected function dbClear() 
    {
        $output = '';
        $res = CategorySearchMapping::deleteAll();
                
        if(!$res) {
            $output = $this->displayWarning($this->l('db clear failed'));
        }
        
        return $output;
    }
    
    protected function dbRegenerate() 
    {            
        return CategorySearchMapping::regenerateSearchIndex();
    }

	public function hookHeader($params)
	{
		$this->context->controller->addCSS(($this->_path).'blocksearchcategories.css', 'all');
		$this->context->controller->addJqueryPlugin('autocomplete');

        $url = $this->context->link->getModuleLink($this->name, 'async', array('ajax' => 1, 'action' => 'search'));
		Media::addJsDef(array('search_url' => $url)); 
		$this->context->controller->addJS(($this->_path).'blocksearchcategories.js');

	}

	public function hookTop($params)
	{
        $key = $this->getCacheId('blocksearchcategories-top');
        
		$this->calculHookCommon($params);
		$this->smarty->assign(array(
			'blocksearch_type' => 'top'
			)
		);
		
		Media::addJsDef(array('blocksearch_type' => 'top'));
		return $this->display(__FILE__, 'blocksearchcategories-top.tpl', Tools::getValue('search_query') ? null : $key );
	}
    
    public function hookRightColumn($params)
    {
        if (!$this->isCached('blocksearchcategories.tpl', $this->getCacheId()))
        {
            $this->calculHookCommon($params);
            $this->smarty->assign(array(
                'blocksearch_type' => 'block',
                )
            );
        }
        Media::addJsDef(array('blocksearch_type' => 'block'));
        return $this->display(__FILE__, 'blocksearch.tpl', Tools::getValue('search_query') ? null : $this->getCacheId());
    }

	public function hookDisplaySearch($params)
    {
        return $this->hookRightColumn($params);
    }

	protected function calculHookCommon($params)
	{
		$this->smarty->assign(array(
			'ENT_QUOTES' =>		ENT_QUOTES,
			'search_ssl' =>		Tools::usingSecureMode(),
			'self' =>			dirname(__FILE__),
		));

		return true;
	}
    
    public function actionObjectCategoryUpdateAfter($params) {
        return;
    }
    
    public function actionObjectCategoryDeleteAfter($params) {
        return;
    }
    
    public function actionObjectCategoryAddAfter($params) {
        return;
    }
    
    public function getContent()
    {
        $output = $this->postProcess();
        $output .= $this->displayForm();
        return $output;
    }
    
    public function postProcess()
    {
        $output = '';
        
        if (Tools::isSubmit('submitReIndex')) {
            $output .= $this->reIndex();
        }
        
        return $output;
    }
    
    public function displayForm()
    {
        $output = '';
        $defaultBtnClass = 'btn btn-default btn-block';
        
        $helper = new HelperForm();

        $index = 0;
		$fields_form = array();
		$fields_form[$index++]['form'] = array(
			'legend' => array(
	            'title' => $this->l('Regenerate Category Search Index'),
	        ),
			'submit' => array(
				'title' => $this->l('ReIndex'),
				'name' => 'submitReIndex',
				'class' => $defaultBtnClass
			)
		);
        
        $default_lang = (int)Configuration::get('PS_LANG_DEFAULT');
		
		// Module, token and currentIndex
		$helper->module = $this;
	    $helper->name_controller = $this->name;
	    $helper->token = Tools::getAdminTokenLite('AdminModules');
	    $helper->currentIndex = $this->getAdminIndexUrl(false);
		
		// Language
	    $helper->default_form_language = $default_lang;
	    $helper->allow_employee_form_lang = $default_lang;

        //No submitAction

		$output .= $helper->generateForm($fields_form);
        
        return $output;
    }
    
    public function reIndex() 
    {
        $output = $this->dbClear();
        $output .= $this->dbRegenerate();
        return $output;
    }
    
    public static function find($id_lang, $expr)
    {
        return CategorySearchMapping::find($id_lang, $expr, Configuration::get('SM_CATEGORIES_LIMIT'));
    }
    
    protected function getAdminIndexUrl($withToken = true) 
    {
        return $this->context->link->getAdminLink('AdminModules', $withToken).'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
    }
}
