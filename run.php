<?php
require_once realpath(__DIR__.'./')."vendor/autoload.php";

class PCWIngest {
  
  public function __construct()
  {
    $this->loopFiles();
  }

  /**
   * Loop all the JSON files in ./data
   */
  private function loopFiles()
  {
    $files = glob('data/*.{json}', GLOB_BRACE);
    foreach($files as $file) {
      $json = file_get_contents($file);
      $data = json_decode($json,true);
      $nid = $data['id'];
 
      echo "\n***************\n";
      echo "Trying to create Fedora entry for nid: $nid\n";

      $this->ingestRdf($json, $data['files'], $nid);
    }
  }

  /**
   * 
   */
  private function ingestRdf($json, $images, $nid)
  {

    $rdf = \CyW\RDF::format($json);
    $response = \CyW\Fedora::ingestRdf("item_$nid", $rdf);
    echo "Response for item creation: $response\n";
    if($response == 201){
      $this->ingestImages($images, $nid);
    }else{
      die('error');
    }

  }

  private function ingestImages($files, $nid)
  {
    foreach ($files as $key => $value) {
      $filename = $value['originalFilename'];
      $fedora = \CyW\Fedora::ingestImages("item_$nid", "images/$filename", $filename);
      echo "Response for image $filename: $fedora\n";
    }
  }
}

(new PCWIngest());
  
  
