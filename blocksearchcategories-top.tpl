<!-- Block search categories module TOP -->
<div id="search_block_top" class="col-sm-4 clearfix">
	<form id="searchbox" method="get" action="{$link->getModuleLink('blocksearchcategories', 'async')|escape:'html':'UTF-8'}" >
		<input type="hidden" name="fc" value="module" />
        <input type="hidden" name="module" value="blocksearchcategories" />
        <input type="hidden" name="controller" value="sync" />
		<input class="search_query form-control" type="text" id="search_query_top" name="search_query" placeholder="{l s='Search' mod='blocksearchcategories'}"  />
		<button type="submit" name="submit_search" class="btn btn-default button-search">
			<span>{l s='Search' mod='blocksearchcategories'}</span>
		</button>
	</form>
</div>
<!-- /Block search categories module TOP -->