<?php

class blocksearchcategoriesasyncModuleFrontController extends ModuleFrontController
{        
	public function displayAjaxSearch()
	{
        $query = Tools::getValue('q');
        if($query) {
            $lang_id = $this->context->language->id;
            $categoryWeights = BlockSearchCategories::find($lang_id, $query);
            $res = array();
            foreach($categoryWeights as $catId => $weight) {
                $category = new Category($catId, $lang_id);
                $res[] = array(
                                'category_link' => $this->context->link->getCategoryLink($category),
                                'cname' => $category->name,
                                'cdesc' => $category->description,
                                'category_id' => $catId
                            );
            }
            
            $this->ajaxDie(Tools::jsonEncode($res));
        }
	}
}
