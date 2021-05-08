<?php
set_time_limit(0);
date_default_timezone_set('Europe/Paris');

/// function combinaison
function mergeFile ( DOMDocument $target, $fileName )    {
set_time_limit(0);
    
    $source = new DOMDocument();
    $source->load($fileName);

    foreach ( $source->getElementsByTagName("channel") as $channel )   {
        $import = $target->importNode($channel, true);
        $target->documentElement->appendChild($import);
    }
    foreach ( $source->getElementsByTagName("programme") as $programme )   {
        $import = $target->importNode($programme, true);
        $target->documentElement->appendChild($import);
    }
}


/// function pluto.tv
function plutotv () {    
    $dom = new DOMDocument();
        $dom->encoding = 'utf-8';
        $dom->xmlVersion = '1.0';
        $dom->formatOutput = true;
        $xml_file_name = "heure/".date("Y-m-d-H").".xml";
        $root = $dom->createElement('tv');

$old = array("-", "T", ":", ".000Z");
$new = array("", "", "", "");
$datetime = new DateTime('+1 day');
$proxy_cmd = "http://theinternetpics.com/freeweb/index.php?q=";
$source = $proxy_cmd.base64_encode("http://api.pluto.tv/v2/channels?start=".date("Y-m-d")."T". date("H:i:s").".000Z&stop=".$datetime->format("Y-m-d")."T". date("H:i:s").".000Z");
$jsonData = file_get_contents($source);
$json = json_decode($jsonData,true);
foreach ($json as $data) {
    $name = $data['name'];
    $id = $data['_id'];
    $logo = $data['colorLogoPNG']['path'];
        $channel_node = $dom->createElement('channel');
            $attr_channel_id = new DOMAttr('id', $id);
            $channel_node->setAttributeNode($attr_channel_id);

            $child_node_name = $dom->createElement('display-name', $name);
            $channel_node->appendChild($child_node_name);

            $child_node_logo = $dom->createElement('icon');
            $channel_node->appendChild($child_node_logo);
                $attr_channel_logo = new DOMAttr('src', $logo);
                $child_node_logo->setAttributeNode($attr_channel_logo);
            $root->appendChild($channel_node);

    
    foreach ($data['timelines'] as $data2) {
            $title = $data2['title'];
            $start = str_replace($old, $new, $data2['start']);
            $stop = str_replace($old, $new, $data2['stop']);
            $desc =  $data2['episode']['description'];
            $subtitle =  $data2['episode']['name'];
            $category =  $data2['episode']['subGenre'];
            $poster =  $data2['episode']['poster']['path'];
            $rating =  $data2['episode']['rating'];
            $episode =  $data2['episode']['number'];
            $date =  $data2['episode']['clip']['originalReleaseDate'];

        $programme_node = $dom->createElement('programme');
            $attr_programme_id = new DOMAttr('channel', $id);
            $attr_programme_start = new DOMAttr('start', $start." +0000");
            $attr_programme_stop = new DOMAttr('stop', $stop." +0000");
            $programme_node->setAttributeNode($attr_programme_start);
            $programme_node->setAttributeNode($attr_programme_stop);
            $programme_node->setAttributeNode($attr_programme_id);          
            $child_node_title = $dom->createElement('title', htmlspecialchars($title));
            $programme_node->appendChild($child_node_title);
            $child_node_subtitle = $dom->createElement('sub-title', htmlspecialchars($subtitle));
            $programme_node->appendChild($child_node_subtitle);
            $child_node_desc = $dom->createElement('desc', htmlspecialchars($desc));
            $programme_node->appendChild($child_node_desc);
            $child_node_category = $dom->createElement('category', htmlspecialchars($category));
            $programme_node->appendChild($child_node_category);
            $child_node_icon = $dom->createElement('icon');
            $programme_node->appendChild($child_node_icon);
                $attr_poster_icon = new DOMAttr('src', $poster);
                $child_node_icon->setAttributeNode($attr_poster_icon);
            $child_node_rating = $dom->createElement('rating');
            $programme_node->appendChild($child_node_rating);
                $child_node_rate = $dom->createElement('value', $rating);
                $child_node_rating->appendChild($child_node_rate);
            $child_node_episode = $dom->createElement('episode-num', $episode);
            $programme_node->appendChild($child_node_episode);
            $child_node_date = $dom->createElement('date', $date);
            $programme_node->appendChild($child_node_date);

            $root->appendChild($programme_node);


}
}
$dom->appendChild($root);

$dom->save($xml_file_name);
}
/// fun de la focntion pluto.tv


$jour = "jour/".date("Y-m-d").".xml";
$heure = "heure/".date("Y-m-d-H").".xml";
///// brouillon 
if (!file_exists($jour)) {

foreach(glob('jour/*') as $file){ // iterate files
  if(is_file($file)) {
    unlink($file); // delete file    
  }
}
 
    $target = new DOMDocument();
    $target->loadXML('<?xml version="1.0" encoding="utf-8"?><tv></tv>');
    mergeFile($target, "https://xmltv.ch/xmltv/xmltv-tnt.xml");
    mergeFile($target, "https://iptv-org.github.io/epg/guides/programme-tv.net.guide.xml");
    $target->save($jour);
    $xml = file_get_contents($jour);
    $replace = str_replace("+0200", "+0000", $xml);    
    file_put_contents($jour, $replace);
}
if (!file_exists($heure)) {
    
foreach(glob('heure/*') as $file){ // iterate files
  if(is_file($file)) {
    unlink($file); // delete file
  }
}       
    plutotv ();
    $target = new DOMDocument();
    $target->loadXML('<?xml version="1.0" encoding="utf-8"?><tv></tv>');
    mergeFile($target, $jour);
    mergeFile($target, "heure/".date("Y-m-d-H").".xml");
    $target->save("epg.xml");

}
//////
header("Status: 301 Moved Permanently", false, 301);
header("Location: epg.xml");
exit();
?>
