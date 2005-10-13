<?php
if(IN_MANAGER_MODE!="true") die("<b>INCLUDE_ORDERING_ERROR</b><br /><br />Please use the MODx Content Manager instead of accessing this file directly.");
if(!$modx->hasPermission('edit_snippet') && $_REQUEST['a']==22) {	$e->setError(3);
	$e->dumpError();	
}
if(!$modx->hasPermission('new_snippet') && $_REQUEST['a']==23) {	$e->setError(3);
	$e->dumpError();	
}



function isNumber($var)
{
	if(strlen($var)==0) {
		return false;
	}
	for ($i=0;$i<strlen($var);$i++) {
		if ( substr_count ("0123456789", substr ($var, $i, 1) ) == 0 ) {
			return false;
		}
    }
	return true;
}

if(isset($_REQUEST['id'])) {
	$id = $_REQUEST['id'];
} else {
	$id=0;
}


// check to see the snippet editor isn't locked
$sql = "SELECT internalKey, username FROM $dbase.".$table_prefix."active_users WHERE $dbase.".$table_prefix."active_users.action=22 AND $dbase.".$table_prefix."active_users.id=$id";
$rs = mysql_query($sql);
$limit = mysql_num_rows($rs);
if($limit>1) {
	for ($i=0;$i<$limit;$i++) {
		$lock = mysql_fetch_assoc($rs);
		if($lock['internalKey']!=$modx->getLoginUserID()) {		
			$msg = sprintf($_lang["lock_msg"],$lock['username'],"snippet");
			$e->setError(5, $msg);
			$e->dumpError();
		}
	}
} 
// end check for lock

// make sure the id's a number
if(!isNumber($id)) {
	echo "Passed ID is NaN!";
	exit;
}

if(isset($_GET['id'])) {
	$sql = "SELECT * FROM $dbase.".$table_prefix."site_snippets WHERE $dbase.".$table_prefix."site_snippets.id = $id;";
	$rs = mysql_query($sql);
	$limit = mysql_num_rows($rs);
	if($limit>1) {
		echo "Oops, Multiple snippets sharing same unique id. Not good.<p>";
		exit;
	}
	if($limit<1) {
		header("Location: /index.php?id=".$site_start);
	}
	$content = mysql_fetch_assoc($rs);
	$_SESSION['itemname']=$content['name'];
	if($content['locked']==1 && $_SESSION['mgrRole']!=1) {
		$e->setError(3);
		$e->dumpError();
	}
} else {
	$_SESSION['itemname']="New snippet";
}
?>
<script language="JavaScript">

function duplicaterecord(){
	if(confirm("<?php echo $_lang['confirm_duplicate_record'] ?>")==true) {
		documentDirty=false;
		document.location.href="index.php?id=<?php echo $_REQUEST['id']; ?>&a=98";
	}
}

function deletedocument() {
	if(confirm("<?php echo $_lang['confirm_delete_snippet']; ?>")==true) {
		documentDirty=false;
		document.location.href="index.php?id=" + document.mutate.id.value + "&a=25";
	}
}

// Current Params
var currentParams = [];

function showParameters(ctrl) {
	var c,p,df,cp;
	var ar,desc,value,key,dt;
	
	currentParams = []; // reset;

	if (ctrl) f = ctrl.form;
	else {
		f= document.forms['mutate'];
		if(!f) return;
	}

	// setup parameters
	tr = (document.getElementById) ? document.getElementById('displayparamrow'):document.all['displayparamrow'];
	dp = (f.properties.value) ? f.properties.value.split("&"):"";
	if(!dp) tr.style.display='none';
	else {
		t='<table width="300" style="margin-bottom:3px;margin-left:14px;background-color:#EEEEEE" cellpadding="2" cellspacing="1"><thead><tr><td width="50%"><?php echo $_lang['parameter']; ?></td><td width="50%"><?php echo $_lang['value']; ?></td></tr></thead>';
		for(p in dp) {
			dp[p]=(dp[p]+'').replace(/^\s|\s$/,""); // trim
			ar = dp[p].split("=");
			key = ar[0]		// param
			ar = (ar[1]+'').split(";");
			desc = ar[0];	// description
			dt = ar[1];		// data type	
			value = decode((ar[2])? ar[2]:'');
			
			// store values for later retrieval
			if (key && dt=='list') currentParams[key] = [desc,dt,value,ar[3]]; 
			else if (key) currentParams[key] = [desc,dt,value];
			
			if (dt) {
				switch(dt) {
				case 'int':
					c = '<input type="text" name="prop_'+key+'" value="'+value+'" size="30" onchange="setParameter(\''+key+'\',\''+dt+'\',this)" />';
					break;
				case 'list':
					value = ar[3];
					c = '<select name="prop_'+key+'" height="1" style="width:168px" onchange="setParameter(\''+key+'\',\''+dt+'\',this)">';
					ls = (ar[2]+'').split(",");
					if(currentParams[key]==ar[2]) currentParams[key] = ls[0]; // use first list item as default
					for(i=0;i<ls.length;i++){						
						c += '<option value="'+ls[i]+'"'+((ls[i]==value)? ' selected="selected"':'')+'>'+ls[i]+'</option>';
					}
					c += '</select>';
					break;
				default:  // string
					c = '<input type="text" name="prop_'+key+'" value="'+value+'" size="30" onchange="setParameter(\''+key+'\',\''+dt+'\',this)" />';
					break;

				}
				t +='<tr><td bgcolor="#FFFFFF" width="50%">'+desc+'</td><td bgcolor="#FFFFFF" width="50%">'+c+'</td></tr>';
			};
		}
		t+='</table>';
		td = (document.getElementById) ? document.getElementById('displayparams'):document.all['displayparams'];	
		td.innerHTML = t;
		tr.style.display='';
	}
	implodeParameters();
}

function setParameter(key,dt,ctrl) {
	var v;	
	if(!ctrl) return null;
	switch (dt) {
		case 'int':
			ctrl.value = parseInt(ctrl.value);
			if(isNaN(ctrl.value)) ctrl.value = 0;
			v = ctrl.value;
			break;
		case 'list':
			v = ctrl.options[ctrl.selectedIndex].value;
			currentParams[key][3] = v;
			implodeParameters();
			return;
			break;
		default:
			v = ctrl.value+'';
			break;
	}
	currentParams[key][2] = v;
	implodeParameters();
}

// implode parameters
function implodeParameters(){
	var v, p, s='';	
	for(p in currentParams){
		if(currentParams[p]) {
			v = currentParams[p].join(";");
			if(s && v) s+=' ';
			if(v) s += '&'+p+'='+ v;
		}
	}
	document.forms['mutate'].properties.value = s;
}

function encode(s){
	s=s+'';
	s = s.replace(/\=/g,'%3D'); // =
	s = s.replace(/\&/g,'%26'); // &
	return s;
}

function decode(s){
	s=s+'';
	s = s.replace(/\%3D/g,'='); // =
	s = s.replace(/\%26/g,'&'); // &
	return s;
}

</script>

<form name="mutate" method="post" action="index.php?a=24">
<?php
	// invoke OnSnipFormPrerender event
	$evtOut = $modx->invokeEvent("OnSnipFormPrerender",array("id" => $id));
	if(is_array($evtOut)) echo implode("",$evtOut);
?>
<input type="hidden" name="id" value="<?php echo $content['id'];?>">
<input type="hidden" name="mode" value="<?php echo $_GET['a'];?>">

<div class="subTitle">
	<span class="right"><img src="media/images/_tx_.gif" width="1" height="5"><br /><?php echo $_lang['snippet_title']; ?></span>

	<table cellpadding="0" cellspacing="0">
		<td id="Button1" onclick="documentDirty=false; document.mutate.save.click(); saveWait('mutate');"><img src="media/images/icons/save.gif" align="absmiddle"> <?php echo $_lang['save']; ?></td>
			<script>createButton(document.getElementById("Button1"));</script>
<?php if($_GET['a']=='22') { ?>
		<td id="Button2" onclick="duplicaterecord();"><img src="media/images/icons/copy.gif" align="absmiddle"> <?php echo $_lang["duplicate"]; ?></td>
			<script>createButton(document.getElementById("Button2"));</script>
		<td id="Button3" onclick="deletedocument();"><img src="media/images/icons/delete.gif" align="absmiddle"> <?php echo $_lang['delete']; ?></span></td>
			<script>createButton(document.getElementById("Button3"));</script>
<?php } ?>
		<td id="Button4" onclick="document.location.href='index.php?a=76';"><img src="media/images/icons/cancel.gif" align="absmiddle"> <?php echo $_lang['cancel']; ?></td>
			<script>createButton(document.getElementById("Button4"));</script>
	</table>
	<div class="stay">   
	<table border="0" cellspacing="1" cellpadding="1">
	<tr>
		<td><span class="comment">&nbsp;After saving:</span></td>
		<td><input name="stay" type="radio" class="inputBox" value="1"  <?php echo $_GET['stay']=='1' ? "checked='checked'":'' ?> /></td><td><span class="comment"><?php echo $_lang['stay_new']; ?></span></td> 
		<td><input name="stay" type="radio" class="inputBox" value="2"  <?php echo $_GET['stay']=='2' ? "checked='checked'":'' ?> /></td><td><span class="comment"><?php echo $_lang['stay']; ?></span></td>
		<td><input name="stay" type="radio" class="inputBox" value=""  <?php echo $_GET['stay']=='' ? "checked='checked'":'' ?> /></td><td><span class="comment"><?php echo $_lang['close']; ?></span></td>
	</tr>
	</table>
	</div>
</div>



<div class="sectionHeader"><img src='media/images/misc/dot.gif' alt="." />&nbsp;<?php echo $_lang['snippet_title']; ?></div><div class="sectionBody">
<?php echo $_lang['snippet_msg']; ?><p />
<link type="text/css" rel="stylesheet" href="media/style/tabs.css" /> 
<script type="text/javascript" src="media/script/tabpane.js"></script> 
<div class="tab-pane" id="snipetPane"> 
	<script type="text/javascript">
		tpSnippet = new WebFXTabPane( document.getElementById( "snipetPane"),false);
	</script> 

	<!-- General -->
    <div class="tab-page" id="tabSnippet"> 
    	<h2 class="tab"><?php echo $_lang["settings_general"] ?></h2> 
    	<script type="text/javascript">tpSnippet.addTabPage( document.getElementById( "tabSnippet" ) );</script> 
		<table border="0" cellspacing="0" cellpadding="0">
		  <tr>
			<td align="left"><?php echo $_lang['snippet_name']; ?>:</td>
			<td align="left"><span style="font-family:'Courier New', Courier, mono">[[</span><input name="name" type="text" maxlength="100" value="<?php echo $content['name'];?>" class="inputBox" style="width:150px;" onChange='documentDirty=true;'><span style="font-family:'Courier New', Courier, mono">]]</span><span class="warning" id='savingMessage'>&nbsp;</span></td>
		  </tr>
		  <tr>
			<td align="left"><?php echo $_lang['snippet_desc']; ?>:&nbsp;&nbsp;</td>
			<td align="left"><span style="font-family:'Courier New', Courier, mono">&nbsp;&nbsp;</span><input name="description" type="text" maxlength="255" value="<?php echo $content['description'];?>" class="inputBox" style="width:300px;" onChange='documentDirty=true;'></td>
		  </tr>
		  <tr>
			<td align="left" colspan="2"><input name="runsnippet" type="checkbox" class="inputBox" /> <?php echo $_lang['snippet_execonsave']; ?></td>
		  </tr>		  
		  <tr>
			<td align="left" valign="top" colspan="2"><input name="locked" type="checkbox" <?php echo $content['locked']==1 ? "checked='checked'" : "" ;?> class="inputBox"> <?php echo $_lang['lock_snippet']; ?> <span class="comment"><?php echo $_lang['lock_snippet_msg']; ?></span></td>
			</td>
		  </tr>
		</table>
		<textarea name="post" style="width:100%; height:370px;" onchange="documentDirty=true;"><?php echo htmlspecialchars($content['snippet']); ?></textarea>
	</div>
	
	<!-- Properties -->
    <div class="tab-page" id="tabProps"> 
    	<h2 class="tab"><?php echo $_lang["settings_properties"] ?></h2> 
    	<script type="text/javascript">tpSnippet.addTabPage( document.getElementById( "tabProps" ) );</script> 
		<table width="90%" border="0" cellspacing="0" cellpadding="0">
		  <tr>
			<td align="left"><?php echo $_lang['import_params']; ?>:&nbsp;&nbsp;</td>
			<td align="left"><span style="font-family:'Courier New', Courier, mono">&nbsp;&nbsp;</span><select name="moduleguid" style="width:300px;" onChange='documentDirty=true;'>
			<option>&nbsp;</option>
			<?php
				$sql =	"SELECT sm.id,sm.name,sm.guid " .
						"FROM ".$modx->getFullTableName("site_modules")." sm ".
						"INNER JOIN ".$modx->getFullTableName("site_module_depobj")." smd ON smd.module=sm.id AND smd.type=40 ".
						"INNER JOIN ".$modx->getFullTableName("site_snippets")." ss ON ss.id=smd.resource ".
						"WHERE smd.resource='$id' AND sm.enable_sharedparams='1' ".
						"ORDER BY sm.name ";
				$ds = $modx->dbQuery($sql);
				if($ds) while($row = $modx->fetchRow($ds)){
					echo "<option value='".$row['guid']."'".($content["moduleguid"]==$row["guid"]? " selected='selected'":"").">".htmlspecialchars($row["name"])."</option>";
				} 
			?>
			</select>
			</td>
		  </tr>
		  <tr>
			<td>&nbsp;</td>
			<td align="left" valign="top"><span style="font-family:'Courier New', Courier, mono">&nbsp;&nbsp;</span><span style="width:300px;" ><span class="comment"><?php echo $_lang['import_params_msg']; ?></span></span><br /><br /></td>
		  </tr>
		  <tr>
			<td align="left" valign="top"><?php echo $_lang['snippet_properties']; ?>:</td>
			<td align="left" valign="top"><span style="font-family:'Courier New', Courier, mono">&nbsp;&nbsp;</span><input name="properties" type="text" maxlength="65535" value="<?php echo $content['properties'];?>" class="inputBox" style="width:300px;" onChange='showParameters(this);documentDirty=true;'></td>
		  </tr>
		  <tr id="displayparamrow">
			<td valign="top" align="left">&nbsp;</td>
			<td align="left" id="displayparams">&nbsp;</td>
		  </tr>
		</table>
	</div>	
</div>
<input type="submit" name="save" style="display:none">
</div>
<?php
	// invoke OnSnipFormRender event
	$evtOut = $modx->invokeEvent("OnSnipFormRender",array("id" => $id));
	if(is_array($evtOut)) echo implode("",$evtOut);
?>
</form>
<script>
	setTimeout('showParameters();',10);
</script>
