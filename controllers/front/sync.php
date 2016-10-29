<?php

class blocksearchcategoriessyncModuleFrontController extends ModuleFrontController
{    
    public function initContent()
    {
        parent::initContent();
        
        $query = Tools::getValue('search_query');
        if($query) {
            $lang_id = $this->context->language->id;
            $categoryWeights = BlockSearchCategories::find($lang_id, $query);
            if(count($categoryWeights)) {
                $catId = array_keys($categoryWeights)[0];
                Tools::redirect($this->context->link->getCategoryLink($catId));
            }
        }
    }
}