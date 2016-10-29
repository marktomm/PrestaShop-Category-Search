<!-- Block search categories module -->
<div id="search_block_left" class="block exclusive">
	<p class="title_block">{l s='Search' mod='blocksearchcategories'}</p>
	<form method="get" action="{$link->getModuleLink('blocksearchcategories', 'async')|escape:'html':'UTF-8'}" id="searchbox">
    	<label for="search_query_block">{l s='Search products:' mod='blocksearchcategories'}</label>
		<p class="block_content clearfix">
			<input type="hidden" name="orderby" value="position" />
			<input type="hidden" name="controller" value="search" />
			<input type="hidden" name="orderway" value="desc" />
			<input class="search_query form-control grey" type="text" id="search_query_block" name="search_query"/>
			<button type="submit" id="search_button" class="btn btn-default button button-small"><span><i class="icon-search"></i></span></button>
		</p>
	</form>
</div>
<!-- /Block search categories module -->