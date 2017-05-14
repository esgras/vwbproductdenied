$(document).ready(function() {
    $('td.pointer').each(function(i, v){
        var $this = $(this);
        var onclick = $this.attr('onclick');
        onclick = onclick.replace(searchController, replaceController);
        var str = onclick.slice(0, onclick.indexOf('token=') + 6) + productsToken + "'";
        $this.attr('onclick', str);
    });
});