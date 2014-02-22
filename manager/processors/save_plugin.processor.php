<?php 
if(IN_MANAGER_MODE!="true") die("<b>INCLUDE_ORDERING_ERROR</b><br /><br />Please use the MODX Content Manager instead of accessing this file directly.");
if(!$modx->hasPermission('save_plugin')) {  
    $modx->webAlertAndQuit($_lang["error_no_privileges"]);
}

$id = intval($_POST['id']);
$name = $modx->db->escape(trim($_POST['name']));
$description = $modx->db->escape($_POST['description']);
$locked = $_POST['locked']=='on' ? '1' : '0' ;
$plugincode = $modx->db->escape($_POST['post']);
$properties = $modx->db->escape($_POST['properties']);
$disabled = $_POST['disabled']=="on" ? '1' : '0';
$moduleguid = $modx->db->escape($_POST['moduleguid']);
$sysevents = $_POST['sysevents'];

//Kyle Jaebker - added category support
if (empty($_POST['newcategory']) && $_POST['categoryid'] > 0) {
    $categoryid = $modx->db->escape($_POST['categoryid']);
} elseif (empty($_POST['newcategory']) && $_POST['categoryid'] <= 0) {
    $categoryid = '0';
} else {
    include_once "categories.inc.php";
    $catCheck = checkCategory($modx->db->escape($_POST['newcategory']));
    if ($catCheck) {
        $categoryid = $catCheck;
    } else {
        $categoryid = newCategory($_POST['newcategory']);
    }
}

if($name=="") $name = "Untitled plugin";

$tblSitePlugins = $modx->getFullTableName('site_plugins');
switch ($_POST['mode']) {
    case '101':

        // invoke OnBeforePluginFormSave event
        $modx->invokeEvent("OnBeforePluginFormSave",
                                array(
                                    "mode"  => "new",
                                    "id"    => $id
                                ));
    
		// disallow duplicate names for new plugins
		$sql = "SELECT COUNT(id) FROM {$dbase}.`{$table_prefix}site_plugins` WHERE name = '{$name}'";
		$rs = $modx->db->query($sql);
		$count = $modx->db->getValue($rs);
		if($count > 0) {
			$modx->manager->saveFormValues(101);
			$modx->webAlertAndQuit(sprintf($_lang['duplicate_name_found_general'], $_lang['plugin'], $name), "index.php?a=101");
		}

		//do stuff to save the new plugin
        $sql = "INSERT INTO {$tblSitePlugins} (name, description, plugincode, disabled, moduleguid, locked, properties, category) VALUES('{$name}', '{$description}', '{$plugincode}', {$disabled}, '{$moduleguid}', {$locked}, '{$properties}', {$categoryid});";
        $modx->db->query($sql);
            // get the id
            if(!$newid=$modx->db->getInsertId()) {
                $modx->webAlertAndQuit("Couldn't get last insert key!");
            }
            
            // save event listeners
            saveEventListeners($newid,$sysevents,$_POST['mode']);
            
            // invoke OnPluginFormSave event
            $modx->invokeEvent("OnPluginFormSave",
                                    array(
                                        "mode"  => "new",
                                        "id"    => $newid
                                    ));
            
		// Set the item name for logger
		$_SESSION['itemname'] = $name;

            // empty cache
            $modx->clearCache('full');

            // finished emptying cache - redirect
            if($_POST['stay']!='') {
                $a = ($_POST['stay']=='2') ? "102&id=$newid":"101";
                $header="Location: index.php?a=".$a."&r=2&stay=".$_POST['stay'];
                header($header);
            } else {
                $header="Location: index.php?a=76&r=2";
                header($header);
            }
        break;
    case '102':

        // invoke OnBeforePluginFormSave event
        $modx->invokeEvent("OnBeforePluginFormSave",
                                array(
                                    "mode"  => "upd",
                                    "id"    => $id
                                ));
     
        //do stuff to save the edited plugin    
        $sql = "UPDATE {$tblSitePlugins} SET name='{$name}', description='{$description}', plugincode='{$plugincode}', disabled={$disabled}, moduleguid='{$moduleguid}', locked={$locked}, properties='{$properties}', category={$categoryid}  WHERE id={$id}";
        $modx->db->query($sql);
            // save event listeners
            saveEventListeners($id,$sysevents,$_POST['mode']);

            // invoke OnPluginFormSave event
            $modx->invokeEvent("OnPluginFormSave",
                                    array(
                                        "mode"  => "upd",
                                        "id"    => $id
                                    ));
            
		// Set the item name for logger
		$_SESSION['itemname'] = $name;

            // empty cache
            $modx->clearCache('full');

            // finished emptying cache - redirect   
            if($_POST['stay']!='') {
                $a = ($_POST['stay']=='2') ? "102&id=$id":"101";
                $header="Location: index.php?a=".$a."&r=2&stay=".$_POST['stay'];
                header($header);
            } else {
                $header="Location: index.php?a=76&r=2";
                header($header);
            }
        break;
    default:
    ?>  
        Erm... You supposed to be here now?     
    <?php
}


# Save Plugin Event Listeners
function saveEventListeners($id,$sysevents,$mode) {
    global $modx;
    // save selected system events
    $tblSitePluginEvents = $modx->getFullTableName('site_plugin_events');
    $sql = "INSERT INTO {$tblSitePluginEvents} (pluginid,evtid,priority) VALUES ";
    for($i=0;$i<count($sysevents);$i++){
        if ($mode == '101') {
            $prioritySql = "select max(priority) as priority from {$tblSitePluginEvents} where evtid={$sysevents[$i]}";
        } else {
            $prioritySql = "select priority from {$tblSitePluginEvents} where evtid={$sysevents[$i]} and pluginid={$id}";
        }
        $rs = $modx->db->query($prioritySql);
        $prevPriority = $modx->db->getRow($rs);
        if ($mode == '101') {
            $priority = isset($prevPriority['priority']) ? $prevPriority['priority'] + 1 : 1;
        } else {
            $priority = isset($prevPriority['priority']) ? $prevPriority['priority'] : 1;
        }
        if($i>0) $sql.=",";
        $sql.= "(".$id.",".$sysevents[$i].",".$priority.")";
    }
    $modx->db->query("DELETE FROM {$tblSitePluginEvents} WHERE pluginid={$id}");
    if (count($sysevents)>0) $modx->db->query($sql);
}

?>