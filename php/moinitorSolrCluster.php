<?php
/*
This page will ping some solr instance cluster of 8 nodes in order to get the status of the cluster.
If cannot get the zookeeper status of the cluster, then the cluster is down and this scripts returns a 503  Service Unavailable.
*/
$start = microtime(true);
$cluser_instances = array(
    'solrl1.instance.net',
    'solrl2.instance.net',
    'solrl3.instance.net',
    'solrl4.instance.net',
    'solrl5.instance.net',
    'solrl6.instance.net',
    'solrl7.instance.net',
    'solrl8.instance.net');
foreach ($cluser_instances as $value) {
  try {
       $cluster_json = file_get_contents('http://'.$value.'/solr/zookeeper?detail=true&path=%2Fclusterstate.json&_=1405115855940');
//           var_dump($cluster_json);
  } catch (Exception $e) {
      $cluster_json = null;
  }
  if ($cluster_json)
      break;
}
if ($cluster_json) {
    $cluster = json_decode($cluster_json, true);
    $cluster = json_decode($cluster['znode']['data'], true);
} else {
    header("HTTP/1.1 503 Service Unavailable");
}
?>
 
<html>
<title>solr cluster ping</title>
<head></head>
<style>
table, th, td
{
border-collapse:collapse;
border:0px solid black;
}
th, td
{
padding:5px;
}
</style>
<body>
<h1>Solr cluster ping</h1>


<br/>

<?php
$doPing = false;
if ($doPing) {
//Direct ping
//Previous way to get a random instance, but it may be down.
    $i = rand(0, sizeof($cluser_instances)-1);
    $value = $cluser_instances[$i];
    $status = 'UNK';
    $statusId = '-';
    $qtime = '-';
    try {
        $ping_data = file_get_contents('http://'.$value.'/solr/bmp/admin/ping');
        if (empty($ping_data))
            throw new Exception('No ping result!.');
        $xml = simplexml_load_string($ping_data);
        $status = $xml->str;
        $statusId = $xml->lst->int[0];
        $qtime = $xml->lst->int[1];
    } catch (Exception $e) {
        $status = 'DOWN' ;
    }
    echo "<h3>instance".$i." (".$qtime.", ".$statusId.") ".$status." </h3>\n"; 
}
?>

<?php if (isset($cluster)) { ?>
<table  border="0">
    <tr>
        <th>Collection</th>
        <th>Shard</th>
        <th>Shard state</th>
        <th>Node</th>
        <th>State</th>
        <th>Core</th>
        <th>leader</th>
    </tr>
    <?php
    
    foreach ($cluster as $collectionName => $collection){
        echo "<tr>";
        echo "<td>".$collectionName."</td>";
        foreach ($collection['shards'] as $shardName => $shard){
            echo "<tr><td></td>";
            echo "<td>".$shardName."</td>";
            echo "<td>".$shard['state']."</td>";
            echo "</tr>\n";            
            foreach ($shard['replicas'] as $repName => $node) {
                $nodeName = explode('.', $node['node_name']);
                $node['leader'] = array_key_exists('leader', $node) ? $node['leader'] : '';
                echo "<tr><td colspan='3'></td>";
                echo "<td>".($nodeName[0])."</td>";
                echo "<td>".$node['state']."</td>";
                echo "<td>".$node['core']."</td>";
                echo "<td>".$node['leader']."</td>";
                echo "</tr>\n";
            }
        }
        echo "</tr>";
    }
    ?>
</table>
<?php } ?>
<?php
$end = microtime(true);
echo "<h3>report time (seg): ".number_format($end-$start, 2)."<h3>"; 
?>
</body> 
 </html>

