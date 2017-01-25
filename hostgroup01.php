<?php
// load the Zabbix Php API which is included in this build (tested on Zabbix v2.2.2)
require_once 'lib/php/ZabbixApi.class.php';
use ZabbixApi\ZabbixApi;

try
{
    // connect to Zabbix API
    $api = new ZabbixApi('http://172.20.152.17/zabbix/api_jsonrpc.php', 'Admin', 'zabbix');

    /* ... do your stuff here ... */
}
catch(Exception $e)
{
    // Exception in ZabbixApi catched
    echo $e->getMessage();
}
// Set Defaults
$api->setDefaultParams(array(
        'output' => 'extend',
));
?>
<!DOCTYPE html>
<html>
<head>
        <meta charset="UTF-8">
        <title>Zabbix Dashboard</title>
        <!-- Let's reset the default style properties -->
        <link rel="stylesheet" type="text/css" href="style/reset.css" />
        <link rel="stylesheet" type="text/css" href="style/theme-alt.css" />
        <!-- added the jQuery library for reloading the page and future features -->
        <script src="lib/js/jquery-2.1.1.min.js"></script>
        <!-- added the masonry js so all blocks are better alligned -->
        <script src="lib/js/masonry.pkgd.min.js"></script>
        <!-- Removed this temporary because I disliked the look -->
        <!-- <body class="js-masonry"  data-masonry-options='{ "columnWidth": 250, "itemSelector": ".groupbox" }'> -->
<body id="bg-two">

<!-- START GET RENDER DATE - Which will show date and time of generating this file -->
<div id="timestamp">
    <div id="date"><?php echo date("d F Y", time()); ?></div>
    <div id="time"><?php echo date("H:i", time()); ?></div>
</div>
<!-- END GET RENDER DATE -->

<!-- We could use the Zabbix HostGroup name here, but would not work in a nice way when using a dozen of hostgroups, yet! So we hardcoded it here. -->
<div id="sheetname">Your Group</div>
<div class="grid">


<?php
//
$groups = $api->hostgroupGet(array(
       'output' => array('name'),
       'selectHosts' => array(
               'flags',
               'hostid',
               'groupids'=> -1,
               'name',
               'maintenance_status'),
       'real_hosts ' => 1,
	    'with_monitored_triggers' => 1,
       'sortfield' => 'name'
    ));

       foreach($groups as $group) {
               $groupIds[] = $group->groupid;
       }

       $triggers = $api->triggerGet(array(
               'output' => array(
                       'priority',
                       'description'),
               'selectHosts' => array('hostid'),
               'groupids' => $groupIds,
               'expandDescription' => 1,
               'only_true' => 1,
               'monitored' => 1,
               'withLastEventUnacknowledged' => 1,
               'sortfield' => 'priority',
               'sortorder' => 'DESC'
       ));

       foreach($triggers as $trigger) {
               foreach($trigger->hosts as $host) {
                       $hostTriggers[$host->hostid][] = $trigger;
               }
       }

// get all hosts from each groupid
    foreach($groups as $group) {
       $groupname = $group->name;
       $hosts = $group->hosts;

       usort($hosts, function ($a, $b) {
               if ($a->name == $b) return 0;
               return ($a->name < $b->name ? -1 : 1);
       });

        if ($hosts) {
        $count = "0";
//      echo "<div class=\"groupbox\">"; // Again, we dont want to use the groupfunction yet
//      echo "<div class=\"title\">" . $groupname . "</div>";

    // print all host IDs
                foreach($hosts as $host) {
                        // Check if host is not disabled, we don't want them!
                        $flaghost = $host->flags;

                        if ($flaghost == "0" && $count == "0") {
                                echo "<div class=\"grid-item\" \"groupbox js-masonry\" data-masonry-options='{ \"itemSelector\": \".hostbox\" }'\">";
                                // echo "<div class=\"title\">" . $groupname . "</div>";
                                $count++;
                        }

                        if ($flaghost == "0" && $count != "0") {

                                $hostid = $host->hostid;
                                $hostname = $host->name;
                                $maintenance = $host->maintenance_status;

                               if (array_key_exists($hostid, $hostTriggers)) {

                                        // Highest Priority error
                                       $hostboxprio = $hostTriggers[$hostid][0]->priority;
                                        //First filter the hosts that are in maintenance and assign the maintenance class if is true
                                        if ($maintenance != "0") {
                                                echo "<div class=\"hostbox maintenance\">";
                                        }
                                        // If hosts are not in maintenance, check for trigger(s) and assign the appropriate class to the box
                                        else {
                                                echo "<div class=\"hostbox nok" . $hostboxprio . "\">";
                                        }
                                        echo "<div class=\"title\">" . $hostname . "</div><div class=\"hostid\">" . $hostid . "</div>";
                                        $count = "0";
                foreach ($hostTriggers[$hostid] as $event) {
                                                if ($count++ <= 0 ) {
                                                        $priority = $event->priority;
                                                        $description = $event->description;

                                        // Remove hostname or host.name in description
                                                        $search = array('{HOSTNAME}', '{HOST.NAME}');
                                                        $description = str_replace($search, "", $description);

                                        // View
                                                        echo "<div class=\"description nok" . $priority ."\">" . $description . "</div>";
                                                } else {
                                                        break;
                                                }
                                        }
                                        }
                                        // If there are no trigger(s) for the host found, assign the "ok" class to the box
                                        else {
                                        echo "<div class=\"hostbox ok\">";
                                        echo "<div class=\"title\">" . $hostname . "</div><div class=\"hostid\">" . $hostid . "</div>";
                                }
                                echo "</div>";
                        }
                }
        if ($count != "0") {echo "</div>";}
        }
    }
    $api->userLogout();
?>
<!-- Second piece of js to gracefully reload the page (value in ms) -->
<script>
        function ReloadPage() {
           location.reload();
        };
        $(document).ready(function() {
          setTimeout("ReloadPage()", 60000);
        });
</script>
</body>
</html>
