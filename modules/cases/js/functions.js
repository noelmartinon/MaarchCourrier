function unlink_case(path_manage_script,case_id,res_id){
	new Ajax.Request(path_manage_script,
	{
		method:'post',
		parameters: { 
			case_id : case_id,
			res_id : res_id,
		},
		onSuccess: function(answer){
			eval('response='+answer.responseText);
			if(response){
				window.top.location.href='index.php?page=details&dir=indexing_searching&id='+res_id;
			}else{
				alert('Something went wrong...');
			}
		},
		onFailure: function(){
			alert('Something went wrong...');
		}
	});
}


function tabClickCases (TabId){
 
    var AllTab = $j(".tab-trig");
    AllTab.removeClass("tab-trig-open");        
    var doc = $j("#"+TabId);        
    doc.addClass("tab-trig-open");

    $j(".frame-targ").css('display','none');
    $j('#frame-'+TabId).css('display','block');

}
function tabClickedCases(TabId, Iframe) {
    $j(".detailsCasesIframe").css("display","none");    
	$j("#"+Iframe).css("display","");
    $j(".detailsCasesButton").removeClass("detailsCasesClicked");
    $j("#"+TabId).addClass("detailsCasesClicked");
}